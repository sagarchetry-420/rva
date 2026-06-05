document.addEventListener('DOMContentLoaded', () => {
    function formatBytes(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024, sizes = ['B', 'KB', 'MB', 'GB', 'TB'], i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    fetch('../api/system_health.php').then(res => res.json()).then(data => {
        if (!document.getElementById('dbStatus')) return;
        document.getElementById('dbStatus').innerText = data.db_status;
        if(data.db_status !== 'Connected') document.getElementById('globalStatus').style.background = '#ef4444';

        document.getElementById('diskStatus').innerText = formatBytes(data.disk.free) + ' free';
        const dBar = document.getElementById('diskBar');
        dBar.style.width = data.disk.used_percent + '%';
        if(data.disk.used_percent > 85) dBar.classList.add('danger');

        if(data.memory.total > 0) {
            document.getElementById('memStatus').innerText = data.memory.used_percent + '% used';
            const mBar = document.getElementById('memBar');
            mBar.style.width = data.memory.used_percent + '%';
            if(data.memory.used_percent > 85) mBar.classList.add('danger');
        } else {
            document.getElementById('memStatus').innerText = 'N/A';
        }
    });

    fetch('../api/fetch_logs.php').then(res => res.json()).then(data => {
        if (document.getElementById('apiBlocks')) document.getElementById('apiBlocks').innerText = data.api_blocks;

        if (document.getElementById('pieChart')) {
            new Chart(document.getElementById('pieChart'), {
                type: 'doughnut',
                data: {
                    labels: data.chart_pie.labels,
                    datasets: [{
                        data: data.chart_pie.data,
                        backgroundColor: ['#ef4444', '#f59e0b', '#3b82f6', '#8b5cf6', '#10b981'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'bottom', labels: { color: '#cbd5e1' } } }
                }
            });
        }

        if (document.getElementById('lineChart')) {
            new Chart(document.getElementById('lineChart'), {
                type: 'line',
                data: {
                    labels: data.chart_line.labels,
                    datasets: [{
                        label: 'System Errors',
                        data: data.chart_line.data,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        x: { ticks: { color: '#94a3b8' }, grid: { color: '#334155' } },
                        y: { ticks: { color: '#94a3b8' }, grid: { color: '#334155' }, beginAtZero: true }
                    },
                    plugins: { legend: { display: false } }
                }
            });
        }

        window.allLogs = data.logs;
        window.allMailLogs = data.mail_logs;
        window.currentPage = 1;
        window.mailCurrentPage = 1;
        window.rowsPerPage = 10;
        
        renderTable();
        renderMailTable();

        if (document.getElementById('prevPage')) {
            document.getElementById('prevPage').addEventListener('click', () => {
                if (window.currentPage > 1) {
                    window.currentPage--;
                    renderTable();
                }
            });
            document.getElementById('nextPage').addEventListener('click', () => {
                const maxPage = Math.ceil(window.allLogs.length / window.rowsPerPage);
                if (window.currentPage < maxPage) {
                    window.currentPage++;
                    renderTable();
                }
            });
        }

        if (document.getElementById('mailPrevPage')) {
            document.getElementById('mailPrevPage').addEventListener('click', () => {
                if (window.mailCurrentPage > 1) {
                    window.mailCurrentPage--;
                    renderMailTable();
                }
            });
            document.getElementById('mailNextPage').addEventListener('click', () => {
                const maxPage = Math.ceil(window.allMailLogs.length / window.rowsPerPage);
                if (window.mailCurrentPage < maxPage) {
                    window.mailCurrentPage++;
                    renderMailTable();
                }
            });
        }
    });

    function renderTable() {
        const tbody = document.getElementById('logsTableBody');
        if (!tbody) return;
        tbody.innerHTML = '';
        const logs = window.allLogs;
        const totalRows = logs.length;
        const maxPage = Math.ceil(totalRows / window.rowsPerPage) || 1;
        document.getElementById('pageInfo').innerText = `Page ${window.currentPage} of ${maxPage}`;
        document.getElementById('prevPage').disabled = window.currentPage === 1;
        document.getElementById('nextPage').disabled = window.currentPage === maxPage;

        if (totalRows === 0) {
            tbody.innerHTML = '<tr><td colspan="4">No logic errors found in the system!</td></tr>';
            return;
        }
        const startIdx = (window.currentPage - 1) * window.rowsPerPage;
        const endIdx = startIdx + window.rowsPerPage;
        logs.slice(startIdx, endIdx).forEach(log => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td style="white-space: nowrap">${log.time}</td>
                <td><span class="badge-type">${log.type}</span></td>
                <td style="color: #ef4444">${log.error}</td>
                <td><code style="background: #0f172a; padding: 2px 6px; border-radius: 4px;">${log.sql}</code></td>
            `;
            tbody.appendChild(tr);
        });
    }

    function renderMailTable() {
        const tbody = document.getElementById('mailLogsTableBody');
        if (!tbody) return;
        tbody.innerHTML = '';
        const logs = window.allMailLogs;
        const totalRows = logs.length;
        const maxPage = Math.ceil(totalRows / window.rowsPerPage) || 1;
        document.getElementById('mailPageInfo').innerText = `Page ${window.mailCurrentPage} of ${maxPage}`;
        document.getElementById('mailPrevPage').disabled = window.mailCurrentPage === 1;
        document.getElementById('mailNextPage').disabled = window.mailCurrentPage === maxPage;

        if (totalRows === 0) {
            tbody.innerHTML = '<tr><td colspan="4">No email delivery failures found!</td></tr>';
            return;
        }
        const startIdx = (window.mailCurrentPage - 1) * window.rowsPerPage;
        const endIdx = startIdx + window.rowsPerPage;
        logs.slice(startIdx, endIdx).forEach(log => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td style="white-space: nowrap">${log.time}</td>
                <td><strong style="color: #cbd5e1;">${log.to}</strong></td>
                <td>${log.subject}</td>
                <td style="color: #f59e0b">${log.error}</td>
            `;
            tbody.appendChild(tr);
        });
    }
});
