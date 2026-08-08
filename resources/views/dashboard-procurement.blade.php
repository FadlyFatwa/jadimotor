@extends('layouts.main')

@section('header')
<div class="row mb-2">
    <div class="col-sm-6">
        <h1 class="m-0">Dashboard Pengadaan</h1>
    </div>
</div>
@endsection

@section('content')

@php
$statusLabel = [
    'draft'                  => ['Draf', 'secondary'],
    'submitted'               => ['Menunggu Persetujuan', 'warning'],
    'approved'                => ['Disetujui', 'success'],
    'rejected'                => ['Ditolak', 'danger'],
    'inquiry_created'         => ['Konfirmasi Harga Dibuat', 'primary'],
    'selection_in_progress'   => ['Pemilihan Supplier', 'info'],
    'po_issued'               => ['Surat Pesanan Diterbitkan', 'dark'],
    'completed'               => ['Selesai', 'success'],
    'open'                    => ['Terbuka', 'warning'],
    'partial_received'        => ['Sebagian Diterima', 'info'],
    'cancelled'               => ['Dibatalkan', 'danger'],
];
@endphp

{{-- ================= RINGKASAN ================= --}}
<div class="row">
    <div class="col-lg-2 col-6">
        <a href="{{ url('needlist') }}" class="text-decoration-none">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ $needlistDraft }}</h3>
                    <p>Needlist Draf</p>
                </div>
                <div class="icon"><i class="fas fa-clipboard"></i></div>
            </div>
        </a>
    </div>

    <div class="col-lg-2 col-6">
        <a href="{{ url('needlist') }}" class="text-decoration-none">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $needlistSubmitted }}</h3>
                    <p>Menunggu Persetujuan</p>
                </div>
                <div class="icon"><i class="fas fa-hourglass-half"></i></div>
            </div>
        </a>
    </div>

    <div class="col-lg-2 col-6">
        <a href="{{ url('needlist') }}" class="text-decoration-none">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $needlistApproved }}</h3>
                    <p>Needlist Disetujui</p>
                </div>
                <div class="icon"><i class="fas fa-check-circle"></i></div>
            </div>
        </a>
    </div>

    <div class="col-lg-2 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $inquiryWaiting }}</h3>
                <p>Konfirmasi Harga Menunggu</p>
            </div>
            <div class="icon"><i class="fas fa-envelope-open-text"></i></div>
        </div>
    </div>

    <div class="col-lg-2 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ $poOpen }}</h3>
                <p>PO Terbuka</p>
            </div>
            <div class="icon"><i class="fas fa-file-invoice"></i></div>
        </div>
    </div>

    <div class="col-lg-2 col-6">
        <div class="small-box bg-dark">
            <div class="inner">
                <h3>{{ $poPartial }}</h3>
                <p>PO Sebagian Diterima</p>
            </div>
            <div class="icon"><i class="fas fa-dolly"></i></div>
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
                <a href="{{ url('needlist') }}" class="btn btn-outline-primary btn-sm"><i class="fas fa-clipboard-list mr-1"></i>Daftar Kebutuhan</a>
                <a href="{{ url('procurement/pemilihan-supplier') }}" class="btn btn-outline-primary btn-sm"><i class="fas fa-handshake mr-1"></i>Pemilihan Supplier</a>
                <a href="{{ url('procurement/saw-kriteria') }}" class="btn btn-outline-primary btn-sm"><i class="fas fa-sliders-h mr-1"></i>Kriteria & Bobot</a>
                <a href="{{ url('procurement/saw-historis') }}" class="btn btn-outline-primary btn-sm"><i class="fas fa-history mr-1"></i>Kinerja Supplier</a>
                <a href="{{ url('procurement/supplier-selection/laporan') }}" class="btn btn-outline-primary btn-sm"><i class="fas fa-chart-bar mr-1"></i>Laporan</a>
            </div>
        </div>
    </div>
</div>

{{-- ================= TABEL ================= --}}
<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Needlist Terbaru</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Diajukan Oleh</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($needlistTerbaru as $n)
                        <tr>
                            <td>{{ $n->kode_needlist }}</td>
                            <td>{{ $n->user->name ?? '-' }}</td>
                            <td><span class="badge badge-{{ $statusLabel[$n->status][1] ?? 'secondary' }}">{{ $statusLabel[$n->status][0] ?? $n->status }}</span></td>
                            <td class="text-right"><a href="{{ url('needlist/'.$n->id.'/show') }}" class="btn btn-sm btn-outline-info"><i class="fas fa-eye"></i></a></td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">Belum ada needlist.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Purchase Order Terbaru</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Kode PO</th>
                            <th>Supplier</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($poTerbaru as $po)
                        <tr>
                            <td>{{ $po->kode_po }}</td>
                            <td>{{ $po->supplier->nama_supplier ?? '-' }}</td>
                            <td><span class="badge badge-{{ $statusLabel[$po->status][1] ?? 'secondary' }}">{{ $statusLabel[$po->status][0] ?? $po->status }}</span></td>
                            <td class="text-right"><a href="{{ url('purchase-order/'.$po->id) }}" class="btn btn-sm btn-outline-info"><i class="fas fa-eye"></i></a></td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">Belum ada purchase order.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
