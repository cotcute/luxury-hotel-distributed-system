<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Node {{ $port }} - Luxury Hotel System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,600;1,600&display=swap" rel="stylesheet">
    <meta http-equiv="refresh" content="2">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-luxury { font-family: 'Playfair Display', serif; }
        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
        }
        .status-dot { animation: pulse-dot 2s infinite; }
        @keyframes pulse-dot {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(34, 197, 94, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
        }

        /* LOG BOX */
        .log-box {
            background: #0f172a;
            border: 1px solid #1e3a5f;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            height: 260px;
            overflow-y: auto;
            padding: 12px;
        }
        .log-line {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            padding: 3px 0;
            border-bottom: 1px solid rgba(30,58,95,0.4);
            line-height: 1.5;
        }
        .log-time    { color: #4a6080; min-width: 70px; flex-shrink: 0; }
        .log-icon    { min-width: 18px; flex-shrink: 0; }
        .log-action  { font-weight: 600; min-width: 160px; flex-shrink: 0; color: #94a3b8; }
        .log-detail  { flex: 1; color: #cbd5e1; word-break: break-word; white-space: pre-wrap; }
        .log-success .log-action { color: #4ade80; }
        .log-success .log-detail { color: #bbf7d0; }
        .log-warning .log-action { color: #fbbf24; }
        .log-warning .log-detail { color: #fef3c7; }
        .log-error   .log-action { color: #f87171; }
        .log-error   .log-detail { color: #fecaca; }
        .log-info    .log-action { color: #38bdf8; }
        .log-info    .log-detail { color: #bae6fd; }
        .log-box::-webkit-scrollbar { width: 5px; }
        .log-box::-webkit-scrollbar-track { background: #0f172a; }
        .log-box::-webkit-scrollbar-thumb { background: #1e3a5f; border-radius: 3px; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen p-4 md:p-8 flex flex-col pt-8">
    <div class="max-w-6xl mx-auto w-full">
        <!-- Header Section -->
        <header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1v1H9V7zm5 0h1v1h-1V7zm-5 4h1v1H9v-1zm5 0h1v1h-1v-1zm-5 4h1v1H9v-1zm5 0h1v1h-1v-1z"></path>
                    </svg>
                    <h1 class="text-3xl font-luxury font-semibold text-slate-900 tracking-wide uppercase">Luxury Hotel</h1>
                </div>
                <p class="text-slate-500 text-sm mt-1 ml-11 font-medium">Hệ Thống Phân Tán Đám Mây - Cơ chế 4PC</p>
            </div>
            <div class="bg-white px-5 py-3 border border-slate-200 rounded-full shadow-sm flex items-center gap-6">
                <div class="flex flex-col items-end">
                    <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Cổng Kết Nối</span>
                    <span class="text-lg font-bold text-blue-700 bg-blue-50 px-2 rounded">{{ $port }}</span>
                </div>
                <div class="h-8 w-px bg-slate-200"></div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-green-500 rounded-full status-dot"></div>
                    <span class="text-sm font-semibold text-green-600 tracking-wide uppercase">Node Online</span>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <div class="glass-card rounded-2xl overflow-hidden mt-4 border border-slate-200">
            <!-- Table Header -->
            <div class="px-6 py-5 border-b border-slate-100 bg-white/50 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-slate-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    Dữ Liệu Đồng Bộ Thời Gian Thực
                </h2>
                <div class="text-xs font-semibold px-3 py-1 bg-amber-50 text-amber-700 rounded-full border border-amber-100">
                    Live Monitor
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/80 text-slate-500 border-b border-slate-200 text-xs font-bold uppercase tracking-wider">
                            <th class="px-6 py-4">Mã GD</th>
                            <th class="px-6 py-4">Khách Hàng</th>
                            <th class="px-6 py-4">Phòng Khóa</th>
                            <th class="px-6 py-4 text-center">Trạng Thái 4PC</th>
                            <th class="px-6 py-4 text-right">Cập Nhật Lúc</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white/60">
                        @forelse($transactions as $tx)
                        <tr class="hover:bg-slate-50 transition-colors duration-200 group">
                            <td class="px-6 py-4">
                                <span class="font-semibold text-slate-700">#{{ $tx->transaction_id }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold text-xs uppercase shadow-sm">
                                        {{ mb_substr($tx->customer_name ?? 'N', 0, 1, 'UTF-8') }}
                                    </div>
                                    <span class="font-medium text-slate-800">{{ $tx->customer_name ?? 'N/A' }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm font-medium bg-slate-100 text-slate-700 border border-slate-200 shadow-sm">
                                    Phòng {{ $tx->room_id ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($tx->status == 'committed')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 border border-green-200 shadow-sm">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>{{ strtoupper($tx->status) }}
                                    </span>
                                @elseif($tx->status == 'pending')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700 border border-amber-200 shadow-sm">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>{{ strtoupper($tx->status) }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700 border border-red-200 shadow-sm">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>{{ strtoupper($tx->status) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right text-sm text-slate-500 font-medium whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($tx->updated_at)->format('H:i:s d/m/Y') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center text-slate-500 bg-slate-50/50">
                                <p class="text-base font-medium">Node đang chờ nhận lệnh từ Server Trung Tâm...</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Footer -->
            <div class="bg-slate-50/80 px-6 py-4 border-t border-slate-100 text-xs text-slate-400 flex justify-between items-center rounded-b-none">
                <span>Hệ thống mạng: Phân tán an toàn (TLS/SSL)</span>
                <span>Tự động làm mới hệ thống: 2 giây</span>
            </div>
        </div>

        {{-- ===== Ô NHẬT KÝ GIAO THỨC 4PC ===== --}}
        <div class="mt-6">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                    <span>📋</span>
                    <span class="font-luxury">Nhật Ký Giao Thức 4-Phase Commit</span>
                </h2>
                <span class="text-xs text-slate-500 bg-white border border-slate-200 px-3 py-1 rounded-full shadow-sm">{{ count($logs) }} sự kiện</span>
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
        const logBox = document.getElementById('logBox');
        if (logBox) logBox.scrollTop = logBox.scrollHeight;
    </script>
</body>
</html>