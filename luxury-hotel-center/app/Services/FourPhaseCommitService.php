<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Log;

class FourPhaseCommitService
{
    protected $nodes;

    public function __construct()
    {
        $this->nodes = [
            'https://luxury-hotel-distributed-system-49mq.onrender.com', // Máy Khánh
            'https://node-3-ngocc.onrender.com', // Máy Ngọc
            'https://node-2-khai-80yz.onrender.com', // Máy Khải
            'https://node-kien.onrender.com', // Máy Kiên
            'https://node-5-duy-b0ca.onrender.com' // Máy Duy
        ];
    }

    public function executeTransaction(array $bookingData): bool
    {
        $transactionId = $bookingData['id'];
        $roomId = $bookingData['room_id'] ?? null;
        $customerName = $bookingData['name'] ?? null;
        $pointOfNoReturn = false; 

        try {
            // PHA 1: HỎI Ý KIẾN
            if (!$this->phase1CanCommit($bookingData)) {
                $this->abortTransaction($transactionId, "TỪ CHỐI (PHA 1)", $roomId, $customerName);
                throw new \Exception("Giao dịch bị từ chối: Không đủ Node sống hoặc bị chiếm phòng!");
            }

            // PHA 2: CHUẨN BỊ
            if (!$this->phase2PreCommit($transactionId, $roomId, $customerName)) {
                $this->abortTransaction($transactionId, "TỪ CHỐI (PHA 2)", $roomId, $customerName);
                throw new \Exception("Giao dịch bị từ chối: Lỗi ở Pha chuẩn bị!");
            }

            $pointOfNoReturn = true;

            // PHA 3: CHỐT HẠ
            $this->phase3DoCommit($transactionId);
            return true; 

        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            
            if ($pointOfNoReturn) {
                return true; 
            }

            if (strpos($errorMsg, 'CƯỚP PHÒNG') !== false) {
                $this->abortTransaction($transactionId, "ABORTED (BỊ CƯỚP PHÒNG)", $roomId, $customerName);
                throw $e; 
            } else {
                $this->abortTransaction($transactionId, "HỦY (KHÔNG ĐỦ NODE SỐNG)", $roomId, $customerName);
                throw $e;
            }
        }
    }

    private function phase1CanCommit($data): bool {
        return $this->sendQuorumRequests('/api/can-commit', ['id' => $data['id'], 'room_id' => $data['room_id']], 'YES');
    }

    private function phase2PreCommit($transactionId, $roomId = null, $customerName = null): bool {
        return $this->sendQuorumRequests('/api/pre-commit', ['transaction_id' => $transactionId, 'room_id' => $roomId, 'customer_name' => $customerName], 'ACK');
    }

    private function phase3DoCommit($transactionId): bool {
        return $this->sendQuorumRequests('/api/do-commit', ['transaction_id' => $transactionId], 'SUCCESS');
    }

    public function abortTransaction($transactionId, $reason = "ABORTED", $roomId = null, $customerName = null) {
        foreach ($this->nodes as $nodeUrl) {
            try {
                Http::withoutVerifying()->timeout(3)->post($nodeUrl . '/api/abort', [
                    'transaction_id' => $transactionId,
                    'reason' => $reason,
                    'room_id' => $roomId,
                    'customer_name' => $customerName
                ]);
            } catch (\Exception $e) {}
        }
    }

    // =========================================================================
    // VŨ KHÍ: ĐIỂM DANH MÁY CHẾT (EVENTUAL CONSISTENCY)
    // =========================================================================
    private function sendQuorumRequests($endpoint, $payload, $expectedStatus): bool
    {
        // Danh bạ để điểm danh
        $nodeNames = [
            'https://luxury-hotel-distributed-system-49mq.onrender.com' => 'Máy Đầu Não (Khánh)',
            'https://node-3-ngocc.onrender.com' => 'Máy Ngọc',
            'https://node-2-khai-80yz.onrender.com' => 'Máy Khải',
            'https://node-kien.onrender.com' => 'Máy Kiên',
            'https://node-5-duy-b0ca.onrender.com' => 'Máy Duy'
        ];

        $responses = Http::pool(function (Pool $pool) use ($endpoint, $payload) {
            foreach ($this->nodes as $nodeUrl) {
                $pool->as($nodeUrl)->withOptions(['verify' => false])->connectTimeout(3)->timeout(5)->post($nodeUrl . $endpoint, $payload);
            }
        });

        $successCount = 0;
        $deadNodesList = []; // Mảng chứa tên các máy bị tắt
        $roomHijackedError = null; 

        foreach ($responses as $nodeUrl => $response) {
            // NẾU MÁY BỊ TẮT HOẶC MẤT MẠNG -> GHI TÊN VÀO SỔ ĐEN
            if ($response instanceof \Exception || !$response->ok()) {
                $deadNodesList[] = $nodeNames[$nodeUrl] ?? 'Máy Ẩn Danh';
                continue; 
            }
            
            $status = $response->json('status');
            
            if (strpos($endpoint, 'can-commit') !== false && $status === 'NO') {
                $roomHijackedError = "CƯỚP PHÒNG|Phòng số {$payload['room_id']} vừa bị khách khác khóa trước!";
                continue; 
            }

            if ($status === $expectedStatus) {
                $successCount++;
            }
        }

        if ($roomHijackedError) {
            throw new \Exception($roomHijackedError);
        }

        // CẦN ÍT NHẤT 1 MÁY SỐNG ĐỂ CHO PHÉP ĐẶT PHÒNG
        $minimumRequired = 1; 

        if ($successCount >= $minimumRequired) {
            // Nếu có máy chết, gom tên lại và quăng ra màn hình
            if (count($deadNodesList) > 0) {
                $deadNames = implode(', ', $deadNodesList);
                session()->flash('sync_warning', "Đặt phòng thành công trên các Server đang Online! Tuy nhiên, [ $deadNames ] đang bị tắt hoặc mất mạng. Hệ thống sẽ đồng bộ bù sau.");
            }
            return true; 
        }

        throw new \Exception("Lỗi Mạng Nghiêm Trọng: Sập toàn bộ hệ thống! Không có máy nào phản hồi.");
    }
}