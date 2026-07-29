@extends('layouts.main')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h2 class="font-weight-bold mb-1" style="font-size:1.5rem">Data Kategori</h2>
            <p class="text-muted mb-0" style="font-size:.875rem">Kelola kategori produk</p>
        </div>
        <a href="{{ route('kategori.create') }}" class="btn btn-danger">
            <i class="fas fa-plus mr-1"></i>Tambah Kategori
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
                        placeholder="Cari kategori...">
                </div>
            </div>
        </div>
        <div class="card-body px-4 pt-3">
            <div class="table-responsive">
                <table id="dataTable" class="table table-hover mb-0" style="width:100%">
                    <thead class="sku-thead">
                        <tr>
                            <th class="px-3 py-3 text-secondary" style="width:5%">No</th>
                            <th class="py-3 text-secondary" style="width:15%">Kode</th>
                            <th class="py-3 text-secondary" style="width:30%">Nama Kategori</th>
                            <th class="py-3 text-secondary" style="width:20%">Slug</th>
                            <th class="py-3 text-secondary">Deskripsi</th>
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
$tableData = $kategoris->map(fn($k) => [
    'id'          => $k->id_kategori,
    'kode'        => $k->kode_kategori,
    'nama'        => $k->nama_kategori,
    'slug'        => $k->slug ?? '-',
    'description' => $k->description ?? '-',
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
        { data: 'slug', render: d => d !== '-' ? '<code>'+d+'</code>' : '-' },
        { data: 'description', render: d => d !== '-' ? d.substring(0,80)+(d.length>80?'...':'') : '-' },
        { data: 'id', orderable: false, className: 'text-center',
          render: id => '<a href="/kategori/'+id+'/edit" class="btn btn-warning btn-xs btn-icon-xs mr-1"><i class="fas fa-edit"></i></a>'
                      + '<form action="/kategori/'+id+'" method="POST" style="display:inline" data-confirm="Yakin hapus?">@csrf @method("DELETE")'
                      + '<button class="btn btn-danger btn-xs btn-icon-xs"><i class="fas fa-trash"></i></button></form>' },
    ],
    language: { zeroRecords:'Tidak ada data', info:'Menampilkan _START_-_END_ dari _TOTAL_ data',
                paginate:{next:'Berikutnya',previous:'Sebelumnya'} },
});

var st; $('#searchInput').on('keyup', function(){ clearTimeout(st); var v=$(this).val(); st=setTimeout(()=>dt.search(v).draw(),350); });
$('#lengthSelector').on('change', function(){ dt.page.len(+$(this).val()).draw(); });
</script>
@endsection
