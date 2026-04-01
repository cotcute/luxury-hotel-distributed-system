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
        0% {
            opacity: 1;
        }

        50% {
            opacity: 0.5;
        }

        100% {
            opacity: 1;
        }
    }
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

        <div class="overflow-x-auto">
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
                        <td class="p-3 border border-slate-700 font-bold text-cyan-300">Phòng
                            {{ $tx->room_id ?? 'N/A' }}</td>
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
                        <td colspan="5" class="p-5 text-center text-slate-500">Node đang chờ nhận lệnh từ Server Trung
                            Tâm...</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>