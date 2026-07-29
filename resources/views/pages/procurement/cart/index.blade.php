@extends('layouts.main')
@section('title', 'Keranjang Kebutuhan')

@section('content')
<div class="container-fluid">

    {{-- HEADER --}}
    <div class="card mb-3 shadow-sm">
        <div class="card-body">
            <h4 class="mb-1">
                <i class="fas fa-shopping-cart me-2 text-primary"></i>
                Keranjang Kebutuhan 
            </h4>
            <small class="text-muted">
                Tambahkan barang yang dibutuhkan sebelum membuat Needlist
            </small>
        </div>
    </div>


    {{-- FORM TAMBAH BARANG --}}
    <div class="card mb-3">
        <div class="card-header">
            <strong>Tambah Barang ke Keranjang</strong>
        </div>
        <div class="card-body">

            <form id="formAddCart">
                @csrf
                <input type="hidden" name="id_variasi" id="id_variasi">

                <div class="row">

                    <div class="col-md-8">
                        <div class="mb-3">
                            <label class="form-label">Barang</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="nama_barang" readonly placeholder="Pilih barang dari daftar...">
                                <button type="button" class="btn btn-outline-info" data-toggle="modal" data-target="#modalBarang">
                                    <i class="fas fa-search me-1"></i> Pilih Barang
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="mb-3">
                            <label class="form-label">Qty</label>
                            <input type="number" name="qty" class="form-control" min="1" value="1">
                        </div>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-primary w-100">
                            <i class="fas fa-plus me-1"></i> Tambah
                        </button>
                    </div>

                </div>
            </form>

        </div>
    </div>

    {{-- DAFTAR CART --}}
    <div class="card" id="cartContainer">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Daftar Barang di Keranjang</strong>

            @if ($cartItems->count())
                <span class="badge bg-info">
                    Total Item: {{ $cartItems->count() }}
                </span>
            @endif
        </div>

        <div class="card-body p-3" >

            <table class="table table-bordered table-striped table-hover align-middle mb-0">
                <thead class="text-center">
                    <tr>
                        <th>Barcode</th>
                        <th>Master Barang</th>
                        <th>Variasi</th>
                        <th style="width:10%">Qty</th>
                        <th style="width:10%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cartItems as $item)
                    <tr>
                        <td>{{ $item->variasi->barcode }}</td>
                        <td>{{ $item->variasi->m_barang->nama_barang }}</td>
                        <td>{{ $item->variasi->nama_variasi }}</td>
                        <td class="text-center">
                            <span class="badge bg-secondary">
                                {{ $item->qty }}
                            </span>
                        </td>
                        <td class="text-center">
                            <form method="POST" action="{{ route('cart.destroy', $item->id) }}" class="form-delete-cart d-inline">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">
                            Keranjang masih kosong.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($cartItems->count())
            <div class="card-footer text-end">
                <form method="POST" action="{{ route('needlist.storeFromCart') }}">
                    @csrf
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-file-alt me-1"></i> Buat Needlist
                    </button>
                </form>
            </div>
        @endif
    </div>

    {{-- MODAL PILIH BARANG --}}
    <div class="modal fade" id="modalBarang">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-box-open me-2"></i>
                        Pilih Barang
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>

                <div class="modal-body">
                    <small class="text-muted d-block mb-2">
                        <i class="fas fa-info-circle me-1"></i>
                        Menampilkan barang dengan stok menipis (&le; 5) atau habis. Gunakan pencarian untuk menampilkan semua barang.
                    </small>
                    <table class="table table-bordered table-striped table-hover dt-responsive nowrap align-middle"
                           style="width:100%;" id="tableBarang">

                        <thead class="text-center">
                            <tr>
                                <th>Barcode</th>
                                <th>Master / Variasi</th>
                                <th>Kendaraan</th>
                                <th>Supplier</th>
                                <th>Harga Beli</th>
                                <th>Stok</th>
                                <th style="width:10%">Aksi</th>
                            </tr>
                        </thead>

                    </table>
                </div>

            </div>
        </div>
    </div>

</div>
@endsection


@section('scripts')
<script>
function formatRupiah(angka) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
}

$(document).ready(function () {
    var tierColors = { OEM: 'primary', Original: 'success', Aftermarket: 'warning', KW: 'secondary' };

    var tableBarang = $('#tableBarang').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        order: [[5, 'asc']],
        ajax: {
            url    : '{{ route("variasi.datatable") }}',
            type   : 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data   : function (d) { d.low_stock_only = true; },
        },
        columns: [
            { data: 'barcode' },
            {
                data: null, orderable: false,
                render: function(data, type, row) {
                    var badge = row.tier
                        ? '<span class="badge badge-' + (tierColors[row.tier] || 'secondary') + ' ml-1">' + row.tier + '</span>'
                        : '';
                    return '<strong>' + row.nama_barang + '</strong><br><small class="text-muted">' + row.nama_variasi + '</small> ' + badge;
                }
            },
            {
                data: 'vehicle',
                render: function(data) { return '<small class="text-muted">' + (data || '-') + '</small>'; }
            },
            {
                data: 'suppliers', orderable: false,
                render: function(data) {
                    if (!data || data.length === 0) return '<span class="text-muted">-</span>';
                    return data.map(function(s) {
                        return '<span class="badge badge-light border text-dark mr-1">' + s.name + '</span>';
                    }).join('');
                }
            },
            {
                data: null, orderable: false,
                render: function(data, type, row) {
                    if (row.harga_beli_min === row.harga_beli_max) return '<small>' + formatRupiah(row.harga_beli_min) + '</small>';
                    return '<small>' + formatRupiah(row.harga_beli_min) + '<br>– ' + formatRupiah(row.harga_beli_max) + '</small>';
                }
            },
            {
                data: 'stock',
                render: function(data) {
                    var v = parseInt(data);
                    if (v <= 0) return '<span class="badge badge-danger">Habis</span>';
                    if (v <= 5) return '<span class="badge badge-warning">' + v + '</span>';
                    return '<span class="badge badge-light border">' + v + '</span>';
                }
            },
            {
                data: null, orderable: false, searchable: false,
                render: function(data, type, row) {
                    var firstSupplier = row.suppliers && row.suppliers.length > 0 ? row.suppliers[0].name : '-';
                    return '<button class="btn btn-sm btn-success btn-pilih-barang"'
                        + ' data-id="' + row.id_variasi + '"'
                        + ' data-barcode="' + row.barcode + '"'
                        + ' data-nama_barang="' + row.nama_barang + '"'
                        + ' data-nama="' + row.nama_variasi + '"'
                        + ' data-supplier="' + firstSupplier + '">Pilih</button>';
                }
            },
        ]
    });

    // Tabel di-init saat modal masih hidden (width=0), jadi kolom harus
    // dihitung ulang setiap kali modal benar-benar tampil agar tidak offside.
    $('#modalBarang').on('shown.bs.modal', function () {
        tableBarang.columns.adjust().responsive.recalc();
    });

    $(document).on('click', '.btn-pilih-barang', function () {
        $('#id_variasi').val($(this).data('id'));
        $('#nama_barang').val($(this).data('barcode') + ' - ' + $(this).data('nama_barang') + ' ' + $(this).data('nama') + ' (' + $(this).data('supplier') + ')');
        $('#modalBarang').modal('hide');
    });

    $('#formAddCart').on('submit', function (e) {
        e.preventDefault();
        $.post("{{ route('cart.store') }}", $(this).serialize(), function () {
            $('#cartContainer').load(location.href + " #cartContainer>*", "");
            $('#formAddCart')[0].reset();
        });
    });

    $(document).on('submit', '.form-delete-cart', function (e) {
        e.preventDefault();
        $.post($(this).attr('action'), $(this).serialize(), function () {
            $('#cartContainer').load(location.href + " #cartContainer>*", "");
        });
    });
});
</script>
@endsection
