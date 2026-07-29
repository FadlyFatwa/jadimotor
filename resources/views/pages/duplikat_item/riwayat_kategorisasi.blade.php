@extends('layouts.main')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h2 class="font-weight-bold mb-1" style="font-size:1.5rem">Riwayat Kategorisasi Item</h2>
            <p class="text-muted mb-0" style="font-size:.875rem">
                Backup nama_variasi sebelum dibersihkan otomatis saat "Terapkan Kategorisasi" — kalau parsing
                keliru membuang sesuatu yang penting, nama aslinya masih bisa dilihat di sini.
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
                        <th>Barcode</th>
                        <th>Nama Variasi Lama</th>
                        <th>Nama Variasi Baru</th>
                        <th>Master Barang Baru</th>
                        <th>No. Part Baru</th>
                        <th>Oleh</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ $log->dikategorikan_at?->format('d/m/Y H:i') }}</td>
                            <td><span class="badge badge-info">{{ $log->barcode }}</span></td>
                            <td class="text-muted">{{ $log->nama_variasi_lama }}</td>
                            <td>{{ $log->variasi->nama_variasi ?? $log->nama_variasi_baru }}</td>
                            <td>{{ $log->mbarang->nama_barang ?? '-' }}</td>
                            <td>{{ $log->part_number_baru ?? '-' }}</td>
                            <td>{{ $log->dikategorikanOlehUser->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Belum ada riwayat kategorisasi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="card-footer bg-white">
                {{ $logs->links('pagination::bootstrap-4') }}
            </div>
        @endif
    </div>
</div>
@endsection
