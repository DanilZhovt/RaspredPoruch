// =====================
// СОСТОЯНИЕ
// =====================
let currentTeacher = null;

// rowId -> { teacherName: value, _base: number }
const distribution = {};


// =====================
// ЭЛЕМЕНТЫ
// =====================
const rows = document.querySelectorAll('table tr[data-id]');
const teacherButtons = document.querySelectorAll('.teacher-btn');


// =====================
// ИНИЦИАЛИЗАЦИЯ ИЗ PHP/1C
// =====================
document.querySelectorAll('tr[data-id]').forEach(row => {

    const rowId = row.dataset.id;
    const cell = row.querySelector('.distributed');

    const base = parseFloat(cell.dataset.base || 0);

    if (!distribution[rowId]) {
        distribution[rowId] = { _base: base };
    } else {
        distribution[rowId]._base = base;
    }

    // 👇 сотрудники из PHP (обязательно добавить data-teachers)
    let teachers = [];
    try {
        teachers = JSON.parse(row.dataset.teachers || "[]");
    } catch (e) {
        teachers = [];
    }

    // если есть один основной преподаватель — сразу кладём его
    teachers.forEach(t => {
        if (!t) return;

        if (distribution[rowId][t] === undefined) {
            distribution[rowId][t] = base; // стартовое значение из 1С
        }
    });
});


// =====================
// ВЫБОР ПРЕПОДА
// =====================
teacherButtons.forEach(btn => {

    btn.addEventListener('click', () => {

        const clickedTeacher = btn.dataset.teacher?.trim();

        const alreadyActive = btn.classList.contains('active');

        teacherButtons.forEach(b => b.classList.remove('active'));
        rows.forEach(r => r.classList.remove('highlight'));

        if (alreadyActive) {
            currentTeacher = null;
            disableEditing();
            renderTable();
            return;
        }

        btn.classList.add('active');
        currentTeacher = clickedTeacher;

        // подсветка строк
        rows.forEach(row => {

            let teachers = [];

            try {
                teachers = JSON.parse(row.dataset.teachers || "[]");
            } catch {}

            const match = teachers.some(t =>
                (t || '').trim() === currentTeacher
            );

            if (match) {
                row.classList.add('highlight');
            }
        });

        enableEditing();
        renderTable();
    });
});


// =====================
// РЕДАКТИРУЕМОСТЬ
// =====================
function enableEditing() {
    document.querySelectorAll('.distributed').forEach(cell => {
        cell.contentEditable = true;
    });
}

function disableEditing() {
    document.querySelectorAll('.distributed').forEach(cell => {
        cell.contentEditable = false;
    });
}


// =====================
// РЕНДЕР
// =====================
function renderTable() {

    document.querySelectorAll('table tr[data-id]').forEach(row => {

        const rowId = row.dataset.id;
        const cell = row.querySelector('.distributed');

        const base = parseFloat(cell.dataset.base || 0);

        const rowData = distribution[rowId] || {};

        const sumTeachers = Object.entries(rowData)
            .filter(([k]) => k !== '_base')
            .reduce((acc, [, v]) => acc + (parseFloat(v) || 0), 0);

        // 👇 общий режим
        if (!currentTeacher) {
            cell.textContent = sumTeachers;
        }

        // 👇 режим конкретного преподавателя
        else {
            const teacherValue = rowData[currentTeacher] || 0;
            cell.textContent = `${teacherValue}/${sumTeachers}`;
        }
    });

    updateDistributedColors();
}


// =====================
// ВВОД ДАННЫХ
// =====================
document.querySelector('table').addEventListener('input', (e) => {

    const cell = e.target;
    if (!cell.classList.contains('distributed')) return;
    if (!currentTeacher) return;

    const row = cell.closest('tr');
    const rowId = row.dataset.id;

    const value = parseFloat(cell.textContent.replace(',', '.')) || 0;

    if (!distribution[rowId]) {
        distribution[rowId] = {};
    }

    distribution[rowId][currentTeacher] = value;

    renderTable();
});


// =====================
// ОГРАНИЧЕНИЕ ВВОДА
// =====================
document.querySelectorAll('.distributed').forEach(cell => {

    cell.addEventListener('keypress', (e) => {
        const char = String.fromCharCode(e.which);
        if (!/[0-9.,]/.test(char)) {
            e.preventDefault();
        }
    });

    cell.addEventListener('blur', () => {
        cell.textContent = cell.textContent.replace(/\n/g, '').trim();
    });
});


// =====================
// ЦВЕТА (OK / OVER)
// =====================
function updateDistributedColors() {

    document.querySelectorAll('table tr[data-id]').forEach(row => {

        const cell = row.querySelector('.distributed');
        const loadCell = row.children[5];

        if (!cell || !loadCell) return;

        const load = parseFloat(loadCell.textContent || 0);

        const rowId = row.dataset.id;
        const rowData = distribution[rowId] || {};

        const sumTeachers = Object.entries(rowData)
            .filter(([k]) => k !== '_base')
            .reduce((acc, [, v]) => acc + (parseFloat(v) || 0), 0);

        cell.classList.remove('ok', 'over');

        if (sumTeachers === load && load !== 0) {
            cell.classList.add('ok');
        } else if (sumTeachers > load) {
            cell.classList.add('over');
        }
    });
}


// =====================
// ПОИСК ПРЕПОДОВ
// =====================
const searchInput = document.getElementById('teacherSearch');

searchInput.addEventListener('input', () => {

    const query = searchInput.value.trim().toLowerCase();

    teacherButtons.forEach(btn => {
        const name = (btn.dataset.teacher || '').toLowerCase();
        btn.style.display = name.includes(query) ? '' : 'none';
    });
});

document.getElementById('applyFilterBtn').addEventListener('click', applyFilters);

function applyFilters() {
    const discipline = document.getElementById('disciplineFilter').value;
    const type = document.getElementById('typeFilter').value;
    const period = document.getElementById('periodFilter').value;
    const direction = document.getElementById('directionFilter').value;

    document.querySelectorAll('table tr[data-id]').forEach(row => {

        const rowDiscipline = row.dataset.discipline || '';
        const rowType = row.dataset.type || '';
        const rowPeriod = row.dataset.period || '';
        const rowDirection = row.dataset.direction || '';

        const match =
            (!discipline || rowDiscipline === discipline) &&
            (!type || rowType === type) &&
            (!period || rowPeriod === period) &&
            (!direction || rowDirection === direction);

        row.style.display = match ? '' : 'none';
    });
}

document.getElementById('saveBtn').addEventListener('click', async () => {

    const footer = document.querySelector('.footer');

    const number = footer.dataset.number;
    const name = footer.dataset.name;

    const url = '/classes/save.php?number='
        + encodeURIComponent(number)
        + '&name='
        + encodeURIComponent(name);

    // блокируем кнопку, чтобы не нажали 2 раза
    const btn = document.getElementById('saveBtn');
    btn.disabled = true;
    btn.textContent = 'Сохранение...';

    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(distribution)
        });

        location.reload();

    } catch (e) {
        alert('Ошибка сети');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Сохранить';
    }
});

document.addEventListener('DOMContentLoaded', () => {

    document.querySelectorAll('.row-number-cell').forEach(cell => {

        cell.style.cursor = 'pointer';

        cell.addEventListener('click', () => {

            const row = cell.closest('tr');

            const teachers = getTeachersFromRow(row);

            // ❗ сбрасываем только визуальные подсветки
            teacherButtons.forEach(b => b.classList.remove('active'));
            rows.forEach(r => r.classList.remove('highlight', 'active-row'));

            // подсветка преподавателей
            teacherButtons.forEach(btn => {

                const name = (btn.dataset.teacher || '').trim();

                if (teachers.includes(name)) {
                    btn.classList.add('active');
                }
            });

            // подсветка самой строки (если нужно)
            row.classList.add('highlight');
        });
    });

});

function getTeachersFromRow(row) {
    try {
        return JSON.parse(row.dataset.teachers || "[]")
            .map(t => (t || '').trim())
            .filter(Boolean);
    } catch {
        return [];
    }
}

// =====================
// СТАРТ
// =====================
disableEditing();
renderTable();