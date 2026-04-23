const data = window.phpData || [];

const tbody = document.getElementById('table-body');
const kafedraFilter = document.getElementById('kafedraFilter');
const yearFilter = document.getElementById('yearFilter');

function renderTable(filteredData) {
    tbody.innerHTML = '';

    filteredData.forEach(item => {
        const row = document.createElement('tr');

        row.innerHTML = `
            <td>${item.Номер ?? ''}</td>
            <td>${item.Кафедра ?? ''}</td>
            <td>${item.УчебныйГод ?? ''}</td>
        `;

        row.addEventListener('click', () => {
            if (item.Номер) {
                const number = encodeURIComponent(item.Номер);
                window.location.href = `https://my-module.local/pages/detail_page_workload/?number=${number}`;
            }
        });

        row.style.cursor = 'pointer';

        tbody.appendChild(row);
    });
}

function fillFilters() {
    const kafedras = [...new Set(data.map(i => i.Кафедра))];
    kafedras.forEach(k => {
        const opt = document.createElement('option');
        opt.value = k;
        opt.textContent = k;
        kafedraFilter.appendChild(opt);
    });

    const years = [...new Set(data.map(i => i.УчебныйГод))];
    years.forEach(y => {
        const opt = document.createElement('option');
        opt.value = y;
        opt.textContent = y;
        yearFilter.appendChild(opt);
    });
}

document.getElementById("applyFilterBtn").addEventListener("click", function () {
    const k = kafedraFilter.value.toLowerCase();
    const y = yearFilter.value.toLowerCase();

    const filtered = data.filter(item => {
        const okK = !k || (item.Кафедра || '').toLowerCase().includes(k);
        const okY = !y || (item.УчебныйГод || '').toLowerCase().includes(y);
        return okK && okY;
    });

    renderTable(filtered);
});

fillFilters();
renderTable(data);