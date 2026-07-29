<table class="table table-bordered">
    <thead>
        <tr>
            <th>Nama Barang</th>
            <th>Variasi</th>
            <th>Qty</th>
            <th>Harga Konfirmasi</th>
            <th>Estimasi Pengiriman</th>
        </tr>
    </thead>
    <tbody>
    @foreach ($inquiry->items as $item)
        @php
            $hargaDefault = $hargaHistoris[$item->id_variasi] ?? 0;
        @endphp
        <tr>
            <td>{{ $item->variasi->m_barang->nama_barang ?? '-' }}</td>
            <td>{{ $item->variasi->nama_variasi ?? '-' }}</td>
            <td>{{ $item->qty }}</td>
            <td>
                <input type="number"
                       class="form-control"
                       name="items[{{ $item->id }}][harga_penawaran]"
                       value="{{ $hargaDefault }}"
                       min="0"
                       required>
                @if($hargaDefault > 0)
                    <small class="text-muted">
                        <i class="fas fa-history fa-xs mr-1"></i>
                        Historis: Rp {{ number_format($hargaDefault, 0, ',', '.') }}
                    </small>
                @endif
            </td>
            <td>
                <input type="datetime-local"
                       class="form-control"
                       name="items[{{ $item->id }}][estimasi_pengiriman]"
                       value="{{ $defaultEstimasi }}"
                       required>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
