<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Node {{ $port }} - 4PC Distributed Monitor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta http-equiv="refresh" content="2">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #053551; /* Darker, modern background */
            color: #e2e8f0;
            font-family: 'Inter', sans-serif;
        }

        .font-mono {
            font-family: 'Fira Code', monospace;
        }

        .pulse-dot {
            box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
            60% { transform: scale(1); box-shadow: 0 0 0 6px rgba(34, 197, 94, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
        }

        /* Tùy chỉnh thanh cuộn cho bảng */
        ::-webkit-scrollbar { height: 8px; width: 8px; }
        ::-webkit-scrollbar-track { background: #1e293b; border-radius: 4px; }
        ::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #64748b; }
    </style>
</head>

<body class="p-4 md:p-8 min-h-screen flex flex-col">
    <div class="max-w-7xl mx-auto w-full flex-1 flex flex-col gap-6">
        
        <header class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 pb-5 border-b border-slate-800">
            <div>
                <div class="flex items-center gap-3">
            
                    <img src="{{ asset('khanh.jpg') }}" alt="Logo Luxury Hotel" class="w-12 h-12 object-contain rounded-lg">
            
                    <div>
                        <h1 class="text-2xl font-bold text-white tracking-tight">LUXURY HOTEL</h1>
                        <p class="text-slate-400 text-sm mt-0.5">Distributed Cloud System Monitor</p>
                    </div>
                </div>
            </div>
            <div>
                <div class="flex items-center gap-3">
                    <div class="bg-indigo-500/20 p-2 rounded-lg border border-indigo-500/30">
                        <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>

                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-white tracking-tight">LUXURY HOTEL</h1>
                        <p class="text-slate-400 text-sm mt-0.5">Distributed Cloud System Monitor</p>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center gap-3 bg-slate-800/50 px-4 py-2 rounded-lg border border-slate-700/50">
                <span class="relative flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                </span>
                <span class="text-sm font-medium text-green-400">System Healthy</span>
            </div>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-slate-800/40 border border-slate-700/50 rounded-xl p-5 flex items-center justify-between">
                <div>
                    <p class="text-slate-400 text-sm font-medium mb-1">Trạng Thái Node</p>
                    <div class="flex items-center gap-2">
                        <div class="w-2.5 h-2.5 bg-green-500 rounded-full pulse-dot"></div>
                        <span class="text-xl font-bold text-white">ONLINE</span>
                    </div>
                </div>
                <div class="p-3 bg-green-500/10 rounded-lg text-green-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>

            <div class="bg-slate-800/40 border border-slate-700/50 rounded-xl p-5 flex items-center justify-between">
                <div>
                    <p class="text-slate-400 text-sm font-medium mb-1">Cổng Lắng Nghe (Port)</p>
                    <span class="text-2xl font-mono font-bold text-indigo-400">{{ $port }}</span>
                </div>
                <div class="p-3 bg-indigo-500/10 rounded-lg text-indigo-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
            </div>

            <div class="bg-slate-800/40 border border-slate-700/50 rounded-xl p-5 flex items-center justify-between">
                <div>
                    <p class="text-slate-400 text-sm font-medium mb-1">Giao Thức Đồng Bộ</p>
                    <span class="text-xl font-bold text-white">4-Phase Commit</span>
                </div>
                <div class="p-3 bg-amber-500/10 rounded-lg text-amber-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
            </div>
        </div>

        <div class="bg-slate-800/40 border border-slate-700/50 rounded-xl overflow-hidden shadow-xl flex-1 flex flex-col">
            <div class="px-6 py-4 border-b border-slate-700/50 bg-slate-800/20 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                    Dữ Liệu Đồng Bộ Thời Gian Thực
                </h2>
                <span class="text-xs text-slate-400 flex items-center gap-1">
                    <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Tự động làm mới 2s
                </span>
            </div>
            
            <div class="overflow-x-auto flex-1">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-900/50 text-slate-400 border-b border-slate-700/50">
                        <tr>
                            <th scope="col" class="px-6 py-4 font-semibold">Mã Giao Dịch</th>
                            <th scope="col" class="px-6 py-4 font-semibold">Khách Hàng</th>
                            <th scope="col" class="px-6 py-4 font-semibold">Phòng Khóa</th>
                            <th scope="col" class="px-6 py-4 font-semibold">Trạng Thái 4PC</th>
                            <th scope="col" class="px-6 py-4 font-semibold text-right">Cập Nhật Lúc</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/50">
                        @forelse($transactions as $tx)
                        <tr class="hover:bg-slate-700/20 transition duration-150 ease-in-out">
                            <td class="px-6 py-4">
                                <span class="font-mono text-slate-300 bg-slate-800 px-2 py-1 rounded border border-slate-700">#{{ $tx->transaction_id }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-slate-700 flex items-center justify-center text-xs font-bold text-slate-300">
                                        {{ substr($tx->customer_name ?? 'N', 0, 1) }}
                                    </div>
                                    <span class="text-slate-200 font-medium">{{ $tx->customer_name ?? 'Không xác định' }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-cyan-400 font-medium flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                                    Phòng {{ $tx->room_id ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($tx->status == 'committed')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-500/10 text-green-400 border border-green-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-400"></span>
                                        COMMITTED
                                    </span>
                                @elseif($tx->status == 'pending')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                        PENDING
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-red-500/10 text-red-400 border border-red-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>
                                        {{ strtoupper($tx->status) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="font-mono text-slate-400 text-xs">
                                    {{ \Carbon\Carbon::parse($tx->updated_at)->format('H:i:s - d/m/Y') }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-500">
                                    <svg class="w-12 h-12 mb-3 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                    <p class="text-sm">Node đang chờ nhận lệnh từ Server Trung Tâm...</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>