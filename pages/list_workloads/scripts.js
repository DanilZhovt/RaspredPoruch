const cathedraFilter = document.getElementById('cathedraFilter');
const yearFilter = document.getElementById('yearFilter');
const rows = document.querySelectorAll('#table-body tr');

document.getElementById("applyFilterBtn").addEventListener("click", function () {
    const k = cathedraFilter.value.toLowerCase();
    const y = yearFilter.value.toLowerCase();

    rows.forEach(row => {
        const cathedra = (row.dataset.cathedra || '').toLowerCase();
        const year = (row.dataset.year || '').toLowerCase();

        const okK = !k || cathedra.includes(k);
        const okY = !y || year.includes(y);

        row.style.display = (okK && okY) ? '' : 'none';
    });
});