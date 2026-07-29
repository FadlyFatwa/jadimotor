@extends('layouts.main')

@section('header')
<div class="row mb-2">
    <div class="col-sm-6">
        <h1 class="m-0">Dashboard ERP</h1>
    </div>
</div>
@endsection

@section('content')

@php
/* ===============================
   DUMMY DATA DASHBOARD ERP
   =============================== */

// Ringkasan Utama
$totalBarang      = 128;
$totalNeedlist    = 24;
$totalPO          = 18;
$totalPenerimaan  = 15;
$totalPenjualan   = 42;

// Status Proses
$needlistPending   = 6;
$poProses          = 4;
$penerimaanSelesai = 11;

// Grafik (Jan–Des)
$grafikPenerimaan = [5, 8, 6, 10, 12, 9, 11, 14, 10, 13, 15, 16];
$grafikPenjualan  = [4, 6, 5, 9, 11, 8, 10, 12, 9, 11, 13, 14];
@endphp

{{-- ================= RINGKASAN UTAMA ================= --}}
<div class="row">
    <div class="col-lg-2 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $totalBarang }}</h3>
                <p>Barang</p>
            </div>
            <div class="icon"><i class="fas fa-box"></i></div>
        </div>
    </div>

    <div class="col-lg-2 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $totalNeedlist }}</h3>
                <p>Needlist</p>
            </div>
            <div class="icon"><i class="fas fa-list"></i></div>
        </div>
    </div>

    <div class="col-lg-2 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ $totalPO }}</h3>
                <p>Purchase Order</p>
            </div>
            <div class="icon"><i class="fas fa-file-invoice"></i></div>
        </div>
    </div>

    <div class="col-lg-2 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $totalPenerimaan }}</h3>
                <p>Penerimaan</p>
            </div>
            <div class="icon"><i class="fas fa-dolly"></i></div>
        </div>
    </div>

    <div class="col-lg-2 col-6">
        <div class="small-box bg-secondary">
            <div class="inner">
                <h3>{{ $totalPenjualan }}</h3>
                <p>Penjualan</p>
            </div>
            <div class="icon"><i class="fas fa-shopping-cart"></i></div>
        </div>
    </div>
</div>

{{-- ================= STATUS PROSES ================= --}}
<div class="row">
    <div class="col-md-4">
        <div class="info-box bg-warning">
            <span class="info-box-icon"><i class="fas fa-clock"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Needlist Pending</span>
                <span class="info-box-number">{{ $needlistPending }}</span>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="info-box bg-info">
            <span class="info-box-icon"><i class="fas fa-sync"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">PO Dalam Proses</span>
                <span class="info-box-number">{{ $poProses }}</span>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="info-box bg-success">
            <span class="info-box-icon"><i class="fas fa-check"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Penerimaan Selesai</span>
                <span class="info-box-number">{{ $penerimaanSelesai }}</span>
            </div>
        </div>
    </div>
</div>

{{-- ================= GRAFIK ================= --}}
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Grafik Penerimaan & Penjualan</h3>
    </div>
    <div class="card-body">
        <canvas id="grafikERP" height="100"></canvas>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('grafikERP').getContext('2d');

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'],
            datasets: [
                {
                    label: 'Penerimaan',
                    data: @json($grafikPenerimaan)
                },
                {
                    label: 'Penjualan',
                    data: @json($grafikPenjualan)
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
</script>
@endsection
