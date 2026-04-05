<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Node {{ $port }} — Luxury Hotel Distributed Monitor</title>
    <meta name="description" content="Hệ thống giám sát node phân tán đám mây - Cơ chế 4PC Luxury Hotel">
    <meta http-equiv="refresh" content="2">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --bg-primary:    #050d1a;
            --bg-secondary:  #0a1628;
            --glass-bg:      rgba(15, 30, 60, 0.55);
            --glass-border:  rgba(100, 160, 255, 0.12);
            --glass-hover:   rgba(255, 196, 80, 0.06);
            --gold:          #f5c842;
            --gold-light:    #fde68a;
            --gold-dim:      rgba(245, 200, 66, 0.15);
            --cyan:          #38bdf8;
            --cyan-dim:      rgba(56, 189, 248, 0.15);
            --green:         #4ade80;
            --green-dim:     rgba(74, 222, 128, 0.15);
            --yellow:        #fbbf24;
            --yellow-dim:    rgba(251, 191, 36, 0.15);
            --red:           #f87171;
            --red-dim:       rgba(248, 113, 113, 0.15);
            --text-primary:  #e8edf5;
            --text-secondary:#8ba3c7;
            --text-muted:    #4a6080;
            --border:        rgba(100, 160, 255, 0.1);
            --border-gold:   rgba(245, 200, 66, 0.35);
        }

        html { scroll-behavior: smooth; }

        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed; inset: 0;
            background:
                radial-gradient(ellipse 900px 600px at 10% 20%, rgba(245,200,66,0.05) 0%, transparent 65%),
                radial-gradient(ellipse 700px 500px at 80% 80%, rgba(56,189,248,0.06) 0%, transparent 60%),
                radial-gradient(ellipse 500px 400px at 50% 50%, rgba(99,102,241,0.04) 0%, transparent 70%);
            pointer-events: none; z-index: 0;
        }

        body::after {
            content: '';
            position: fixed; inset: 0;
            background-image:
                linear-gradient(rgba(100,160,255,0.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(100,160,255,0.025) 1px, transparent 1px);
            background-size: 50px 50px;
            pointer-events: none; z-index: 0;
        }

        .wrapper {
            position: relative; z-index: 1;
            max-width: 1060px; margin: 0 auto;
            padding: 36px 24px 60px;
        }

        .header {
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 20px;
            padding: 28px 36px;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px; margin-bottom: 24px;
            box-shadow: 0 8px 40px rgba(0,0,0,0.4), inset 0 1px 0 rgba(255,255,255,0.05);
        }

        .brand { display: flex; flex-direction: column; gap: 4px; }
        .brand-logo { display: flex; align-items: center; gap: 12px; }
        .brand-icon {
            width: 44px; height: 44px;
            background: linear-gradient(135deg, var(--gold), #c97c00);
            border-radius: 12px; display: flex; align-items: center;
            justify-content: center; font-size: 22px;
            box-shadow: 0 4px 16px rgba(245,200,66,0.35);
        }
        .brand-name {
            font-size: 26px; font-weight: 800; letter-spacing: 3px;
            background: linear-gradient(90deg, var(--gold), var(--gold-light));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text; line-height: 1;
        }
        .brand-sub { font-size: 12px; color: var(--text-secondary); letter-spacing: 1.5px; margin-top: 2px; }

        .status-group { display: flex; flex-direction: column; align-items: flex-end; gap: 10px; }
        .status-badge {
            display: inline-flex; align-items: center; gap: 8px;
            background: var(--green-dim); border: 1px solid rgba(74,222,128,0.3);
            border-radius: 100px; padding: 6px 16px;
            font-size: 12px; font-weight: 700; color: var(--green); letter-spacing: 1.5px;
        }
        .pulse-dot {
            width: 8px; height: 8px; background: var(--green); border-radius: 50%;
            animation: pulse-ring 2s ease-in-out infinite;
        }
        @keyframes pulse-ring {
            0%   { box-shadow: 0 0 0 0 rgba(74,222,128,0.5); }
            70%  { box-shadow: 0 0 0 8px rgba(74,222,128,0); }
            100% { box-shadow: 0 0 0 0 rgba(74,222,128,0); }
        }
        .port-badge { display: inline-flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text-secondary); }
        .port-value {
            font-family: 'JetBrains Mono', monospace; font-size: 15px; font-weight: 700;
            color: var(--gold); background: var(--gold-dim); border: 1px solid var(--border-gold);
            border-radius: 8px; padding: 3px 12px; letter-spacing: 1px;
        }

        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card {
            background: var(--glass-bg); backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border); border-radius: 16px;
            padding: 20px 22px; display: flex; flex-direction: column; gap: 6px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 12px 30px rgba(0,0,0,0.35); }
        .stat-label { font-size: 11px; font-weight: 600; letter-spacing: 1.5px; color: var(--text-muted); text-transform: uppercase; }
        .stat-value { font-family: 'JetBrains Mono', monospace; font-size: 26px; font-weight: 700; line-height: 1; }
        .stat-sub   { font-size: 11px; color: var(--text-muted); }
        .stat-gold  .stat-value { color: var(--gold); }
        .stat-cyan  .stat-value { color: var(--cyan); }
        .stat-green .stat-value { color: var(--green); }

        .panel {
            background: var(--glass-bg); backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border); border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 8px 40px rgba(0,0,0,0.35), inset 0 1px 0 rgba(255,255,255,0.04);
        }

        .panel-header {
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 12px; padding: 20px 28px;
            border-bottom: 1px solid var(--border); background: rgba(0,0,0,0.2);
        }

        .panel-title {
            display: flex; align-items: center; gap: 12px;
            font-size: 14px; font-weight: 700; color: var(--text-primary); letter-spacing: 1px;
        }
        .panel-title-bar {
            width: 4px; height: 20px;
            background: linear-gradient(180deg, var(--gold), var(--cyan)); border-radius: 4px;
        }

        .sync-indicator {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 11px; color: var(--cyan); background: var(--cyan-dim);
            border: 1px solid rgba(56,189,248,0.2); border-radius: 100px;
            padding: 4px 12px; font-weight: 600; letter-spacing: 0.5px;
            animation: blink-fade 2s ease-in-out infinite;
        }
        @keyframes blink-fade { 0%, 100% { opacity: 1; } 50% { opacity: 0.55; } }

        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        thead tr { background: rgba(0,0,0,0.25); }
        thead th {
            padding: 14px 20px; text-align: left;
            font-size: 11px; font-weight: 700; letter-spacing: 1.5px;
            color: var(--text-muted); text-transform: uppercase;
            border-bottom: 1px solid var(--border); white-space: nowrap;
        }
        tbody tr { border-bottom: 1px solid rgba(100,160,255,0.06); transition: background 0.18s ease; }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: var(--glass-hover); }
        tbody td { padding: 14px 20px; font-size: 13.5px; vertical-align: middle; white-space: nowrap; }

        .td-id { font-family: 'JetBrains Mono', monospace; font-weight: 700; font-size: 13px; color: var(--text-primary); }
        .td-id span { opacity: 0.4; font-weight: 400; }
        .td-customer { color: var(--gold-light); font-weight: 500; }
        .td-room { font-family: 'JetBrains Mono', monospace; font-weight: 700; color: var(--cyan); }
        .td-time { font-family: 'JetBrains Mono', monospace; font-size: 12px; color: var(--text-muted); }

        .status-pill {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 11px; font-weight: 700; letter-spacing: 1px;
            border-radius: 100px; padding: 4px 14px;
        }
        .status-dot { width: 6px; height: 6px; border-radius: 50%; }
        .s-committed { background: var(--green-dim); border: 1px solid rgba(74,222,128,0.25); color: var(--green); }
        .s-committed .status-dot { background: var(--green); }
        .s-pending { background: var(--yellow-dim); border: 1px solid rgba(251,191,36,0.25); color: var(--yellow); }
        .s-pending .status-dot { background: var(--yellow); animation: pulse-ring-yellow 2s ease-in-out infinite; }
        @keyframes pulse-ring-yellow {
            0%   { box-shadow: 0 0 0 0 rgba(251,191,36,0.5); }
            70%  { box-shadow: 0 0 0 6px rgba(251,191,36,0); }
            100% { box-shadow: 0 0 0 0 rgba(251,191,36,0); }
        }
        .s-aborted { background: var(--red-dim); border: 1px solid rgba(248,113,113,0.25); color: var(--red); }
        .s-aborted .status-dot { background: var(--red); }

        .empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 14px; padding: 64px 32px; color: var(--text-muted); }
        .empty-icon { font-size: 40px; opacity: 0.4; }
        .empty-title { font-size: 15px; font-weight: 600; color: var(--text-secondary); }
        .empty-sub { font-size: 13px; animation: blink-fade 2s ease-in-out infinite; }

        .footer { margin-top: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; padding: 0 4px; }
        .footer-text { font-size: 11px; color: var(--text-muted); letter-spacing: 0.5px; }
        .footer-protocol { font-family: 'JetBrains Mono', monospace; font-size: 11px; color: var(--gold); opacity: 0.7; }

        /* LOG BOX */
        .log-box {
            background: rgba(2,8,20,0.9);
            border: 1px solid rgba(56,189,248,0.15);
            border-radius: 12px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            height: 280px;
            overflow-y: auto;
            padding: 14px;
        }
        .log-line {
            display: flex; align-items: flex-start; gap: 8px;
            padding: 4px 0;
            border-bottom: 1px solid rgba(100,160,255,0.06);
            line-height: 1.5;
        }
        .log-time    { color: var(--text-muted); min-width: 70px; flex-shrink: 0; }
        .log-icon    { min-width: 18px; flex-shrink: 0; }
        .log-action  { font-weight: 700; min-width: 160px; flex-shrink: 0; color: var(--text-secondary); }
        .log-detail  { flex: 1; color: var(--text-primary); word-break: break-word; white-space: pre-wrap; opacity: 0.8; }
        .log-success .log-action { color: var(--green); }
        .log-success .log-detail { color: #bbf7d0; }
        .log-warning .log-action { color: var(--gold); }
        .log-warning .log-detail { color: var(--gold-light); }
        .log-error   .log-action { color: var(--red); }
        .log-error   .log-detail { color: #fecaca; }
        .log-info    .log-action { color: var(--cyan); }
        .log-info    .log-detail { color: #bae6fd; }
        .log-box::-webkit-scrollbar { width: 5px; }
        .log-box::-webkit-scrollbar-track { background: rgba(2,8,20,0.9); }
        .log-box::-webkit-scrollbar-thumb { background: rgba(100,160,255,0.2); border-radius: 3px; }

        @media (max-width: 640px) {
            .wrapper { padding: 20px 14px 48px; }
            .header { padding: 20px; }
            .brand-name { font-size: 20px; letter-spacing: 2px; }
            .stats-row { grid-template-columns: 1fr 1fr; }
            .panel-header { padding: 16px 18px; }
            thead th, tbody td { padding: 12px 14px; }
        }
    </style>
</head>

<body>
<div class="wrapper">

    <!-- ── HEADER ── -->
    <header class="header">
        <div class="brand">
            <div class="brand-logo">
                <div class="brand-icon">🏨</div>
                <div class="brand-name">LUXURY HOTEL</div>
            </div>
            <div class="brand-sub">HỆ THỐNG PHÂN TÁN ĐÁM MÂY &nbsp;·&nbsp; CƠ CHẾ 4PC</div>
        </div>
        <div class="status-group">
            <div class="status-badge">
                <div class="pulse-dot"></div>
                NODE ONLINE
            </div>
            <div class="port-badge">
                CỔNG HIỆN TẠI &nbsp;
                <span class="port-value">{{ $port }}</span>
            </div>
        </div>
    </header>

    <!-- ── STATS ── -->
    @php
        $total     = $transactions->count();
        $committed = $transactions->where('status', 'committed')->count();
        $pending   = $transactions->where('status', 'pending')->count();
    @endphp
    <div class="stats-row">
        <div class="stat-card stat-gold">
            <div class="stat-label">Tổng giao dịch</div>
            <div class="stat-value">{{ $total }}</div>
            <div class="stat-sub">10 gần nhất</div>
        </div>
        <div class="stat-card stat-green">
            <div class="stat-label">Đã cam kết</div>
            <div class="stat-value">{{ $committed }}</div>
            <div class="stat-sub">COMMITTED</div>
        </div>
        <div class="stat-card stat-cyan">
            <div class="stat-label">Đang xử lý</div>
            <div class="stat-value">{{ $pending }}</div>
            <div class="stat-sub">PENDING</div>
        </div>
    </div>

    <!-- ── TABLE PANEL ── -->
    <div class="panel">
        <div class="panel-header">
            <div class="panel-title">
                <div class="panel-title-bar"></div>
                DỮ LIỆU ĐỒNG BỘ THỜI GIAN THỰC
            </div>
            <div class="sync-indicator">⟳&nbsp; AUTO-REFRESH 2s</div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Mã GD</th>
                        <th>Khách Hàng</th>
                        <th>Phòng Khóa</th>
                        <th>Trạng Thái 4PC</th>
                        <th>Cập nhật lúc</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $tx)
                    <tr>
                        <td class="td-id"><span>#</span>{{ $tx->transaction_id }}</td>
                        <td class="td-customer">{{ $tx->customer_name ?? 'N/A' }}</td>
                        <td class="td-room">Phòng {{ $tx->room_id ?? 'N/A' }}</td>
                        <td>
                            @php $s = strtolower($tx->status); @endphp
                            <span class="status-pill
                                @if($s === 'committed') s-committed
                                @elseif($s === 'pending') s-pending
                                @else s-aborted
                                @endif">
                                <span class="status-dot"></span>
                                {{ strtoupper($tx->status) }}
                            </span>
                        </td>
                        <td class="td-time">{{ \Carbon\Carbon::parse($tx->updated_at)->format('H:i:s  d/m/Y') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <div class="empty-icon">📡</div>
                                <div class="empty-title">Chưa có dữ liệu</div>
                                <div class="empty-sub">Node đang chờ nhận lệnh từ Server Trung Tâm…</div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===== Ô NHẬT KÝ GIAO THỨC 4PC ===== --}}
    <div class="panel" style="margin-top: 24px;">
        <div class="panel-header">
            <div class="panel-title">
                <div class="panel-title-bar"></div>
                📋 NHẬT KÝ GIAO THỨC 4-PHASE COMMIT
            </div>
            <span class="sync-indicator">{{ count($logs) }} SỰ KIỆN</span>
        </div>
        <div style="padding: 16px 20px;">
            <div class="log-box" id="logBox">
                @forelse($logs as $log)
                @php
                    $icons = ['success' => '✅', 'warning' => '⚠️', 'error' => '❌', 'info' => 'ℹ️'];
                    $icon  = $icons[$log->status] ?? 'ℹ️';
                @endphp
                <div class="log-line log-{{ $log->status }}">
                    <span class="log-time">[{{ \Carbon\Carbon::parse($log->created_at)->format('H:i:s') }}]</span>
                    <span class="log-icon">{{ $icon }}</span>
                    <span class="log-action">{{ $log->action }}</span>
                    <span class="log-detail">{{ $log->details }}</span>
                </div>
                @empty
                <div class="empty-state">
                    <div class="empty-icon">🔍</div>
                    <div class="empty-title">Chưa có nhật ký</div>
                    <div class="empty-sub">Node đang chờ giao dịch đầu tiên...</div>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- ── FOOTER ── -->
    <footer class="footer">
        <div class="footer-text">© 2025 Luxury Hotel &nbsp;·&nbsp; Distributed Cloud System</div>
        <div class="footer-protocol">4PC PROTOCOL &nbsp;·&nbsp; PORT {{ $port }}</div>
    </footer>

</div>

<script>
    const logBox = document.getElementById('logBox');
    if (logBox) logBox.scrollTop = logBox.scrollHeight;
</script>
</body>
</html>