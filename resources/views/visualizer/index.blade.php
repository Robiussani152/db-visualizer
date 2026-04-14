<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>DB Visualizer Pro</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ route('visualizer.assets', ['type' => 'css', 'file' => 'visualizer.css']) }}">
</head>
<body>

<!-- TOPBAR -->
<header class="topbar">
    <div class="brand">
        <div class="brand-dot"><i class="fa-brands fa-laravel"></i></div>
        <span class="brand-name">DB Visualizer <sup>Pro</sup></span>
    </div>

    <div class="topbar-right">
        <form method="POST" action="{{ route('visualizer.clear-cache') }}">
            @csrf
            <button type="submit" class="btn-cache">
                <i class="fa fa-broom me-1"></i> Clear Cache
            </button>
        </form>

        <div class="search-box">
            <i class="fa fa-magnifying-glass"></i>
            <input type="text" id="search" placeholder="Search models…" autocomplete="off">
        </div>
    </div>
</header>

<!-- PAGE -->
<div class="page">

    <!-- Stats -->
    <div class="stat-row">
        <div class="stat-item">
            <div class="label">Laravel</div>
            <div class="value" style="font-size:15px;margin-top:2px">{{ app()->version() }}</div>
        </div>
        <div class="stat-item">
            <div class="label">PHP</div>
            <div class="value" style="font-size:15px;margin-top:2px">{{ phpversion() }}</div>
        </div>
        <div class="stat-item">
            <div class="label">Environment</div>
            <div class="value" style="font-size:15px;margin-top:2px;text-transform:capitalize">{{ app()->environment() }}</div>
        </div>
        <div class="stat-item">
            <div class="label">Models</div>
            <div class="value" id="totalModels">—</div>
        </div>
        <div class="stat-item">
            <div class="label">Tables</div>
            <div class="value" id="totalTables">—</div>
        </div>
        <div class="stat-item">
            <div class="label">Orphan Tables</div>
            <div class="value" id="orphanTablesCount">—</div>
        </div>
        <div class="stat-item">
            <div class="label">Avg Score</div>
            <div class="value" id="avgScore">—</div>
        </div>
    </div>

    <!-- Packages -->
    <div class="panel">
        <div class="panel-header" data-bs-toggle="collapse" data-bs-target="#pkgCollapse" aria-expanded="false">
            <div>
                <div class="title">Installed Packages</div>
                <div class="hint">Click to expand</div>
            </div>
            <i class="fa fa-chevron-down chevron"></i>
        </div>
        <div id="pkgCollapse" class="collapse">
            <div class="panel-body">
                <div class="row g-2">
                    @foreach($extraPackages as $pkg)
                        <div class="col-md-6 col-lg-4">
                            <div class="pkg-chip">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="pkg-name">{{ $pkg['name'] }}</span>
                                    <span class="pkg-version">{{ $pkg['version'] }}</span>
                                </div>
                                @if($pkg['description'])
                                    <div class="text-muted" style="font-size:12px">{{ $pkg['description'] }}</div>
                                @endif
                                @if($pkg['type'])
                                    <div class="mt-2"><span class="pkg-type">{{ $pkg['type'] }}</span></div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Orphan tables -->
    <div class="panel">
        <div class="panel-header" data-bs-toggle="collapse" data-bs-target="#orphanCollapse" aria-expanded="false">
            <div>
                <div class="title">Tables without a Model</div>
                <div class="hint">Click to expand</div>
            </div>
            <i class="fa fa-chevron-down chevron"></i>
        </div>
        <div id="orphanCollapse" class="collapse">
            <div class="panel-body">
                <ul id="orphanTablesList"></ul>
            </div>
        </div>
    </div>

    <!-- Model grid -->
    <div class="row g-3 mt-1" id="app">
        <div class="loader"><div class="spin"></div>Scanning models…</div>
    </div>

</div>

<!-- MODAL -->
<div class="modal fade" id="detailModal" tabindex="-1">
<div class="modal-dialog modal-xl modal-dialog-scrollable">
<div class="modal-content">
    <div class="modal-header">
        <div>
            <div class="modal-title" id="mTitle"></div>
            <small id="mTable"></small>
        </div>
        <button class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body" id="modalBody"></div>
</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const dbvDataUrl   = @json(route('visualizer.data'));
    const dbvDetailUrl = @json(route('visualizer.detail', ['model' => '__MODEL__']));
</script>
<script src="{{ route('visualizer.assets', ['type' => 'js', 'file' => 'visualizer.js']) }}"></script>

</body>
</html>
