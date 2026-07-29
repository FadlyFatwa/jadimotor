@extends('layouts.main')
@section('title', 'Transaksi Penjualan')
@section('content')

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12">
            <h1>Transaksi Penjualan</h1>
        </div>
    </div>

    <form action="{{ route('penjualan.store') }}" method="POST" id="form-transaksi">
        @csrf

        <!-- Info Transaksi -->
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <strong>Informasi Transaksi</strong>
            </div>
            <div class="card-body row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="invoice">No. Invoice</label>
                        <input type="text" class="form-control" id="invoice" name="invoice" value="{{ 'INV-' . date('Ymd') . '-' . str_pad($lastInvoiceNumber + 1, 4, '0', STR_PAD_LEFT) }}" readonly>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="tanggal">Tanggal</label>
                        <input type="datetime-local" class="form-control" id="tanggal" name="tanggal" value="{{ date('Y-m-d\TH:i') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="kasir">Kasir</label>
                        <input type="text" class="form-control" id="kasir" value="{{ Auth::user()->name }}" readonly>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="pelanggan_id">Pelanggan</label>
                        <select name="pelanggan_id" id="pelanggan-select" class="form-control" required>
                            <option value="">-- Pilih Pelanggan --</option>
                            @foreach($pelanggans as $pelanggan)
                                <option value="{{ $pelanggan->id }}">{{ $pelanggan->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Opsi Input Barang -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center bg-info text-white">
                <strong>Tambah Barang</strong>
                <button type="button" class="btn btn-light btn-sm" data-toggle="modal" data-target="#modal-barang">
                    <i class="fas fa-search mr-1"></i> Pilih dari Daftar
                </button>
            </div>
            <div class="card-body form-inline">
                <label class="mr-2">Scan Barcode:</label>
                <input type="text" id="barcode-input" class="form-control mr-2" placeholder="Scan atau ketik barcode" autofocus>
                <label class="mr-2">Qty:</label>
                <input type="number" id="qty-input" class="form-control mr-2" value="1" min="1" style="width: 80px;">
                <button type="button" id="btn-tambah-cart" class="btn btn-success">
                    <i class="fas fa-plus mr-1"></i> Tambah
                </button>
            </div>
        </div>

        <!-- Tabel Cart -->
        <div class="card mb-3">
            <div class="card-header bg-info text-white">
                <strong>Daftar Barang</strong>
            </div>
            <div class="card-body p-0">
                @include('pages.penjualan.cart')
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-12 text-right">
                <button type="button" id="btn-cancel" class="btn btn-danger mr-2">
                    <i class="fas fa-times mr-1"></i> Cancel
                </button>
                <button type="submit" id="btn-proses" class="btn btn-primary" {{ count($cartItems) == 0 ? 'disabled' : '' }}>
                    <i class="fas fa-check mr-1"></i> Proses Pembayaran
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Modal Pilih Barang -->
<div class="modal fade" id="modal-barang" tabindex="-1" role="dialog" aria-labelledby="modalBarangLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalBarangLabel">Pilih Barang</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered table-sm" id="table-barang">
                    <thead>
                        <tr>
                            <th>Barcode</th>
                            <th>Nama</th>
                            <th>Stok</th>
                            <th>Harga Jual</th>
                            <th width="80">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($barangs as $barang)
                        <tr>
                            <td>{{ $barang->barcode }}</td>
                            <td>{{ $barang->nama_variasi }}</td>
                            <td>{{ $barang->stock }}</td>
                            <td>{{ number_format($barang->harga_jual) }}</td>
                            <td>
                                <button type="button" class="btn btn-sm btn-success pilih-barang"
                                    data-id="{{ $barang->id_variasi }}"
                                    data-nama="{{ $barang->nama_variasi }}"
                                    data-harga_jual="{{ $barang->harga_jual }}"
                                    data-stock="{{ $barang->stock }}">
                                    <i class="fas fa-plus"></i> Pilih
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- Modal Edit Cart -->
<!-- Modal Edit Cart -->
<div class="modal fade" id="modal-edit-cart" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Edit Item</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="form-edit-cart">
                    <input type="hidden" id="edit-cart-id" name="cart_id"> <!-- Pastikan ini ada -->
                    <div class="form-group">
                        <label>Nama Barang</label>
                        <input type="text" class="form-control" id="edit-nama-barang" readonly>
                    </div>
                    <div class="form-group">
                        <label>Harga</label>
                        <input type="number" class="form-control" id="edit-harga_jual" name="harga_jual" min="1">
                    </div>
                    <div class="form-group">
                        <label>Diskon</label>
                        <input type="number" class="form-control" id="edit-diskon" name="diskon" min="0">
                    </div>
                    <div class="form-group">
                        <label>Quantity</label>
                        <input type="number" class="form-control" id="edit-qty" name="qty" min="1">
                        <small class="text-muted">Stok tersedia: <span id="edit-stock"></span></small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btn-update-cart">Simpan Perubahan</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Initialize Select2
    $('#pelanggan-select').select2({
        placeholder: '-- Pilih Pelanggan --',
        width: '100%'
    });

    // Payment method toggle
    $('#metode-pembayaran').change(function() {
        if ($(this).val() === 'cash') {
            $('#cash-section').show();
        } else {
            $('#cash-section').hide();
        }
    });

    // Calculate totals
    function calculateTotals() {
        let subtotal = 0;
        
        $('.subtotal').each(function() {
            const value = $(this).text().replace(/[^0-9]/g, '');
            subtotal += parseInt(value) || 0;
        });

        const diskon = parseInt($('#diskon-input').val()) || 0;
        const grandTotal = subtotal - (subtotal * diskon / 100);

        $('#subtotal-total').text('Rp ' + subtotal.toLocaleString());
        $('#grand-total').text('Rp ' + grandTotal.toLocaleString());

        // Calculate change if cash payment
        if ($('#metode-pembayaran').val() === 'cash') {
            const cashBayar = parseInt($('#cash-bayar').val()) || 0;
            const kembalian = cashBayar - grandTotal;
            $('#kembalian').val(kembalian > 0 ? kembalian : 0);
        }

        // Enable/disable process button
        $('#btn-proses').prop('disabled', subtotal === 0);
    }

    // Update quantity
    function updateQuantity(index, newQty) {
        const harga_jual = parseInt($(`input[name="items[${index}][harga_jual]"]`).val()) || 0;
        const subtotal = harga_jual * newQty;
        $(`.subtotal[data-index="${index}"]`).text(subtotal.toLocaleString());
        calculateTotals();
    }

    // Quantity buttons
    $(document).on('click', '.btn-plus', function() {
        const index = $(this).data('index');
        const input = $(`.qty-input[data-index="${index}"]`);
        const currentQty = parseInt(input.val()) || 0;
        const stock = parseInt(input.data('stock')) || 0;
        
        if (currentQty < stock) {
            input.val(currentQty + 1).trigger('change');
        } else {
            alert('Stok tidak mencukupi!');
        }
    });

    $(document).on('click', '.btn-minus', function() {
        const index = $(this).data('index');
        const input = $(`.qty-input[data-index="${index}"]`);
        const currentQty = parseInt(input.val()) || 1;
        
        if (currentQty > 1) {
            input.val(currentQty - 1).trigger('change');
        }
    });

    // Quantity and price change events
    $(document).on('change', '.qty-input, .harga_jual-input', function() {
        const index = $(this).data('index');
        const qty = parseInt($(`.qty-input[data-index="${index}"]`).val()) || 0;
        updateQuantity(index, qty);
    });

    // Diskon change event
    $('#diskon-input').on('change', function() {
        calculateTotals();
    });

    // Cash bayar change event
    $('#cash-bayar').on('change', function() {
        calculateTotals();
    });

    // Initial calculation
    calculateTotals();

    // Add to cart function
    $('#btn-tambah-cart').on('click', function() {
        const barcode = $('#barcode-input').val().trim();
        const qty = parseInt($('#qty-input').val()) || 1;

        if (!barcode) {
            alert('Barcode harus diisi!');
            return;
        }

        if (qty <= 0) {
            alert('Qty harus lebih dari 0!');
            return;
        }

        $.ajax({
            url: "{{ route('penjualan.cart.add') }}",
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                barcode: barcode,
                qty: qty
            },
            success: function(res) {
                alert(res.message);
                $('#barcode-input').val('').focus();
                $('#qty-input').val(1);
                refreshCart();
            },
            error: function(xhr) {
                alert(xhr.responseJSON.message || 'Gagal menambah barang');
            }
        });
    });

    // Edit cart item - Buka modal
$(document).on('click', '.btn-edit-cart', function() {
    const cartId = $(this).data('id');
    const nama = $(this).data('nama');
    const harga_jual = $(this).data('harga_jual');
    const diskon = $(this).data('diskon');
    const qty = $(this).data('qty');
    const stock = $(this).data('stock');
    
    // Isi form modal
    $('#edit-cart-id').val(cartId); // Pastikan ini ada di form
    $('#edit-nama-barang').val(nama);
    $('#edit-harga_jual').val(harga_jual);
    $('#edit-diskon').val(diskon);
    $('#edit-qty').val(qty);
    $('#edit-stock').text(stock);
    
    $('#modal-edit-cart').modal('show');
});

// Update cart item
$('#btn-update-cart').on('click', function() {
    const cartId = $('#edit-cart-id').val(); // Ambil ID dari form
    
    $.ajax({
        url: `/penjualan/cart/${cartId}`, // Gunakan parameter dalam URL
        method: 'PUT', // Method PUT untuk update
        data: {
            _token: '{{ csrf_token() }}',
            harga_jual: $('#edit-harga_jual').val(),
            diskon: $('#edit-diskon').val(),
            qty: $('#edit-qty').val()
        },
        beforeSend: function() {
            $('#btn-update-cart').prop('disabled', true)
                .html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');
        },
        success: function(res) {
            $('#modal-edit-cart').modal('hide');
            refreshCart();
            showAlert('success', res.message);
        },
        error: function(xhr) {
            showAlert('error', xhr.responseJSON.message || 'Gagal memperbarui keranjang');
        },
        complete: function() {
            $('#btn-update-cart').prop('disabled', false)
                .html('Simpan Perubahan');
        }
    });
});
// Contoh untuk delete
$(document).on('click', '.btn-hapus-cart', function() {
    const cartId = $(this).data('id');
    
    Swal.fire({
        title: 'Konfirmasi',
        text: 'Hapus item dari keranjang?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#d33',
    }).then((result) => {
        if (!result.isConfirmed) return;
        $.ajax({
            url: `/penjualan/cart/${cartId}`,
            method: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(res) {
                refreshCart();
                showAlert('success', res.message);
            },
            error: function(xhr) {
                handleError(xhr);
            }
        });
    });
});

// Function untuk refresh cart
function refreshCart() {
    $.ajax({
        url: "{{ route('penjualan.cart.get') }}",
        method: "GET",
        success: function(html) {
            $('#cart-body').html($(html).find('#cart-body').html());
            $('tfoot').html($(html).find('tfoot').html());
        },
        error: function() {
            showAlert('error', 'Gagal memuat ulang keranjang');
        }
    });
}

// Function untuk menampilkan alert
function showAlert(type, message) {
    const alertHtml = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
        ${message}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>`;
    
    $('#alert-container').html(alertHtml);
    setTimeout(() => $('.alert').alert('close'), 3000);
}

    // Select item from modal
    $(document).on('click', '.pilih-barang', function() {
        const id = $(this).data('id');
        const nama = $(this).data('nama');
        const harga_jual = $(this).data('harga_jual');
        const stock = $(this).data('stock');

        const qty = prompt('Masukkan jumlah:', 1);
        if (qty !== null && qty > 0) {
            if (qty > stock) {
                alert('Stok tidak cukup! Tersedia: ' + stock);
                return;
            }

            $.ajax({
                url: "{{ route('penjualan.cart.add') }}",
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    id_variasi: id,
                    nama_barang_jual: nama,
                    harga_jual: harga_jual,
                    qty: qty
                },
                success: function(res) {
                    $('#modal-barang').modal('hide');
                    refreshCart();
                },
                error: function(xhr) {
                    alert(xhr.responseJSON.message || 'Gagal menambah barang');
                }
            });
        }
    });

    // Cancel transaction
    $('#btn-cancel').on('click', function() {
        Swal.fire({
            title: 'Konfirmasi',
            text: 'Batalkan transaksi? Semua item di keranjang akan dihapus.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, batalkan',
            cancelButtonText: 'Tidak',
            confirmButtonColor: '#d33',
        }).then((result) => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: "{{ route('penjualan.cart.clear') }}",
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function() {
                    window.location.reload();
                },
                error: function(xhr) {
                    alert(xhr.responseJSON.message || 'Gagal membatalkan transaksi');
                }
            });
        });
    });

    // Auto focus on barcode input
    $('#barcode-input').focus();

    // Refresh cart function
    function refreshCart() {
        $.ajax({
            url: "{{ route('penjualan.cart.get') }}",
            method: "GET",
            success: function(html) {
                $('#cart-body').html($(html).find('#cart-body').html());
                calculateTotals();
            }
        });
    }
});
</script>
@endsection