// Тесты scipts.js detail_page_workload

let scripts;

beforeEach(() => {
    document.body.innerHTML = `
        <div class="footer" data-number="000000033" data-name="Кафедра">
            <button id="saveBtn">Сохранить</button>
            <button id="toggleHeaderBtn">Скрыть фильтры</button>
            <button id="generateReportBtn" class="btn-link">Сформировать отчет</button>
        </div>
        
        <input type="text" id="teacherSearch" placeholder="Поиск преподавателя...">
        
        <select id="disciplineFilter">
            <option value="">Все</option>
            <option value="Математика">Математика</option>
            <option value="Физика">Физика</option>
        </select>
        
        <select id="typeFilter">
            <option value="">Все</option>
            <option value="Лекции">Лекции</option>
            <option value="Практика">Практика</option>
        </select>
        
        <select id="periodFilter">
            <option value="">Все</option>
            <option value="1">1 семестр</option>
        </select>
        
        <select id="directionFilter">
            <option value="">Все</option>
            <option value="Очное">Очное</option>
        </select>
        
        <button id="applyFilterBtn">Применить фильтр</button>
        
        <table>
            <thead>
                <tr>
                    <th>№</th><th>Дисциплина</th><th>Тип занятия</th>
                    <th>Период контроля</th><th>Направление</th>
                    <th>Нагрузка</th><th>Распределено</th>
                </tr>
            </thead>
            <tbody>
                <tr data-id="row1" 
                    data-discipline="Математика" 
                    data-type="Лекции" 
                    data-period="1" 
                    data-direction="Очное"
                    data-teachers='["Иванов И.И."]'
                    data-teachers-hours='[{"name":"Иванов И.И.","hours":5}]'>
                    <td class="row-number-cell">1</td>
                    <td>Математика</td>
                    <td>Лекции</td>
                    <td>1</td>
                    <td>Очное</td>
                    <td>10</td>
                    <td class="distributed editable" data-base="0">0</td>
                </tr>
                <tr data-id="row2" 
                    data-discipline="Физика" 
                    data-type="Практика" 
                    data-period="1" 
                    data-direction="Очное"
                    data-teachers='["Петров П.П.","Сидоров С.С."]'
                    data-teachers-hours='[{"name":"Петров П.П.","hours":3},{"name":"Сидоров С.С.","hours":0}]'>
                    <td class="row-number-cell">2</td>
                    <td>Физика</td>
                    <td>Практика</td>
                    <td>1</td>
                    <td>Очное</td>
                    <td>20</td>
                    <td class="distributed editable" data-base="0">0</td>
                </tr>
            </tbody>
        </table>
        
        <div class="sidebar">
            <button class="teacher-btn" data-teacher="Иванов И.И.">
                Иванов И.И.
            </button>
            <button class="teacher-btn" data-teacher="Петров П.П.">
                Петров П.П.
            </button>
            <button class="teacher-btn" data-teacher="Сидоров С.С.">
                Сидоров С.С.
            </button>
        </div>
        
        <div id="overDistributionModal" style="display: none;">
            <button id="closeOverModalBtn">ОК</button>
        </div>
    `;

    jest.resetModules();

    scripts = require('../pages/detail_page_workload/scripts.js');

    scripts.state.currentTeacher = null;
    scripts.state.distribution = {};
});

describe('parseNumber', () => {
    test('parses integer string', () => {
        expect(scripts.parseNumber('5')).toBe(5);
    });

    test('parses float string with comma', () => {
        expect(scripts.parseNumber('5,5')).toBe(5.5);
    });

    test('returns 0 for empty string', () => {
        expect(scripts.parseNumber('')).toBe(0);
    });
});

describe('getTeachersFromRow', () => {
    test('returns teachers array', () => {
        const row = document.querySelector('tr[data-id="row1"]');
        const teachers = scripts.getTeachersFromRow(row);
        expect(teachers).toEqual(['Иванов И.И.']);
    });
});

describe('getTeachersDataFromRow', () => {
    test('filters out teachers with 0 hours', () => {
        const row = document.querySelector('tr[data-id="row2"]');
        const data = scripts.getTeachersDataFromRow(row);
        expect(data).toHaveLength(1);
        expect(data[0].name).toBe('Петров П.П.');
    });
});

describe('initDistribution', () => {
    test('initializes distribution with teachers data', () => {
        scripts.initDistribution();
        expect(scripts.state.distribution['row1']).toBeDefined();
        expect(scripts.state.distribution['row1']['Иванов И.И.']).toBe(5);
    });

    test('does not include teachers with 0 hours', () => {
        scripts.initDistribution();
        expect(scripts.state.distribution['row2']).toBeDefined();
        expect(scripts.state.distribution['row2']['Сидоров С.С.']).toBeUndefined();
    });
});

describe('getRowTotal', () => {
    test('calculates total distribution', () => {
        scripts.state.distribution['row1'] = {
            'Иванов И.И.': 5,
            'Петров П.П.': 3,
            '_base': 10
        };
        expect(scripts.getRowTotal('row1')).toBe(8);
    });
});

describe('highlightTeacherRows', () => {
    test('highlights rows with teacher', () => {
        scripts.state.distribution['row1'] = { 'Иванов И.И.': 5 };
        scripts.highlightTeacherRows('Иванов И.И.');

        const row = document.querySelector('tr[data-id="row1"]');
        expect(row.classList.contains('highlight')).toBe(true);
    });
});

describe('hasOverDistribution', () => {
    test('returns true when over class exists', () => {
        const cell = document.querySelector('.distributed');
        cell.classList.add('over');
        expect(scripts.hasOverDistribution()).toBe(true);
    });
});

describe('applyFilters', () => {
    test('filters rows by discipline', () => {
        document.getElementById('disciplineFilter').value = 'Математика';
        scripts.applyFilters();

        const row1 = document.querySelector('tr[data-id="row1"]');
        const row2 = document.querySelector('tr[data-id="row2"]');

        expect(row1.style.display).toBe('');
        expect(row2.style.display).toBe('none');
    });
});