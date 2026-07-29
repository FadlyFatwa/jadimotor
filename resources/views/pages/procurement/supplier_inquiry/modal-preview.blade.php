<table class="table table-bordered">
    <thead>
        <tr>
            <th>Barcode</th>
            <th>Variasi</th>
            <th>Qty</th>
            <th>Harga</th>
            <th>Estimasi</th>
        </tr>
    </thead>
    <tbody>
    @foreach ($inquiry->items as $item)
        <tr>
            <td>{{ $item->variasi->barcode }}</td>
            <td>{{ $item->variasi->nama_variasi }}</td>
            <td>{{ $item->qty }}</td>
            <td>Rp {{ number_format($item->harga_penawaran,0,',','.') }}</td>
            <td>{{ $item->estimasi_pengiriman }} hari</td>
        </tr>
    @endforeach
    </tbody>
</table>
