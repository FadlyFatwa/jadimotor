@extends('layouts.main')

@section('title', 'Tambah Kategori')

@section('content_header')
    <h1>Tambah Kategori</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <a href="{{ route('kategori.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
        <div class="card-body">
            <form action="{{ route('kategori.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label>Kode Kategori <span class="text-danger">*</span></label>
                    <input type="text" name="kode_kategori" maxlength="10"
                        class="form-control @error('kode_kategori') is-invalid @enderror"
                        value="{{ old('kode_kategori') }}" placeholder="Maks. 10 karakter">
                    @error('kode_kategori')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label>Nama Kategori <span class="text-danger">*</span></label>
                    <input type="text" name="nama_kategori"
                        class="form-control @error('nama_kategori') is-invalid @enderror"
                        value="{{ old('nama_kategori') }}" id="nama_kategori">
                    @error('nama_kategori')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label>Slug <small class="text-muted">(otomatis dari nama, bisa diubah)</small></label>
                    <input type="text" name="slug"
                        class="form-control @error('slug') is-invalid @enderror"
                        value="{{ old('slug') }}" id="slug" placeholder="contoh: spare-part-mesin">
                    @error('slug')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="description" rows="3"
                        class="form-control @error('description') is-invalid @enderror"
                        placeholder="Deskripsi singkat kategori...">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan
                </button>
            </form>
        </div>
    </div>
@stop

@section('js')
<script>
    document.getElementById('nama_kategori').addEventListener('input', function () {
        const slug = this.value.toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
        document.getElementById('slug').value = slug;
    });
</script>
@stop
