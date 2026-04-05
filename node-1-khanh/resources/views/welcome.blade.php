<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Node {{ $port }} - Distributed Monitor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta http-equiv="refresh" content="2">
    <style>
    body {
        background-color: #0f172a;
        color: #e2e8f0;
        font-family: 'Consolas', monospace;
    }

    .glass-panel {
        background: rgba(30, 41, 59, 0.7);
        backdrop-filter: blur(10px);
        border: 1px solid #334155;
    }

    .pulse {
        animation: pulse-animation 2s infinite;
    }

    @keyframes pulse-animation {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
    }

    /* LOG BOX */
    .log-box {
        background: #020617;
        border: 1px solid #1e3a5f;
        border-radius: 8px;
        font-family: 'Consolas', 'Courier New', monospace;
        font-size: 12px;
        height: 260px;
        overflow-y: auto;
        padding: 12px;
        scroll-behavior: smooth;
    }

    .log-line {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        padding: 3px 0;
        border-bottom: 1px solid rgba(30, 58, 95, 0.4);
        line-height: 1.5;
    }

    .log-time { color: #4a6080; min-width: 70px; flex-shrink: 0; }
    .log-icon { min-width: 18px; flex-shrink: 0; }
    .log-action { color: #94a3b8; min-width: 160px; flex-shrink: 0; font-weight: 600; }
    .log-detail { color: #cbd5e1; flex: 1; word-break: break-word; white-space: pre-wrap; }

    .log-success .log-action { color: #4ade80; }
    .log-success .log-detail { color: #bbf7d0; }
    .log-warning .log-action { color: #fbbf24; }
    .log-warning .log-detail { color: #fef3c7; }
    .log-error   .log-action { color: #f87171; }
    .log-error   .log-detail { color: #fecaca; }
    .log-info    .log-action { color: #38bdf8; }
    .log-info    .log-detail { color: #bae6fd; }

    .log-box::-webkit-scrollbar { width: 5px; }
    .log-box::-webkit-scrollbar-track { background: #020617; }
    .log-box::-webkit-scrollbar-thumb { background: #1e3a5f; border-radius: 3px; }
    </style>
</head>

<body class="p-8">
    <div class="max-w-5xl mx-auto glass-panel p-6 rounded-xl shadow-2xl">
        <div class="flex justify-between items-center border-b border-slate-600 pb-4 mb-6">
            <div>
                <h1 class="text-3xl font-bold text-amber-400 tracking-wider">LUXURY HOTEL</h1>
                <p class="text-slate-400 text-sm mt-1">Hệ Thống Phân Tán Đám Mây - Cơ chế 4PC</p>
            </div>
            <div class="text-right">
                <div class="text-green-400 font-bold text-xl flex items-center justify-end gap-2">
                    <div class="w-3 h-3 bg-green-500 rounded-full pulse"></div>
                    NODE ONLINE
                </div>
                <div class="text-slate-300 text-lg mt-1">CỔNG: <span
                        class="text-amber-400 font-bold border border-amber-400 px-2 py-1 rounded">{{ $port }}</span>
                </div>
            </div>
        </div>

        <h2 class="text-xl text-slate-200 mb-4 border-l-4 border-amber-400 pl-3">DỮ LIỆU ĐỒNG BỘ THỜI GIAN THỰC</h2>

        <div class="overflow-x-auto mb-8">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-800 text-slate-300">
                        <th class="p-3 border border-slate-700">Mã GD</th>
                        <th class="p-3 border border-slate-700">Khách Hàng</th>
                        <th class="p-3 border border-slate-700">Phòng Khóa</th>
                        <th class="p-3 border border-slate-700">Trạng Thái 4PC</th>
                        <th class="p-3 border border-slate-700">Cập nhật lúc</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $tx)
                    <tr class="hover:bg-slate-800 transition">
                        <td class="p-3 border border-slate-700 font-bold text-white">#{{ $tx->transaction_id }}</td>
                        <td class="p-3 border border-slate-700 text-amber-200">{{ $tx->customer_name ?? 'N/A' }}</td>
                        <td class="p-3 border border-slate-700 font-bold text-cyan-300">Phòng {{ $tx->room_id ?? 'N/A' }}</td>
                        <td class="p-3 border border-slate-700 font-bold
                            @if($tx->status == 'committed') text-green-400
                            @elseif($tx->status == 'pending') text-yellow-400
                            @else text-red-400 @endif">
                            {{ strtoupper($tx->status) }}
                        </td>
                        <td class="p-3 border border-slate-700 text-slate-400">
                            {{ \Carbon\Carbon::parse($tx->updated_at)->format('H:i:s d/m/Y') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="p-5 text-center text-slate-500">Node đang chờ nhận lệnh từ Server Trung Tâm...</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ===== Ô NHẬT KÝ GIAO THỨC 4PC ===== --}}
        <div class="border-t border-slate-700 pt-6">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-lg font-bold text-amber-400 flex items-center gap-2">
                    <span>📋</span> Nhật Ký Giao Thức 4-Phase Commit
                </h2>
                <span class="text-xs text-slate-500 bg-slate-800 px-2 py-1 rounded">{{ count($logs) }} sự kiện</span>
            </div>

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
                <div class="text-slate-600 italic text-center py-8">
                    📡 Chưa có nhật ký. Node đang chờ giao dịch đầu tiên...
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <script>
        // Cuộn xuống cuối log box khi tải trang
        const logBox = document.getElementById('logBox');
        if (logBox) logBox.scrollTop = logBox.scrollHeight;
    </script>
</body>

</html>