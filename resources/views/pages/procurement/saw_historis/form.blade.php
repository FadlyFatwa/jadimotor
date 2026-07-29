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

                {{-- ── C2 + C3 ── --}}
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>
                                <span class="badge badge-primary mr-1">C2</span>
                                Termin Pembayaran
                            </label>
                            <select name="termin_pembayaran"
                                    class="form-control @error('termin_pembayaran') is-invalid @enderror">
                                <option value="">-- Pilih --</option>
                                @php
                                    $terminOptions = [
                                        1 => ['label' => '1 — Cash / Bayar di Muka',        'sub' => 'Pembayaran sebelum atau saat barang diterima'],
                                        2 => ['label' => '2 — Tempo 14 Hari',                'sub' => 'Pembayaran paling lambat 14 hari setelah terima barang'],
                                        3 => ['label' => '3 — Tempo 30 Hari',                'sub' => 'Pembayaran paling lambat 30 hari setelah terima barang'],
                                        4 => ['label' => '4 — Tempo 60 Hari',                'sub' => 'Pembayaran paling lambat 60 hari setelah terima barang'],
                                        5 => ['label' => '5 — Tempo 90 Hari atau Lebih',     'sub' => 'Pembayaran 90 hari ke atas / konsinyasi'],
                                    ];
                                    $oldTermin = old('termin_pembayaran', $historis->termin_pembayaran ?? '');
                                @endphp
                                @foreach($terminOptions as $val => $opt)
                                    <option value="{{ $val }}" {{ $oldTermin == $val ? 'selected' : '' }}>
                                        {{ $opt['label'] }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">
                                Jangka waktu pembayaran yang diberikan supplier. Lebih panjang = lebih baik (benefit).
                            </small>
                            @error('termin_pembayaran')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>
                                <span class="badge badge-primary mr-1">C3</span>
                                Lead Time (Hari)
                            </label>
                            <input type="text" inputmode="decimal" name="lead_time"
                                   value="{{ old('lead_time', $historis->lead_time ?? '') }}"
                                   class="form-control @error('lead_time') is-invalid @enderror"
                                   placeholder="contoh: 7 atau 2,5"
                                   oninput="this.value = this.value.replace(',', '.')">
                            <small class="form-text text-muted">
                                Rata-rata hari dari pemesanan sampai barang tiba. Lebih kecil = lebih baik (cost).
                            </small>
                            @error('lead_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- ── C4 + C5 ── --}}
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>
                                <span class="badge badge-primary mr-1">C4</span>
                                Akurasi Kuantitas
                                <small class="text-muted">(%)</small>
                            </label>
                            <input type="text" inputmode="decimal" name="akurasi_kuantitas"
                                   value="{{ old('akurasi_kuantitas', $historis->akurasi_kuantitas ?? '') }}"
                                   class="form-control @error('akurasi_kuantitas') is-invalid @enderror"
                                   placeholder="contoh: 95,5"
                                   oninput="this.value = this.value.replace(',', '.')">
                            <small class="form-text text-muted">
                                % barang diterima sesuai qty yang dipesan. Lebih tinggi = lebih baik (benefit).
                            </small>
                            @error('akurasi_kuantitas')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>
                                <span class="badge badge-primary mr-1">C5</span>
                                Tingkat Pemenuhan
                                <small class="text-muted">(%)</small>
                            </label>
                            <input type="text" inputmode="decimal" name="tingkat_pemenuhan"
                                   value="{{ old('tingkat_pemenuhan', $historis->tingkat_pemenuhan ?? '') }}"
                                   class="form-control @error('tingkat_pemenuhan') is-invalid @enderror"
                                   placeholder="contoh: 88"
                                   oninput="this.value = this.value.replace(',', '.')">
                            <small class="form-text text-muted">
                                % order yang dipenuhi tepat waktu. Lebih tinggi = lebih baik (benefit).
                            </small>
                            @error('tingkat_pemenuhan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- ── C6 ── --}}
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>
                                <span class="badge badge-primary mr-1">C6</span>
                                Komunikasi
                            </label>
                            <select name="komunikasi"
                                    class="form-control @error('komunikasi') is-invalid @enderror">
                                <option value="">-- Pilih --</option>
                                @php
                                    $komOptions = [
                                        1 => ['label' => '1 — Sangat Buruk',    'sub' => 'Sangat sulit dihubungi, tidak responsif, informasi tidak jelas'],
                                        2 => ['label' => '2 — Buruk',           'sub' => 'Sering lambat merespons, kadang informasi tidak akurat'],
                                        3 => ['label' => '3 — Cukup',           'sub' => 'Merespons dalam waktu wajar, informasi cukup jelas'],
                                        4 => ['label' => '4 — Baik',            'sub' => 'Responsif dan informatif, jarang ada miskomunikasi'],
                                        5 => ['label' => '5 — Sangat Baik',     'sub' => 'Sangat responsif, proaktif memberikan update, tidak ada miskomunikasi'],
                                    ];
                                    $oldKom = old('komunikasi', $historis->komunikasi ?? '');
                                @endphp
                                @foreach($komOptions as $val => $opt)
                                    <option value="{{ $val }}" {{ $oldKom == $val ? 'selected' : '' }}>
                                        {{ $opt['label'] }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">
                                Responsivitas dan kejelasan komunikasi supplier. Lebih tinggi = lebih baik (benefit).
                            </small>
                            @error('komunikasi')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
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
