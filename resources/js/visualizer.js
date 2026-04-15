/* DB Visualizer – main script
 * URLs are injected by the Blade view as dbvDataUrl / dbvDetailUrl globals.
 */

let timer;
let globalData = [];

function esc(str) {
    if (str == null) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function scoreClass(s) { return s >= 75 ? 'score-high' : s >= 50 ? 'score-mid' : 'score-low'; }
function barColor(s)   { return s >= 75 ? '#16a34a'   : s >= 50 ? '#d97706'   : '#dc2626'; }

/* ── LOAD ─────────────────────────────────────── */
function loadData(search = '') {
    document.getElementById('app').innerHTML =
        `<div class="loader"><div class="spin"></div>Scanning models…</div>`;

    fetch(`${dbvDataUrl}?search=${encodeURIComponent(search)}`)
        .then(r => r.json())
        .then(res => {
            globalData = res.data || [];
            render(globalData);
            updateStats(globalData, res.meta || {});
        })
        .catch(() => {
            document.getElementById('app').innerHTML =
                `<div class="loader" style="color:#dc2626"><i class="fa fa-circle-exclamation me-1"></i>Failed to load data</div>`;
        });
}

/* ── STATS ────────────────────────────────────── */
function updateStats(data, meta) {
    document.getElementById('totalModels').innerText       = data.length;
    document.getElementById('totalTables').innerText       = meta.total_tables ?? 0;
    document.getElementById('orphanTablesCount').innerText = meta.orphan_tables_count ?? 0;

    const avg = data.length
        ? (data.reduce((s, i) => s + (i.performance_score ?? 0), 0) / data.length).toFixed(1)
        : '0.0';
    document.getElementById('avgScore').innerText = avg;

    const list = document.getElementById('orphanTablesList');
    list.innerHTML = '';
    (meta.orphan_tables || []).forEach(t => {
        const li = document.createElement('li');
        li.innerText = t;
        list.appendChild(li);
    });
}

/* ── RENDER ───────────────────────────────────── */
function render(data) {
    if (!data.length) {
        document.getElementById('app').innerHTML = `<div class="loader">No models found</div>`;
        return;
    }

    document.getElementById('app').innerHTML = data.map((item, i) => {
        const score = item.performance_score ?? 0;
        const cx = item.complexity === 'High' ? 't-red'
            : item.complexity === 'Medium' ? 't-amber'
            : 't-green';

        return `
        <div class="col-sm-6 col-md-4 col-xl-3">
            <div class="model-card">
                <div class="mc-top">
                    <div style="min-width:0">
                        <div class="mc-num">#${i + 1}</div>
                        <div class="mc-name" title="${esc(item.model)}">${esc(item.model)}</div>
                        <div class="mc-table">${esc(item.table)}</div>
                    </div>
                    <span class="score-badge ${scoreClass(score)}">${score}</span>
                </div>

                <div class="mc-bar">
                    <div class="mc-bar-fill" style="width:${score}%;background:${barColor(score)}"></div>
                </div>

                <div class="mc-tags">
                    <span class="tag ${item.unused_relations_count ? 't-red'   : 't-gray'}">${item.unused_relations_count ?? 0} unused rel</span>
                    <span class="tag ${item.unused_columns_count   ? 't-amber' : 't-gray'}">${item.unused_columns_count ?? 0} unused col</span>
                    <span class="tag ${item.n_plus_one_issues      ? 't-red'   : 't-gray'}">N+1: ${item.n_plus_one_issues ?? 0}</span>
                    ${item.soft_deletes ? `<span class="tag t-green">Soft Delete</span>` : ''}
                    <span class="tag ${cx}">${item.complexity ?? 'Low'}</span>
                    ${item.quality_label ? `<span class="tag t-purple">${esc(item.quality_label)}</span>` : ''}
                </div>

                <div class="mc-meta">${(item.relations ?? []).length} relations · ${(item.columns ?? []).length} columns</div>

                <button class="btn-view" onclick="openDetail('${esc(item.model)}')">View Details</button>
            </div>
        </div>`;
    }).join('');
}

/* ── DETAIL ───────────────────────────────────── */
function openDetail(model) {
    fetch(dbvDetailUrl.replace('__MODEL__', encodeURIComponent(model)))
        .then(r => r.json())
        .then(data => {
            if (!data || data.message) { alert('Model not found'); return; }

            const rP = (data.unused_relations_count ?? 0) * 10;
            const cP = (data.unused_columns_count   ?? 0) * 2;
            const nP = (data.n_plus_one_issues       ?? 0) * 15;
            const eP = (data.missing_eager_loads     ?? 0) * 10;
            const xP = data.complexity === 'High' ? 10 : 0;
            const sB = data.soft_deletes      ? 5 : 0;
            const cB = data.cache_used        ? 5 : 0;
            const aB = data.api_resource_used ? 5 : 0;
            const final = Math.max(0, Math.min(100, 100 - rP - cP - nP - eP - xP + sB + cB + aB));

            document.getElementById('mTitle').innerText = data.model ?? '';
            document.getElementById('mTable').innerText = data.table  ?? '';

            const deductRow = (label, val) => val
                ? `<div class="score-line" style="color:#dc2626"><span>${label}</span><b>−${val}</b></div>` : '';
            const bonusRow  = (label, val) => val
                ? `<div class="score-line" style="color:#16a34a"><span>${label}</span><b>+${val}</b></div>` : '';

            const cols = (data.columns_detailed ?? []).map(c =>
                `<span class="col-pill ${c.used ? 'col-used' : 'col-unused'}">${esc(c.name)}</span>`
            ).join('');

            const rels = (data.relations ?? []).map(r => `
                <tr>
                    <td><code style="font-size:12px">${esc(r.method)}</code></td>
                    <td><span class="tag t-gray" style="font-size:11px">${esc(r.type ?? '—')}</span></td>
                    <td style="font-size:12px;color:var(--muted)">${esc(r.related ?? '—')}</td>
                    <td>
                        ${r.used
                            ? `<span style="color:#15803d;font-weight:600;font-size:12px">Used</span>`
                            : `<span style="color:#b91c1c;font-weight:600;font-size:12px">Unused</span>`}
                        ${r.n_plus_one    ? `<span class="b-n1 ms-1">N+1</span>` : ''}
                        ${r.missing_eager ? `<span class="b-eager ms-1">Eager Missing</span>` : ''}
                    </td>
                </tr>`
            ).join('');

            document.getElementById('modalBody').innerHTML = `
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="score-box">
                            <div class="box-title">Score Breakdown</div>
                            <div class="score-line"><span>Base</span><b>100</b></div>
                            ${deductRow('Unused Relations', rP)}
                            ${deductRow('Unused Columns', cP)}
                            ${deductRow('N+1 Issues', nP)}
                            ${deductRow('Missing Eager Loads', eP)}
                            ${deductRow('Complexity', xP)}
                            ${bonusRow('Soft Deletes', sB)}
                            ${bonusRow('Cache Used', cB)}
                            ${bonusRow('API Resource', aB)}
                            <div class="score-line total">
                                <span>Final Score</span>
                                <span style="color:${barColor(final)}">${final} / 100</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <p class="sec-label">Columns</p>
                        <div class="mb-4">${cols || '<span style="color:var(--muted);font-size:13px">No columns</span>'}</div>

                        <p class="sec-label">Relations</p>
                        ${rels
                            ? `<table class="table rel-table mb-0">
                                <thead><tr><th>Method</th><th>Type</th><th>Related</th><th>Status</th></tr></thead>
                                <tbody>${rels}</tbody>
                               </table>`
                            : `<p style="color:var(--muted);font-size:13px">No relations defined</p>`}
                    </div>
                </div>`;

            new bootstrap.Modal(document.getElementById('detailModal')).show();
        });
}

/* ── CHEVRON TOGGLE ───────────────────────────── */
document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(el => {
    const t = document.querySelector(el.dataset.bsTarget);
    t.addEventListener('show.bs.collapse', () => el.setAttribute('aria-expanded', 'true'));
    t.addEventListener('hide.bs.collapse', () => el.setAttribute('aria-expanded', 'false'));
});

/* ── SEARCH ───────────────────────────────────── */
document.getElementById('search').addEventListener('input', function () {
    clearTimeout(timer);
    timer = setTimeout(() => loadData(this.value), 300);
});

/* ── INIT ─────────────────────────────────────── */
loadData();
