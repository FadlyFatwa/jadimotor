@extends('layouts.main')

@section('content')
<div class="container-fluid">

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Bukti Penerimaan Barang</h5>
            <div>
                <a href="{{ route('receipts.create', $receipt->purchase_order_id) }}" class="btn btn-sm btn-secondary">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali ke PO
                </a>
                <button onclick="window.print()" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-print mr-1"></i> Cetak
                </button>
            </div>
        </div>

        <div class="card-body">
            <table class="table table-sm">
                <tr>
                    <th style="width:180px;">Kode Penerimaan</th>
                    <td>{{ $receipt->kode_receipt }}</td>
                </tr>
                <tr>
                    <th>No PO</th>
                    <td>{{ $receipt->purchaseOrder->kode_po ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Supplier</th>
                    <td>{{ $receipt->purchaseOrder->supplier->nama_supplier ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Tanggal Terima</th>
                    <td>{{ \Carbon\Carbon::parse($receipt->tanggal_terima)->format('d M Y') }}</td>
                </tr>
                <tr>
                    <th>Diterima Oleh</th>
                    <td>{{ $receipt->user->name ?? '-' }}</td>
                </tr>
            </table>

            <hr>

            <h6>Barang Diterima</h6>

            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Nama Barang</th>
                        <th>Variasi</th>
                        <th class="text-center">Qty Order</th>
                        <th class="text-center">Qty Diterima (transaksi ini)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($receipt->items as $item)
                        <tr>
                            <td>{{ $item->variasi->m_barang->nama_barang ?? '-' }}</td>
                            <td>{{ $item->variasi->nama_variasi ?? '-' }}</td>
                            <td class="text-center">{{ $item->qty_order }}</td>
                            <td class="text-center">{{ $item->qty_received }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
