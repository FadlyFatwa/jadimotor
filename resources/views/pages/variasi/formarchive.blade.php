@extends('layouts.main')

@section('title', isset($variasi) ? 'Edit Barang' : 'Tambah Barang')

@section('content_header')
    <h1 class="text-center">{{ isset($variasi) ? 'Edit Barang' : 'Tambah Barang' }}</h1>
@stop

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white text-center">
                    <h3 class="card-title mb-0">Form {{ isset($variasi) ? 'Edit' : 'Tambah' }} Barang</h3>
                </div>
                <div class="card-body p-4">
                    <form action="{{ isset($variasi) ? route('barang.update', $variasi->id_variasi) : route('barang.store') }}" method="POST">
                        @csrf
                        @if(isset($variasi))
                            @method('PUT')
                        @endif

                        <!-- Barcode -->
                        <div class="form-group mb-3 text-center">
                            <label class="form-label">Barcode</label>
                            <input type="text" name="barcode" class="form-control form-control @error('barcode') is-invalid @enderror text-center"
                                value="{{ old('barcode', $variasi->barcode ?? $nextBarcode ?? '') }}"
                                {{ isset($variasi) ? 'readonly' : '' }}>
                            @error('barcode')
                                <div class="invalid-feedback d-block text-center">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Nama Barang -->
                        <div class="form-group mb-3 text-center">
                            <label class="form-label">Nama Barang</label>
                            <input type="text" name="nama_variasi" class="form-control form-control @error('nama_variasi') is-invalid @enderror text-center"
                                value="{{ old('nama_variasi', $variasi->nama_variasi ?? '') }}"
                                placeholder="Masukkan nama variasi">
                            @error('nama_variasi')
                                <div class="invalid-feedback d-block text-center">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Kategori -->
                        {{-- <div class="form-group mb-3 text-center">
                            <label class="form-label">Kategori</label>
                            <select name="ID_Kategori" class="form-select form-select select2 @error('ID_Kategori') is-invalid @enderror w-100 mx-auto" style="display: inline-block;">
                                <option value="">-- Pilih Kategori --</option>
                                @foreach($kategoris as $kategori)
                                    <option value="{{ $kategori->ID_Kategori }}"
                                        {{ (old('ID_Kategori', $variasi->ID_Kategori ?? '')) == $kategori->ID_Kategori ? 'selected' : '' }}>
                                        {{ $kategori->nama_kategori }}
                                    </option>
                                @endforeach
                            </select>
                            @error('ID_Kategori')
                                <div class="invalid-feedback d-block text-center">{{ $message }}</div>
                            @enderror
                        </div> --}}

                        <!-- Supplier -->
                        <div class="form-group mb-3 text-center">
                            <label class="form-label">Supplier</label>
                            <select name="id_unit" class="form-select form-select select2 @error('id_unit') is-invalid @enderror w-100 mx-auto" style="display: inline-block;">
                                <option value="">-- Pilih Supplier --</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id_unit }}"
                                        {{ (old('id_unit', $variasi->id_unit ?? '') == $supplier->id_unit) ? 'selected' : '' }}>
                                        {{ $supplier->nama_supplier }}
                                    </option>
                                @endforeach
                            </select>
                            @error('id_unit')
                                <div class="invalid-feedback d-block text-center">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- MBarang -->
                        <div class="form-group mb-3 text-center">
                            <label class="form-label">MBarang</label>
                            <select name="ID_MBarang" class="form-select form-select select2 @error('ID_MBarang') is-invalid @enderror w-100 mx-auto" style="display: inline-block;">
                                <option value="">-- Pilih MBarang --</option>
                                @foreach($m_barangs as $m_barang)
                                    <option value="{{ $m_barang->ID_MBarang }}"
                                        {{ (old('ID_MBarang', $variasi->ID_MBarang ?? '') == $m_barang->ID_MBarang) ? 'selected' : '' }}>
                                        {{ $m_barang->nama_m_barang }}
                                    </option>
                                @endforeach
                            </select>
                            @error('ID_MBarang')
                                <div class="invalid-feedback d-block text-center">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Modal -->
                        <div class="form-group mb-3 text-center">
                            <label class="form-label">Harga Beli (Modal)</label>
                            <input type="text" name="modal" id="modal" class="form-control @error('modal') is-invalid @enderror text-center"
                                value="{{ old('modal', isset($variasi) ? number_format($variasi->modal, 0, ',', '.') : '') }}"
                                oninput="formatNumber(this); generateKodeModal()" placeholder="Masukkan harga beli">
                            @error('modal')
                                <div class="invalid-feedback d-block text-center">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Kode Modal -->
                        <div class="form-group mb-3 text-center">
                            <label class="form-label">Kode Modal</label>
                            <input type="text" name="kode_modal" id="kode_modal" class="form-control text-center"
                                value="{{ old('kode_modal', $variasi->kode_modal ?? '') }}">
                        </div>

                        <!-- Harga Jual -->
                        <div class="form-group mb-3 text-center">
                            <label class="form-label">Harga Jual</label>
                            <input type="text" name="harga" id="harga" class="form-control @error('harga') is-invalid @enderror text-center"
                                value="{{ old('harga', isset($variasi) ? number_format($variasi->harga, 0, ',', '.') : '') }}"
                                oninput="formatNumber(this)" placeholder="Masukkan harga jual">
                            @error('harga')
                                <div class="invalid-feedback d-block text-center">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Tombol Simpan & Batal -->
                        <div class="d-flex justify-content-center gap-3 mt-4">
                            <button type="submit" class="btn btn-success btn-sm px-5">
                                <i class="fas fa-save me-2"></i>{{ isset($variasi) ? 'Update' : 'Simpan' }}
                            </button>
                            <a href="{{ route('barang.index') }}" class="btn btn-secondary btn-sm px-5">
                                <i class="fas fa-arrow-left me-2"></i>Kembali
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Format angka menjadi ribuan dengan titik
    function formatNumber(input) {
        let value = input.value.replace(/\D/g, ''); // Hapus semua karakter selain angka
        if (value.length > 0) {
            value = parseInt(value, 10).toLocaleString('id-ID'); // Format ke ribuan dengan titik
        }
        input.value = value;
    }

    // Generate kode modal
    function generateKodeModal() {
        const mapping = {
            '0': 'U', '1': 'A', '2': 'B', '3': 'C', '4': 'D',
            '5': 'E', '6': 'F', '7': 'G', '8': 'H', '9': 'J'
        };

        const modalInput = document.getElementById('modal').value.replace(/\./g, '');
        const numericValue = modalInput.replace(/[^0-9]/g, '');

        if (!numericValue) {
            document.getElementById('kode_modal').value = '';
            return;
        }

        let zeroCount = 0;
        for (let i = numericValue.length - 1; i >= 0; i--) {
            if (numericValue[i] === '0') {
                zeroCount++;
            } else break;
        }

        let kodeModal = '';
        const baseValue = numericValue.slice(0, numericValue.length - zeroCount);
        for (const digit of baseValue) {
            kodeModal += mapping[digit] || '';
        }

        if (zeroCount > 0) {
            kodeModal += 'U';
            if (zeroCount > 1) {
                kodeModal += zeroCount.toString();
            }
        }

        document.getElementById('kode_modal').value = kodeModal;
    }

    // Jalankan generate kode modal saat pertama kali load (untuk edit)
    @if(isset($barang))
        document.addEventListener('DOMContentLoaded', function () {
            generateKodeModal();
        });
    @endif
</script>
@endsection