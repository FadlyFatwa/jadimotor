@extends('layouts.main')

@section('content')
<div class="container-fluid">

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between">
            <h5>Detail Purchase Order</h5>
            <a href="{{ route('purchase-order.print', $po->id) }}"
               class="btn btn-sm btn-secondary">
                🖨 Cetak PO
            </a>
        </div>

        <div class="card-body">
            <table class="table table-sm">
                <tr>
                    <th>No PO</th>
                    <td>{{ $po->kode_po }}</td>
                </tr>
                <tr>
                    <th>Supplier</th>
                    <td>{{ $po->supplier->nama_supplier }}</td>
                </tr>
                <tr>
                    <th>Needlist</th>
                    <td>{{ $po->needlist->kode_needlist }}</td>
                </tr>
                <tr>
                    <th>Tanggal</th>
                    <td>{{ $po->created_at->format('d M Y') }}</td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        <span class="badge badge-primary">
                            {{ ucfirst($po->status) }}
                        </span>
                    </td>
                </tr>
            </table>

            <hr>

            <h6>Detail Barang</h6>

            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Nama Barang</th>
                        <th>Variasi</th>
                        <th>Qty</th>
                        <th>Harga</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                @php $total = 0; @endphp
                @foreach($po->items as $item)
                    @php
                        $subtotal = $item->qty_order * $item->harga_beli;
                        $total += $subtotal;
                    @endphp
                    <tr>
                        <td>{{ $item->variasi->m_barang->nama_barang }}</td>
                        <td>{{ $item->variasi->nama_variasi }}</td>
                        <td>{{ $item->qty_order }}</td>
                        <td>Rp {{ number_format($item->harga_beli, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" class="text-right">Total</th>
                        <th>Rp {{ number_format($total, 0, ',', '.') }}</th>
                    </tr>
                </tfoot>
            </table>

        </div>
    </div>

</div>
@endsection
