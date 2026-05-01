const disciplineFilter = document.getElementById('disciplineFilter');
const typeFilter = document.getElementById('typeFilter');
const periodFilter = document.getElementById('periodFilter');
const directionFilter = document.getElementById('directionFilter');

const rows = document.querySelectorAll('table tr[data-discipline]');

document.getElementById("applyFilterBtn").addEventListener("click", function () {

    const d = disciplineFilter.value.toLowerCase();
    const t = typeFilter.value.toLowerCase();
    const p = periodFilter.value.toLowerCase();
    const dir = directionFilter.value.toLowerCase();

    rows.forEach(row => {
        const discipline = (row.dataset.discipline || '').toLowerCase();
        const type = (row.dataset.type || '').toLowerCase();
        const period = (row.dataset.period || '').toLowerCase();
        const direction = (row.dataset.direction || '').toLowerCase();

        const okD = !d || discipline === d;
        const okT = !t || type === t;
        const okP = !p || period === p;
        const okDir = !dir || direction === dir;

        row.style.display = (okD && okT && okP && okDir) ? '' : 'none';
    });
});

const teacherButtons = document.querySelectorAll('.teacher-btn');
const tableRows = document.querySelectorAll('table tr[data-discipline]');

teacherButtons.forEach(btn => {
    btn.addEventListener('click', () => {

        const alreadyActive = btn.classList.contains('active');

        teacherButtons.forEach(b => b.classList.remove('active'));
        tableRows.forEach(row => row.classList.remove('highlight'));

        if (alreadyActive) return;

        btn.classList.add('active');

        const teacherName = btn.dataset.teacher.trim().toLowerCase();

        tableRows.forEach(row => {
            let teachersRaw = row.dataset.teachers;
            let teachers = [];

            try {
                const parsed = JSON.parse(teachersRaw);

                if (Array.isArray(parsed)) {
                    teachers = parsed;
                } else if (typeof parsed === 'string') {
                    teachers = [parsed];
                }

            } catch (e) {
                console.warn("Ошибка парсинга:", teachersRaw);
            }

            const match = teachers.some(t =>
                (t || '').trim().toLowerCase() === teacherName
            );

            if (match) {
                row.classList.add('highlight');
            }
        });

    });
});

const searchInput = document.getElementById('teacherSearch');
const teacherButtonsList = document.querySelectorAll('.teacher-btn');

searchInput.addEventListener('input', () => {

    const query = searchInput.value.trim().toLowerCase();

    teacherButtonsList.forEach(btn => {
        const name = (btn.dataset.teacher || '').toLowerCase();

        if (name.includes(query)) {
            btn.style.display = '';
        } else {
            btn.style.display = 'none';
        }
    });

});


document.querySelectorAll('.distributed.editable').forEach(cell => {

    cell.addEventListener('input', () => {
        updateDistributedColors();
    });

    cell.addEventListener('blur', () => {
        // убираем переносы строк (частая проблема contenteditable)
        cell.textContent = cell.textContent.replace(/\n/g, '').trim();
    });

    cell.addEventListener('keypress', (e) => {
        const char = String.fromCharCode(e.which);

        if (!/[0-9.,]/.test(char)) {
            e.preventDefault();
        }
    });

});

function updateDistributedColors() {

    document.querySelectorAll('table tr[data-discipline]').forEach(row => {

        const loadCell = row.children[5];
        const distributedCell = row.querySelector('.distributed');

        if (!loadCell || !distributedCell) return;

        const load = parseFloat(loadCell.textContent.replace(',', '.')) || 0;

        const text = distributedCell.textContent.trim();

        const distributed = parseFloat(text.replace(',', '.'));

        distributedCell.classList.remove('ok', 'over');

        if (!text) return;

        if (!isNaN(distributed)) {
            if (distributed === load && load !== 0) {
                distributedCell.classList.add('ok');
            } else if (distributed > load) {
                distributedCell.classList.add('over');
            }
        }

    });
}

updateDistributedColors();