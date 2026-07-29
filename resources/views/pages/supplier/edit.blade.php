@extends('layouts.main')

@section('title', 'Edit Supplier')

@section('content_header')
    <h1>Edit Supplier</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('supplier.update', $supplier->id_supplier) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label>Kode Supplier</label>
                    <input type="text" name="kode_supplier" class="form-control @error('kode_supplier') is-invalid @enderror" value="{{ old('kode_supplier', $supplier->kode_supplier) }}">
                    @error('kode_supplier')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label>Nama Supplier</label>
                    <input type="text" name="nama_supplier" class="form-control @error('nama_supplier') is-invalid @enderror" value="{{ old('nama_supplier', $supplier->nama_supplier) }}">
                    @error('nama_supplier')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label>No. Telp</label>
                    <input type="text" name="no_telp" class="form-control @error('no_telp') is-invalid @enderror" value="{{ old('no_telp', $supplier->no_telp) }}">
                    @error('no_telp')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label>Alamat</label>
                    <textarea name="alamat" class="form-control @error('alamat') is-invalid @enderror">{{ old('alamat', $supplier->alamat) }}</textarea>
                    @error('alamat')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary">Update</button>
            </form>
        </div>
    </div>
@stop