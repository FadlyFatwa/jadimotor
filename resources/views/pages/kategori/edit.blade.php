@extends('layouts.main')

@section('title', 'Edit Kategori')

@section('content_header')
    <h1>Edit Kategori</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <a href="{{ route('kategori.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
        <div class="card-body">
            <form action="{{ route('kategori.update', $kategori->id_kategori) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label>Kode Kategori <span class="text-danger">*</span></label>
                    <input type="text" name="kode_kategori" maxlength="10"
                        class="form-control @error('kode_kategori') is-invalid @enderror"
                        value="{{ old('kode_kategori', $kategori->kode_kategori) }}">
                    @error('kode_kategori')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label>Nama Kategori <span class="text-danger">*</span></label>
                    <input type="text" name="nama_kategori"
                        class="form-control @error('nama_kategori') is-invalid @enderror"
                        value="{{ old('nama_kategori', $kategori->nama_kategori) }}">
                    @error('nama_kategori')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label>Slug</label>
                    <input type="text" name="slug"
                        class="form-control @error('slug') is-invalid @enderror"
                        value="{{ old('slug', $kategori->slug) }}">
                    @error('slug')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="description" rows="3"
                        class="form-control @error('description') is-invalid @enderror">{{ old('description', $kategori->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
            </form>
        </div>
    </div>
@stop
