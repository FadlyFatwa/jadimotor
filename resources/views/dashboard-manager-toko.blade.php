@extends('layouts.main')

@section('header')
<div class="row mb-2">
    <div class="col-sm-6">
        <h1 class="m-0">Dashboard Manager Toko</h1>
    </div>
</div>
@endsection

@section('content')

{{-- ================= RINGKASAN ================= --}}
<div class="row">
    <div class="col-lg-3 col-6">
        <a href="{{ url('needlist/supervisor') }}" class="text-decoration-none">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $needlistWaitingApproval }}</h3>
                    <p>Needlist Menunggu Persetujuan</p>
                </div>
                <div class="icon"><i class="fas fa-user-check"></i></div>
            </div>
        </a>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $needlistApprovedThisMonth }}</h3>
                <p>Disetujui Bulan Ini</p>
            </div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $poBerjalan }}</h3>
                <p>Purchase Order Berjalan</p>
            </div>
            <div class="icon"><i class="fas fa-file-invoice"></i></div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <a href="{{ url('penjualan') }}" class="text-decoration-none">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $penjualanHariIniJumlah }}</h3>
                    <p>Transaksi Hari Ini</p>
                </div>
                <div class="icon"><i class="fas fa-cash-register"></i></div>
            </div>
        </a>
    </div>
</div>

{{-- ================= OMSET ================= --}}
<div class="row">
    <div class="col-md-6">
        <div class="info-box bg-gradient-primary">
            <span class="info-box-icon"><i class="fas fa-coins"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Omset Hari Ini</span>
                <span class="info-box-number">Rp {{ number_format($penjualanHariIniTotal, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="info-box bg-gradient-success">
            <span class="info-box-icon"><i class="fas fa-chart-line"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Omset Bulan Ini ({{ $penjualanBulanIniJumlah }} transaksi)</span>
                <span class="info-box-number">Rp {{ number_format($penjualanBulanIniTotal, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>
</div>

{{-- ================= AKSI CEPAT ================= --}}
<div class="row">
    <div class="col-12">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Aksi Cepat</h3>
            </div>
            <div class="card-body d-flex flex-wrap" style="gap: .5rem;">
                <a href="{{ url('needlist/supervisor') }}" class="btn btn-outline-primary btn-sm"><i class="fas fa-user-check mr-1"></i>Persetujuan Needlist</a>
                <a href="{{ url('penjualan') }}" class="btn btn-outline-primary btn-sm"><i class="fas fa-cash-register mr-1"></i>Penjualan (POS)</a>
                <a href="{{ url('procurement/supplier-selection/laporan') }}" class="btn btn-outline-primary btn-sm"><i class="fas fa-chart-bar mr-1"></i>Laporan Pengadaan</a>
            </div>
        </div>
    </div>
</div>

{{-- ================= TABEL ================= --}}
<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Menunggu Persetujuan</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Diajukan Oleh</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($needlistMenungguPersetujuan as $n)
                        <tr>
                            <td>{{ $n->kode_needlist }}</td>
                            <td>{{ $n->user->name ?? '-' }}</td>
                            <td class="text-right"><a href="{{ route('needlist.review', $n->id) }}" class="btn btn-sm btn-primary">Review</a></td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center text-muted py-3">Tidak ada needlist menunggu persetujuan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Penjualan Terbaru</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Nota</th>
                            <th>Pelanggan</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($penjualanTerbaru as $p)
                        <tr>
                            <td>{{ $p->nomor_nota }}</td>
                            <td>{{ $p->pelanggan->nama ?? 'Umum' }}</td>
                            <td class="text-right">Rp {{ number_format($p->grand_total, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center text-muted py-3">Belum ada penjualan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
