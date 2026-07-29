@extends('layouts.main')

@section('content')
<div class="container-fluid">

    {{-- ===== PAGE HEADER ===== --}}
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h2 class="font-weight-bold mb-1" style="font-size:1.5rem">Data Variasi / SKU</h2>
            <p class="text-muted mb-0" style="font-size:.875rem">Kelola data barang, variasi, dan supplier</p>
        </div>
        <a href="{{ route('barang.create') }}" class="btn btn-danger">
            <i class="fas fa-plus mr-1"></i>Tambah Variasi
        </a>
    </div>

    {{-- ===== CARD TABLE ===== --}}
    <div class="card shadow-sm">

        {{-- Filter bar --}}
        <div class="card-body pb-0 pt-3 px-4">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <span class="text-muted mr-2" style="font-size:.875rem">Tampilkan:</span>
                    <select id="lengthSelector" class="form-control form-control-sm" style="width:70px; border-radius:6px">
                        <option value="10">10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <div class="position-relative" style="width:300px">
                    <i class="fas fa-search text-muted search-input-icon"></i>
                    <input type="text" id="searchInput"
                        class="form-control search-input-with-icon"
                        placeholder="Cari barcode, nama barang, supplier...">
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="card-body px-4 pt-3">
            <div class="table-responsive">
                <table id="skuTable" class="table table-hover mb-0" style="width:100%">
                    <thead class="sku-thead">
                        <tr>
                            <th style="width:4%"  class="text-secondary">No</th>
                            <th style="width:9%"  class="text-secondary">Barcode</th>
                            <th style="width:25%" class="text-secondary">Nama Barang</th>
                            <th style="width:8%"  class="text-secondary">Tier</th>
                            <th style="width:12%" class="text-secondary">Supplier</th>
                            <th style="width:10%" class="text-secondary">Cost Code</th>
                            <th style="width:11%" class="text-secondary">Harga Beli</th>
                            <th style="width:11%" class="text-secondary">Harga Jual</th>
                            <th style="width:6%"  class="text-secondary text-center">Stok</th>
                            <th style="width:8%"  class="text-secondary text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

    </div>
</div>

{{-- ===== DETAIL MODAL ===== --}}
<div class="modal fade" id="detailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle mr-2"></i>Detail SKU
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="modalBody">
                <div class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                    <p class="mt-2 text-muted">Memuat detail...</p>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" id="btnEditModal" class="btn btn-warning btn-sm">
                    <i class="fas fa-edit mr-1"></i>Edit
                </a>
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i>Tutup
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
var skuTable = $('#skuTable').DataTable({
    processing  : true,
    serverSide  : true,
    searching   : true,
    lengthChange: false,
    autoWidth   : false,
    pageLength  : 10,
    dom         : 'tip',
    order       : [[1, 'asc']],
    ajax: {
        url    : '{{ route("variasi.datatable") }}',
        type   : 'POST',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
    },
    columns: [
        { data: null, orderable: false, searchable: false,
          render: (d, t, r, meta) => meta.row + meta.settings._iDisplayStart + 1 },
        { data: 'barcode' },
        { data: null, orderable: true,
          render: function(d, t, row) {
              return '<strong>' + esc(row.nama_barang) + '</strong> ' + esc(row.nama_variasi)
                   + '<br><small class="text-muted"><i class="fas fa-car fa-xs mr-1"></i>' + esc(row.vehicle) + '</small>'
                   + (row.part_number && row.part_number !== '-' ? '<br><small>' + esc(row.part_number) + '</small>' : '');
          }},
        { data: 'tier', orderable: false,
          render: function(d) {
              if (!d) return '<span class="text-muted">-</span>';
              var colors = {OEM:'primary', Original:'success', Aftermarket:'warning', KW:'secondary'};
              return '<span class="badge badge-'+(colors[d]||'secondary')+'">'+d+'</span>';
          }},
        { data: 'suppliers', orderable: false,
          render: function(suppliers) {
              if (!suppliers || suppliers.length === 0) return '<span class="text-muted">-</span>';
              return suppliers.map(function(s) {
                  return '<span class="badge badge-light border mr-1" style="font-weight:normal">' + esc(s.name) + '</span>';
              }).join('');
          }},
        { data: 'suppliers', orderable: false,
          render: function(suppliers) {
              if (!suppliers || suppliers.length === 0) return '-';
              var codes = suppliers.map(s => s.kode_beli).filter(c => c && c !== '-');
              return codes.length ? codes.join(' / ') : '-';
          }},
        { data: null, orderable: false,
          render: function(d, t, row) {
              var min = row.harga_beli_min || 0, max = row.harga_beli_max || 0;
              var fmt = n => 'Rp ' + parseInt(n).toLocaleString('id-ID');
              return min === max ? fmt(min) : fmt(min) + '<br><small class="text-muted">s/d ' + fmt(max) + '</small>';
          }},
        { data: 'harga_jual', render: d => 'Rp ' + parseInt(d || 0).toLocaleString('id-ID') },
        { data: 'stock', className: 'text-center' },
        { data: 'id_variasi', orderable: false, searchable: false, className: 'text-center',
          render: function(id, t, row) {
              return '<button class="btn btn-info btn-xs btn-icon-xs mr-1" onclick="openDetailModal(' + id + ')" title="Detail"><i class="fas fa-eye"></i></button>'
                   + '<a href="{{ url("barang") }}/' + id + '/edit" class="btn btn-warning btn-xs btn-icon-xs mr-1" title="Edit"><i class="fas fa-edit"></i></a>'
                   + '<button class="btn btn-danger btn-xs btn-icon-xs" onclick="confirmDelete(' + id + ')" title="Hapus"><i class="fas fa-trash"></i></button>';
          }},
    ],
    language: {
        processing  : '<i class="fas fa-spinner fa-spin"></i> Memuat...',
        zeroRecords : 'Tidak ada data ditemukan',
        info        : 'Menampilkan _START_-_END_ dari _TOTAL_ data',
        infoEmpty   : 'Tidak ada data',
        infoFiltered: '(difilter dari _MAX_ total data)',
        paginate    : { next: 'Berikutnya', previous: 'Sebelumnya' },
    },
});

// Search debounce
var searchTimer;
$('#searchInput').on('keyup', function() {
    clearTimeout(searchTimer);
    var val = $(this).val();
    searchTimer = setTimeout(function() { skuTable.search(val).draw(); }, 400);
});

// Length selector
$('#lengthSelector').on('change', function() {
    skuTable.page.len(parseInt($(this).val())).draw();
});

function esc(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Detail modal
function openDetailModal(id) {
    $('#modalBody').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><p class="mt-2 text-muted">Memuat detail...</p></div>');
    $('#btnEditModal').attr('href', '#');
    $('#detailModal').modal('show');

    $.get('/variasi/' + id + '/detail', function(d) {
        $('#btnEditModal').attr('href', '{{ url("barang") }}/' + (d.variasi ? d.variasi.id : '') + '/edit');
        $('#modalBody').html(buildModalHtml(d));
    }).fail(function() {
        $('#modalBody').html('<div class="alert alert-danger">Gagal memuat detail.</div>');
    });
}

function buildModalHtml(d) {
    var rp   = n => 'Rp ' + parseInt(n || 0).toLocaleString('id-ID');
    var e    = s => esc(s);
    var dash = v => (v && v !== '-') ? e(v) : '<span class="text-muted">—</span>';
    var tc   = {OEM:'primary',Original:'success',Aftermarket:'warning',KW:'secondary'};

    function section(icon, title, body) {
        return '<div class="card card-outline card-primary mb-3">'
             + '<div class="card-header py-2"><h6 class="mb-0"><i class="fas fa-' + icon + ' mr-2 text-primary"></i>' + title + '</h6></div>'
             + '<div class="card-body py-2">' + body + '</div></div>';
    }
    function row(label, value) {
        return '<div class="row mb-1"><div class="col-5 text-muted small">' + label + '</div>'
             + '<div class="col-7 small">' + value + '</div></div>';
    }

    var mb   = d.master_barang || {};
    var unit = mb.unit || {};
    var v    = d.variasi || {};

    var tierBadge = v.tier ? '<span class="badge badge-'+(tc[v.tier]||'secondary')+' ml-1">'+v.tier+'</span>' : '';
    var s1 = row('Nama', dash(mb.name)) + row('Kategori', dash(mb.category)) + row('Satuan', dash(unit.name));
    var s2 = row('Merk / Brand', dash(v.brand_name) + tierBadge)
           + row('No Part', v.part_number && v.part_number !== '-' ? e(v.part_number) : '—')
           + row('Barcode', e(d.barcode || '-'));

    var mobils = d.penggunaan_mobil || [];
    var s3body = mobils.length === 0
        ? '<p class="text-muted small mb-0"><i>Belum ada data penggunaan kendaraan.</i></p>'
        : '<ul class="list-unstyled mb-0">' + mobils.map(function(m) {
            var years = (m.start_year || '') + (m.end_year ? '–' + m.end_year : m.start_year ? '–skrg' : '');
            return '<li class="mb-1 small"><i class="fas fa-car text-muted mr-2"></i>'
                 + '<strong>' + e(m.vehicle_name) + ' ' + e(m.generation_code) + '</strong>'
                 + (m.generation_name ? ' <em class="text-muted">(' + e(m.generation_name) + ')</em>' : '')
                 + (years ? ' <span class="badge badge-light border">' + years + '</span>' : '')
                 + (m.compatibility_notes ? '<br><span class="text-muted ml-4">' + e(m.compatibility_notes) + '</span>' : '')
                 + '</li>';
          }).join('') + '</ul>';

    // Supplier & Harga — loop semua supplier
    var suppliers = d.suppliers || [];
    var s4body = row('Harga Jual', '<strong>' + rp(d.harga_jual) + '</strong>') + '<hr class="my-2">';
    if (suppliers.length === 0) {
        s4body += '<p class="text-muted small mb-0"><i>Belum ada data supplier.</i></p>';
    } else {
        s4body += suppliers.map(function(s, i) {
            return '<div class="mb-2 pb-2' + (i < suppliers.length - 1 ? ' border-bottom' : '') + '">'
                 + '<div class="font-weight-bold small mb-1"><i class="fas fa-building text-muted mr-1"></i>' + e(s.name)
                 + (s.code && s.code !== '-' ? ' <span class="text-muted">(' + e(s.code) + ')</span>' : '') + '</div>'
                 + row('Harga Beli', rp(s.harga_beli_netto))
                 + row('Kode Beli', s.cost_code && s.cost_code !== '-' ? e(s.cost_code) : '—')
                 + row('Harga List', rp(s.harga_list))
                 + row('Kode List', s.harga_list_code && s.harga_list_code !== '-' ? e(s.harga_list_code) : '—')
                 + '</div>';
        }).join('');
    }

    var s5 = row('Stok Total', '<strong>' + (d.stock || 0) + '</strong> ' + e(unit.name || 'pcs'))
           + row('Status', d.is_active
               ? '<span class="badge badge-success"><i class="fas fa-check mr-1"></i>Aktif</span>'
               : '<span class="badge badge-secondary"><i class="fas fa-times mr-1"></i>Nonaktif</span>');

    return section('cube','Master Barang', s1)
         + section('tag','Variasi / Merk', s2)
         + section('car','Penggunaan Kendaraan', s3body)
         + section('building','Supplier & Harga', s4body)
         + section('boxes','Stock', s5);
}

// Delete
function confirmDelete(id) {
    Swal.fire({
        title: 'Konfirmasi',
        text: 'Yakin hapus data ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#d33',
    }).then((result) => {
        if (!result.isConfirmed) return;
        $.post('/barang/' + id + '/destroy', {
            _method: 'DELETE',
            _token: $('meta[name="csrf-token"]').attr('content')
        }, function() {
            skuTable.ajax.reload(null, false);
            toastr.success('Data berhasil dihapus.');
        }).fail(function() { toastr.error('Gagal menghapus data.'); });
    });
}
</script>
@endsection
