const state = {
    currentTeacher: null,
    distribution: {}
};

const selectors = {
    rows: 'table tr[data-id]',
    distributed: '.distributed',
    teacherBtn: '.teacher-btn'
};

const elements = {
    rows: [...document.querySelectorAll(selectors.rows)],
    teacherButtons: [...document.querySelectorAll(selectors.teacherBtn)],
    table: document.querySelector('table'),
    searchInput: document.getElementById('teacherSearch'),
    saveBtn: document.getElementById('saveBtn'),
    applyFilterBtn: document.getElementById('applyFilterBtn'),
    generateReportBtn: document.getElementById('generateReportBtn')
};

init();

function init() {
    initDistribution();
    bindTeacherButtons();
    bindTableEditing();
    bindSearch();
    bindFilters();
    bindSave();
    bindGenerateReport();
    bindRowSelection();
    bindHeaderToggle();

    setEditingEnabled(false);
    renderTable();
}

function initDistribution() {
    elements.rows.forEach(row => {
        const rowId = row.dataset.id;
        const cell = row.querySelector(selectors.distributed);

        if (!state.distribution[rowId]) {
            state.distribution[rowId] = {};
        }

        const teachersData = getTeachersDataFromRow(row);

        if (teachersData.length > 0) {
            teachersData.forEach(teacherData => {
                if (teacherData.hours > 0) {
                    state.distribution[rowId][teacherData.name] = teacherData.hours;
                }
            });
        } else {
            const base = parseNumber(cell?.dataset.base);
            state.distribution[rowId]._base = base;

            const teachers = getTeachersFromRow(row);
            teachers.forEach(teacher => {
                state.distribution[rowId][teacher] = base;
            });
        }
    });
}

function getTeachersDataFromRow(row) {
    try {
        const data = JSON.parse(row.dataset.teachersHours || '[]');
        return data.map(item => ({
            name: (item.name || '').trim(),
            hours: parseFloat(item.hours) || 0
        })).filter(item => item.name && item.hours > 0);
    } catch {
        return [];
    }
}

function bindTeacherButtons() {
    elements.teacherButtons.forEach(button => {
        button.addEventListener('click', () => {
            const teacher = button.dataset.teacher?.trim();

            const isActive = button.classList.contains('active');

            resetTeacherSelection();

            if (isActive) {
                state.currentTeacher = null;
                setEditingEnabled(false);
                renderTable();
                return;
            }

            state.currentTeacher = teacher;

            button.classList.add('active');

            highlightTeacherRows(teacher);

            setEditingEnabled(true);
            renderTable();
        });
    });
}

function resetTeacherSelection() {
    elements.teacherButtons.forEach(btn =>
        btn.classList.remove('active')
    );

    elements.rows.forEach(row =>
        row.classList.remove('highlight', 'active-row')
    );
}

function highlightTeacherRows(teacher) {
    elements.rows.forEach(row => {
        const rowId = row.dataset.id;
        const teachersFromData = getTeachersFromRow(row);
        const teachersFromState = state.distribution[rowId]
            ? Object.keys(state.distribution[rowId]).filter(key =>
                key !== '_base' && state.distribution[rowId][key] > 0
            )
            : [];

        const allTeachers = [...new Set([...teachersFromData, ...teachersFromState])];

        if (allTeachers.includes(teacher)) {
            row.classList.add('highlight');
        }
    });
}

function renderTable() {
    elements.rows.forEach(row => {
        const rowId = row.dataset.id;
        const cell = row.querySelector(selectors.distributed);

        const total = getRowTotal(rowId);

        if (!state.currentTeacher) {
            cell.textContent = total;
            return;
        }

        const teacherValue =
            state.distribution[rowId]?.[state.currentTeacher] || 0;

        cell.textContent = `${teacherValue}/${total}`;
    });

    updateDistributedColors();
}

function getRowTotal(rowId) {
    const rowData = state.distribution[rowId] || {};

    return Object.entries(rowData)
        .filter(([key]) => key !== '_base')
        .reduce((sum, [, value]) => {
            return sum + parseNumber(value);
        }, 0);
}

function setEditingEnabled(enabled) {
    document.querySelectorAll(selectors.distributed).forEach(cell => {
        cell.contentEditable = enabled;
    });
}

function bindTableEditing() {
    elements.table.addEventListener('input', handleCellInput);

    document
        .querySelectorAll(selectors.distributed)
        .forEach(cell => {
            cell.addEventListener('keypress', validateCellInput);
            cell.addEventListener('blur', handleCellBlur);

            cell.addEventListener('focus', selectCellText);
        });
}

function handleCellBlur(event) {
    const cell = event.target;

    cleanupCell(event);

    if (state.currentTeacher) {
        const row = cell.closest('tr');
        updateRowTeachersAttribute(row);

        renderTable();
    }
}

function selectCellText(event) {
    const cell = event.target;

    setTimeout(() => {
        const range = document.createRange();

        range.selectNodeContents(cell);

        const selection = window.getSelection();

        selection.removeAllRanges();
        selection.addRange(range);
    }, 0);
}

function handleCellInput(event) {
    const cell = event.target;

    if (!cell.classList.contains('distributed')) return;
    if (!state.currentTeacher) return;

    const row = cell.closest('tr');
    const rowId = row.dataset.id;

    let raw = cell.textContent
        .replace(',', '.')
        .replace(/[^\d.]/g, '');

    const parts = raw.split('.');

    if (parts.length > 2) {
        raw = parts[0] + '.' + parts.slice(1).join('');
    }

    cell.textContent = raw;

    moveCaretToEnd(cell);

    const value = parseNumber(raw);

    if (!state.distribution[rowId]) {
        state.distribution[rowId] = {};
    }

    state.distribution[rowId][state.currentTeacher] = value;

    updateRowTeachersAttribute(row);

    updateDistributedColors();
}

function updateRowTeachersAttribute(row) {
    const rowId = row.dataset.id;
    const teachersData = state.distribution[rowId] || {};

    const teachersWithHours = Object.entries(teachersData)
        .filter(([key, value]) => key !== '_base' && value > 0)
        .map(([key]) => key);

    row.dataset.teachers = JSON.stringify(teachersWithHours);

    const teachersHoursData = Object.entries(teachersData)
        .filter(([key]) => key !== '_base')
        .map(([key, value]) => ({
            name: key,
            hours: value
        }));

    row.dataset.teachersHours = JSON.stringify(teachersHoursData);
}

function moveCaretToEnd(element) {
    const range = document.createRange();
    const selection = window.getSelection();

    range.selectNodeContents(element);
    range.collapse(false);

    selection.removeAllRanges();
    selection.addRange(range);
}

function validateCellInput(event) {
    const char = String.fromCharCode(event.which);

    if (!/[0-9.,]/.test(char)) {
        event.preventDefault();
    }
}

function cleanupCell(event) {
    event.target.textContent = event.target.textContent
        .replace(/\n/g, '')
        .trim();
}

function updateDistributedColors() {
    elements.rows.forEach(row => {
        const cell = row.querySelector(selectors.distributed);
        const loadCell = row.children[5];

        if (!cell || !loadCell) return;

        const rowId = row.dataset.id;

        const load = parseNumber(loadCell.textContent);
        const total = getRowTotal(rowId);

        cell.classList.remove('ok', 'over');

        if (load !== 0 && total === load) {
            cell.classList.add('ok');
        } else if (total > load) {
            cell.classList.add('over');
        }
    });
}

function bindSearch() {
    elements.searchInput?.addEventListener('input', () => {
        const query = elements.searchInput.value
            .trim()
            .toLowerCase();

        elements.teacherButtons.forEach(button => {
            const teacher = (
                button.dataset.teacher || ''
            ).toLowerCase();

            button.style.display = teacher.includes(query)
                ? ''
                : 'none';
        });
    });
}

function bindFilters() {
    elements.applyFilterBtn?.addEventListener(
        'click',
        applyFilters
    );
}

function applyFilters() {
    const filters = {
        discipline: getFilterValue('disciplineFilter'),
        type: getFilterValue('typeFilter'),
        period: getFilterValue('periodFilter'),
        direction: getFilterValue('directionFilter')
    };

    elements.rows.forEach(row => {
        const match = Object.entries(filters).every(
            ([key, value]) => {
                if (!value) return true;

                return (row.dataset[key] || '') === value;
            }
        );

        row.style.display = match ? '' : 'none';
    });
}

function getFilterValue(id) {
    return document.getElementById(id)?.value || '';
}

function bindSave() {
    elements.saveBtn?.addEventListener('click', () => saveDistribution(false));
}

function bindGenerateReport() {
    elements.generateReportBtn?.addEventListener('click', () => saveDistribution(true));
}

async function saveDistribution(redirectAfterSave = false) {
    if (hasOverDistribution()) {
        showOverDistributionModal();
        return;
    }

    hideOverDistributionModal();

    const footer = document.querySelector('.footer');
    const number = footer.dataset.number;
    const name = footer.dataset.name;

    const url = '/classes/DistributionSaver.php?number=' + encodeURIComponent(number) + '&name=' + encodeURIComponent(name);
    const button = redirectAfterSave ? elements.generateReportBtn : elements.saveBtn;

    button.disabled = true;
    const originalText = button.textContent;
    button.textContent = 'Сохранение...';

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(state.distribution)
        });

        const result = await response.json();

        if (!response.ok) {
            throw new Error('HTTP error ' + response.status);
        }

        if (result.status === 'error') {
            throw new Error(result.message || 'Unknown error');
        }

        if (redirectAfterSave) {
            window.location.href = '/pages/report_page/?number=' + encodeURIComponent(number) + '&name=' + encodeURIComponent(name);
        } else {
            location.reload();
        }
    } catch (error) {
        alert('Ошибка при сохранении: ' + error.message);
        button.disabled = false;
        button.textContent = originalText;
    }
}

function bindRowSelection() {
    document
        .querySelectorAll('.row-number-cell')
        .forEach(cell => {
            cell.style.cursor = 'pointer';

            cell.addEventListener('click', () => {
                const row = cell.closest('tr');

                const teachers = getTeachersFromRow(row);

                resetTeacherSelection();

                elements.teacherButtons.forEach(button => {
                    const teacher = (
                        button.dataset.teacher || ''
                    ).trim();

                    if (teachers.includes(teacher)) {
                        button.classList.add('active');
                    }
                });

                row.classList.add('highlight');
            });
        });
}

function getTeachersFromRow(row) {
    try {
        return JSON.parse(row.dataset.teachers || '[]')
            .map(teacher => (teacher || '').trim())
            .filter(Boolean);

    } catch {
        return [];
    }
}

function parseNumber(value) {
    return parseFloat(
        String(value || '0').replace(',', '.')
    ) || 0;
}

function bindHeaderToggle() {
    const button = document.getElementById('toggleHeaderBtn');

    if (!button) return;

    button.addEventListener('click', () => {
        document.body.classList.toggle('header-hidden');

        const hidden =
            document.body.classList.contains('header-hidden');

        button.textContent = hidden
            ? 'Показать фильтры'
            : 'Скрыть фильтры';
    });
}

function hasOverDistribution() {
    return document.querySelector('.over') !== null;
}

function showOverDistributionModal() {
    const modal = document.getElementById('overDistributionModal');
    if (modal) {
        modal.style.display = 'block';
        document.body.classList.add('modal-open');
    }
}

function hideOverDistributionModal() {
    const modal = document.getElementById('overDistributionModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const closeBtn = document.getElementById('closeOverModalBtn');
    if (closeBtn) {
        closeBtn.addEventListener('click', hideOverDistributionModal);
    }

    const modal = document.getElementById('overDistributionModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                hideOverDistributionModal();
            }
        });
    }
});
