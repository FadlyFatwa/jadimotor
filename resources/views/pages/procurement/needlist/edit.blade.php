@extends('layouts.main')
@section('title', 'Edit Needlist #' . $needlist->kode_needlist)

@section('content')
<div class="container-fluid">

    {{-- HEADER --}}
    <div class="card mb-3 shadow-sm">
        <div class="card-body d-flex justify-content-between align-items-center">

            <div>
                <h4 class="mb-1">
                    <i class="fas fa-edit me-2 text-primary"></i>
                    Edit Needlist #{{ $needlist->kode_needlist }}
                </h4>
                <small class="text-muted">
                    Perbarui item dan catatan sebelum diajukan
                </small>
            </div>

            <div>
                @php
                    $badge_class = [
                        'draft' => 'secondary',
                        'submitted' => 'info',
                        'approved' => 'success',
                        'rejected' => 'danger'
                    ][$needlist->status] ?? 'secondary';
                @endphp
                <span class="badge bg-{{ $badge_class }} fs-6">
                    {{ strtoupper($needlist->status) }}
                </span>
            </div>

        </div>
    </div>


    <form id="needlist-form" method="POST" action="{{ route('needlist.update', $needlist->id) }}">
        @csrf
        @method('PUT')

        {{-- Input tersembunyi --}}
        <input type="hidden" name="action_type" id="action_type" value="save">
        <input type="hidden" name="temp_items_json" id="temp_items_json">

        {{-- CARD CATATAN + TAMBAH BARANG --}}
        <div class="card mb-3">
            <div class="card-body">

                <div class="row">

                    <div class="col-md-9">
                        <div class="mb-3">
                            <label class="form-label">Catatan / Memo</label>
                            <textarea name="catatan" id="catatan" class="form-control" rows="2"
                                      placeholder="Tambahkan catatan kebutuhan...">{{ $needlist->catatan }}</textarea>
                        </div>
                    </div>

                    <div class="col-md-3 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-primary w-100"
                                data-toggle="modal" data-target="#modalBarang">
                            <i class="fas fa-plus me-1"></i> Tambah Barang
                        </button>
                    </div>

                </div>

            </div>
        </div>

        {{-- CONTAINER ITEM NEEDLIST --}}
        <div class="d-flex justify-content-end mb-2" id="refToggleBar" style="display:none!important">
            <button type="button" id="btnToggleRef" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-eye mr-1"></i> Tampilkan Referensi
            </button>
        </div>
        <div id="needlist-items-container">
            <p class="text-center text-muted">Memuat item needlist...</p>
        </div>

        {{-- TOOLBAR TOMBOL AKSI --}}
        <div class="card mt-3">
            <div class="card-body d-flex justify-content-between align-items-center">

                <div>
                    <button type="button" onclick="submitForm('save')" class="btn btn-success">
                        <i class="fas fa-save me-1"></i> Simpan Draft
                    </button>

                    @if ($needlist->status === 'draft' || $needlist->status === 'rejected')
                        <button type="button" onclick="submitForm('submit')" class="btn btn-info ms-2">
                            <i class="fas fa-paper-plane me-1"></i> Simpan & Ajukan
                        </button>
                    @endif
                </div>

                <a href="{{ route('needlist.show', $needlist->id) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Batal
                </a>

            </div>
        </div>

    </form>

</div>

{{-- MODAL PILIH BARANG --}}
<div class="modal fade" id="modalBarang">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-box-open me-2"></i>
                    Pilih Barang
                </h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <div class="modal-body">
                <table id="tableBarang" 
                       class="table table-bordered table-striped table-hover dt-responsive nowrap align-middle"
                       style="width:100%;">
                    <thead class="table-dark text-center">
                        <tr>
                            <th>Barcode</th>
                            <th>Master / Variasi</th>
                            <th>Kendaraan</th>
                            <th>Supplier</th>
                            <th>Harga Beli</th>
                            <th>Stok</th>
                            <th style="width:10%">Aksi</th>
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
let needlistItems = { approved_items: [], draft_items: [] };
const needlistId = {{ $needlist->id }};
var showReferensi = false;

function formatRupiah(angka) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
}
function ucfirst(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; }

// Union-Find: cluster item berdasarkan kesamaan vehicle_gen_ids
function clusterByVehicle(items) {
    var n = items.length;
    var parent = Array.from({length: n}, function(_, i) { return i; });
    function find(x) { return parent[x] === x ? x : (parent[x] = find(parent[x])); }
    function union(x, y) { parent[find(x)] = find(y); }

    items.forEach(function(a, i) {
        if (!a.vehicle_gen_ids || !a.vehicle_gen_ids.length) return;
        var setA = new Set(a.vehicle_gen_ids);
        items.forEach(function(b, j) {
            if (j <= i || !b.vehicle_gen_ids || !b.vehicle_gen_ids.length) return;
            if (b.vehicle_gen_ids.some(function(id) { return setA.has(id); })) union(i, j);
        });
    });

    var clusterMap = {}, universalItems = [];
    items.forEach(function(item, i) {
        if (!item.vehicle_gen_ids || !item.vehicle_gen_ids.length) {
            universalItems.push(item);
        } else {
            var root = find(i);
            if (!clusterMap[root]) clusterMap[root] = [];
            clusterMap[root].push(item);
        }
    });

    var result = Object.values(clusterMap).map(function(clusterItems) {
        var seen = {}, labels = [];
        clusterItems.forEach(function(item) {
            (item.vehicle_names || '').split(' / ').forEach(function(v) {
                if (v && !seen[v]) { seen[v] = true; labels.push(v); }
            });
        });
        labels.sort();
        return { label: labels.join(' / '), items: clusterItems };
    });

    if (universalItems.length) result.push({ label: '', items: universalItems });
    return result;
}

function applyRefVisibility() {
    if (showReferensi) { $('.ref-row').show(); } else { $('.ref-row').hide(); }
    var hasRef = $('.ref-row').length > 0;
    $('#refToggleBar').css('display', hasRef ? 'flex' : 'none');
}

function renderNeedlistItems() {
    var tierOrder  = ['OEM', 'Original', 'Aftermarket', 'KW'];
    var tierColors = { OEM: 'primary', Original: 'success', Aftermarket: 'warning', KW: 'secondary' };

    var allItems = needlistItems.approved_items.concat(
        needlistItems.draft_items.map(function(item, i) { return Object.assign({}, item, {temp_id: i}); })
    );

    var byMaster = {};
    allItems.forEach(function(item) {
        var masterName = item.nama_master || 'N/A';
        if (!byMaster[masterName]) byMaster[masterName] = [];
        byMaster[masterName].push(item);
    });

    var htmlContent = '';
    Object.keys(byMaster).forEach(function(master) {
        var masterItems = byMaster[master];
        var refCount    = masterItems.filter(function(i) { return i.is_reference; }).length;
        var activeCount = masterItems.length - refCount;

        htmlContent += '<div class="card mb-3 shadow-sm" style="border-left:3px solid #007bff;">'
            + '<div class="card-header d-flex justify-content-between align-items-center py-2" style="background:rgba(0,123,255,.06);">'
            + '<strong class="text-primary"><i class="fas fa-box mr-1"></i>' + master + '</strong>'
            + '<div><span class="badge badge-secondary mr-1">' + activeCount + ' aktif</span>'
            + (refCount > 0 ? '<span class="badge badge-light border text-muted">' + refCount + ' referensi</span>' : '')
            + '</div></div>';

        var clusters = clusterByVehicle(masterItems);
        var hasMultiCluster = clusters.length > 1 || (clusters.length === 1 && clusters[0].label);

        clusters.forEach(function(cluster) {
            if (hasMultiCluster && cluster.label) {
                htmlContent += '<div class="border-top px-3 py-2 d-flex align-items-center" style="background:rgba(0,0,0,.025);">'
                    + '<i class="fas fa-car mr-2 text-secondary" style="font-size:.8rem;"></i>'
                    + '<strong style="font-size:.85rem;">' + cluster.label + '</strong></div>';
            }

            var byTier = {};
            cluster.items.forEach(function(item) {
                var tier = item.tier || '__universal__';
                if (!byTier[tier]) byTier[tier] = [];
                byTier[tier].push(item);
            });
            var sortedTiers = Object.keys(byTier).sort(function(a, b) {
                var ia = tierOrder.indexOf(a) < 0 ? 99 : tierOrder.indexOf(a);
                var ib = tierOrder.indexOf(b) < 0 ? 99 : tierOrder.indexOf(b);
                return ia - ib;
            });
            var hasMultiTier = sortedTiers.length > 1;

            sortedTiers.forEach(function(tierKey) {
                var tierItems = byTier[tierKey];
                var tierLabel = tierKey === '__universal__' ? 'Umum' : tierKey;
                var tierColor = tierColors[tierKey] || 'secondary';

                if (hasMultiTier) {
                    htmlContent += '<div class="px-3 py-2 d-flex align-items-center" style="background:rgba(0,0,0,.03);border-top:1px solid #dee2e6;">'
                        + '<span class="badge badge-' + tierColor + ' mr-2">' + tierLabel + '</span>'
                        + '<small class="text-muted">' + tierItems.length + ' item</small></div>';
                }

                htmlContent += '<div><table class="table table-bordered table-sm mb-0"><thead class="sku-thead"><tr>'
                    + '<th class="py-2 text-secondary">Barcode</th>'
                    + '<th class="py-2 text-secondary">Variasi / Merk</th>'
                    + '<th class="py-2 text-secondary text-center" style="width:90px;">Qty</th>'
                    + '<th class="py-2 text-secondary text-center" style="width:65px;">Stok</th>'
                    + '<th class="py-2 text-secondary">Harga Beli</th>'
                    + '<th class="py-2 text-secondary">Supplier</th>'
                    + '<th class="py-2 text-secondary text-center" style="width:80px;">Status</th>'
                    + '<th class="py-2 text-secondary text-center" style="width:65px;" title="Jadikan referensi (tidak masuk SAW)">'
                    +   '<i class="fas fa-eye mr-1" style="font-size:.8rem"></i>Ref</th>'
                    + '<th class="py-2 text-secondary text-center" style="width:45px;">Aksi</th>'
                    + '</tr></thead><tbody>';

                tierItems.forEach(function(item) {
                    var isRef     = !!item.is_reference;
                    var rowClass  = (isRef ? 'ref-row ' : '') + (!item.is_approved ? 'draft-item' : '');
                    var rowStyle  = isRef ? 'opacity:.5;' : '';
                    var detailId  = item.detail_id;
                    var rowKey    = detailId || item.temp_id;
                    var badge     = {pending:'warning', approved:'success', rejected:'danger'}[item.status] || 'secondary';
                    var stockBadge= item.stock <= 0
                        ? '<span class="badge badge-danger">Habis</span>'
                        : item.stock <= 5
                        ? '<span class="badge badge-warning">' + item.stock + '</span>'
                        : '<span class="badge badge-light border">' + item.stock + '</span>';
                    var dataAttrs = item.is_approved ? ''
                        : 'data-detail-id="' + rowKey + '" data-id-variasi="' + item.id_variasi + '" data-status="' + item.status + '"';

                    htmlContent += '<tr class="' + rowClass + '" ' + dataAttrs + ' style="' + rowStyle + '">'
                        + '<td><small class="text-muted">' + item.barcode + '</small></td>'
                        + '<td><strong>' + item.nama_variasi + '</strong>'
                        + (isRef ? '<br><span class="badge badge-light border text-muted" style="font-size:.68rem"><i class="fas fa-eye mr-1"></i>Referensi</span>' : '')
                        + '</td>'
                        + '<td class="text-center">'
                        + (item.is_approved
                            ? '<strong>' + item.qty + '</strong>'
                            : '<input type="number" name="qty_temp[' + rowKey + ']" class="form-control form-control-sm text-center" min="1" value="' + item.qty + '">')
                        + '</td>'
                        + '<td class="text-center">' + stockBadge + '</td>'
                        + '<td><small>' + formatRupiah(item.harga_beli) + '</small></td>'
                        + '<td><small>' + item.nama_supplier + '</small></td>'
                        + '<td class="text-center"><span class="badge badge-' + badge + '" style="font-size:.7rem">' + ucfirst(item.status) + '</span></td>'
                        + '<td class="text-center">'
                        + (item.is_approved
                            ? '<span class="text-muted small">—</span>'
                            : '<div class="custom-control custom-switch d-flex justify-content-center">'
                            + '<input type="checkbox" class="custom-control-input ref-toggle"'
                            + ' id="ref_' + rowKey + '" data-key="' + rowKey + '" ' + (isRef ? 'checked' : '') + '>'
                            + '<label class="custom-control-label" for="ref_' + rowKey + '"></label></div>')
                        + '</td>'
                        + '<td class="text-center">'
                        + (item.is_approved
                            ? '<span class="text-success" title="Disetujui"><i class="fas fa-check-circle"></i></span>'
                            : '<button type="button" class="btn btn-outline-danger btn-sm" title="Hapus"'
                            + ' onclick="hapusDraftItem(' + rowKey + ', false)"><i class="fas fa-trash"></i></button>')
                        + '</td></tr>';
                });

                htmlContent += '</tbody></table></div>';
            });
        });

        htmlContent += '</div>';
    });

    if (allItems.length === 0) {
        htmlContent = '<div class="alert alert-warning">Needlist ini tidak memiliki item yang valid. Silakan tambahkan barang.</div>';
    }

    $('#needlist-items-container').html(htmlContent);
    applyRefVisibility();
}

// Fungsi untuk mengambil data item dari server (DB Approved + Session Draft)
function fetchNeedlistItems() {
    $.get("{{ route('needlist.draft.json', $needlist->id) }}")
        .done(function(data) {
            needlistItems = data;
            renderNeedlistItems();
        })
        .fail(function() {
            $('#needlist-items-container').html('<div class="alert alert-danger">Gagal memuat data item. Silakan refresh halaman.</div>');
        });
}

// --- Fungsi Aksi ---

// Fungsi yang menentukan aksi sebelum submit form
function submitForm(action) {
    // 1. Kumpulkan data item non-approved (yang ada di needlistItems.draft_items)
    let itemsToSubmit = [];
    let isValid = true;

    // Kumpulkan Qty dari input form
    let submittedQtys = {};
    $('input[name^="qty_temp["]').each(function() {
        const id = $(this).attr('name').match(/\[(.*?)\]/)[1];
        submittedQtys[id] = parseInt($(this).val());
    });
    
    // Kumpulkan state toggle referensi dari DOM
    let refStates = {};
    $('.ref-toggle').each(function() {
        refStates[$(this).data('key')] = $(this).is(':checked');
    });

    // Gabungkan data draft dari JS state dengan Qty terbaru dari form
    const finalDraftItems = needlistItems.draft_items.map((item, index) => {
        const key = item.detail_id || index;
        const newQty = submittedQtys[key];

        if (isNaN(newQty) || newQty < 1) {
            isValid = false;
        }

        return {
            detail_id:    item.detail_id,
            id_variasi:   item.id_variasi,
            qty:          newQty,
            status:       item.status,
            is_reference: refStates[key] ?? false,
        };
    });

    if (!isValid) {
        alert('Semua Qty item harus minimal 1 dan tidak boleh kosong.');
        return;
    }
    
    if (finalDraftItems.length === 0 && needlistItems.approved_items.length === 0) {
        alert('Needlist tidak boleh kosong.');
        return;
    }


    // 2. Masukkan data item draft/rejected final ke hidden input JSON
    $('#temp_items_json').val(JSON.stringify(finalDraftItems));

    // 3. Proses Submit
    document.getElementById('action_type').value = action;
    
    if (action === 'submit') {
        Swal.fire({
            title: 'Konfirmasi',
            text: 'Apakah Anda yakin ingin menyimpan perubahan dan mengajukan Needlist ini ke Supervisor?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, ajukan',
            cancelButtonText: 'Batal',
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('needlist-form').submit();
            }
        });
        return;
    }

    document.getElementById('needlist-form').submit();
}

// Fungsi untuk menghapus item draft via AJAX (memanipulasi session)
function hapusDraftItem(detailIdOrTempId, isApproved) {
    if (isApproved) {
        alert('Item yang sudah di-approved tidak dapat dihapus.');
        return;
    }

    Swal.fire({
        title: 'Konfirmasi',
        text: 'Apakah Anda yakin ingin menghapus item ini dari draft Needlist?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#d33',
    }).then((result) => {
        if (!result.isConfirmed) return;

        // Cari indeks item di array draft_items berdasarkan detail_id atau temp_id (index)
        let itemIndex = -1;
        let tempId;

        if (typeof detailIdOrTempId === 'number' && detailIdOrTempId === 0) {
            // Jika item baru (detail_id=null), cari berdasarkan index
            itemIndex = detailIdOrTempId;
            tempId = itemIndex;

        } else if (detailIdOrTempId === null) {
            // Ini tidak boleh terjadi jika rendering benar, tapi sebagai safety.
            alert('Error: ID item tidak valid.');
            return;

        } else {
            // Item yang sudah ada di DB (detail_id > 0), cari berdasarkan detail_id
            itemIndex = needlistItems.draft_items.findIndex(item => item.detail_id === detailIdOrTempId);
            // Map itemIndex (posisi di array) ke tempId
            tempId = itemIndex;
        }

        if (itemIndex === -1) {
            alert('Item tidak ditemukan dalam draft.');
            return;
        }

        const idVariasi = needlistItems.draft_items[itemIndex].id_variasi;

        // Panggil route DELETE untuk menghapus dari session
        $.ajax({
            url: `{{ url('needlist') }}/${needlistId}/draft/remove/${tempId}`,
            type: 'DELETE',
            data: { _token: "{{ csrf_token() }}", id_variasi: idVariasi }
        })
        .done(function() {
            // Hapus dari state JS dan render ulang
            needlistItems.draft_items.splice(itemIndex, 1);
            renderNeedlistItems();
            alert('Item berhasil dihapus dari draft.');
        })
        .fail(function(xhr) {
            alert(xhr.responseJSON.error || 'Gagal menghapus item dari draft.');
        });
    });
}


$(document).ready(function () {
    fetchNeedlistItems();

    // Toggle tampilkan/sembunyikan item referensi
    $('#btnToggleRef').on('click', function() {
        showReferensi = !showReferensi;
        $(this).html(showReferensi
            ? '<i class="fas fa-eye-slash mr-1"></i> Sembunyikan Referensi'
            : '<i class="fas fa-eye mr-1"></i> Tampilkan Referensi');
        applyRefVisibility();
    });

    var tierColors = { OEM: 'primary', Original: 'success', Aftermarket: 'warning', KW: 'secondary' };

    // Inisialisasi DataTables untuk Modal Barang
    $('#tableBarang').DataTable({
        processing: true,
        serverSide: true,
        order: [[5, 'asc']],
        ajax: {
            url    : '{{ route("variasi.datatable") }}',
            type   : 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        },
        columns: [
            { data: 'barcode' },
            {
                data: null, orderable: false,
                render: function(data, type, row) {
                    var badge = row.tier
                        ? '<span class="badge badge-' + (tierColors[row.tier] || 'secondary') + ' ml-1">' + row.tier + '</span>'
                        : '';
                    return '<strong>' + row.nama_barang + '</strong><br><small class="text-muted">' + row.nama_variasi + '</small> ' + badge;
                }
            },
            {
                data: 'vehicle',
                render: function(data) { return '<small class="text-muted">' + (data || '-') + '</small>'; }
            },
            {
                data: 'suppliers', orderable: false,
                render: function(data) {
                    if (!data || data.length === 0) return '<span class="text-muted">-</span>';
                    return data.map(function(s) {
                        return '<span class="badge badge-light border text-dark mr-1">' + s.name + '</span>';
                    }).join('');
                }
            },
            {
                data: null, orderable: false,
                render: function(data, type, row) {
                    if (row.harga_beli_min === row.harga_beli_max) return '<small>' + formatRupiah(row.harga_beli_min) + '</small>';
                    return '<small>' + formatRupiah(row.harga_beli_min) + '<br>– ' + formatRupiah(row.harga_beli_max) + '</small>';
                }
            },
            {
                data: 'stock',
                render: function(data) {
                    var v = parseInt(data);
                    if (v <= 0) return '<span class="badge badge-danger">Habis</span>';
                    if (v <= 5) return '<span class="badge badge-warning">' + v + '</span>';
                    return '<span class="badge badge-light border">' + v + '</span>';
                }
            },
            {
                data: null, orderable: false, searchable: false,
                render: function(data, type, row) {
                    return '<button class="btn btn-sm btn-success btn-pilih-barang"'
                        + ' data-id="' + row.id_variasi + '">'
                        + 'Pilih</button>';
                }
            },
        ]
    });

    // Tambahkan barang ke needlist via Ajax (memanipulasi session draft)
    $(document).on('click', '.btn-pilih-barang', function () {
        const id_variasi = $(this).data('id');
        
        // Panggil route POST untuk menambahkan ke session draft
        $.post("{{ route('needlist.draft.add', $needlist->id) }}", {
            _token: "{{ csrf_token() }}",
            id_variasi: id_variasi
        })
        .done(function (response) {
            $('#modalBarang').modal('hide');
            // Tambahkan item baru ke state JS dan render ulang
            needlistItems.draft_items.push(response.item);
            renderNeedlistItems();
            alert('Item berhasil ditambahkan ke draft.');
        })
        .fail(function (xhr) {
            alert(xhr.responseJSON.error || 'Gagal menambahkan barang ke draft.');
        });
    });
});
</script>
@endsection