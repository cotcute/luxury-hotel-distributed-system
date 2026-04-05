<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Node {{ $port }} - Monitor Khách Sạn Hoàng Gia</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <meta http-equiv="refresh" content="2">
    <style>
        :root {
            --gold: #d4af37;
            --gold-light: #f9e29d;
            --rich-black: #0a0a0a;
            --charcoal: #1a1a1a;
            --glass: rgba(18, 18, 18, 0.8);
        }

        body {
            background-color: var(--rich-black);
            background-image:
                linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)),
                url('{{ asset('luxury_hotel_bg_1775040618119.png') }}');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: #e2e8f0;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            padding: 2rem;
        }

        h1, h2, h3, .font-royal { font-family: 'Cinzel', serif; }

        .luxury-card {
            background: var(--glass);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(214, 175, 55, 0.3);
            box-shadow: 0 40px 100px -20px rgba(0, 0, 0, 0.9),
                        inset 0 0 40px rgba(214, 175, 55, 0.05);
            position: relative;
            overflow: hidden;
        }

        .luxury-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--gold), transparent);
        }

        .gold-text { color: var(--gold); text-shadow: 0 0 15px rgba(212, 175, 55, 0.4); }
        .shimmer {
            background: linear-gradient(90deg, transparent, rgba(214, 175, 55, 0.1), transparent);
            background-size: 200% 100%;
            animation: shimmer-effect 3s infinite linear;
        }
        @keyframes shimmer-effect {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }

        .pulse-gold { animation: pulse-gold-animation 2s infinite; }
        @keyframes pulse-gold-animation {
            0%, 100% { box-shadow: 0 0 0 0 rgba(212, 175, 55, 0.4); }
            50% { box-shadow: 0 0 0 10px rgba(212, 175, 55, 0); }
        }

        .table-luxury thead { background: rgba(214, 175, 55, 0.1); }
        .table-luxury th {
            font-family: 'Cinzel', serif;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            font-size: 0.75rem;
            border-bottom: 2px solid var(--gold);
        }
        .table-luxury td { border-bottom: 1px solid rgba(214, 175, 55, 0.1); }

        .status-badge {
            padding: 4px 12px; border-radius: 2px;
            font-size: 0.65rem; font-weight: 700;
            letter-spacing: 0.1em; text-transform: uppercase;
            border: 1px solid transparent;
        }
        .status-committed { background: rgba(34,197,94,0.15); color: #4ade80; border-color: rgba(34,197,94,0.4); }
        .status-pending   { background: rgba(234,179,8,0.15);  color: #fde047; border-color: rgba(234,179,8,0.4); }
        .status-aborted   { background: rgba(239,68,68,0.15);  color: #f87171; border-color: rgba(239,68,68,0.4); }

        .ornament { display: flex; align-items: center; justify-content: center; gap: 1.5rem; margin: 1.5rem 0; }
        .ornament::before, .ornament::after {
            content: ''; height: 1px; flex-grow: 1;
            background: linear-gradient(90deg, transparent, var(--gold), transparent);
        }

        /* LOG BOX - Gold theme */
        .log-box {
            background: rgba(5,5,5,0.95);
            border: 1px solid rgba(212,175,55,0.25);
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            height: 280px;
            overflow-y: auto;
            padding: 12px;
        }
        .log-line {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            padding: 4px 0;
            border-bottom: 1px solid rgba(212,175,55,0.08);
            line-height: 1.5;
        }
        .log-time    { color: rgba(212,175,55,0.35); min-width: 70px; flex-shrink: 0; }
        .log-icon    { min-width: 18px; flex-shrink: 0; }
        .log-action  { font-weight: 600; min-width: 160px; flex-shrink: 0; color: rgba(212,175,55,0.7); }
        .log-detail  { flex: 1; color: rgba(212,175,55,0.5); word-break: break-word; white-space: pre-wrap; }
        .log-success .log-action { color: #4ade80; }
        .log-success .log-detail { color: #bbf7d0; }
        .log-warning .log-action { color: var(--gold); }
        .log-warning .log-detail { color: var(--gold-light); }
        .log-error   .log-action { color: #f87171; }
        .log-error   .log-detail { color: #fecaca; }
        .log-info    .log-action { color: #38bdf8; }
        .log-info    .log-detail { color: #bae6fd; }
        .log-box::-webkit-scrollbar { width: 5px; }
        .log-box::-webkit-scrollbar-track { background: rgba(0,0,0,0.5); }
        .log-box::-webkit-scrollbar-thumb { background: rgba(212,175,55,0.3); border-radius: 3px; }
    </style>
</head>

<body>
    <div class="w-full max-w-6xl mx-auto luxury-card rounded-sm p-12 relative">
        <!-- Huy hiệu góc -->
        <div class="absolute top-0 right-0 p-6 opacity-30 pointer-events-none">
            <svg width="140" height="140" viewBox="0 0 100 100" fill="none">
                <path d="M50 5L60 35H40L50 5Z" fill="var(--gold)"/>
                <path d="M50 95L40 65H60L50 95Z" fill="var(--gold)"/>
                <path d="M5 50L35 40V60L5 50Z" fill="var(--gold)"/>
                <path d="M95 50L65 60V40L95 50Z" fill="var(--gold)"/>
                <circle cx="50" cy="50" r="15" stroke="var(--gold)" stroke-width="1.5"/>
                <path d="M50 30L55 45H45L50 30Z" fill="var(--gold)"/>
            </svg>
        </div>

        <header class="flex flex-col md:flex-row justify-between items-center mb-12 border-b border-yellow-900/20 pb-10">
            <div class="text-center md:text-left mb-8 md:mb-0">
                <h1 class="text-6xl font-bold tracking-[0.25em] gold-text mb-3">LUXURY HOTEL</h1>
                <p class="text-slate-400 text-xs uppercase tracking-[0.4em] shimmer">Giám Sát Phân Tán Đám Mây • Giao Thức 4PC</p>
            </div>
            <div class="text-center md:text-right">
                <div class="flex items-center justify-center md:justify-end gap-3 mb-4">
                    <div class="w-3 h-3 bg-green-500 rounded-full pulse-gold"></div>
                    <span class="text-xs font-bold tracking-[0.2em] text-green-400">NODE STATUS: ONLINE</span>
                </div>
                <div class="inline-block border border-yellow-800/40 px-6 py-3 bg-yellow-900/10">
                    <span class="text-slate-400 text-[9px] uppercase tracking-widest mr-3">Cổng Truy Cập</span>
                    <span class="text-2xl font-bold gold-text font-royal">{{ $port }}</span>
                </div>
            </div>
        </header>

        <section>
            <div class="ornament">
                <span class="gold-text text-sm tracking-[0.3em] uppercase font-royal">Đồng Bộ Dữ Liệu Thời Gian Thực</span>
            </div>

            <div class="mt-10 overflow-hidden rounded-sm border border-yellow-900/10">
                <div class="overflow-x-auto">
                    <table class="w-full text-left table-luxury">
                        <thead>
                            <tr class="text-yellow-600/60 uppercase">
                                <th class="p-6">Mã Giao Dịch</th>
                                <th class="p-6">Tên Khách Hàng</th>
                                <th class="p-6">Phòng Được Cấp</th>
                                <th class="p-6">Trạng Thái 4PC</th>
                                <th class="p-6 text-right">Cập Nhật Lúc</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-yellow-900/5">
                            @forelse($transactions as $tx)
                            <tr class="hover:bg-yellow-900/[0.04] transition-all duration-500 group">
                                <td class="p-6">
                                    <span class="font-royal gold-text font-bold text-lg">#{{ $tx->transaction_id }}</span>
                                </td>
                                <td class="p-6">
                                    <div class="flex flex-col">
                                        <span class="text-slate-100 font-semibold text-base">{{ $tx->customer_name ?? 'N/A' }}</span>
                                        <span class="text-[9px] text-slate-500 uppercase tracking-widest mt-1">Thành Viên Cao Cấp</span>
                                    </div>
                                </td>
                                <td class="p-6">
                                    <span class="bg-yellow-900/10 text-yellow-200 px-4 py-1.5 text-xs border border-yellow-800/20 font-royal">
                                        PHÒNG {{ $tx->room_id ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="p-6">
                                    <span class="status-badge
                                        @if($tx->status == 'committed') status-committed
                                        @elseif($tx->status == 'pending') status-pending
                                        @else status-aborted @endif">
                                        {{ strtoupper($tx->status) }}
                                    </span>
                                </td>
                                <td class="p-6 text-right text-slate-400 font-mono text-xs">
                                    {{ \Carbon\Carbon::parse($tx->updated_at)->format('H:i:s • d.m.Y') }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="p-20 text-center">
                                    <p class="text-slate-500 italic tracking-[0.2em] text-sm font-royal">
                                        Đang chờ lệnh từ máy chủ trung tâm...
                                    </p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        {{-- ===== Ô NHẬT KÝ GIAO THỨC 4PC ===== --}}
        <section class="mt-12">
            <div class="ornament">
                <span class="gold-text text-sm tracking-[0.3em] uppercase font-royal">📋 Nhật Ký Giao Thức 4-Phase Commit</span>
            </div>
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs text-slate-500 tracking-[0.15em] uppercase">Thời gian thực · tự động cập nhật</span>
                <span class="text-xs gold-text border border-yellow-800/30 px-3 py-1">{{ count($logs) }} SỰ KIỆN</span>
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
                <div class="text-yellow-900/60 italic text-center py-8 font-royal tracking-widest text-sm">
                    📡 ĐANG CHỜ GIAO DỊCH ĐẦU TIÊN...
                </div>
                @endforelse
            </div>
        </section>

        <footer class="mt-12 flex justify-between items-center text-[10px] text-slate-600 tracking-[0.3em] uppercase">
            <div>© 2026 ROYAL LUXURY GRP.</div>
            <div class="gold-text opacity-40">CHỈ DÀNH CHO NHÂN VIÊN ĐƯỢC ỦY QUYỀN</div>
        </footer>
    </div>

    <script>
        const logBox = document.getElementById('logBox');
        if (logBox) logBox.scrollTop = logBox.scrollHeight;
    </script>
</body>

</html>