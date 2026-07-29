@extends('layouts.main')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h2 class="font-weight-bold mb-1" style="font-size:1.5rem">Riwayat Penggabungan Item Duplikat</h2>
            <p class="text-muted mb-0" style="font-size:.875rem">
                Arsip barcode item yang pernah digabungkan ke barcode target, supaya tidak ada data yang hilang.
            </p>
        </div>
        <a href="{{ route('duplikat-item.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left mr-1"></i>Kembali ke Deteksi
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="sku-thead">
                    <tr>
                        <th>Tanggal</th>
                        <th>Barcode Target</th>
                        <th>Item Target Sekarang</th>
                        <th>Barcode Digabung</th>
                        <th>Nama Item Digabung (saat itu)</th>
                        <th class="text-right">Stok Dipindah</th>
                        <th>Oleh</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($merges as $merge)
                        <tr>
                            <td>{{ $merge->merged_at?->format('d/m/Y H:i') }}</td>
                            <td><span class="badge badge-info">{{ $merge->target_barcode }}</span></td>
                            <td>{{ $merge->target->nama_variasi ?? '(item sudah tidak ada)' }}</td>
                            <td><span class="badge badge-secondary">{{ $merge->merged_barcode }}</span></td>
                            <td>{{ $merge->merged_nama_variasi }}</td>
                            <td class="text-right">{{ rtrim(rtrim(number_format($merge->stock_moved, 2, '.', ''), '0'), '.') ?: '0' }}</td>
                            <td>{{ $merge->mergedByUser->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Belum ada riwayat penggabungan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($merges->hasPages())
            <div class="card-footer bg-white">
                {{ $merges->links('pagination::bootstrap-4') }}
            </div>
        @endif
    </div>
</div>
@endsection
