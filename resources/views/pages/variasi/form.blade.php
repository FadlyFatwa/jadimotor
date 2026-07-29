@extends('layouts.main')

@section('title', isset($variasi) ? 'Edit Barang' : 'Tambah Barang')

@section('content_header')
    <h1>{{ isset($variasi) ? 'Edit' : 'Tambah' }} Barang / Variasi</h1>
@stop

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-9">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title mb-0">Form {{ isset($variasi) ? 'Edit' : 'Tambah' }} Barang</h3>
                </div>
                <div class="card-body p-4">
                    <form id="formBarang"
                        action="{{ isset($variasi) ? route('barang.update', $variasi->id_variasi) : route('barang.store') }}"
                        method="POST">
                        @csrf
                        @if(isset($variasi)) @method('PUT') @endif

                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                            </div>
                        @endif

                        {{-- ===== INFO DASAR ===== --}}
                        <h6 class="font-weight-bold text-muted mb-3 border-bottom pb-1">Informasi Dasar</h6>
                        <div class="row">
                            <div class="col-md-3 form-group">
                                <label>Barcode <span class="text-danger">*</span></label>
                                <input type="text" name="barcode"
                                    class="form-control @error('barcode') is-invalid @enderror"
                                    value="{{ old('barcode', $variasi->barcode ?? $nextBarcode ?? '') }}"
                                    {{ isset($variasi) ? 'readonly' : '' }}>
                                @error('barcode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3 form-group">
                                <label>Part Number</label>
                                <input type="text" name="part_number"
                                    class="form-control @error('part_number') is-invalid @enderror"
                                    value="{{ old('part_number', $variasi->part_number ?? '') }}"
                                    placeholder="No. part supplier">
                                @error('part_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3 form-group">
                                <label>Grade / Tier</label>
                                <select name="tier" class="form-control @error('tier') is-invalid @enderror">
                                    <option value="">-- Pilih Tier --</option>
                                    @foreach(['OEM','Original','Aftermarket','Aftermarket A','Aftermarket B','Aftermarket C','KW','Lelangan'] as $t)
                                        <option value="{{ $t }}" {{ old('tier', $variasi->tier ?? '') === $t ? 'selected' : '' }}>
                                            {{ $t }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('tier')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3 form-group">
                                <label>Status</label>
                                <div class="mt-2">
                                    <div class="custom-control custom-switch">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="checkbox" name="is_active" value="1"
                                            class="custom-control-input" id="is_active"
                                            {{ old('is_active', $variasi->is_active ?? true) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="is_active">Variasi Aktif</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Master Barang <span class="text-danger">*</span></label>
                                <select name="id_barang" id="id_barang_select" class="form-control @error('id_barang') is-invalid @enderror">
                                    <option value="">-- Pilih Master Barang --</option>
                                    @foreach($m_barangs as $mb)
                                        <option value="{{ $mb->id_barang }}"
                                            {{ old('id_barang', $variasi->id_barang ?? '') == $mb->id_barang ? 'selected' : '' }}>
                                            {{ $mb->nama_barang }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('id_barang')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Nama Variasi <span class="text-danger">*</span></label>
                                <input type="text" name="nama_variasi"
                                    class="form-control @error('nama_variasi') is-invalid @enderror"
                                    value="{{ old('nama_variasi', $variasi->nama_variasi ?? '') }}"
                                    placeholder="Contoh: 1000cc, Merah, dll">
                                @error('nama_variasi')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label>Unit <span class="text-danger">*</span></label>
                                <select name="id_unit" class="form-control select2 @error('id_unit') is-invalid @enderror">
                                    <option value="">-- Pilih Unit --</option>
                                    @foreach($units as $unit)
                                        <option value="{{ $unit->id_unit }}"
                                            {{ old('id_unit', $variasi->id_unit ?? '') == $unit->id_unit ? 'selected' : '' }}>
                                            {{ $unit->nama_unit }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('id_unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4 form-group">
                                <label>Harga Jual <span class="text-danger">*</span></label>
                                <input type="text" name="harga_jual" id="harga_jual"
                                    class="form-control @error('harga_jual') is-invalid @enderror"
                                    value="{{ old('harga_jual', isset($variasi) ? number_format($variasi->harga_jual, 0, ',', '.') : '') }}"
                                    oninput="formatNumber(this)" placeholder="0">
                                @error('harga_jual')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        {{-- ===== DATA SUPPLIER ===== --}}
                        <h6 class="font-weight-bold text-muted mb-2 border-bottom pb-1 mt-3">Data Supplier & Harga</h6>
                        <p class="small text-muted mb-2">Satu SKU bisa terdaftar ke lebih dari satu supplier — masing-masing dengan harga & kode beli sendiri. Data ini dipakai saat sistem mengirim inquiry & menghitung rekomendasi supplier (SAW).</p>
                        @php
                            $existingSV = (isset($variasi) && $variasi->suppliervariasi->count())
                                ? $variasi->suppliervariasi->values()
                                : collect([null]);
                        @endphp
                        <div id="supplier-container">
                            @foreach($existingSV as $i => $sv)
                            <div class="supplier-row border rounded p-2 mb-2 position-relative">
                                <button type="button"
                                    class="btn btn-sm btn-link text-danger btn-remove-supplier position-absolute"
                                    style="top:-4px; right:-4px; display:none;" title="Hapus supplier ini">
                                    <i class="fas fa-times"></i>
                                </button>
                                <div class="row align-items-end">
                                    <div class="col-md-3 form-group mb-1">
                                        <label class="small">Supplier</label>
                                        <select name="supplier_data[{{ $i }}][id_supplier]" class="form-control select2 supplier_select">
                                            <option value="">-- Supplier --</option>
                                            @foreach($suppliers as $sup)
                                                <option value="{{ $sup->id_supplier }}"
                                                    {{ ($sv->id_supplier ?? null) == $sup->id_supplier ? 'selected' : '' }}>
                                                    {{ $sup->nama_supplier }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2 form-group mb-1">
                                        <label class="small">Harga List</label>
                                        <input type="text" name="supplier_data[{{ $i }}][harga_list]"
                                            class="form-control harga_list_input"
                                            value="{{ $sv ? number_format($sv->harga_list ?? 0, 0, ',', '.') : '' }}"
                                            placeholder="0"
                                            oninput="formatNumber(this); generateKode(this,'list'); updateHargaBeli(this)">
                                    </div>
                                    <div class="col-md-1 form-group mb-1">
                                        <label class="small">Diskon %</label>
                                        <input type="text" name="supplier_data[{{ $i }}][diskon]"
                                            class="form-control diskon_input"
                                            value="{{ $sv->diskon ?? '' }}"
                                            placeholder="%" oninput="updateHargaBeli(this)">
                                    </div>
                                    <div class="col-md-2 form-group mb-1">
                                        <label class="small">Kode List</label>
                                        <input type="text" name="supplier_data[{{ $i }}][kode_list]"
                                            class="form-control kode_list_input"
                                            value="{{ $sv->kode_list ?? '' }}" readonly>
                                    </div>
                                    <div class="col-md-2 form-group mb-1">
                                        <label class="small">Harga Beli</label>
                                        <input type="text" name="supplier_data[{{ $i }}][harga_beli]"
                                            class="form-control harga_beli_input"
                                            value="{{ $sv ? number_format($sv->harga_beli ?? 0, 0, ',', '.') : '' }}"
                                            placeholder="0"
                                            oninput="formatNumber(this); generateKode(this, 'beli')">
                                    </div>
                                    <div class="col-md-2 form-group mb-1">
                                        <label class="small">Kode Beli</label>
                                        <input type="text" name="supplier_data[{{ $i }}][kode_beli]"
                                            class="form-control kode_beli_input"
                                            value="{{ $sv->kode_beli ?? '' }}" readonly>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <button type="button" id="btnAddSupplier" class="btn btn-sm btn-outline-primary mb-3">
                            <i class="fas fa-plus"></i> Tambah Supplier
                        </button>

                        {{-- ===== KOMPATIBILITAS KENDARAAN ===== --}}
                        <h6 class="font-weight-bold text-muted mb-2 border-bottom pb-1 mt-3">Kompatibilitas Kendaraan</h6>
                        <p class="small text-muted mb-2">Klik pabrikan untuk melihat pilihan kendaraan (opsional)</p>

                        @if($vehicleGenerations->isEmpty())
                            <p class="text-muted small">Belum ada data kendaraan.
                                <a href="{{ route('kendaraan.create') }}" target="_blank">Tambah kendaraan</a>
                            </p>
                        @else
                        <div id="vehicleAccordion">
                            @foreach($vehicleGenerations as $manufacturer => $vehicleGroups)
                                @php
                                    $mfrSlug = Str::slug($manufacturer);
                                    $hasSelected = false;
                                    foreach($vehicleGroups as $gens) {
                                        foreach($gens as $gen) {
                                            if(in_array($gen->id, $selectedGenerations ?? [])) { $hasSelected = true; break 2; }
                                        }
                                    }
                                @endphp
                                <div class="card card-outline card-secondary mb-1">
                                    <div class="card-header py-2 px-3" style="cursor:pointer"
                                        data-toggle="collapse" data-target="#mfr_{{ $mfrSlug }}" aria-expanded="{{ $hasSelected ? 'true' : 'false' }}">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="font-weight-bold">
                                                <i class="fas fa-industry mr-2 text-muted"></i>{{ $manufacturer }}
                                            </span>
                                            <i class="fas fa-chevron-down small text-muted"></i>
                                        </div>
                                    </div>
                                    <div id="mfr_{{ $mfrSlug }}" class="collapse {{ $hasSelected ? 'show' : '' }}">
                                        <div class="card-body pt-2 pb-2">
                                            @foreach($vehicleGroups as $vehicleName => $gens)
                                                <p class="mb-1 mt-2 small font-weight-bold text-primary">
                                                    <i class="fas fa-car mr-1"></i>{{ $vehicleName }}
                                                </p>
                                                <div class="row ml-2">
                                                    @foreach($gens as $gen)
                                                    <div class="col-md-4 mb-1">
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input"
                                                                id="gen_{{ $gen->id }}"
                                                                name="vehicle_generation_ids[]"
                                                                value="{{ $gen->id }}"
                                                                {{ in_array($gen->id, $selectedGenerations ?? []) ? 'checked' : '' }}>
                                                            <label class="custom-control-label small" for="gen_{{ $gen->id }}">
                                                                <strong>{{ $gen->code }}</strong>
                                                                @if($gen->nickname) <em>({{ $gen->nickname }})</em> @endif
                                                                @if($gen->start_year)
                                                                    <span class="text-muted">{{ $gen->start_year }}-{{ $gen->end_year ?? 'skrg' }}</span>
                                                                @endif
                                                            </label>
                                                        </div>
                                                    </div>
                                                    @endforeach
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @endif

                        {{-- ===== TOMBOL ===== --}}
                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('barang.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> {{ isset($variasi) ? 'Simpan Perubahan' : 'Simpan' }}
                            </button>
                        </div>
                    </form>

                    {{-- ===== MODAL: TAMBAH MASTER BARANG BARU ===== --}}
                    <div class="modal fade" id="modalCreateMBarang" tabindex="-1" role="dialog">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Tambah Master Barang Baru</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label>Nama Barang <span class="text-danger">*</span></label>
                                        <input type="text" id="newMBarangNama" class="form-control">
                                        <div class="invalid-feedback" id="errNewMBarangNama"></div>
                                    </div>
                                    <div class="form-group">
                                        <label>Kode Barang <span class="text-danger">*</span></label>
                                        <input type="text" id="newMBarangKode" class="form-control">
                                        <div class="invalid-feedback" id="errNewMBarangKode"></div>
                                    </div>
                                    <div class="form-group mb-0">
                                        <label>Kategori <span class="text-danger">*</span></label>
                                        <select id="newMBarangKategori" class="form-control">
                                            <option value="">-- Pilih Kategori --</option>
                                            @foreach($kategoris as $kategori)
                                                <option value="{{ $kategori->id_kategori }}">{{ $kategori->nama_kategori }}</option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback" id="errNewMBarangKategori"></div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                    <button type="button" class="btn btn-primary" id="btnSaveNewMBarang">
                                        <i class="fas fa-save"></i> Simpan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Mapping: A=1 B=2 C=3 D=4 E=5 F=6 G=7 H=8 I=9 J=0
// Trailing zeros: disingkat dengan J + jumlah (misal 10000 → AJ4)
const digitMap = {'1':'A','2':'B','3':'C','4':'D','5':'E','6':'F','7':'G','8':'H','9':'I','0':'J'};

function formatNumber(input) {
    let v = input.value.replace(/\D/g, '');
    input.value = v ? parseInt(v, 10).toLocaleString('id-ID') : '';
}

function generateKode(input, type) {
    const raw = input.value.replace(/\D/g, '');
    if (!raw) return;
    // Hitung trailing zeros
    let zeros = 0;
    for (let i = raw.length - 1; i >= 0; i--) { if (raw[i] === '0') zeros++; else break; }
    // Map digit non-zero (bagian depan)
    const prefix = raw.slice(0, raw.length - zeros);
    let kode = prefix.split('').map(d => digitMap[d] || '').join('');
    // Trailing zeros: J + jumlah (J saja jika hanya 1)
    if (zeros > 0) {
        kode += 'J';
        if (zeros > 1) kode += zeros;
    }
    const row = input.closest('.supplier-row');
    const target = row.querySelector(type === 'beli' ? '.kode_beli_input' : '.kode_list_input');
    if (target) target.value = kode;
}

function updateHargaBeli(input) {
    const row = input.closest('.supplier-row');
    const hargaList = parseInt((row.querySelector('.harga_list_input')?.value||'0').replace(/\D/g,''),10);
    const diskon    = parseFloat((row.querySelector('.diskon_input')?.value||'0').replace(',','.'));
    if (!hargaList || !diskon) return;
    const hargaBeli = Math.round(hargaList - (diskon/100)*hargaList);
    const hbInput = row.querySelector('.harga_beli_input');
    if (hbInput) { hbInput.value = hargaBeli.toLocaleString('id-ID'); generateKode(hbInput, 'beli'); }
}

// select2 diinisialisasi oleh layout (main.blade.php), kecuali Master Barang:
// field ini butuh konfigurasi khusus (tombol "tambah baru" saat tidak ditemukan)
// sehingga diinisialisasi manual di bawah dan tidak diberi class .select2.

function buildNoResultsCreate(params) {
    const term = (params.term || '').trim();
    const $wrap = $('<div class="text-center py-1"></div>');
    $wrap.append($('<div class="mb-1 text-muted"></div>').text('Master barang tidak ditemukan.'));
    const $btn = $('<button type="button" class="btn btn-sm btn-outline-primary"></button>')
        .append('<i class="fas fa-plus mr-1"></i>')
        .append(document.createTextNode(term ? `Tambah "${term}" sebagai master barang baru` : 'Tambah master barang baru'));
    $btn.on('click', function () {
        $('#id_barang_select').select2('close');
        openCreateMBarangModal(term);
    });
    $wrap.append($btn);
    return $wrap;
}

function openCreateMBarangModal(nama) {
    $('#newMBarangNama').val(nama || '');
    $('#newMBarangKode').val('');
    $('#newMBarangKategori').val('');
    $('#modalCreateMBarang .invalid-feedback').text('');
    $('#modalCreateMBarang .is-invalid').removeClass('is-invalid');
    $('#modalCreateMBarang').modal('show');
}

$('#id_barang_select').select2({
    theme: 'bootstrap-5',
    placeholder: '-- Pilih Master Barang --',
    width: '100%',
    allowClear: true,
    language: { noResults: buildNoResultsCreate },
});

$('#btnSaveNewMBarang').on('click', function () {
    const $btn = $(this).prop('disabled', true);
    const fieldMap = {
        nama_barang: 'newMBarangNama',
        kode_barang: 'newMBarangKode',
        id_kategori: 'newMBarangKategori',
    };
    $('#modalCreateMBarang .invalid-feedback').text('');
    $('#modalCreateMBarang .is-invalid').removeClass('is-invalid');

    $.ajax({
        url: "{{ route('m_barang.store') }}",
        method: 'POST',
        dataType: 'json',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            nama_barang: $('#newMBarangNama').val(),
            kode_barang: $('#newMBarangKode').val(),
            id_kategori: $('#newMBarangKategori').val(),
            is_active: 1,
        },
        success: function (res) {
            const opt = new Option(res.nama_barang, res.id_barang, true, true);
            $('#id_barang_select').append(opt).trigger('change');
            $('#modalCreateMBarang').modal('hide');
            toastr.success('Master barang berhasil ditambahkan.');
        },
        error: function (xhr) {
            if (xhr.status === 422) {
                const errors = (xhr.responseJSON && xhr.responseJSON.errors) || {};
                Object.keys(errors).forEach(function (field) {
                    const id = fieldMap[field];
                    if (!id) return;
                    $('#' + id).addClass('is-invalid');
                    $('#err' + id.charAt(0).toUpperCase() + id.slice(1)).text(errors[field][0]);
                });
            } else {
                toastr.error('Gagal menambahkan master barang.');
            }
        },
        complete: function () { $btn.prop('disabled', false); },
    });
});

// ===== Multi-supplier: tambah/hapus baris & cegah pilih supplier yang sama =====
const supplierOptionsHtml = `<option value="">-- Supplier --</option>@foreach($suppliers as $sup)<option value="{{ $sup->id_supplier }}">{{ addslashes($sup->nama_supplier) }}</option>@endforeach`;
let supplierRowIndex = {{ $existingSV->count() }};

function buildSupplierRow(index) {
    return `
    <div class="supplier-row border rounded p-2 mb-2 position-relative">
        <button type="button" class="btn btn-sm btn-link text-danger btn-remove-supplier position-absolute" style="top:-4px; right:-4px;" title="Hapus supplier ini">
            <i class="fas fa-times"></i>
        </button>
        <div class="row align-items-end">
            <div class="col-md-3 form-group mb-1">
                <label class="small">Supplier</label>
                <select name="supplier_data[${index}][id_supplier]" class="form-control select2 supplier_select">${supplierOptionsHtml}</select>
            </div>
            <div class="col-md-2 form-group mb-1">
                <label class="small">Harga List</label>
                <input type="text" name="supplier_data[${index}][harga_list]" class="form-control harga_list_input" placeholder="0"
                    oninput="formatNumber(this); generateKode(this,'list'); updateHargaBeli(this)">
            </div>
            <div class="col-md-1 form-group mb-1">
                <label class="small">Diskon %</label>
                <input type="text" name="supplier_data[${index}][diskon]" class="form-control diskon_input" placeholder="%"
                    oninput="updateHargaBeli(this)">
            </div>
            <div class="col-md-2 form-group mb-1">
                <label class="small">Kode List</label>
                <input type="text" name="supplier_data[${index}][kode_list]" class="form-control kode_list_input" readonly>
            </div>
            <div class="col-md-2 form-group mb-1">
                <label class="small">Harga Beli</label>
                <input type="text" name="supplier_data[${index}][harga_beli]" class="form-control harga_beli_input" placeholder="0"
                    oninput="formatNumber(this); generateKode(this, 'beli')">
            </div>
            <div class="col-md-2 form-group mb-1">
                <label class="small">Kode Beli</label>
                <input type="text" name="supplier_data[${index}][kode_beli]" class="form-control kode_beli_input" readonly>
            </div>
        </div>
    </div>`;
}

function toggleRemoveButtons() {
    const rows = document.querySelectorAll('#supplier-container .supplier-row');
    rows.forEach(row => {
        row.querySelector('.btn-remove-supplier').style.display = rows.length > 1 ? '' : 'none';
    });
}

// Disable opsi supplier yang sudah dipilih di baris lain, supaya 1 SKU tidak bisa
// terdaftar dua kali ke supplier yang sama. Select2 membaca ulang properti
// `disabled` tiap kali dropdown dibuka, jadi cukup ubah elemen <option> aslinya
// — tidak perlu trigger apa pun ke select2 (memicu event 'change' di sini lagi).
function refreshSupplierOptions() {
    const selects = document.querySelectorAll('#supplier-container .supplier_select');
    const chosen = Array.from(selects).map(s => s.value).filter(v => v);
    selects.forEach(sel => {
        Array.from(sel.options).forEach(opt => {
            if (!opt.value) return;
            opt.disabled = chosen.includes(opt.value) && sel.value !== opt.value;
        });
    });
}

function removeSupplierRow(row) {
    if (document.querySelectorAll('#supplier-container .supplier-row').length <= 1) return;
    // Selector dibatasi ke tag <select> saja — Select2 menambahkan class "select2"
    // juga ke <span> container yang dibuatnya, jadi find('.select2') polos akan
    // menangkap KEDUANYA (select asli + span). Memanggil .select2('destroy') pada
    // set 2 elemen itu throw saat giliran elemen span (bukan instance Select2),
    // sehingga row.remove() di bawah ini tidak pernah jalan pada klik pertama.
    const $sel = $(row).find('select.select2');
    if ($sel.data('select2')) { $sel.select2('destroy'); }
    row.remove();
    toggleRemoveButtons();
    refreshSupplierOptions();
}

function bindSupplierRow(row) {
    $(row).find('.supplier_select').on('change', refreshSupplierOptions);
    row.querySelector('.btn-remove-supplier').addEventListener('click', () => removeSupplierRow(row));
}

$(document).ready(function () {
    // Baris yang sudah ada di-render server-side; select2-nya sudah diinisialisasi
    // oleh layout, jadi cukup pasang handler hapus & change saja.
    document.querySelectorAll('#supplier-container .supplier-row').forEach(bindSupplierRow);
    toggleRemoveButtons();
    refreshSupplierOptions();

    document.getElementById('btnAddSupplier').addEventListener('click', function () {
        const container = document.getElementById('supplier-container');
        const wrapper = document.createElement('div');
        wrapper.innerHTML = buildSupplierRow(supplierRowIndex++).trim();
        const row = wrapper.firstElementChild;
        container.appendChild(row);
        $(row).find('select.select2').select2({ theme: 'bootstrap-5', placeholder: '-- Pilih --', width: '100%', allowClear: true });
        bindSupplierRow(row);
        toggleRemoveButtons();
        refreshSupplierOptions();
    });
});
</script>
@endsection
