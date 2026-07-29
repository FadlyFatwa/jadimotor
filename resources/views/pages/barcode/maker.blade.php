@extends('layouts.main')
@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-barcode mr-2"></i>Print Barcode</h3>
            <div class="card-tools">
                <a href="{{ url()->previous() }}" class="btn btn-warning btn-sm">
                    <i class="fas fa-undo mr-1"></i> Kembali
                </a>
            </div>
        </div>
        <div class="card-body">
            @if(count($items) > 0)
            <form id="form-print" action="" method="GET" target="_blank">
                @csrf
                <input type="hidden" name="ids" value="{{ request('ids') }}">

                <!-- Pilih Format Label -->
                <div class="form-group">
                    <label for="form">Pilih Format Label</label>
                    <select name="form" id="form" class="form-control" required>
                        <option value="barcode_107">Label 107</option>
                        <option value="barcode_101">Label 101 (Label Besar)</option>
                        <option value="barcode_fanbelt">Label Fanbelt</option>
                    </select>
                </div>

                <!-- Input Start Kolom & Baris -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="start_col">Start Kolom</label>
                        <input type="number" name="start_col" id="start_col" class="form-control" required min="1" max="3" value="1">
                    </div>
                    <div class="col-md-6">
                        <label for="start_row">Start Baris</label>
                        <input type="number" name="start_row" id="start_row" class="form-control" required min="1" max="10" value="1">
                    </div>
                </div>

                <!-- Tabel Item Print -->
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Barcode</th>
                            <th width="50%" id="col-nama-barang1">Nama Barang</th>
                            <th width="15%" id="col-nama-barang2" style="display: none;">Nama Barang</th>
                            <th width="15%" id="col-nama-mobil" style="display: none;">Nama Mobil</th>
                            <th width="15%" id="col-no-part" style="display: none;">Merk / No. Part</th>
                            <th width="15%" id="col-tipe" style="display: none;">Ukuran / Tipe Fanbelt</th>
                            <th width="10%">Karakter</th>
                            <th width="10%">Jumlah Print</th>
                            <th width="10%">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                        <tr>
                            <td>
                                <img src="data:image/png;base64,{{ $item['barcode'] }}" alt="Barcode">
                                <br>{{ $item['barcode_number'] }}
                            </td>
                            <td class="nama-barang1-col">{{ $item['nama_barang'] }}</td>
                            <td class="nama-barang2-col" style="display: none;">
                                <input type="text" name="nama_barang2[{{ $item['id'] }}]" class="form-control" value="{{ $item['nama_barang'] }}">
                            </td>
                            <td class="nama-mobil-col" style="display: none;">
                                <input type="text" name="nama_mobil[{{ $item['id'] }}]" class="form-control" placeholder="Contoh: Toyota Avanza">
                            </td>
                            <td class="no-part-col" style="display: none;">
                                <input type="text" name="no_part[{{ $item['id'] }}]" class="form-control" placeholder="Contoh: DENSO, AC Delco">
                            </td>
                            <td class="tipe-col" style="display: none;">
                                <input type="text" name="tipe[{{ $item['id'] }}]" class="form-control" placeholder="Contoh: 120cm x 10mm">
                            </td>
                            <td>
                                <input type="number" name="max_characters[{{ $item['id'] }}]" class="form-control" min="1" value="30">
                            </td>
                            <td>
                                <input type="number" name="quantity[{{ $item['id'] }}]" class="form-control" min="1" value="{{ $item['jumlah'] }}">
                            </td>
                            <td>
                                <input type="date" name="date[{{ $item['id'] }}]" class="form-control" value="{{ $item['tanggal'] }}">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- Tombol Submit -->
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-print mr-1"></i> Print Barcode Terpilih
                </button>
            </form>
            @else
            <div class="alert alert-danger">
                <strong>Error!</strong> Tidak ada data barang yang dipilih.
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .nama-barang1-col,
    .nama-barang2-col,
    .nama-mobil-col,
    .no-part-col,
    .tipe-col {
        display: none;
    }
</style>
@endsection

@section('scripts')> 
<script>
$(document).ready(function () {
    // Toggle kolom tambahan berdasarkan pilihan label
    $('#form').on('change', function () {
        var selectedForm = $(this).val();

        if (selectedForm === 'barcode_107') {
            $('.nama-barang1-col').show();
            $('.nama-barang2-col, .nama-mobil-col, .no-part-col, .tipe-col').hide()
                .find('input').prop('required', false);
            $('#col-nama-barang1').show();
            $('#col-nama-barang2, #col-nama-mobil, #col-no-part, #col-tipe').hide();
            $('#form-print').attr('action', "{{ route('barcode.print.template') }}");

            $('#start_col').attr({ min: 1, max: 3 }).val(1);
            $('#start_row').attr({ min: 1, max: 10 }).val(1);

        } else if (selectedForm === 'barcode_101') {
            $('.nama-barang2-col, .nama-mobil-col, .no-part-col').show()
                .find('input').prop('required', true);
            $('.nama-barang1-col, .tipe-col').hide()
                .find('input').prop('required', false);
            $('#col-nama-barang2, #col-nama-mobil, #col-no-part').show();
            $('#col-nama-barang1, #col-tipe').hide();
            $('#form-print').attr('action', "{{ route('barcode.print.template.101') }}");

            $('#start_col').attr({ min: 1, max: 2 }).val(1);
            $('#start_row').attr({ min: 1, max: 3 }).val(1);

        } else if (selectedForm === 'barcode_fanbelt') {
            $('.nama-barang2-col, .tipe-col, .nama-mobil-col').show()
                .find('input').prop('required', true);
            $('.nama-barang1-col, .no-part-col').hide()
                .find('input').prop('required', false);
            $('#col-nama-barang2, #col-nama-mobil, #col-tipe').show();
            $('#col-nama-barang1, #col-no-part').hide();
            $('#form-print').attr('action', "{{ route('barcode.print.template.fanbelt') }}");

            $('#start_col').attr({ min: 1, max: 2 }).val(1);
            $('#start_row').attr({ min: 1, max: 3 }).val(1);
        }
    });

    // Set default saat halaman pertama kali dimuat
    $('#form').trigger('change');
});
</script>
@endsection