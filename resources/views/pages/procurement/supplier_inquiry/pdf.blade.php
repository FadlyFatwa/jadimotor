<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Supplier Inquiry PDF</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h3 { text-align: center; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
        .no-border th, .no-border td { border: none; }
        .signature { margin-top: 40px; width: 100%; display: flex; justify-content: space-between; }
        .signature div { text-align: center; width: 40%; }
    </style>
</head>
<body>
    <h3>FORM PERMINTAAN PENAWARAN HARGA</h3>

    <table class="no-border">
        <tr><th>Supplier</th><td>{{ $inquiry->supplier->nama_supplier }}</td></tr>
        <tr><th>Needlist ID</th><td>{{ $inquiry->needlist->id }}</td></tr>
        <tr><th>Tanggal</th><td>{{ now()->format('d/m/Y') }}</td></tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Barang</th>
                <th>Qty</th>
                <th>Satuan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($inquiry->items as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item->variasi->m_barang->nama_barang }}</td>
                <td>{{ $item->qty }}</td>
                <td>{{ $item->variasi->satuan ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="signature">
        <div>
            <strong>Purchasing</strong><br><br><br>______________________
        </div>
        <div>
            <strong>{{ $inquiry->supplier->nama_supplier }}</strong><br><br><br>______________________
        </div>
    </div>
</body>
</html>
