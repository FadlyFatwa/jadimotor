@extends('layouts.main')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-truck mr-2"></i>Daftar Penerimaan</h3>
            <a href="{{ route('penerimaan.create') }}" class="btn btn-sm btn-success float-right">
                <i class="fas fa-plus mr-1"></i>Tambah Penerimaan
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="penerimaanTable" class="table table-bordered table-striped" style="width:100%;">
                    <thead class="thead-dark">
                        <tr>
                            <th>No</th>
                            <th>Invoice</th>
                            <th>Supplier</th>
                            <th>Tanggal Nota</th>
                            <th>Jatuh Tempo</th>
                            <th>Total</th>
                            <th>Status Pembayaran</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- DataTables isi --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- Modal Detail -->
<div class="modal fade" id="detailModal" tabindex="-1" role="dialog" aria-labelledby="detailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detail Penerimaan</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p><strong>Invoice:</strong> <span id="invoice"></span></p>
        <p><strong>Supplier:</strong> <span id="supplier"></span></p>
        <p><strong>Tanggal Nota:</strong> <span id="tanggal_nota"></span></p>
        <p><strong>Metode Pembayaran:</strong> <span id="metode_pembayaran"></span></p>
        <hr>
        <table class="table table-bordered" id="detailTable">
          <thead>
            <tr>
              <th>Nama Barang</th>
              <th>Jumlah</th>
              <th>Harga</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
        <hr>
        <p><strong>Subtotal:</strong> <span id="subtotal"></span></p>
        <p><strong>PPN (11%):</strong> <span id="ppn"></span></p>
        <p><strong>Grand Total:</strong> <span id="grand_total"></span></p>
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script> 
<script>
$(document).ready(function() {
    var table = $('#penerimaanTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: "{{ route('penerimaan.index') }}",
        columns: [
            { data: null, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1, orderable: false, searchable: false },
            { data: 'Invoice', name: 'Invoice' },
            { data: 'supplier', name: 'supplier.Nama_Supplier' },
            { data: 'Tanggal_Nota', name: 'Tanggal_Nota' },
            { data: 'Jatuh_Tempo', name: 'Jatuh_Tempo' },
            { 
                data: 'Grand_Total', 
                name: 'Grand_Total',
                render: $.fn.dataTable.render.number('.', ',', 0, 'Rp ')
            },
            { 
                data: 'status_pembayaran', 
                name: 'status_pembayaran',
                render: function(data) {
                    return data === 'lunas'
                        ? '<span class="badge bg-success">Lunas</span>'
                        : '<span class="badge bg-warning">Belum Lunas</span>';
                }
            },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        buttons: ["copy", "csv", "excel", "pdf", "print", "colvis"],
        lengthChange: true,
        autoWidth: false
    });
});

$(document).on('click', '.view-btn', function () {
    var id = $(this).data('id');
    $.ajax({
        url: `/penerimaan/${id}/detail`,
        type: 'GET',
        success: function(response) {
            const p = response.penerimaan;
            $('#invoice').text(p.Invoice);
            $('#supplier').text(p.supplier.nama_supplier);
            $('#tanggal_nota').text(moment(p.Tanggal_Nota).format('DD-MM-YYYY')); // Now moment is defined
            $('#metode_pembayaran').text(p.Metode_Pembayaran);
            let tbody = '';
            let subtotal = 0;
            response.details.forEach(item => {
                let total = item.Jumlah * item.Harga;
                subtotal += total;
                tbody += `
                    <tr>
                        <td>${item.barang.nama_barang}</td>
                        <td>${item.Jumlah}</td>
                        <td>Rp ${formatRupiah(item.Harga)}</td>
                        <td>Rp ${formatRupiah(total)}</td>
                    </tr>
                `;
            });
            $('#detailTable tbody').html(tbody);
            $('#subtotal').text('Rp ' + formatRupiah(subtotal));
            $('#ppn').text('Rp ' + formatRupiah(p.PPN));
            $('#grand_total').text('Rp ' + formatRupiah(p.Grand_Total));
            $('#detailModal').modal('show');
        }
    });
});

function formatRupiah(angka) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(angka);
}
</script>
@endsection