@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    {{-- Tiêu đề --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-gray-800 border-start border-5 border-warning ps-3">Quản Lý Đặt Phòng</h2>
    </div>

    {{-- Thông báo --}}
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show shadow-sm">
        <i class="fa-solid fa-check-circle me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fa-solid fa-list-check me-2"></i> Danh sách đơn đặt
                phòng</h6>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-uppercase small fw-bold text-secondary">
                        <tr>
                            <th class="ps-4 py-3">ID</th>
                            <th>Khách hàng</th>
                            <th style="width: 30%">Phòng & Lịch trình</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th class="text-end pe-4">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bookings as $booking)
                        <tr>
                            <td class="ps-4 fw-bold text-muted">#{{ $booking->id }}</td>

                            {{-- Khách hàng --}}
                            <td>
                                <div class="fw-bold text-dark">{{ $booking->name }}</div>
                                <div class="small text-muted"><i
                                        class="fa-solid fa-phone me-1"></i>{{ $booking->phone }}</div>
                            </td>

                            {{-- Phòng --}}
                            <td>
                                <div class="badge bg-info text-dark mb-1 text-wrap text-start"
                                    style="font-weight: 500;">
                                    {{ Str::limit(str_replace('Phòng: ', '', explode('|', $booking->note)[0]), 40) }}
                                </div>
                                <div class="small text-muted mt-1">
                                    {{-- Sử dụng check_in thay vì checkin để khớp DB --}}
                                    <i class="fa-regular fa-calendar-check text-success me-1"></i>
                                    {{ \Carbon\Carbon::parse($booking->check_in)->format('d/m/Y') }}
                                    <i class="fa-solid fa-arrow-right mx-1 text-secondary" style="font-size: 10px;"></i>
                                    {{ \Carbon\Carbon::parse($booking->check_out)->format('d/m/Y') }}
                                    <span class="fw-bold ms-1 text-dark">({{ $booking->total_nights }} đêm)</span>
                                </div>
                            </td>

                            {{-- Tiền --}}
                            <td class="fw-bold text-danger">
                                {{ number_format($booking->total_price, 0, ',', '.') }} ₫
                            </td>

                            {{-- Trạng thái --}}
                            <td>
                                @if($booking->status == 'pending')
                                <span class="badge bg-warning text-dark border border-warning">Chờ duyệt</span>
                                @elseif($booking->status == 'confirmed')
                                <span class="badge bg-primary shadow-sm">Đã duyệt</span>
                                @elseif($booking->status == 'checked_in')
                                <span class="badge bg-info text-dark shadow-sm">Đang ở</span>
                                @elseif($booking->status == 'checked_out')
                                <span class="badge bg-success shadow-sm">Hoàn tất</span>
                                @else
                                <span class="badge bg-secondary">Đã hủy</span>
                                @endif
                            </td>

                            {{-- Hành động --}}
                            <td class="text-end pe-4">
                                <form action="{{ route('admin.bookings.update', $booking->id) }}" method="POST"
                                    class="d-inline-block">
                                    @csrf

                                    @if($booking->status == 'pending')
                                    <button name="status" value="confirmed" class="btn btn-sm btn-outline-success me-1"
                                        title="Duyệt">
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                    <button name="status" value="cancelled" class="btn btn-sm btn-outline-danger"
                                        title="Hủy" onclick="return confirm('Hủy đơn này?')">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                    @elseif($booking->status == 'confirmed')
                                    <button name="status" value="checked_in"
                                        class="btn btn-sm btn-primary shadow-sm px-3">Check-in</button>
                                    <button name="status" value="cancelled"
                                        class="btn btn-sm btn-light text-danger border ms-1">Hủy</button>
                                    @elseif($booking->status == 'checked_in')
                                    <button name="status" value="checked_out"
                                        class="btn btn-sm btn-warning text-dark fw-bold shadow-sm px-3">Check-out</button>
                                    @else
                                    <button type="button" class="btn btn-sm btn-light border text-muted"
                                        disabled>Xong</button>
                                    @endif
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fa-regular fa-folder-open fa-3x mb-3"></i>
                                <p>Chưa có đơn đặt phòng nào.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Phân trang --}}
        @if($bookings->hasPages())
        <div class="card-footer bg-white py-3 d-flex justify-content-end">
            {{ $bookings->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>
@endsection