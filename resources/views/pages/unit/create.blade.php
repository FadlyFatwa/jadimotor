@extends('layouts.main')

@section('title', 'Tambah Unit')

@section('header')
    <h1>Tambah Unit</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('unit.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label>Kode Unit</label>
                    <input type="text" name="kode_unit" class="form-control @error('kode_unit') is-invalid @enderror" value="{{ old('kode_unit') }}">
                    @error('kode_unit')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label>Nama Unit</label>
                    <input type="text" name="nama_unit" class="form-control @error('nama_unit') is-invalid @enderror" value="{{ old('nama_unit') }}">
                    @error('nama_unit')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </form>
        </div>
    </div>
@stop