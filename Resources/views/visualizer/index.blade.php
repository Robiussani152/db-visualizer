<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>DB Visualizer Pro</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- FontAwesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
body {
    background: #f8f9fb;
    font-family: 'Segoe UI', sans-serif;
}

.topbar {
    background: linear-gradient(90deg, #ff2d20, #ff4d4d);
    color: #fff;
    padding: 16px 20px;
}

.topbar small {
    color: rgba(255,255,255,0.85);
}

.model-card {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #eee;
    transition: 0.2s;
}

.model-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 28px rgba(0,0,0,0.08);
}

.loader {
    text-align: center;
    padding: 60px;
    color: #888;
}

.badge {
    font-weight: 500;
}
</style>
</head>

<body>

<!-- TOP BAR -->
<div class="topbar d-flex justify-content-between align-items-center">

    <div>
        <h5 class="mb-0">
            <i class="fa-brands fa-laravel"></i> DB Visualizer <b>Pro</b>
        </h5>
        <small>Laravel Model Intelligence Dashboard</small>
    </div>

    <input type="text"
           id="search"
           class="form-control"
           style="width:280px"
           placeholder="Search model...">

</div>

<!-- STATS -->
<div class="container-fluid mt-3">

    <div class="row g-3 mb-3">

        <div class="col-md-3">
            <div class="card p-3">
                <small>Total Models</small>
                <h4 id="totalModels">0</h4>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card p-3">
                <small>Average Score</small>
                <h4 id="avgScore">0</h4>
            </div>
        </div>

    </div>

    <div class="row g-3" id="app">
        <div class="loader">
            <i class="fa fa-spinner fa-spin"></i> Loading...
        </div>
    </div>

</div>

<!-- MODAL -->
<div class="modal fade" id="detailModal">
<div class="modal-dialog modal-xl">
<div class="modal-content">

    <div class="modal-header bg-light">
        <div>
            <h5 class="modal-title mb-0" id="mTitle">Model Details</h5>
            <small class="text-muted" id="mTable"></small>
        </div>
        <button class="btn-close" data-bs-dismiss="modal"></button>
    </div>

    <div class="modal-body" id="modalBody"></div>

</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>

let timer;
let globalData = [];

/* SAFE TEXT */
function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

/* LOAD */
function loadData(search = '') {

    document.getElementById('app').innerHTML =
        `<div class="loader"><i class="fa fa-spinner fa-spin"></i> Loading...</div>`;

    fetch(`/dbv/data?search=${encodeURIComponent(search)}`)
        .then(r => r.json())
        .then(data => {
            globalData = Array.isArray(data) ? data : [];
            render(globalData);
            updateStats(globalData);
        })
        .catch(() => {
            document.getElementById('app').innerHTML =
                `<div class="text-danger text-center">Failed to load data</div>`;
        });
}

/* STATS */
function updateStats(data) {

    document.getElementById('totalModels').innerText = data.length || 0;

    let totalScore = 0;
    let count = data.length || 1;

    data.forEach(i => {
        totalScore += (i.performance_score ?? 0);
    });

    let avg = totalScore / count;

    document.getElementById('avgScore').innerText =
        isNaN(avg) ? '0.0' : avg.toFixed(1);
}

/* RENDER */
function render(data) {

    if (!data.length) {
        document.getElementById('app').innerHTML =
            `<div class="text-center text-muted">No models found</div>`;
        return;
    }

    let html = '';

    data.forEach(item => {

        let score = item.performance_score ?? 0;

        html += `
        <div class="col-md-4 col-xl-3">

            <div class="card model-card h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between">

                        <div>
                            <h6 class="mb-0">${escapeHtml(item.model)}</h6>
                            <small class="text-muted">${escapeHtml(item.table)}</small>
                        </div>

                        <span class="badge bg-primary">
                            ${score}/100
                        </span>

                    </div>

                    <hr>

                    <div class="d-flex flex-wrap gap-1">

                        <span class="badge bg-danger">
                            Unused Rel: ${item.unused_relations_count ?? 0}
                        </span>

                        <span class="badge bg-warning text-dark">
                            Unused Col: ${item.unused_columns_count ?? 0}
                        </span>

                        <span class="badge bg-dark">
                            N+1: ${item.n_plus_one_issues ?? 0}
                        </span>

                        <span class="badge bg-secondary">
                            Eager Miss: ${item.missing_eager_loads ?? 0}
                        </span>

                        ${item.soft_deletes ? `<span class="badge bg-success">Soft Deletes</span>` : ''}
                        ${item.cache_used ? `<span class="badge bg-info text-dark">Cache</span>` : ''}
                        ${item.api_resource_used ? `<span class="badge bg-primary">API Resource</span>` : ''}

                        <span class="badge bg-dark">
                            ${item.quality_label ?? 'N/A'}
                        </span>

                        <span class="badge bg-${
                            item.complexity === 'High'
                                ? 'danger'
                                : item.complexity === 'Medium'
                                    ? 'warning text-dark'
                                    : 'success'
                        }">
                            ${item.complexity ?? 'Low'}
                        </span>

                    </div>

                    <div class="mt-3">
                        <div class="progress" style="height:8px;">
                            <div class="progress-bar ${
                                score >= 75 ? 'bg-success' :
                                score >= 50 ? 'bg-warning' :
                                'bg-danger'
                            }"
                            style="width:${score}%"></div>
                        </div>
                    </div>

                    <div class="mt-3 small text-muted">
                        Relations: <b>${(item.relations ?? []).length}</b> |
                        Columns: <b>${(item.columns ?? []).length}</b>
                    </div>

                    <button class="btn btn-sm btn-outline-danger w-100 mt-3"
                        onclick="openDetail('${escapeHtml(item.model)}')">
                        View Details
                    </button>

                </div>

            </div>

        </div>`;
    });

    document.getElementById('app').innerHTML = html;
}

/* DETAIL */
function openDetail(model) {

    fetch(`/db-visualizer/detail/${encodeURIComponent(model)}`)
        .then(r => r.json())
        .then(data => {

            if (!data || data.message) {
                alert('Model not found');
                return;
            }

            let base = 100;

            let relationPenalty = (data.unused_relations_count ?? 0) * 10;
            let columnPenalty = (data.unused_columns_count ?? 0) * 2;
            let nPlusOnePenalty = (data.n_plus_one_issues ?? 0) * 15;
            let eagerPenalty = (data.missing_eager_loads ?? 0) * 10;

            let complexityPenalty = (data.complexity === 'High') ? 10 : 0;

            let softBonus = data.soft_deletes ? 5 : 0;
            let cacheBonus = data.cache_used ? 5 : 0;
            let apiBonus = data.api_resource_used ? 5 : 0;

            let finalScore =
                base
                - relationPenalty
                - columnPenalty
                - nPlusOnePenalty
                - eagerPenalty
                - complexityPenalty
                + softBonus
                + cacheBonus
                + apiBonus;

            finalScore = Math.max(0, Math.min(100, finalScore));

            document.getElementById('mTitle').innerText = data.model ?? '';
            document.getElementById('mTable').innerText = data.table ?? '';

            let cols = (data.columns_detailed ?? []).map(c => `
                <span class="badge m-1 ${c.used ? 'bg-light text-dark border' : 'bg-danger'}">
                    ${escapeHtml(c.name)}
                </span>
            `).join('');

            let rels = (data.relations ?? []).map(r => `
                <tr>
                    <td>${escapeHtml(r.method)}</td>
                    <td>${escapeHtml(r.type ?? '-')}</td>
                    <td>${escapeHtml(r.related ?? '-')}</td>
                    <td>${r.used ? '<span class="text-success">Used</span>' : '<span class="text-danger">Unused</span>'}</td>
                </tr>
            `).join('');

            document.getElementById('modalBody').innerHTML = `
                <div class="card p-3 mb-3 bg-light">
                    <h6>Score Breakdown</h6>

                    <div class="d-flex justify-content-between"><span>Base</span><b>100</b></div>
                    <div class="d-flex justify-content-between text-danger"><span>Relation</span><b>-${relationPenalty}</b></div>
                    <div class="d-flex justify-content-between text-danger"><span>Column</span><b>-${columnPenalty}</b></div>
                    <div class="d-flex justify-content-between text-danger"><span>N+1</span><b>-${nPlusOnePenalty}</b></div>
                    <div class="d-flex justify-content-between text-danger"><span>Eager Load</span><b>-${eagerPenalty}</b></div>
                    <div class="d-flex justify-content-between text-success"><span>Final</span><b>${finalScore}/100</b></div>

                </div>

                <h6>Columns</h6>
                <div>${cols}</div>

                <h6 class="mt-3">Relations</h6>
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>Method</th>
                            <th>Type</th>
                            <th>Related</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>${rels}</tbody>
                </table>
            `;

            new bootstrap.Modal(document.getElementById('detailModal')).show();
        });
}

/* SEARCH */
document.getElementById('search').addEventListener('input', function () {
    clearTimeout(timer);
    timer = setTimeout(() => loadData(this.value), 300);
});

loadData();

</script>

</body>
</html>