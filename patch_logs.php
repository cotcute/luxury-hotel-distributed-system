<?php

$nodes = [
    'c:/Users/FPTSHOP/Desktop/DU_AN_HOTEL_FINAL/node-1-khanh',
    'c:/Users/FPTSHOP/Desktop/DU_AN_HOTEL_FINAL/node-2-khai',
    'c:/Users/FPTSHOP/Desktop/DU_AN_HOTEL_FINAL/node-3-ngoc',
    'c:/Users/FPTSHOP/Desktop/DU_AN_HOTEL_FINAL/node-4-kien',
    'c:/Users/FPTSHOP/Desktop/DU_AN_HOTEL_FINAL/node-5-duy',
];

$logSystemCodeController = <<<PHP

    private function logSystem(\$txnId, \$action, \$details, \$status = 'info')
    {
        try {
            \Illuminate\Support\Facades\DB::table('node_logs')->insert([
                'node_port' => request()->getHost(),
                'transaction_id' => \$txnId,
                'action' => \$action,
                'details' => \$details,
                'status' => \$status,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } catch (\Exception \$e) {}
    }
}
PHP;

foreach ($nodes as $nodeDir) {
    // ---------------------------------------------------------------- //
    // 1. PATCH NodeController.php
    // ---------------------------------------------------------------- //
    $controllerPath = $nodeDir . '/app/Http/Controllers/NodeController.php';
    if (file_exists($controllerPath)) {
        $content = file_get_contents($controllerPath);
        
        // Remove trailing brace
        $content = preg_replace('/}\s*$/', '', $content);

        // Inject logSystem logic for CAN-COMMIT
        if (!str_contains($content, 'PHA 1: TRƯNG CẦU')) {
            $content = preg_replace(
                "/(return response\(\)->json\(\['status' => \\\$isRoomLocked \? 'NO' : 'YES'\]\);)/", 
                "\\\$vote = \\\$isRoomLocked ? 'NO' : 'YES';\n        \$this->logSystem(\\\$transactionId ?? 'UNKNOWN', 'PHA 1: TRƯNG CẦU', 'Phản hồi Can-Commit: VOTE ' . \\\$vote, \\\$vote === 'YES' ? 'success' : 'warning');\n        $1", 
                $content
            );
        }

        // Inject logSystem logic for PRE-COMMIT
        if (!str_contains($content, 'PHA 2: KHÓA TẠM')) {
            $content = preg_replace(
                "/(\\\$nodeIdentity\s*=\s*.*?;)(\s*.*?)DB::table\('node_bookings'\)->updateOrInsert(?=.*?return response\(\)->json\(\['status' => 'ACK'\]\);)/s", 
                "$1$2DB::table('node_bookings')->updateOrInsert", 
                $content
            );
            $content = preg_replace(
                "/(return response\(\)->json\(\['status' => 'ACK'\]\);)/", 
                "\$this->logSystem(\\\$transactionId ?? 'UNKNOWN', 'PHA 2: KHÓA TẠM', 'Pre-Commit: Khóa tạm thời CSDL chờ hiệu lệnh', 'info');\n            $1", 
                $content
            );
        }

        // Inject logSystem logic for DO-COMMIT
        if (!str_contains($content, 'PHA 3: CHỐT GIAO DỊCH')) {
            $content = preg_replace(
                "/(return response\(\)->json\(\['status' => 'SUCCESS'\]\);)/", 
                "\$this->logSystem(\\\$transactionId ?? 'UNKNOWN', 'PHA 3: CHỐT GIAO DỊCH', 'Do-Commit: Đóng gói và lưu vĩnh viễn giao dịch', 'success');\n        $1", 
                $content
            );
        }
        
        // Add the helper function
        if (!str_contains($content, 'function logSystem')) {
            $content .= $logSystemCodeController;
        } else {
            $content .= "\n}\n";
        }

        file_put_contents($controllerPath, $content);
    }

    // ---------------------------------------------------------------- //
    // 2. PATCH FourPhaseCommitService.php
    // ---------------------------------------------------------------- //
    $servicePath = $nodeDir . '/app/Services/FourPhaseCommitService.php';
    if (file_exists($servicePath)) {
        $content = file_get_contents($servicePath);

        // Remove trailing brace
        $content = preg_replace('/}\s*$/', '', $content);

        // Inject logic Can-Commit broadcast
        if (!str_contains($content, 'BẮT ĐẦU 4PC')) {
            $content = preg_replace(
                "/(\\\$others\s*=\s*\\\$this->getOtherNodes\(\);)/", 
                "$1\n        \$this->logSystem(\\\$txnId, 'BẮT ĐẦU 4PC', 'Nhạc trưởng khởi động: Gửi Can-Commit tới ' . count(\\\$others) . ' server để kiểm tra phòng', 'warning');", 
                $content
            );
        }

        // Inject logic Pre-Commit broadcast
        if (!str_contains($content, 'ĐIỀU PHỐI')) {
            $content = preg_replace(
                "/(\\\$this->broadcastPreCommit\(\\\$txnId, \\\$roomId, \\\$customerName, \\\$yesNodes\);)/", 
                "\$this->logSystem(\\\$txnId, 'PHA 3: ĐIỀU PHỐI', 'Ra lệnh Pre-Commit khóa phòng trên toàn mạng', 'info');\n        $1", 
                $content
            );
        }

        // Inject logic Do-Commit broadcast
        if (!str_contains($content, 'ĐỒNG BỘ TOÀN MẠNG')) {
            $content = str_replace(
                "\$this->broadcastDoCommit(\$txnId, \$yesNodes, \$roomId, \$customerName);", 
                "\$this->logSystem(\$txnId, 'PHA 4: ĐỒNG BỘ TOÀN MẠNG', 'Phát động Do-Commit: Bắt buộc toàn mạng nhận chung 1 bộ dữ liệu chốt', 'success');\n        \$this->broadcastDoCommit(\$txnId, \$yesNodes, \$roomId, \$customerName);", 
                $content
            );
        }

        // Inject log function
        if (!str_contains($content, 'function logSystem')) {
            $content .= $logSystemCodeController;
        } else {
            $content .= "\n}\n";
        }

        file_put_contents($servicePath, $content);
    }
}

echo "Patched all controllers and services.\n";
