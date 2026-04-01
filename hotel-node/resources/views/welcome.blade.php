<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luxury Node {{ $port }} - Premium Hotel Monitor</title>
    <!-- Premium Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <meta http-equiv="refresh" content="3">
    <style>
        :root {
            --gold-primary: #D4AF37;
            --gold-gradient: linear-gradient(135deg, #CFB53B 0%, #D4AF37 50%, #8A6D3B 100%);
            --metallic-gold: linear-gradient(to right, #bf953f, #fcf6ba, #b38728, #fbf5b7, #aa771c);
            --luxury-black: #0a0a0a;
            --glass-bg: rgba(10, 10, 10, 0.85);
        }

        body {
            background: url('/images/luxury-bg.png') no-repeat center center fixed;
            background-size: cover;
            color: #d1d5db;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at center, transparent 0%, rgba(0, 0, 0, 0.4) 100%);
            z-index: -1;
        }

        .luxury-serif {
            font-family: 'Playfair Display', serif;
        }

        .gold-border {
            border: 1px solid rgba(212, 175, 55, 0.3);
            position: relative;
        }

        .gold-border::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border: 1px solid rgba(212, 175, 55, 0.1);
            pointer-events: none;
            margin: 2px;
        }

        .glass-panel {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
        }

        .metallic-text {
            background: var(--metallic-gold);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: bold;
        }

        .pulse-gold {
            animation: pulse-gold-animation 3s infinite;
        }

        @keyframes pulse-gold-animation {
            0% { transform: scale(1); opacity: 1; box-shadow: 0 0 0 0 rgba(212, 175, 55, 0.7); }
            50% { transform: scale(1.1); opacity: 0.8; box-shadow: 0 0 10px 4px rgba(212, 175, 55, 0); }
            100% { transform: scale(1); opacity: 1; box-shadow: 0 0 0 0 rgba(212, 175, 55, 0); }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #1a1a1a; }
        ::-webkit-scrollbar-thumb { background: var(--gold-primary); border-radius: 3px; }
    </style>
</head>

<body class="p-4 md:p-8">
    <div class="overlay"></div>

    <div class="max-w-6xl w-full glass-panel p-8 rounded-2xl gold-border">
        <div class="flex flex-col md:flex-row justify-between items-center border-b border-white/10 pb-6 mb-8 gap-6">
            <div class="text-center md:text-left">
                <h1 class="luxury-serif text-4xl md:text-5xl tracking-[0.1em] metallic-text uppercase mb-2">Luxury Hotel</h1>
                <p class="text-zinc-400 text-sm tracking-widest uppercase italic">Hệ Thống Giám Sát Phân Tán • Cloud Node {{ $port }}</p>
            </div>
            
            <div class="flex flex-col items-center md:items-end gap-3">
                <div class="flex items-center gap-3 bg-black/40 px-4 py-2 rounded-full border border-white/5">
                    <div class="w-2.5 h-2.5 bg-[#D4AF37] rounded-full pulse-gold"></div>
                    <span class="text-xs font-semibold tracking-widest text-[#D4AF37]">SYSTEM ACTIVE</span>
                </div>
                <div class="text-white/80 text-sm tracking-wider">
                    ACCESS PORT: <span class="bg-gradient-to-r from-[#D4AF37] to-[#aa771c] px-3 py-1 rounded text-black font-bold ml-1 text-xs">{{ $port }}</span>
                </div>
            </div>
        </div>

        <div class="mb-8 flex items-center gap-4">
            <div class="h-px flex-1 bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
            <h2 class="luxury-serif text-lg italic text-[#D4AF37] px-4">Dữ liệu đồng bộ thời gian thực</h2>
            <div class="h-px flex-1 bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
        </div>

        <div class="overflow-hidden rounded-xl border border-white/5 bg-black/20">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-white/5 text-[#D4AF37] uppercase text-[11px] tracking-[0.2em] font-semibold">
                            <th class="p-5">Mã Giao Dịch</th>
                            <th class="p-5">Khách Thuê</th>
                            <th class="p-5">Phòng Khóa</th>
                            <th class="p-5">Trạng Thái 4PC</th>
                            <th class="p-5">Cập Nhật Sau Cùng</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse($transactions as $tx)
                        <tr class="hover:bg-white/[0.03] transition-colors duration-300">
                            <td class="p-5">
                                <span class="font-mono text-white tracking-widest text-sm italic">#{{ $tx->transaction_id }}</span>
                            </td>
                            <td class="p-5">
                                <span class="text-zinc-300 font-medium">{{ $tx->customer_name ?? 'Khách VIP' }}</span>
                            </td>
                            <td class="p-5">
                                <div class="flex items-center gap-2">
                                    <div class="w-1.5 h-1.5 rounded-full bg-cyan-500/50"></div>
                                    <span class="text-cyan-400 font-semibold tracking-tighter">PHÒNG {{ $tx->room_id ?? '---' }}</span>
                                </div>
                            </td>
                            <td class="p-5">
                                @php
                                    $statusColor = match($tx->status) {
                                        'committed' => 'text-emerald-400 border-emerald-400/20 bg-emerald-400/5',
                                        'pending' => 'text-amber-400 border-amber-400/20 bg-amber-400/5',
                                        default => 'text-rose-400 border-rose-400/20 bg-rose-400/5',
                                    };
                                @endphp
                                <span class="px-3 py-1 rounded border text-[10px] font-bold tracking-widest uppercase {{ $statusColor }}">
                                    {{ $tx->status }}
                                </span>
                            </td>
                            <td class="p-5 text-zinc-500 text-xs">
                                {{ \Carbon\Carbon::parse($tx->updated_at)->format('H:i:s • d/m/Y') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="p-12 text-center">
                                <div class="flex flex-col items-center gap-4">
                                    <div class="w-12 h-12 rounded-full border border-white/10 flex items-center justify-center">
                                        <div class="w-2 h-2 rounded-full bg-[#D4AF37] animate-ping"></div>
                                    </div>
                                    <p class="text-zinc-500 italic text-sm tracking-widest">Đang khởi tạo kết nối với trung tâm điều hành...</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-8 flex justify-between items-center text-[10px] tracking-widest text-zinc-600 uppercase">
            <span>Systems Integrity: Verified</span>
            <span>Luxury Hotel - Distributed Infrastructure</span>
        </div>
    </div>
</body>

</html>