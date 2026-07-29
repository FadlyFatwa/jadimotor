@extends('layouts.main')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h2 class="font-weight-bold mb-1" style="font-size:1.5rem">Data Supplier</h2>
            <p class="text-muted mb-0" style="font-size:.875rem">Kelola data supplier / vendor</p>
        </div>
        <a href="{{ route('supplier.create') }}" class="btn btn-danger">
            <i class="fas fa-plus mr-1"></i>Tambah Supplier
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body pb-0 pt-3 px-4">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <span class="text-muted mr-2" style="font-size:.875rem">Tampilkan:</span>
                    <select id="lengthSelector" class="form-control form-control-sm" style="width:70px; border-radius:6px">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
                <div class="position-relative" style="width:280px">
                    <i class="fas fa-search text-muted search-input-icon"></i>
                    <input type="text" id="searchInput" class="form-control search-input-with-icon"
                        placeholder="Cari supplier...">
                </div>
            </div>
        </div>
        <div class="card-body px-4 pt-3">
            <div class="table-responsive">
                <table id="dataTable" class="table table-hover mb-0" style="width:100%">
                    <thead class="sku-thead">
                        <tr>
                            <th class="px-3 py-3 text-secondary" style="width:5%">No</th>
                            <th class="py-3 text-secondary" style="width:10%">Kode</th>
                            <th class="py-3 text-secondary" style="width:25%">Nama Supplier</th>
                            <th class="py-3 text-secondary" style="width:15%">No. Telp</th>
                            <th class="py-3 text-secondary">Alamat</th>
                            <th class="py-3 text-secondary text-center" style="width:12%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@php
$tableData = $suppliers->map(fn($s) => [
    'id'     => $s->id_supplier,
    'kode'   => $s->kode_supplier,
    'nama'   => $s->nama_supplier,
    'telp'   => $s->no_telp,
    'alamat' => $s->alamat,
])->values()->toArray();
@endphp

@section('scripts')
<script>
var dt = $('#dataTable').DataTable({
    processing: true, searching: true, lengthChange: false,
    pageLength: 10, dom: 'tip', order: [[2, 'asc']],
    data: @json($tableData),
    columns: [
        { data: null, orderable: false, render: (d,t,r,m) => m.row + m.settings._iDisplayStart + 1 },
        { data: 'kode' },
        { data: 'nama' },
        { data: 'telp' },
        { data: 'alamat' },
        { data: 'id', orderable: false, className: 'text-center',
          render: id => '<a href="/supplier/'+id+'/edit" class="btn btn-warning btn-xs btn-icon-xs mr-1"><i class="fas fa-edit"></i></a>'
                      + '<form action="/supplier/'+id+'" method="POST" style="display:inline" data-confirm="Yakin hapus?">@csrf @method("DELETE")'
                      + '<button class="btn btn-danger btn-xs btn-icon-xs"><i class="fas fa-trash"></i></button></form>' },
    ],
    language: { zeroRecords:'Tidak ada data', info:'Menampilkan _START_-_END_ dari _TOTAL_ data',
                paginate:{next:'Berikutnya',previous:'Sebelumnya'} },
});
var st; $('#searchInput').on('keyup', function(){ clearTimeout(st); var v=$(this).val(); st=setTimeout(()=>dt.search(v).draw(),350); });
$('#lengthSelector').on('change', function(){ dt.page.len(+$(this).val()).draw(); });
</script>
@endsection
