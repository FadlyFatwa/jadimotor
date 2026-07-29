<!-- Contoh untuk create.blade.php -->
@extends('layouts.main')

@section('title', 'Edit Barang')

@section('content_header')
    <h1>Edit Barang</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('m_barang.update', $m_barang->ID_MBarang) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label>Kode MBarang</label>
                    <input type="text" name="kode_m_barang" class="form-control @error('kode_m_barang') is-invalid @enderror" value="{{ old('kode_m_barang', $m_barang->kode_m_barang) }}">
                    @error('kode_m_barang')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label>Nama MBarang</label>
                    <input type="text" name="nama_m_barang" class="form-control @error('nama_m_barang') is-invalid @enderror" value="{{ old('nama_m_barang', $m_barang->nama_m_barang) }}">
                    @error('nama_m_barang')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label>Unit</label>
                    <select name="id_unit" class="form-control @error('id_unit') is-invalid @enderror">
                        <option value="">Pilih Unit</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id_unit }}" {{ old('id_unit', $m_barang->id_unit) == $unit->id_unit ? 'selected' : '' }}>
                                {{ $unit->nama_unit }}
                            </option>
                        @endforeach
                    </select>
                    @error('id_unit')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary">Update</button>
            </form>
        </div>
    </div>
@stop