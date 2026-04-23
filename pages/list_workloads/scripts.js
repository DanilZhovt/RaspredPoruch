const kafedraFilter = document.getElementById('kafedraFilter');
const yearFilter = document.getElementById('yearFilter');
const rows = document.querySelectorAll('#table-body tr');

document.getElementById("applyFilterBtn").addEventListener("click", function () {
    const k = kafedraFilter.value.toLowerCase();
    const y = yearFilter.value.toLowerCase();

    rows.forEach(row => {
        const kafedra = (row.dataset.kafedra || '').toLowerCase();
        const year = (row.dataset.year || '').toLowerCase();

        const okK = !k || kafedra.includes(k);
        const okY = !y || year.includes(y);

        row.style.display = (okK && okY) ? '' : 'none';
    });
});