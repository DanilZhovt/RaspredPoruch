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