<!-- Contoh untuk create.blade.php -->
@extends('layouts.main')

@section('title', 'Edit Barang')

@section('content_header')
    <h1>Edit Barang</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('barang.update', $barang->ID_Barang) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label>Kode Barang</label>
                    <input type="text" name="barcode" class="form-control @error('barcode') is-invalid @enderror" value="{{ old('barcode', $barang->barcode) }}">
                    @error('barcode')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label>Nama Barang</label>
                    <input type="text" name="nama_barang" class="form-control @error('nama_barang') is-invalid @enderror" value="{{ old('nama_barang', $barang->nama_barang) }}">
                    @error('nama_barang')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label>Kategori</label>
                    <select name="id_kategori" class="form-control @error('id_kategori') is-invalid @enderror">
                        <option value="">Pilih Kategori</option>
                        @foreach($kategoris as $kategori)
                            <option value="{{ $kategori->id_kategori }}" {{ old('id_kategori', $barang->id_kategori) == $kategori->id_kategori ? 'selected' : '' }}>
                                {{ $kategori->nama_kategori }}
                            </option>
                        @endforeach
                    </select>
                    @error('id_kategori')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label>Supplier</label>
                    <select name="id_supplier" class="form-control @error('id_supplier') is-invalid @enderror">
                        <option value="">Pilih Supplier</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id_unit }}" {{ old('id_unit', $barang->id_unit) == $supplier->id_unit ? 'selected' : '' }}>
                                {{ $supplier->nama_supplier }}
                            </option>
                        @endforeach
                    </select>
                    @error('id_unit')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label>Unit</label>
                    <select name="id_unit" class="form-control @error('id_unit') is-invalid @enderror">
                        <option value="">Pilih Unit</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id_unit }}" {{ old('id_unit', $barang->id_unit) == $unit->id_unit ? 'selected' : '' }}>
                                {{ $unit->nama_unit }}
                            </option>
                        @endforeach
                    </select>
                    @error('id_unit')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label>Stok</label>
                    <input type="number" name="stock" class="form-control @error('stock') is-invalid @enderror" value="{{ old('stock', $barang->stoCk) }}">
                    @error('stock')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label>Harga Beli</label>
                    <input type="number" step="0.01" name="modal" class="form-control @error('modal') is-invalid @enderror" value="{{ old('modal', $barang->modal) }}">
                    @error('modal')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label>Harga Jual</label>
                    <input type="number" step="0.01" name="harga" class="form-control @error('harga') is-invalid @enderror" value="{{ old('harga', $barang->harga) }}">
                    @error('harga')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary">Update</button>
            </form>
        </div>
    </div>
@stop