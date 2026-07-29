@extends('layouts.main')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h2 class="font-weight-bold mb-1" style="font-size:1.5rem">Data Barang / Variasi</h2>
            <p class="text-muted mb-0" style="font-size:.875rem">Kelola variasi produk dan supplier barang</p>
        </div>
        <div>
            <a href="{{ route('barang.createMultiple') }}" class="btn btn-outline-danger mr-2">
                <i class="fas fa-plus mr-1"></i>Tambah Banyak
            </a>
            <a href="{{ route('barang.create') }}" class="btn btn-danger">
                <i class="fas fa-plus mr-1"></i>Tambah Variasi
            </a>
        </div>
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
                        <option value="100">100</option>
                    </select>
                </div>
                <div class="position-relative" style="width:280px">
                    <i class="fas fa-search text-muted search-input-icon"></i>
                    <input type="text" id="searchInput" class="form-control search-input-with-icon"
                        placeholder="Cari barcode, nama barang, variasi...">
                </div>
            </div>
        </div>
        <div class="card-body px-4 pt-3">
            <div class="table-responsive">
                <table id="dataTable" class="table table-hover mb-0" style="width:100%">
                    <thead class="sku-thead">
                        <tr>
                            <th class="px-3 py-3 text-secondary" style="width:5%">No</th>
                            <th class="py-3 text-secondary" style="width:10%">Barcode</th>
                            <th class="py-3 text-secondary" style="width:25%">Master Barang</th>
                            <th class="py-3 text-secondary" style="width:20%">Variasi</th>
                            <th class="py-3 text-secondary" style="width:15%">Supplier</th>
                            <th class="py-3 text-secondary text-center" style="width:8%">Harga Jual</th>
                            <th class="py-3 text-secondary text-center" style="width:6%">Stok</th>
                            <th class="py-3 text-secondary text-center" style="width:8%">Status</th>
                            <th class="py-3 text-secondary text-center" style="width:10%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
var dt = $('#dataTable').DataTable({
    processing: true, serverSide: true, searching: true,
    lengthChange: false, pageLength: 10, dom: 'tip', order: [[1, 'asc']],
    ajax: "{{ route('barang.index') }}",
    columns: [
        { data: null, orderable: false, render: (d,t,r,m) => m.row + m.settings._iDisplayStart + 1 },
        { data: 'barcode' },
        { data: 'nama_barang' },
        { data: 'nama_variasi' },
        { data: 'supplier',   orderable: false },
        { data: 'harga_jual', className: 'text-center',
          render: d => d ? 'Rp '+parseInt(d).toLocaleString('id-ID') : '-' },
        { data: 'stock',     className: 'text-center' },
        { data: 'is_active', orderable: false, className: 'text-center' },
        { data: 'action',    orderable: false, className: 'text-center' },
    ],
    language: { processing:'<i class="fas fa-spinner fa-spin"></i> Memuat...', zeroRecords:'Tidak ada data',
                info:'Menampilkan _START_-_END_ dari _TOTAL_ data', paginate:{next:'Berikutnya',previous:'Sebelumnya'} },
});
var st; $('#searchInput').on('keyup', function(){ clearTimeout(st); var v=$(this).val(); st=setTimeout(()=>dt.search(v).draw(),350); });
$('#lengthSelector').on('change', function(){ dt.page.len(+$(this).val()).draw(); });
</script>
@endsection
