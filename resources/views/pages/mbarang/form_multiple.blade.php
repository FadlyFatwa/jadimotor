@extends('layouts.main')
@section('title', 'Tambah Banyak Barang')
@section('content_header')
    <h1>Tambah Banyak Barang</h1>
@stop
@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Form Tambah Banyak Barang</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('barang.storeMultiple') }}" method="POST" id="multiple-barang-form">
            @csrf

            <!-- Grid 3 Kolom -->
            <div id="barang-list" class="row">

                <!-- Baris pertama -->
                <div class="col-md-4 mb-3 barang-row" data-index="0">
                    <div class="card">
                        <div class="card-header bg-light">
                            <strong>Barang 1</strong>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Barcode</label>
                                <input type="text" name="barangs[0][barcode]" class="form-control barcode-input"
                                       value="{{ $nextBarcode ?? '00001' }}" required >
                            </div>
                            <div class="form-group">
                                <label>Nama Barang</label>
                                <input type="text" name="barangs[0][nama_barang]" class="form-control nama-input"
                                       required>
                            </div>
                            <div class="form-group">
                                <label>Kategori</label>
                                <select name="barangs[0][ID_Kategori]" class="form-control select2 kategori-input" style="display: inline-block;" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    @foreach($kategoris as $kategori)
                                        <option value="{{ $kategori->ID_Kategori }}">{{ $kategori->nama_kategori }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Supplier</label>
                                <select name="barangs[0][id_supplier]" class="form-control select2 supplier-input" required>
                                    <option value="">-- Pilih Supplier --</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id_supplier }}">{{ $supplier->nama_supplier }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Unit</label>
                                <select name="barangs[0][ID_Unit]" class="form-control select2 unit-input" required>
                                    <option value="">-- Pilih Unit --</option>
                                    @foreach($units as $unit)
                                        <option value="{{ $unit->ID_Unit }}">{{ $unit->nama_unit }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Modal</label>
                                <input type="text" name="barangs[0][modal]" class="form-control modal-input"
                                       placeholder="Contoh: 10000" oninput="formatNumber(this); generateKodeModalFromRow(this)" required>
                            </div>
                            <div class="form-group">
                                <label>Kode Modal</label>
                                <input type="text" name="barangs[0][kode_modal]" class="form-control kode-modal-input" required>
                            </div>
                            <div class="form-group">
                                <label>Harga</label>
                                <input type="text" name="barangs[0][harga]" class="form-control harga-input"
                                       placeholder="Contoh: 15000" oninput="formatNumber(this)" required>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="mt-3 text-center">
                <button type="button" class="btn btn-secondary" id="add-more" disabled>+ Tambah Baris</button>
                <button type="submit" class="btn btn-primary">Simpan Semua</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts') 
<script>
    let rowCount = 1;
    const baseBarcode = parseInt("{{ $nextBarcode }}");

    // Fungsi untuk menginisialisasi Select2 dengan konfigurasi yang benar
    function initSelect2(element) {
        $(element).select2({
            theme: 'bootstrap-5',
            width: '100%',
            dropdownParent: $(element).closest('.card-body'), // Penting untuk positioning
            placeholder: "-- Pilih --"
        }).on('select2:open', function(e) {
            // Fix untuk z-index saat dropdown terbuka
            setTimeout(() => {
                const dropdown = document.querySelector('.select2-container--open .select2-dropdown');
                if (dropdown) {
                    dropdown.style.zIndex = '9999';
                }
                
                let searchField = document.querySelector('.select2-container--open .select2-search__field');
                if (searchField) {
                    searchField.focus();
                    searchField.addEventListener('keydown', function(e) {
                        if (e.key === "ArrowDown") {
                            const results = document.querySelector('.select2-results__option');
                            if (results) results.focus();
                        }
                    });
                }
            }, 50);
        });
    }

    // Validasi apakah semua input di baris pertama terisi
    function validateFirstRow() {
        const row = document.querySelector('.barang-row[data-index="0"]');
        const barcode = row.querySelector('.barcode-input').value.trim();
        const nama = row.querySelector('.nama-input').value.trim();
        const kategori = row.querySelector('.kategori-input').value.trim();
        const supplier = row.querySelector('.supplier-input').value.trim();
        const unit = row.querySelector('.unit-input').value.trim();

        return barcode && nama && kategori && supplier && unit;
    }

    // Cek validasi saat input berubah
    function setupValidationCheckers() {
        const inputs = document.querySelectorAll('.barang-row[data-index="0"] input, .barang-row[data-index="0"] select');
        inputs.forEach(input => {
            input.addEventListener('input', () => {
                document.getElementById('add-more').disabled = !validateFirstRow();
            });
        });
    }

    // Fungsi menambahkan baris baru
    document.getElementById('add-more').addEventListener('click', () => {
        const index = rowCount;

        const prevRow = document.querySelector(`.barang-row[data-index="${index - 1}"]`);
        const prevBarcodeInput = prevRow.querySelector('.barcode-input');
        let prevBarcode = parseInt(prevBarcodeInput.value.replace(/\D/g, ''), 10) || baseBarcode + (index - 1);
        const newBarcode = String(prevBarcode + 1).padStart(5, '0');

        const newRow = document.createElement('div');
        newRow.classList.add('col-md-4', 'mb-3', 'barang-row');
        newRow.setAttribute('data-index', index);

        newRow.innerHTML = `
            <div class="card">
                <div class="card-header bg-light">
                    <strong>Barang ${index + 1}</strong>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Barcode</label>
                        <input type="text" name="barangs[${index}][barcode]" class="form-control barcode-input"
                               value="${newBarcode}" required >
                    </div>
                    <div class="form-group">
                        <label>Nama Barang</label>
                        <input type="text" name="barangs[${index}][nama_barang]" class="form-control nama-input" required>
                    </div>
                    <div class="form-group">
                        <label>Kategori</label>
                        <select name="barangs[${index}][ID_Kategori]" class="form-control select2 kategori-input" required>
                            <option value="">-- Pilih Kategori --</option>
                            @foreach($kategoris as $kategori)
                                <option value="{{ $kategori->ID_Kategori }}">{{ $kategori->nama_kategori }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Supplier</label>
                        <select name="barangs[${index}][id_supplier]" class="form-control select2 supplier-input" required>
                            <option value="">-- Pilih Supplier --</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id_supplier }}">{{ $supplier->nama_supplier }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Unit</label>
                        <select name="barangs[${index}][ID_Unit]" class="form-control select2 unit-input" required>
                            <option value="">-- Pilih Unit --</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->ID_Unit }}">{{ $unit->nama_unit }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Modal</label>
                        <input type="text" name="barangs[${index}][modal]" class="form-control modal-input"
                               placeholder="Contoh: 10000" oninput="formatNumber(this); generateKodeModalFromRow(this)" required>
                    </div>
                    <div class="form-group">
                        <label>Kode Modal</label>
                        <input type="text" name="barangs[${index}][kode_modal]" class="form-control kode-modal-input" required>
                    </div>
                    <div class="form-group">
                        <label>Harga</label>
                        <input type="text" name="barangs[${index}][harga]" class="form-control harga-input"
                               placeholder="Contoh: 15000" oninput="formatNumber(this)" required>
                    </div>
                    <div class="d-flex justify-content-end mt-2">
                        <button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            </div>
        `;

        document.getElementById('barang-list').appendChild(newRow);
        rowCount++;

        // Inisialisasi Select2 di baris baru dengan fungsi yang sudah diperbaiki
        newRow.querySelectorAll('.select2').forEach(select => {
            initSelect2(select);
        });

        // Event listener untuk tombol hapus
        newRow.querySelector('.remove-row').addEventListener('click', () => {
            if (document.querySelectorAll('.barang-row').length > 1) {
                newRow.remove();
            } else {
                alert('Minimal harus ada satu barang.');
            }
        });

        setupValidationCheckers();
    });

    // Format angka ribuan
    function formatNumber(input) {
        let value = input.value.replace(/\D/g, '');
        if (value.length > 0) {
            value = parseInt(value, 10).toLocaleString('id-ID');
        }
        input.value = value;
    }

    // Generate kode modal
    function generateKodeModalFromRow(input) {
        const mapping = {
            '0': 'U', '1': 'A', '2': 'B', '3': 'C', '4': 'D',
            '5': 'E', '6': 'F', '7': 'G', '8': 'H', '9': 'J'
        };
        const parent = input.closest('.card-body');
        const kodeInput = parent.querySelector('.kode-modal-input');
        const rawValue = input.value.replace(/\./g, '').replace(/\D/g, '');

        if (!rawValue) {
            kodeInput.value = '';
            return;
        }

        let zeroCount = 0;
        for (let i = rawValue.length - 1; i >= 0; i--) {
            if (rawValue[i] === '0') {
                zeroCount++;
            } else break;
        }

        const baseValue = rawValue.slice(0, rawValue.length - zeroCount);
        let kodeModal = '';

        for (const digit of baseValue) {
            kodeModal += mapping[digit] || '';
        }

        if (zeroCount > 0) {
            kodeModal += 'U';
            if (zeroCount > 1) {
                kodeModal += zeroCount.toString();
            }
        }

        kodeInput.value = kodeModal;
    }

    // Inisialisasi awal
    $(document).ready(function () {
        // Inisialisasi Select2 untuk baris pertama
        document.querySelectorAll('.select2').forEach(select => {
            initSelect2(select);
        });
        
        setupValidationCheckers();
        document.getElementById('add-more').disabled = !validateFirstRow();
    });
</script>
@endsection