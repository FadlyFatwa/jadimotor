@extends('layouts.main')

@section('content')
<div class="container-fluid">

    {{-- HEADER CARD --}}
    <div class="card mb-3 shadow-sm">
        <div class="card-body d-flex justify-content-between align-items-center">

            <div>
                <h4 class="mb-1">
                    <i class="fas fa-truck-loading me-2 text-primary"></i>
                    Penerimaan Barang
                </h4>
                <small class="text-muted">
                    Daftar Purchase Order yang siap diterima
                </small>
            </div>

            {{-- (Opsional) nanti bisa dipakai untuk filter --}}
            <div>
                <span class="badge bg-info">
                    Total PO: {{ $purchaseOrders->count() }}
                </span>
            </div>

        </div>
    </div>


    {{-- CARD TABEL --}}
    <div class="card">
        <div class="card-body p-0">
            <table class="table table-bordered table-striped table-hover dt-responsive nowrap align-middle mb-0"
                   style="width:100%;">

                <thead class="text-center">
                    <tr>
                        <th style="width:5%">No</th>
                        <th>Kode PO</th>
                        <th>Supplier</th>
                        <th>Tanggal PO</th>
                        <th>Status</th>
                        <th>Riwayat</th>
                        <th style="width:20%">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($purchaseOrders as $i => $po)
                        @php
                            $statusClass = match($po->status) {
                                'open' => 'bg-secondary',
                                'partial_received' => 'bg-warning',
                                'completed' => 'bg-success',
                                'cancelled' => 'bg-danger',
                                default => 'bg-secondary'
                            };
                        @endphp
                        <tr>
                            <td class="text-center">{{ $i + 1 }}</td>
                            <td>{{ $po->kode_po }}</td>
                            <td>{{ $po->supplier->nama_supplier ?? '-' }}</td>
                            <td>{{ \Carbon\Carbon::parse($po->tanggal_po)->format('d-m-Y') }}</td>
                            <td class="text-center">
                                <span class="badge {{ $statusClass }}">
                                    {{ strtoupper(str_replace('_', ' ', $po->status)) }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if($po->receipts_count > 0)
                                    <span class="badge bg-light text-dark border">{{ $po->receipts_count }}x diterima</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($po->status === 'completed')
                                    <button class="btn btn-success btn-sm" disabled>
                                        <i class="fas fa-check me-1"></i> Selesai
                                    </button>
                                @elseif($po->status === 'partial_received')
                                    <a href="{{ route('receipts.create', $po->id) }}"
                                    class="btn btn-warning btn-sm">
                                        <i class="fas fa-truck me-1"></i> Lanjut Terima
                                    </a>
                                @else
                                    <a href="{{ route('receipts.create', $po->id) }}"
                                    class="btn btn-primary btn-sm">
                                        <i class="fas fa-box-open me-1"></i> Terima
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">
                                Belum ada Purchase Order.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>
    </div>

</div>

@endsection
