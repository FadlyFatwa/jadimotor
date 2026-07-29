@extends('layouts.main')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h2 class="font-weight-bold mb-1" style="font-size:1.5rem">Deteksi &amp; Merge Item Duplikat</h2>
            <p class="text-muted mb-0" style="font-size:.875rem">
                Temukan item yang sebenarnya sama tapi namanya berbeda (urutan kata, typo, singkatan),
                lalu gabungkan jadi satu SKU.
            </p>
        </div>
        <div>
            <a href="{{ route('duplikat-item.riwayat') }}" class="btn btn-outline-secondary">
                <i class="fas fa-history mr-1"></i>Riwayat Penggabungan
            </a>
            <a href="{{ route('duplikat-item.riwayat-kategorisasi') }}" class="btn btn-outline-secondary">
                <i class="fas fa-undo mr-1"></i>Riwayat Kategorisasi
            </a>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body py-3 px-4 d-flex align-items-center justify-content-between flex-wrap" style="gap:.75rem;">
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="fuzzyToggle" {{ $includeFuzzy ? 'checked' : '' }}>
                <label class="custom-control-label" for="fuzzyToggle">
                    Sertakan deteksi typo/singkatan (lanjutan, dibandingkan per kategori)
                </label>
            </div>
            <div class="d-flex align-items-center" style="gap:.75rem;">
                <form id="formCariBarcode" class="form-inline mb-0" method="GET" action="{{ route('duplikat-item.index') }}">
                    <input type="hidden" name="fuzzy" value="{{ $includeFuzzy ? 1 : 0 }}">
                    <div class="input-group input-group-sm" style="width:240px;">
                        <input type="text" name="barcode" class="form-control" placeholder="Cari barcode..."
                               value="{{ $barcodeSearch }}">
                        @if($barcodeSearch !== '')
                            <div class="input-group-append">
                                <a href="{{ route('duplikat-item.index', ['fuzzy' => $includeFuzzy ? 1 : 0]) }}"
                                   class="btn btn-outline-secondary" title="Hapus pencarian">
                                    <i class="fas fa-times"></i>
                                </a>
                            </div>
                        @endif
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
                <span class="badge badge-pill badge-light text-muted">{{ $groups->total() }} grup ditemukan</span>
            </div>
        </div>
    </div>

    @if($groups->isEmpty())
        <div class="card shadow-sm">
            <div class="card-body text-center text-muted py-5">
                <i class="fas fa-check-circle fa-2x mb-2 text-success"></i>
                <p class="mb-0">Tidak ada item duplikat yang terdeteksi saat ini.</p>
            </div>
        </div>
    @endif

    @foreach($groups as $gi => $group)
        @php
            $groupNo = $groups->firstItem() + $gi;
            $sugg = $group['category_suggestion'];
        @endphp
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="font-weight-bold">Grup #{{ $groupNo }}</span>
                <span class="text-muted" style="font-size:.8rem">{{ $group['items']->count() }} item mirip</span>
            </div>
            <form action="{{ route('duplikat-item.terapkan') }}" method="POST" onsubmit="return confirmTerapkan(this)">
                @csrf
                <input type="hidden" name="mode" class="js-mode" value="merge">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="sku-thead">
                            <tr>
                                <th style="width:7%" class="text-center">Target</th>
                                <th style="width:7%" class="text-center">Ikut Diproses</th>
                                <th>Barcode</th>
                                <th>Nama Barang</th>
                                <th>Nama Variasi</th>
                                @if($sugg)
                                    <th>Nama Variasi Baru</th>
                                @endif
                                <th>Kategori</th>
                                <th>Grade</th>
                                <th>Supplier &amp; Harga Modal</th>
                                <th class="text-right">Stok</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($group['items'] as $item)
                                <tr>
                                    <td class="text-center">
                                        <input type="radio" name="target_id_variasi" value="{{ $item['id_variasi'] }}"
                                               {{ $item['id_variasi'] == $group['default_target_id'] ? 'checked' : '' }}>
                                    </td>
                                    <td class="text-center">
                                        <input type="checkbox" name="merge_ids[]" value="{{ $item['id_variasi'] }}" checked>
                                    </td>
                                    <td>{{ $item['barcode'] }}</td>
                                    <td>{{ $item['nama_barang'] }}</td>
                                    <td>
                                        {{ $item['nama_variasi'] }}
                                        @if($item['part_number'])
                                            <div class="text-muted" style="font-size:.75rem">PN: {{ $item['part_number'] }}</div>
                                        @endif
                                    </td>
                                    @if($sugg)
                                        <td>
                                            <input type="text" name="nama_variasi_baru[{{ $item['id_variasi'] }}]"
                                                   class="form-control form-control-sm"
                                                   value="{{ $sugg['item_previews'][$item['id_variasi']] ?? '' }}">
                                        </td>
                                    @endif
                                    <td>{{ $item['kategori'] }}</td>
                                    <td style="min-width:140px;">
                                        @php
                                            $gradeLabel = ['G' => 'Original', 'B' => 'KW', 'L' => 'Lelangan', 'AFTERMARKET' => 'Aftermarket'][$item['grade']] ?? $item['grade'];
                                            $gradeBadge = ['G' => 'badge-success', 'B' => 'badge-warning', 'L' => 'badge-danger', 'AFTERMARKET' => 'badge-secondary'][$item['grade']] ?? 'badge-light';
                                        @endphp
                                        <span class="badge {{ $gradeBadge }}">{{ $gradeLabel }}</span>
                                        @if($sugg)
                                            <select name="tier_override[{{ $item['id_variasi'] }}]"
                                                    class="form-control form-control-sm mt-1" style="font-size:.75rem;">
                                                @foreach($tierOptions as $opt)
                                                    <option value="{{ $opt }}" {{ $opt === $gradeLabel ? 'selected' : '' }}>{{ $opt }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                    </td>
                                    <td style="font-size:.8rem">
                                        @forelse($item['suppliers'] as $sv)
                                            <div>{{ $sv['nama_supplier'] }} — Rp{{ number_format($sv['harga_beli'], 0, ',', '.') }}</div>
                                        @empty
                                            <span class="text-muted">-</span>
                                        @endforelse
                                    </td>
                                    <td class="text-right">{{ rtrim(rtrim(number_format($item['stock'], 2, '.', ''), '0'), '.') ?: '0' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($sugg)
                    <div class="px-3 py-1">
                        <small class="text-muted">
                            <i class="fas fa-info-circle mr-1"></i>"Ikut Diproses" artinya: ikut <strong>digabung</strong> kalau
                            klik "Kategorikan &amp; Gabungkan", atau ikut <strong>dikategorikan tanpa digabung</strong> (tetap SKU
                            sendiri-sendiri, cuma sama-sama masuk Master Barang ini) kalau klik "Kategorikan Saja". Dropdown di
                            bawah badge "Grade" sudah terisi hasil deteksi otomatis — ganti manual kalau perlu, misalnya naikkan
                            "Aftermarket" jadi "Aftermarket A/B/C" untuk item yang kualitasnya beda dari sesama Aftermarket.
                        </small>
                    </div>
                @endif

                @if($sugg)
                    <div class="border-top">
                        <div class="card-body bg-light">
                            <h6 class="font-weight-bold text-muted mb-2">
                                <i class="fas fa-magic mr-1"></i>Saran Kategorisasi (item target masih di "BELUM DIKATEGORIKAN")
                            </h6>
                            <p class="small text-muted mb-1">
                                Hasil parsing teks nama item secara otomatis — <strong>cek &amp; sunting dulu</strong> sebelum diterapkan,
                                parsing tidak selalu 100% akurat (terutama kalau ada kode model kendaraan yang belum ada di data
                                kendaraan, mis. "T120SS").
                            </p>
                            <p class="small text-muted mb-3">
                                Kategorisasi diterapkan ke item target <strong>dulu</strong>, baru item lain digabungkan ke target.
                                Kata-kata di "Nama Barang" &amp; kendaraan yang dipilih akan <strong>dihapus dari nama variasi item
                                target</strong> (sisanya, termasuk no. part &amp; tag grade, tetap). Nama lama otomatis dibackup di
                                <a href="{{ route('duplikat-item.riwayat-kategorisasi') }}" target="_blank">Riwayat Kategorisasi</a>
                                kalau perlu dikembalikan. Kosongkan "Nama Barang" kalau cuma mau gabung tanpa kategorisasi.
                            </p>
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <label class="small font-weight-bold mb-1">Nama Barang (Master)</label>
                                    <select class="form-control form-control-sm select2 js-pilih-mbarang" style="width:100%">
                                        <option value="" {{ $sugg['matched_mbarang'] ? '' : 'selected' }}></option>
                                        @foreach($mbarangNames as $name)
                                            <option value="{{ $name }}"
                                                {{ mb_strtolower($name) === mb_strtolower($sugg['tipe_part']) ? 'selected' : '' }}>
                                                {{ $name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <input type="text" name="nama_barang" class="form-control form-control-sm js-nama-barang-final mt-1"
                                           value="{{ $sugg['tipe_part'] }}">
                                    @if($sugg['matched_mbarang'])
                                        <small class="text-success">
                                            <i class="fas fa-check-circle"></i> Cocok dengan Master Barang yang sudah ada.
                                        </small>
                                    @else
                                        <small class="text-muted">
                                            Pilih dari dropdown kalau sudah ada yang cocok (otomatis isi kotak di bawahnya), atau
                                            edit langsung kotak di bawah untuk nama baru.
                                        </small>
                                    @endif
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label class="small font-weight-bold mb-1">No. Part</label>
                                    <input type="text" name="part_number" class="form-control form-control-sm"
                                           value="{{ $sugg['part_number'] }}">
                                </div>
                                <div class="col-md-5 mb-2">
                                    <label class="small font-weight-bold mb-1">Kecocokan Kendaraan</label>
                                    <select name="vehicle_generation_ids[]" multiple class="form-control form-control-sm select2"
                                            style="width:100%" data-placeholder="Cari kendaraan...">
                                        @foreach($vehicleOptions as $manufacturer => $vehicleGroups)
                                            @foreach($vehicleGroups as $vehicleName => $vehicle)
                                                <optgroup label="{{ $manufacturer }} - {{ $vehicleName }}">
                                                    @foreach($vehicle->generations as $gen)
                                                        <option value="{{ $gen->id }}"
                                                            {{ in_array($gen->id, $sugg['generation_ids']) ? 'selected' : '' }}>
                                                            {{ $vehicleName }} {{ $gen->code }}@if($gen->start_year) {{ substr($gen->start_year, -2) }}-{{ $gen->end_year ? substr($gen->end_year, -2) : 'now' }} @endif @if($gen->nickname) ({{ $gen->nickname }})@endif
                                                        </option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Ketik untuk cari, klik untuk pilih/batalkan.</small>
                                </div>
                            </div>
                            <p class="small text-muted mb-0">
                                Kolom <strong>"Nama Variasi Baru"</strong> di tabel atas sudah terisi pratinjau per item (kata
                                merk/brand tidak ikut dihapus, jadi tetap beda satu sama lain) — sunting manual kalau "Nama
                                Barang"/kendaraan di atas diubah, supaya tetap sesuai.
                            </p>
                        </div>
                    </div>
                @endif

                <div class="card-footer bg-white text-right">
                    @if($sugg)
                        <button type="submit" class="btn btn-outline-primary btn-sm" onclick="return prepareSubmit(this, false)">
                            <i class="fas fa-tag mr-1"></i>Kategorikan Saja
                        </button>
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return prepareSubmit(this, true)">
                            <i class="fas fa-code-branch mr-1"></i>Kategorikan &amp; Gabungkan
                        </button>
                    @else
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return prepareSubmit(this, true)">
                            <i class="fas fa-code-branch mr-1"></i>Gabungkan Grup Ini
                        </button>
                    @endif
                </div>
            </form>
        </div>
    @endforeach

    {{ $groups->links('pagination::bootstrap-4') }}
</div>
@endsection

@section('scripts')
<script>
$('#fuzzyToggle').on('change', function () {
    var params = { fuzzy: $(this).is(':checked') ? 1 : 0 };
    @if($barcodeSearch !== '')
        params.barcode = @json($barcodeSearch);
    @endif
    window.location.href = '{{ route('duplikat-item.index') }}?' + $.param(params);
});

$('.js-pilih-mbarang').on('select2:select', function (e) {
    $(this).closest('form').find('.js-nama-barang-final').val(e.params.data.id);
});

function prepareSubmit(button, allowMerge) {
    var form = button.closest('form');
    form.querySelector('.js-mode').value = allowMerge ? 'merge' : 'categorize_only';
    return true;
}

function confirmTerapkan(form) {
    var mode = form.querySelector('.js-mode').value;
    var namaBarangInput = form.querySelector('input[name="nama_barang"]');
    var checkedCount = form.querySelectorAll('input[name="merge_ids[]"]:checked').length;
    var willCategorize = namaBarangInput && namaBarangInput.value.trim() !== '';

    if (mode === 'merge' && !willCategorize && checkedCount < 2) {
        alert('Isi "Nama Barang" untuk mengategorikan, dan/atau pilih minimal 2 item (termasuk target) untuk digabungkan.');
        return false;
    }
    if (mode === 'categorize_only' && !willCategorize) {
        alert('Isi "Nama Barang" untuk mengategorikan.');
        return false;
    }

    var messages = [];
    if (mode === 'categorize_only') {
        messages.push(checkedCount + ' item yang dicentang akan dikategorikan ke Master Barang yang sama, TANPA digabung ' +
            '(tetap SKU/barcode sendiri-sendiri) — pastikan tiap kolom "Nama Variasi Baru" sudah benar per item');
    } else {
        if (willCategorize) {
            messages.push('item target akan dikategorikan dulu — pastikan "Nama Barang" & "No. Part" sudah benar-benar bersih ' +
                '(cuma tipe part / nomor part, bukan sisa nama kendaraan/merk), karena keduanya akan dihapus dari nama variasi target');
        }
        if (checkedCount >= 2) {
            messages.push('item selain target akan dinonaktifkan (bukan dihapus), stok & data supplier dipindah ke target');
        }
    }

    Swal.fire({
        title: 'Konfirmasi',
        text: 'Lanjutkan? ' + messages.join('; lalu ') + '.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, lanjutkan',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) form.submit();
    });

    return false;
}
</script>
@endsection
