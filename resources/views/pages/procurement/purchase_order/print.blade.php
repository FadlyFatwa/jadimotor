<!DOCTYPE html>
<html>
<head>
    <title>Purchase Order</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 6px; }
        th { background: #f2f2f2; }
    </style>
</head>
<body>

<h3 style="text-align:center;">PURCHASE ORDER</h3>

<table>
    <tr>
        <td>No PO</td>
        <td>{{ $po->kode_po }}</td>
    </tr>
    <tr>
        <td>Supplier</td>
        <td>{{ $po->supplier->nama_supplier }}</td>
    </tr>
    <tr>
        <td>Needlist</td>
        <td>{{ $po->needlist->kode_needlist }}</td>
    </tr>
    <tr>
        <td>Tanggal</td>
        <td>{{ $po->created_at->format('d M Y') }}</td>
    </tr>
</table>

<br>

<table>
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
            <th colspan="4">Total</th>
            <th>Rp {{ number_format($total, 0, ',', '.') }}</th>
        </tr>
    </tfoot>
</table>

<br><br>

<table style="border:none;">
    <tr>
        <td style="border:none; text-align:center;">
            Disetujui Oleh<br><br><br>
            (__________________)
        </td>
        <td style="border:none; text-align:center;">
            Supplier<br><br><br>
            (__________________)
        </td>
    </tr>
</table>

</body>
</html>
