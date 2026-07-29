@extends('layouts.main')

@section('title', isset($m_barang) ? 'Edit Barang' : 'Tambah Barang')

@section('content_header')
    <h1>{{ isset($m_barang) ? 'Edit' : 'Tambah' }} Master Barang</h1>
@stop

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title mb-0">
                        Form {{ isset($m_barang) ? 'Edit' : 'Tambah' }} Barang
                    </h3>
                </div>
                <div class="card-body p-4">
                    <form action="{{ isset($m_barang) ? route('m_barang.update', $m_barang->id_barang) : route('m_barang.store') }}" method="POST">
                        @csrf
                        @if(isset($m_barang))
                            @method('PUT')
                        @endif

                        <div class="form-group">
                            <label>Kode Barang <span class="text-danger">*</span></label>
                            <input type="text" name="kode_barang"
                                class="form-control @error('kode_barang') is-invalid @enderror"
                                value="{{ old('kode_barang', $m_barang->kode_barang ?? '') }}">
                            @error('kode_barang')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label>Nama Barang <span class="text-danger">*</span></label>
                            <input type="text" name="nama_barang"
                                class="form-control @error('nama_barang') is-invalid @enderror"
                                value="{{ old('nama_barang', $m_barang->nama_barang ?? '') }}"
                                placeholder="Nama produk">
                            @error('nama_barang')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label>Kategori <span class="text-danger">*</span></label>
                            <select name="id_kategori" class="form-control select2 @error('id_kategori') is-invalid @enderror">
                                <option value="">-- Pilih Kategori --</option>
                                @foreach($kategoris as $kategori)
                                    <option value="{{ $kategori->id_kategori }}"
                                        {{ old('id_kategori', $m_barang->id_kategori ?? '') == $kategori->id_kategori ? 'selected' : '' }}>
                                        {{ $kategori->nama_kategori }}
                                    </option>
                                @endforeach
                            </select>
                            @error('id_kategori')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label>Deskripsi</label>
                            <textarea name="description" rows="3"
                                class="form-control @error('description') is-invalid @enderror"
                                placeholder="Deskripsi produk (opsional)">{{ old('description', $m_barang->description ?? '') }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1"
                                    class="custom-control-input" id="is_active"
                                    {{ old('is_active', $m_barang->is_active ?? true) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_active">Barang Aktif</label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('m_barang.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> {{ isset($m_barang) ? 'Simpan Perubahan' : 'Simpan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
