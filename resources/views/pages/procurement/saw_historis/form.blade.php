@extends('layouts.main')
@section('title', isset($historis) ? 'Edit Historis SAW' : 'Tambah Historis SAW')

@section('content')
<div class="container-fluid">

    {{-- HEADER --}}
    <div class="card mb-3 shadow-sm">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">
                    {{ isset($historis) ? 'Edit' : 'Tambah' }} Data Historis SAW
                </h4>
                <small class="text-muted">Rekam performa historis supplier (digunakan sebagai input kriteria SAW)</small>
            </div>
            <a href="{{ route('saw.historis.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Kembali
            </a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">

            <form method="POST"
                  action="{{ isset($historis) ? route('saw.historis.update', $historis->id) : route('saw.historis.store') }}">
                @csrf
                @if(isset($historis)) @method('PUT') @endif

                {{-- ERROR SUMMARY --}}
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- ── Supplier ── --}}
                <div class="form-group">
                    <label>Supplier <span class="text-danger">*</span></label>
                    <select name="supplier_id"
                            id="supplier_id"
                            class="form-control @error('supplier_id') is-invalid @enderror"
                            data-placeholder="-- Pilih Supplier --"
                            style="width:100%"
                            required>
                        <option value=""></option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id_supplier }}"
                                {{ old('supplier_id', $historis->supplier_id ?? '') == $s->id_supplier ? 'selected' : '' }}>
                                {{ $s->nama_supplier }}
                            </option>
                        @endforeach
                    </select>
                    @error('supplier_id')
                        <span class="text-danger small">{{ $message }}</span>
                    @enderror
                </div>

                {{-- ── Periode ── --}}
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Periode Mulai <span class="text-danger">*</span></label>
                            <input type="date" name="periode_mulai"
                                   value="{{ old('periode_mulai', isset($historis) ? $historis->periode_mulai->format('Y-m-d') : '') }}"
                                   class="form-control @error('periode_mulai') is-invalid @enderror" required>
                            @error('periode_mulai')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Periode Akhir <span class="text-danger">*</span></label>
                            <input type="date" name="periode_akhir"
                                   value="{{ old('periode_akhir', isset($historis) ? $historis->periode_akhir->format('Y-m-d') : '') }}"
                                   class="form-control @error('periode_akhir') is-invalid @enderror" required>
                            @error('periode_akhir')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Jumlah Transaksi</label>
                            <input type="number" name="jumlah_transaksi" min="0"
                                   value="{{ old('jumlah_transaksi', $historis->jumlah_transaksi ?? 0) }}"
                                   class="form-control @error('jumlah_transaksi') is-invalid @enderror">
                            @error('jumlah_transaksi')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <hr>
                <h6 class="mb-3 text-primary">
                    <i class="fas fa-calculator mr-1"></i>
                    Nilai Kriteria SAW
                </h6>

                {{-- ── Nilai per Kriteria (C2 dst — semua kecuali C1 yang dari Supplier Inquiry) ── --}}
                @php
                    $terminOptions = [
                        1 => '1 — Cash / Bayar di Muka',
                        2 => '2 — Tempo 14 Hari',
                        3 => '3 — Tempo 30 Hari',
                        4 => '4 — Tempo 60 Hari',
                        5 => '5 — Tempo 90 Hari atau Lebih',
                    ];
                    $komOptions = [
                        1 => '1 — Sangat Buruk',
                        2 => '2 — Buruk',
                        3 => '3 — Cukup',
                        4 => '4 — Baik',
                        5 => '5 — Sangat Baik',
                    ];
                    $hintByKode = [
                        'C2' => 'Jangka waktu pembayaran yang diberikan supplier. Lebih panjang = lebih baik (benefit).',
                        'C3' => 'Rata-rata hari dari pemesanan sampai barang tiba. Lebih kecil = lebih baik (cost).',
                        'C4' => '% barang diterima sesuai qty yang dipesan. Lebih tinggi = lebih baik (benefit).',
                        'C5' => '% order yang dipenuhi tepat waktu. Lebih tinggi = lebih baik (benefit).',
                        'C6' => 'Responsivitas dan kejelasan komunikasi supplier. Lebih tinggi = lebih baik (benefit).',
                    ];
                @endphp
                <div class="row">
                    @foreach($kriteriaDinamis as $k)
                        @php
                            $fieldName = "nilai_kriteria.{$k->id}";
                            $oldVal = old($fieldName, ($nilaiDinamis ?? collect())[$k->id]->nilai ?? '');
                            $hint = $hintByKode[$k->kode] ?? ($k->jenis === 'benefit'
                                ? 'Lebih tinggi = lebih baik (benefit).'
                                : 'Lebih kecil = lebih baik (cost).');
                        @endphp
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>
                                    <span class="badge badge-primary mr-1">{{ $k->kode }}</span>
                                    {{ $k->nama }}
                                    @if($k->satuan)
                                        <small class="text-muted">({{ $k->satuan }})</small>
                                    @endif
                                </label>

                                @if($k->kode === 'C2')
                                    <select name="nilai_kriteria[{{ $k->id }}]"
                                            class="form-control @error($fieldName) is-invalid @enderror">
                                        <option value="">-- Pilih --</option>
                                        @foreach($terminOptions as $val => $label)
                                            <option value="{{ $val }}" {{ $oldVal == $val ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                @elseif($k->kode === 'C6')
                                    <select name="nilai_kriteria[{{ $k->id }}]"
                                            class="form-control @error($fieldName) is-invalid @enderror">
                                        <option value="">-- Pilih --</option>
                                        @foreach($komOptions as $val => $label)
                                            <option value="{{ $val }}" {{ $oldVal == $val ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="text" inputmode="decimal"
                                           name="nilai_kriteria[{{ $k->id }}]"
                                           value="{{ $oldVal }}"
                                           class="form-control @error($fieldName) is-invalid @enderror"
                                           placeholder="contoh: 4"
                                           oninput="this.value = this.value.replace(',', '.')">
                                @endif

                                <small class="form-text text-muted">{{ $hint }}</small>
                                @error($fieldName)
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- ── Panduan Skala ── --}}
                <div class="row mt-2 mb-3">
                    <div class="col-md-6">
                        <div class="card border-light bg-light">
                            <div class="card-body py-2 px-3">
                                <p class="mb-1 font-weight-bold text-primary" style="font-size:.82rem;">
                                    <i class="fas fa-info-circle mr-1"></i> Skala C2 — Termin Pembayaran
                                </p>
                                <table class="table table-sm table-borderless mb-0" style="font-size:.8rem;">
                                    <tr><td class="py-0"><span class="badge badge-secondary">1</span></td><td class="py-0">Cash / Bayar di Muka</td></tr>
                                    <tr><td class="py-0"><span class="badge badge-warning">2</span></td><td class="py-0">Tempo 14 Hari</td></tr>
                                    <tr><td class="py-0"><span class="badge badge-info">3</span></td><td class="py-0">Tempo 30 Hari</td></tr>
                                    <tr><td class="py-0"><span class="badge badge-primary">4</span></td><td class="py-0">Tempo 60 Hari</td></tr>
                                    <tr><td class="py-0"><span class="badge badge-success">5</span></td><td class="py-0">Tempo 90 Hari atau Lebih</td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-light bg-light">
                            <div class="card-body py-2 px-3">
                                <p class="mb-1 font-weight-bold text-primary" style="font-size:.82rem;">
                                    <i class="fas fa-info-circle mr-1"></i> Skala C6 — Komunikasi
                                </p>
                                <table class="table table-sm table-borderless mb-0" style="font-size:.8rem;">
                                    <tr><td class="py-0"><span class="badge badge-danger">1</span></td><td class="py-0">Sangat Buruk</td></tr>
                                    <tr><td class="py-0"><span class="badge badge-warning">2</span></td><td class="py-0">Buruk</td></tr>
                                    <tr><td class="py-0"><span class="badge badge-secondary">3</span></td><td class="py-0">Cukup</td></tr>
                                    <tr><td class="py-0"><span class="badge badge-info">4</span></td><td class="py-0">Baik</td></tr>
                                    <tr><td class="py-0"><span class="badge badge-success">5</span></td><td class="py-0">Sangat Baik</td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── Catatan ── --}}
                <div class="form-group">
                    <label>Catatan</label>
                    <textarea name="catatan" rows="2" class="form-control"
                              placeholder="Keterangan tambahan (opsional)">{{ old('catatan', $historis->catatan ?? '') }}</textarea>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    <a href="{{ route('saw.historis.index') }}" class="btn btn-outline-secondary mr-2">
                        Batal
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i>
                        {{ isset($historis) ? 'Simpan Perubahan' : 'Tambah Data' }}
                    </button>
                </div>

            </form>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
$(function () {
    $('#supplier_id').select2({
        theme: 'bootstrap-5',
        placeholder: '-- Pilih Supplier --',
        allowClear: true,
        width: '100%',
    });
});
</script>
@endsection
