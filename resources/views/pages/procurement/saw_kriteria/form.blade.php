@extends('layouts.main')
@section('title', isset($kriteria) ? 'Edit Kriteria SAW' : 'Tambah Kriteria SAW')

@section('content')
<div class="container-fluid">

    <div class="card mb-3 shadow-sm">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">
                    {{ isset($kriteria) ? 'Edit' : 'Tambah' }} Kriteria SAW
                </h4>
                <small class="text-muted">Atur kriteria dan bobot yang dipakai dalam perhitungan SAW</small>
            </div>
            <a href="{{ route('saw.kriteria.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Kembali
            </a>
        </div>
    </div>

    @if(isset($kriteria) && preg_match('/^C[1-6]$/', $kriteria->kode))
        <div class="alert alert-warning py-2">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            Kriteria <strong>{{ $kriteria->kode }}</strong> termasuk 6 kriteria bawaan yang nilainya otomatis
            diisi dari data Supplier Inquiry / Historis Supplier. Mengubah <strong>kode</strong> atau menghapus
            kriteria ini dapat memengaruhi perhitungan SAW yang memakai kode tersebut.
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">

            <form method="POST"
                  action="{{ isset($kriteria) ? route('saw.kriteria.update', $kriteria->id) : route('saw.kriteria.store') }}">
                @csrf
                @if(isset($kriteria)) @method('PUT') @endif

                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Kode</label>
                            <input type="text" value="{{ $kriteria->kode ?? $nextKode }}"
                                   class="form-control" readonly disabled>
                            <small class="form-text text-muted">
                                @if(isset($kriteria))
                                    Dibuat otomatis saat kriteria ditambahkan, tidak dapat diubah.
                                @else
                                    Dibuat otomatis oleh sistem (urutan kode berikutnya).
                                @endif
                            </small>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="form-group">
                            <label>Nama Kriteria <span class="text-danger">*</span></label>
                            <input type="text" name="nama" maxlength="100"
                                   value="{{ old('nama', $kriteria->nama ?? '') }}"
                                   class="form-control @error('nama') is-invalid @enderror"
                                   placeholder="contoh: Kualitas Kemasan" required>
                            @error('nama')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Jenis <span class="text-danger">*</span></label>
                            <select name="jenis" class="form-control @error('jenis') is-invalid @enderror" required>
                                @php $oldJenis = old('jenis', $kriteria->jenis ?? ''); @endphp
                                <option value="">-- Pilih --</option>
                                <option value="benefit" {{ $oldJenis === 'benefit' ? 'selected' : '' }}>Benefit (lebih besar lebih baik)</option>
                                <option value="cost" {{ $oldJenis === 'cost' ? 'selected' : '' }}>Cost (lebih kecil lebih baik)</option>
                            </select>
                            @error('jenis')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Bobot <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.05" min="0" max="1" name="bobot"
                                       value="{{ old('bobot', $kriteria->bobot ?? '') }}"
                                       class="form-control @error('bobot') is-invalid @enderror"
                                       placeholder="contoh: 0.20" required>
                                <div class="input-group-append">
                                    <span class="input-group-text">(0 – 1)</span>
                                </div>
                            </div>
                            <small class="form-text text-muted">Total bobot semua kriteria aktif harus = 1.0 (100%).</small>
                            @error('bobot')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Satuan</label>
                            <input type="text" name="satuan" maxlength="30"
                                   value="{{ old('satuan', $kriteria->satuan ?? '') }}"
                                   class="form-control @error('satuan') is-invalid @enderror"
                                   placeholder="contoh: Rp, %, Hari, Skala">
                            @error('satuan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Urutan</label>
                            <input type="number" min="0" name="urutan"
                                   value="{{ old('urutan', $kriteria->urutan ?? 0) }}"
                                   class="form-control @error('urutan') is-invalid @enderror">
                            <small class="form-text text-muted">Urutan tampil kriteria, angka kecil ditampilkan lebih dulu.</small>
                            @error('urutan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4 d-flex align-items-center">
                        <div class="form-group form-check mt-4">
                            <input type="checkbox" name="is_active" id="is_active" class="form-check-input"
                                   value="1" {{ old('is_active', $kriteria->is_active ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Aktif (diikutsertakan dalam perhitungan SAW)
                            </label>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    <a href="{{ route('saw.kriteria.index') }}" class="btn btn-outline-secondary mr-2">
                        Batal
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i>
                        {{ isset($kriteria) ? 'Simpan Perubahan' : 'Tambah Kriteria' }}
                    </button>
                </div>

            </form>
        </div>
    </div>

</div>
@endsection
