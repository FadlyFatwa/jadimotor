@extends('layouts.main')

@section('title', isset($vehicle) ? 'Edit Kendaraan' : 'Tambah Kendaraan')

@section('content_header')
    <h1>{{ isset($vehicle) ? 'Edit' : 'Tambah' }} Kendaraan</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <a href="{{ route('kendaraan.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
        <div class="card-body">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <form action="{{ isset($vehicle) ? route('kendaraan.update', $vehicle) : route('kendaraan.store') }}" method="POST">
                        @csrf
                        @if(isset($vehicle)) @method('PUT') @endif

                        <div class="form-group">
                            <label>Nama Kendaraan <span class="text-danger">*</span></label>
                            <input type="text" name="name"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $vehicle->name ?? '') }}"
                                placeholder="Contoh: Yamaha NMAX, Honda Beat">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label>Pabrikan (Manufacturer) <span class="text-danger">*</span></label>
                            <input type="text" name="manufacturer"
                                class="form-control @error('manufacturer') is-invalid @enderror"
                                value="{{ old('manufacturer', $vehicle->manufacturer ?? '') }}"
                                placeholder="Contoh: Yamaha, Honda, Suzuki">
                            @error('manufacturer')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('kendaraan.index') }}" class="btn btn-secondary">Batal</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> {{ isset($vehicle) ? 'Simpan Perubahan' : 'Simpan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop
