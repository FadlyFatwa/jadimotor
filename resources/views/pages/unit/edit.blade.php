@extends('layouts.main')

@section('title', 'Edit Unit')

@section('content_header')
    <h1>Edit Unit</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('unit.update', $unit->id_supplier) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label>Kode Unit</label>
                    <input type="text" name="kode_unit" class="form-control @error('kode_unit') is-invalid @enderror" value="{{ old('kode_unit', $unit->kode_unit) }}">
                    @error('kode_unit')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label>Nama Unit</label>
                    <input type="text" name="nama_unit" class="form-control @error('nama_unit') is-invalid @enderror" value="{{ old('nama_unit', $unit->nama_unit) }}">
                    @error('nama_unit')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary">Update</button>
            </form>
        </div>
    </div>
@stop