<table class="table table-bordered table-sm" id="cart-table">
    <thead class="thead-light">
        <tr>
            <th width="5%">No</th>
            <th width="15%">Barcode</th>
            <th width="25%">Nama Barang</th>
            <th width="15%">Harga</th>
            <th width="10%">Qty</th>
            <th width="15%">Subtotal</th>
            <th width="15%">Aksi</th>
        </tr>
    </thead>
    <tbody id="cart-body">
        @forelse ($cartItems as $index => $item)
        <tr data-id="{{ $item->id }}">
            <td>{{ $index + 1 }}</td>
            <td>{{ $item->barcode }}</td>
            <td>{{ $item->nama_barang_jual }}</td>
            <td class="text-right">
                Rp {{ number_format($item->harga, 0, ',', '.') }}
                @if($item->diskon > 0)
                    <br><small class="text-danger">-Diskon Rp {{ number_format($item->diskon, 0, ',', '.') }}</small>
                @endif
            </td>
            <td class="text-center">{{ $item->qty }}</td>
            <td class="text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
            <td class="text-center">
                <button class="btn btn-sm btn-warning btn-edit-cart" 
                        data-id="{{ $item->id }}"
                        data-nama="{{ $item->nama_barang_jual }}"
                        data-harga="{{ $item->harga }}"
                        data-diskon="{{ $item->diskon }}"
                        data-qty="{{ $item->qty }}"
                        data-stock="{{ $item->barang->stock ?? 0 }}">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button class="btn btn-sm btn-danger btn-hapus-cart" data-id="{{ $item->id }}">
                    <i class="fas fa-trash"></i> Hapus
                </button>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7" class="text-center">Belum ada barang ditambahkan</td>
        </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="5" class="text-right"><strong>Total</strong></td>
            <td class="text-right"><strong>Rp {{ number_format($cartItems->sum('subtotal'), 0, ',', '.') }}</strong></td>
            <td></td>
        </tr>
    </tfoot>
</table>