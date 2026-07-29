@extends('layouts.main')

@section('title', isset($generasi) ? 'Edit Generasi' : 'Tambah Generasi')

@section('content_header')
    <h1>{{ isset($generasi) ? 'Edit' : 'Tambah' }} Generasi — {{ $kendaraan->name }}</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <a href="{{ route('kendaraan.generasi.index', $kendaraan) }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
        <div class="card-body">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <form action="{{ isset($generasi) ? route('kendaraan.generasi.update', [$kendaraan, $generasi]) : route('kendaraan.generasi.store', $kendaraan) }}" method="POST">
                        @csrf
                        @if(isset($generasi)) @method('PUT') @endif

                        <div class="form-group">
                            <label>Kode Generasi <span class="text-danger">*</span></label>
                            <input type="text" name="code"
                                class="form-control @error('code') is-invalid @enderror"
                                value="{{ old('code', $generasi->code ?? '') }}"
                                placeholder="Contoh: G1, MK1, 2019-2022">
                            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label>Nama / Nickname</label>
                            <input type="text" name="nickname"
                                class="form-control @error('nickname') is-invalid @enderror"
                                value="{{ old('nickname', $generasi->nickname ?? '') }}"
                                placeholder="Contoh: New NMAX, Beat ESP">
                            @error('nickname')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Tahun Mulai</label>
                                <input type="number" name="start_year"
                                    class="form-control @error('start_year') is-invalid @enderror"
                                    value="{{ old('start_year', $generasi->start_year ?? '') }}"
                                    min="1900" max="2100" placeholder="2019">
                                @error('start_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Tahun Akhir <small class="text-muted">(kosongkan jika masih diproduksi)</small></label>
                                <input type="number" name="end_year"
                                    class="form-control @error('end_year') is-invalid @enderror"
                                    value="{{ old('end_year', $generasi->end_year ?? '') }}"
                                    min="1900" max="2100" placeholder="2023">
                                @error('end_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('kendaraan.generasi.index', $kendaraan) }}" class="btn btn-secondary">Batal</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> {{ isset($generasi) ? 'Simpan Perubahan' : 'Simpan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop
