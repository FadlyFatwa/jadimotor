@extends('layouts.main')

@section('title', 'Tambah Penerimaan')

@section('content')
<div class="container-fluid px-4">
    <h1>Tambah Penerimaan</h1>

    <!-- Card Utama -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Form Tambah Penerimaan</h3>
        </div>
        <div class="card-body">
            <!-- Form Tambah Penerimaan -->
            <form action="{{ route('penerimaan.store') }}" method="POST">
                @csrf

                <!-- Informasi Umum -->
                <div class="card mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="card-title">Informasi Umum</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Nomor Invoice -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="Invoice" class="form-label">Nomor Invoice</label>
                                    <input type="text" name="Invoice" id="Invoice" class="form-control" required>
                                </div>
                            </div>

                            <!-- Supplier -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="id_supplier" class="form-label">Supplier</label>
                                    <select name="id_supplier" id="id_supplier" class="form-control select2" required>
                                        <option value=""></option>
                                        @foreach ($suppliers as $supplier)
                                            <option value="{{ $supplier->id_unit }}">{{ $supplier->nama_supplier }}</option>
                                        @endforeach
                                    </select>
                                    
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Tanggal Nota -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="Tanggal_Nota" class="form-label">Tanggal Nota</label>
                                    <input type="date" name="Tanggal_Nota" id="Tanggal_Nota" class="form-control" required>
                                </div>
                            </div>

                            <!-- Tanggal Barang Datang -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="Tanggal_Datang" class="form-label">Tanggal Barang Datang</label>
                                    <input type="date" name="Tanggal_Datang" id="Tanggal_Datang" class="form-control" required>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="Metode_Pembayaran" class="form-label">Metode Pembayaran</label>
                                    <select name="Metode_Pembayaran" id="Metode_Pembayaran" class="form-control" required>
                                        <option value="cash">Cash</option>
                                        <option value="kredit">Kredit</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Jatuh Tempo (akan muncul hanya jika kredit dipilih) -->
                            <div class="col-md-4 jatuh-tempo-container" style="display: none;">
                                <div class="form-group">
                                    <label for="Jatuh_Tempo" class="form-label">Jatuh Tempo</label>
                                    <input type="date" name="Jatuh_Tempo" id="Jatuh_Tempo" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Replace your current Detail Barang section with this: -->
                <!-- Detail Barang -->
                <div class="card mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="card-title">Detail Barang</h5>
                    </div>
                    <div class="card-body">
                        <div id="barang-details">
                            <!-- Barang item template -->
                            <div class="barang-item">
                                <div class="row">
                                    <!-- Barang -->
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label class="form-label">Barang</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control barang-nama" placeholder="Klik untuk memilih barang" readonly>
                                                <input type="text" class="barang-id" name="barang_details[0][ID_Barang]">
                                                <button type="button" class="btn btn-primary pilih-barang">
                                                    <i class="fas fa-search"></i> Pilih
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Jumlah -->
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="form-label">Jumlah</label>
                                            <input type="number" name="barang_details[0][Jumlah]" class="form-control barang-jumlah" placeholder="Jumlah" required>
                                        </div>
                                    </div>

                                    <!-- Harga -->
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="form-label">Harga</label>
                                            <input type="number" name="barang_details[0][Harga]" class="form-control barang-harga" placeholder="Harga" required>
                                        </div>
                                    </div>

                                    <!-- Subtotal per item -->
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="form-label">Subtotal</label>
                                            <div class="input-group">
                                                <span class="input-group-text">Rp</span>
                                                <input type="text" class="form-control barang-subtotal" placeholder="Subtotal" readonly>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Hapus button -->
                                    <div class="col-md-1 d-flex align-items-end">
                                        <button type="button" class="btn btn-danger btn-sm hapus-barang mb-3">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tombol Tambah Barang -->
                        <div class="mt-3">
                            <button type="button" class="btn btn-success tambah-barang">Tambah Barang</button>
                            <button type="button" class="btn btn-primary tambah-barang-baru">Tambah Barang Baru</button>
                        </div>
                    </div>
                </div>
                <!-- Add this after the Detail Barang section -->
                <!-- Subtotal dan Grand Total -->
                <div class="card mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="card-title">Total</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 offset-md-6">
                                <table class="table table-bordered">
                                    <tbody>
                                        <tr>
                                            <th>Subtotal</th>
                                            <td class="text-end" id="subtotal">Rp 0</td>
                                        </tr>
                                        <!-- In your Total section, modify the PPN row like this: -->
                                        <tr>
                                            <th>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="include_ppn" name="include_ppn" checked>
                                                    <label class="form-check-label" for="include_ppn">PPN (11%)</label>
                                                </div>
                                            </th>
                                            <td class="text-end" id="ppn">Rp 0</td>
                                        </tr>
                                        <tr class="table-active">
                                            <th>Grand Total</th>
                                            <td class="text-end" id="grandtotal">Rp 0</td>
                                        </tr>
                                    </tbody>
                                </table>
                                <!-- Hidden input for form submission -->
                                <input type="hidden" name="subtotal" id="subtotal_value" value="0">
                                <input type="hidden" name="ppn_value" id="ppn_value" value="0">
                                <input type="hidden" name="grandtotal" id="grandtotal_value" value="0">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tombol Simpan -->
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Simpan Penerimaan</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Modal Pilih Barang -->
    <div class="modal fade" id="modal-barang" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Pilih Barang</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <table id="datatable-barang" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th>Barcode</th>
                                <th>Nama Barang</th>
                                <th>Kategori</th>
                                <th>Unit</th>
                                <th>Modal</th>
                                <th>Harga</th>
                                <th>Stock</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data akan diisi oleh DataTable -->
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Barang Baru -->
    <div class="modal fade" id="modalTambahBarang" tabindex="-1" aria-labelledby="modalTambahBarangLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTambahBarangLabel">Tambah Barang Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formTambahBarang">
                        <!-- Barcode -->
                        <div class="mb-3">
                            <label for="Barcode" class="form-label">Barcode</label>
                            <input type="text" name="barcode" id="Barcode" class="form-control" readonly>
                        </div>

                        <!-- Nama Barang -->
                        <div class="mb-3">
                            <label for="Nama_Barang" class="form-label">Nama Barang</label>
                            <input type="text" name="nama_barang" id="Nama_Barang" class="form-control" required>
                        </div>

                        <!-- Kategori -->
                        <div class="mb-3">
                            <label for="ID_Kategori" class="form-label">Kategori</label>
                            <select name="ID_Kategori" id="ID_Kategori" class="form-control select2" required>
                                <option value="">-- Pilih Kategori --</option>
                                @foreach ($kategoris as $kategori)
                                    <option value="{{ $kategori->ID_Kategori }}">{{ $kategori->nama_kategori }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Supplier -->
                        <div class="mb-3">
                            <label for="ID_Sup_Display" class="form-label">Supplier</label>
                            <input type="text" id="ID_Sup_Display" class="form-control" readonly>
                            <input type="hidden" name="id_unit" id="ID_Sup">
                        </div>

                        <!-- Unit -->
                        <div class="mb-3">
                            <label for="ID_Unit" class="form-label">Unit</label>
                            <select name="ID_Unit" id="ID_Unit" class="form-control select2" required>
                                <option value="">-- Pilih Unit --</option>
                                @foreach ($units as $unit)
                                    <option value="{{ $unit->ID_Unit }}">{{ $unit->nama_unit }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Harga Beli -->
                        <div class="mb-3">
                            <label for="modal" class="form-label">Harga Beli</label>
                            <input type="text" name="modal" id="modal" class="form-control" placeholder="Masukkan harga beli">
                        </div>

                        <!-- Kode Modal -->
                        <div class="mb-3">
                            <label for="Kode_Modal" class="form-label">Kode Modal</label>
                            <input type="text" name="kode_modal" id="Kode_Modal" class="form-control">
                        </div>

                        <!-- Harga Jual -->
                        <div class="mb-3">
                            <label for="Harga_Jual" class="form-label">Harga Jual</label>
                            <input type="number" name="harga" id="Harga_Jual" class="form-control" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="simpanBarang">Simpan</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('scripts')
<script>
$(document).ready(function () {
    // Inisialisasi variabel
    let barangIndex = 1;
    let currentRow = null; // Untuk menyimpan referensi baris yang sedang dipilih
    let barangTable = null; // Untuk menyimpan instance DataTable

    // Format currency
   function formatCurrency(number) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(number);

}
function calculateItemSubtotal(item) {
    const jumlah = parseFloat(item.find('.barang-jumlah').val()) || 0;
    const harga = parseFloat(item.find('.barang-harga').val()) || 0;
    const subtotal = jumlah * harga;
    
    item.find('.barang-subtotal').val(formatCurrency(subtotal));
    return subtotal;
}

// Tampilkan jatuh tempo jika metode pembayaran kredit
$('#Metode_Pembayaran').on('change', function() {
    if ($(this).val() === 'kredit') {
        $('.jatuh-tempo-container').show();
        $('#Jatuh_Tempo').prop('required', true);
    } else {
        $('.jatuh-tempo-container').hide();
        $('#Jatuh_Tempo').prop('required', false);
    }
});

// Fungsi perhitungan total dengan PPN optional
function calculateTotals() {
    let subtotal = 0;
    
    $('.barang-item').each(function() {
        subtotal += calculateItemSubtotal($(this));
    });
    
    // PPN optional
    const includePPN = $('#include_ppn').is(':checked');
    const ppn = includePPN ? subtotal * 0.11 : 0;
    const grandtotal = subtotal + ppn;
    
    // Update display
    $('#subtotal').text(formatCurrency(subtotal));
    $('#ppn').text(formatCurrency(ppn));
    $('#grandtotal').text(formatCurrency(grandtotal));
    
    // Update hidden inputs
    $('#subtotal_value').val(subtotal);
    $('#ppn_value').val(ppn);
    $('#grandtotal_value').val(grandtotal);
}

// Add event listener for PPN checkbox change
$(document).on('change', '#include_ppn', function() {
    calculateTotals();
});

$(document).ready(function () {
    // Inisialisasi toastr fallback
    if (typeof toastr === 'undefined') {
        window.toastr = {
            error: function(m) { alert('ERROR: ' + m); },
            warning: function(m) { alert('WARNING: ' + m); },
            success: function(m) { alert('SUCCESS: ' + m); }
        };
    }

    // Set CSRF token untuk semua permintaan AJAX
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Inisialisasi select2 untuk elemen yang sudah ada
    $('.select2').select2({
        theme: 'bootstrap-5',
        placeholder: function() {
            return $(this).data('placeholder') || '-- Pilih --';
        }
    });

    let barangIndex = 1;

    // Ketika supplier dipilih
    $('#id_unit').on('change', function () {
        const supplierId = $(this).val();
        
        if (!supplierId) return;

        // Set supplier ID untuk modal tambah barang baru
        $('#ID_Sup').val(supplierId);
        $('#ID_Sup_Display').val($('#id_unit option:selected').text());

        // Reset list barang
        $('#barang-details').empty();

        // Tambahkan kembali satu form barang kosong
        const resetItem = `
            <div class="barang-item">
                <div class="row">
                    <div class="col-md-5">
                        <div class="form-group">
                            <label class="form-label">Barang</label>
                            <div class="input-group">
                                <input type="text" class="form-control barang-nama" placeholder="Klik untuk memilih barang" readonly>
                                <input type="text" class="barang-id" name="barang_details[0][ID_Barang]">
                                <button type="button" class="btn btn-primary pilih-barang">
                                    <i class="fas fa-search"></i> Pilih
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="form-label">Jumlah</label>
                            <input type="number" name="barang_details[0][Jumlah]" class="form-control barang-jumlah" placeholder="Jumlah" required>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="form-label">Harga</label>
                            <input type="number" name="barang_details[0][Harga]" class="form-control barang-harga" placeholder="Harga" required>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="form-label">Subtotal</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" class="form-control barang-subtotal" placeholder="Subtotal" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-danger btn-sm hapus-barang mb-3">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;

        $('#barang-details').append(resetItem);

        // Reset index barang
        barangIndex = 1;

        // Reset total
        $('#subtotal').text('Rp 0');
        $('#ppn').text('Rp 0');
        $('#grandtotal').text('Rp 0');
        $('#subtotal_value').val(0);
        $('#ppn_value').val(0);
        $('#grandtotal_value').val(0);
    });

   


    

    // Event listener untuk input jumlah dan harga
    $(document).on('input', '.barang-jumlah, .barang-harga', function() {
        calculateItemSubtotal($(this).closest('.barang-item'));
        calculateTotals();
    });

    // Event listener untuk checkbox PPN
    $(document).on('change', '#include_ppn', function () {
        calculateTotals();
    });

    // Tampilkan jatuh tempo jika metode pembayaran kredit
    $('#Metode_Pembayaran').on('change', function() {
        if ($(this).val() === 'kredit') {
            $('.jatuh-tempo-container').show();
            $('#Jatuh_Tempo').prop('required', true);
        } else {
            $('.jatuh-tempo-container').hide();
            $('#Jatuh_Tempo').prop('required', false);
        }
    });

    // Inisialisasi DataTable untuk modal barang
    function initBarangTable(supplierId) {
        if (barangTable) {
            barangTable.destroy();
        }
        barangTable = $('#datatable-barang').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('penerimaan.get-barang-datatable') }}",
                data: function(d) {
                    d.supplier_id = supplierId;
                }
            },
            columns: [
                { data: 'barcode', name: 'barcode' },
                { data: 'nama_barang', name: 'nama_barang' },
                { data: 'kategori.nama_kategori', name: 'kategori.nama_kategori' },
                { data: 'unit.nama_unit', name: 'unit.nama_unit' },
                { 
                    data: 'modal', 
                    name: 'modal',
                    render: function(data) {
                        return formatCurrency(data);
                    }
                },
                { 
                    data: 'harga', 
                    name: 'harga',
                    render: function(data) {
                        return formatCurrency(data);
                    }
                },
                { data: 'stock', name: 'stock' },
                {
                    data: 'ID_Barang',
                    name: 'action',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        return `
                            <button class="btn btn-success btn-sm select-item" 
                                data-id="${row.ID_Barang}" 
                                data-barcode="${row.barcode}"
                                data-nama="${row.nama_barang}"
                                data-modal="${row.modal}">
                                <i class="fas fa-check"></i> Pilih
                            </button>
                        `;
                    }
                }
            ],
        });
    }

    // Event untuk tombol pilih barang
    $(document).on('click', '.pilih-barang', function() {
        const supplierId = $('#id_unit').val();
        
        if (!supplierId) {
            toastr.warning('Pilih supplier terlebih dahulu');
            return;
        }
        
        currentRow = $(this).closest('.barang-item');
        initBarangTable(supplierId);
        $('#modal-barang').modal('show');
    });

    // Event untuk memilih barang dari DataTable
    $(document).on('click', '.select-item', function() {
        console.log($(this).data()); // Lihat apakah id, barcode, nama, dll muncul
        const id = $(this).data('id');
        const barcode = $(this).data('barcode');
        const nama = $(this).data('nama');
        const harga = $(this).data('modal');

        if (!id) {
            alert("ID Barang tidak ditemukan");
            return;
        }

        currentRow.find('.barang-id').val(id);
        currentRow.find('.barang-nama').val(`${barcode} - ${nama}`);
        currentRow.find('.barang-harga').val(harga);
        $('#modal-barang').modal('hide');
        calculateTotals();
    });
    // Event untuk menambah barang baru
    $('.tambah-barang').on('click', function() {
        const supplierId = $('#id_unit').val();
        if (!supplierId) {
            toastr.warning('Pilih supplier terlebih dahulu');
            return;
        }

        const newItem = `
            <div class="barang-item">
                <div class="row mt-3">
                    <div class="col-md-5">
                        <div class="form-group">
                            <label class="form-label">Barang</label>
                            <div class="input-group">
                                <input type="text" class="form-control barang-nama" placeholder="Klik untuk memilih barang" readonly>
                                <input type="text" class="barang-id" name="barang_details[${barangIndex}][ID_Barang]">
                                <button type="button" class="btn btn-primary pilih-barang">
                                    <i class="fas fa-search"></i> Pilih
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="form-label">Jumlah</label>
                            <input type="number" name="barang_details[${barangIndex}][Jumlah]" class="form-control barang-jumlah" placeholder="Jumlah" required>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="form-label">Harga</label>
                            <input type="number" name="barang_details[${barangIndex}][Harga]" class="form-control barang-harga" placeholder="Harga" required>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="form-label">Subtotal</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" class="form-control barang-subtotal" placeholder="Subtotal" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-danger btn-sm hapus-barang mb-3">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        $('#barang-details').append(newItem);
        barangIndex++;
    });

    // Event untuk menghapus barang
    $(document).on('click', '.hapus-barang', function() {
        $(this).closest('.barang-item').remove();
        calculateTotals();
    });

    // Buka modal tambah barang baru
    $('.tambah-barang-baru').on('click', function() {
        const supplierId = $('#id_unit').val();
        if (!supplierId) {
            toastr.warning('Pilih supplier terlebih dahulu');
            return;
        }
        
        $('#ID_Sup').val(supplierId);
        $('#ID_Sup_Display').val($('#id_unit option:selected').text());
        
        // Generate barcode
        $.ajax({
            url: "{{ route('barang.generate-barcode') }}",
            method: 'GET',
            success: function(response) {
                $('#Barcode').val(response.barcode);
                $('#modalTambahBarang').modal('show');
                
                // Inisialisasi select2 dalam modal setelah ditampilkan
                $('#modalTambahBarang .select2').select2({
                    theme: 'bootstrap-5',
                    dropdownParent: $('#modalTambahBarang')
                });
            },
            error: function() {
                toastr.error('Gagal menghasilkan barcode');
            }
        });
    });

    // Simpan barang baru
    $('#simpanBarang').on('click', function() {
        const formData = {
            barcode: $('#Barcode').val(),
            nama_barang: $('#Nama_Barang').val(),
            ID_Kategori: $('#ID_Kategori').val(),
            id_unit: $('#ID_Sup').val(),
            ID_Unit: $('#ID_Unit').val(),
            modal: $('#modal').val().replace(/\./g, ''),
            kode_modal: $('#Kode_Modal').val(),
            harga: $('#Harga_Jual').val(),
        };

        $.ajax({
            url: "{{ route('barang.store') }}",
            method: 'POST',
            data: formData,
            success: function(response) {
                const barang = response.barang;

                $('.barang-select').each(function() {
                    $(this).find('option[value="' + barang.ID_Barang + '"]').remove();
                    $(this).append(new Option(barang.nama_barang, barang.ID_Barang));
                    $(this).trigger('change');
                });

                const $lastSelect = $('.barang-select').last();
                $lastSelect.val(barang.ID_Barang).trigger('change');
                $lastSelect.closest('.barang-item').find('.barang-harga').val(barang.harga);

                $('#modalTambahBarang').modal('hide');
                $('#formTambahBarang')[0].reset();

                toastr.success('Barang berhasil ditambahkan');
            },

            error: function(xhr) {
                let errorMessage = 'Gagal menambahkan barang';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    errorMessage = Object.values(xhr.responseJSON.errors).join('<br>');
                }
                toastr.error(errorMessage);
            }
        });
    });

    // Format number and generate kode modal
    $('#modal').on('input', function() {
        // Simpan posisi cursor
        const cursorPosition = this.selectionStart;
        
        // Dapatkan nilai tanpa format
        let value = $(this).val().replace(/\./g, '');
        
        // Format hanya jika ada nilai
        if (value) {
            const num = parseInt(value, 10);
            if (!isNaN(num)) {
                $(this).val(num.toLocaleString('id-ID'));
                
                // Kembalikan posisi cursor setelah formatting
                const newCursorPos = cursorPosition + ($(this).val().length - value.length);
                this.setSelectionRange(newCursorPos, newCursorPos);
            } else {
                $(this).val('');
            }
        } else {
            $(this).val('');
        }
        
        // Generate kode modal
        generateKodeModal();
    });

    // Generate kode modal
    function generateKodeModal() {
        const mapping = {
            '0': 'U', '1': 'A', '2': 'B', '3': 'C', '4': 'D',
            '5': 'E', '6': 'F', '7': 'G', '8': 'H', '9': 'J'
        };
        
        // Get the modal value and remove thousand separators
        const rawValue = $('#modal').val().replace(/\./g, '');
        
        // Remove any non-numeric characters
        const numericValue = rawValue.replace(/\D/g, '');
        
        // If empty, clear the kode_modal field
        if (!numericValue) {
            $('#Kode_Modal').val('');
            return;
        }

        // Count trailing zeros
        let zeroCount = 0;
        for (let i = numericValue.length - 1; i >= 0; i--) {
            if (numericValue[i] === '0') {
                zeroCount++;
            } else {
                break;
            }
        }

        // Process the base value (without trailing zeros)
        const baseValue = numericValue.slice(0, numericValue.length - zeroCount);
        let kodeModal = '';
        
        // Map each digit to its corresponding character
        for (const digit of baseValue) {
            kodeModal += mapping[digit] || '';
        }

        // Add suffix for trailing zeros
        if (zeroCount > 0) {
            kodeModal += 'U';  // Add 'U' to indicate zeros
            
            // If more than one zero, add the count
            if (zeroCount > 1) {
                kodeModal += zeroCount.toString();
            }
        }

        // Set the generated code
        $('#Kode_Modal').val(kodeModal);
    }

    $(document).on('submit', 'form', function(e) {
        $('.barang-item').each(function() {
            let hargaInput = $(this).find('.barang-harga');
            let hargaValue = hargaInput.val();

            // Hilangkan semua karakter selain angka
            let cleanedValue = parseFloat(hargaValue.replace(/[^0-9]/g, '')) || 0;

            // Set nilai bersih ke input hidden atau ubah tipe input menjadi number
            hargaInput.val(cleanedValue);
        });
    });
    });
});

</script>
@endsection