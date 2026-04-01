@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <!-- Tiêu đề trang -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0 text-gray-800">Hộp Thư Khách Hàng</h2>
        <span class="badge bg-warning text-dark fs-6 px-3 py-2">
            <i class="fa-solid fa-envelope me-2"></i> Tổng tin: {{ $messages->total() }}
        </span>
    </div>

    <!-- Card chứa bảng dữ liệu -->
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-header bg-white py-3 d-flex align-items-center">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fa-solid fa-list me-2"></i> Danh sách tin nhắn mới
                nhất</h6>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th scope="col" class="ps-4 py-3" style="width: 5%">#</th>
                            <th scope="col" style="width: 20%">Người gửi</th>
                            <th scope="col" style="width: 45%">Nội dung tin nhắn</th>
                            <th scope="col" style="width: 15%">Thời gian</th>
                            <th scope="col" class="text-end pe-4" style="width: 15%">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($messages as $key => $msg)
                        <tr>
                            <td class="ps-4 fw-bold text-muted">{{ $messages->firstItem() + $key }}</td>

                            <!-- Cột thông tin người gửi -->
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3"
                                        style="width: 40px; height: 40px; font-size: 14px;">
                                        {{ strtoupper(substr($msg->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">{{ $msg->name }}</div>
                                        <small class="text-muted">{{ $msg->email }}</small>
                                    </div>
                                </div>
                            </td>

                            <!-- Cột nội dung (giới hạn 100 ký tự) -->
                            <td>
                                <p class="mb-1 text-dark" style="font-size: 0.95rem;">
                                    "{{ Str::limit($msg->message, 120) }}"
                                </p>
                            </td>

                            <!-- Cột thời gian -->
                            <td>
                                <span class="badge bg-light text-dark border mb-1">
                                    <i class="fa-regular fa-clock me-1"></i> {{ $msg->created_at->diffForHumans() }}
                                </span>
                                <br>
                                <small class="text-muted" style="font-size: 12px;">
                                    {{ $msg->created_at->format('d/m/Y H:i') }}
                                </small>
                            </td>

                            <!-- Cột hành động -->
                            <td class="text-end pe-4">
                                <a href="mailto:{{ $msg->email }}" class="btn btn-sm btn-outline-primary shadow-sm"
                                    title="Trả lời qua Email">
                                    <i class="fa-solid fa-reply"></i> Trả lời
                                </a>
                            </td>
                        </tr>
                        @empty
                        <!-- Giao diện khi không có dữ liệu -->
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="text-muted opacity-50">
                                    <i class="fa-regular fa-folder-open fa-4x mb-3"></i>
                                    <p class="fs-5 mb-0">Chưa có tin nhắn liên hệ nào.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Phân trang -->
        @if($messages->hasPages())
        <div class="card-footer bg-white py-3 d-flex justify-content-end">
            {{ $messages->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>

<style>
/* CSS phụ trợ để làm đẹp thêm */
.table-hover tbody tr:hover {
    background-color: rgba(201, 160, 80, 0.05);
    /* Màu vàng nhạt khi hover */
    transition: all 0.2s ease;
}

.btn-outline-primary {
    border-color: #0d6efd;
    color: #0d6efd;
}

.btn-outline-primary:hover {
    background-color: #0d6efd;
    color: white;
}
</style>
@endsection