@extends('layouts.main')
@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-box-open mr-2"></i>Daftar Detail Penerimaan</h3>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <button id="btnShowCheckbox" class="btn btn-secondary"><i class="fas fa-check-square mr-2"></i>Tampilkan Pilihan</button>
                <button id="btnPrintBarcode" class="btn btn-primary d-none"><i class="fas fa-print mr-2"></i>Print Barcode Terpilih</button>
                <button id="btnCancel" class="btn btn-danger d-none"><i class="fas fa-times mr-2"></i>Batal</button>
            </div>

            <div class="table-responsive">
                <table id="detailPenerimaanTable" class="table table-bordered table-striped" style="width:100%;">
                    <thead class="thead-dark">
                        <tr>
                            <!-- Kolom "Pilih" dinamis -->
                            <th width="5%" id="th-select" class="d-none">
                                <input type="checkbox" id="select-all" />
                            </th>
                            <!-- Kolom "No" tetap tampil -->
                            <th width="5%">No</th>
                            <th>Barcode</th>
                            <th>Nama Barang</th>
                            <th>Supplier</th>
                            <th>Jumlah</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th width="10%">Aksi</th>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> 
<script>
$(document).ready(function() {
    // Variabel untuk menyimpan state checkbox
    let isCheckboxVisible = false;
    let selectedIds = new Set();

    const table = $('#detailPenerimaanTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: "{{ route('detail.penerimaan.index') }}",
        columns: [
            { 
                data: null, 
                render: function(data, type, row) {
                    const isChecked = selectedIds.has(row.ID_detail_penerimaan) ? 'checked' : '';
                    return `<input type="checkbox" class="item-checkbox d-none" value="${row.ID_detail_penerimaan}" ${isChecked}>`;
                },
                orderable: false, 
                searchable: false,
                className: 'd-none'
            },
            { 
                data: null, 
                render: function(data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }, 
                orderable: false 
            },
            { data: 'barang.barcode', name: 'barang.barcode' },
            { data: 'barang.nama_barang', name: 'barang.nama_barang' },
            { data: 'barang.supplier.nama_supplier', name: 'barang.supplier.nama_supplier' },
            { data: 'Jumlah', name: 'Jumlah' },
            { 
                data: 'Tanggal', 
                name: 'Tanggal',
                render: function(data) {
                    return new Date(data).toLocaleDateString();
                }
            },
            { 
                data: 'Status', 
                name: 'Status',
                render: function(data) {
                    let badgeClass = data === 'Aktif' ? 'badge-success' : 'badge-danger';
                    return `<span class="badge ${badgeClass}">${data}</span>`;
                }
            },
            { 
                data: 'action', 
                name: 'action', 
                orderable: false, 
                searchable: false,
                render: function(data, type, row) {
                    return `
                        <a href="{{ url('barcode/print') }}/${row.ID_detail_penerimaan}" class="btn btn-sm btn-info" target="_blank">
                            <i class="fas fa-print"></i>
                        </a>
                    `;
                }
            }
        ],
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print', 'colvis'
        ],
        lengthChange: true,
        autoWidth: false,
        createdRow: function(row, data, dataIndex) {
            // Atur visibilitas checkbox sesuai state
            if (isCheckboxVisible) {
                $(row).find('td:eq(0)').removeClass('d-none');
                $(row).find('.item-checkbox').removeClass('d-none');
            } else {
                $(row).find('td:eq(0)').addClass('d-none');
                $(row).find('.item-checkbox').addClass('d-none');
            }
        },
        drawCallback: function(settings) {
            // Pertahankan state checkbox saat paging
            if (isCheckboxVisible) {
                $('#th-select').removeClass('d-none');
                $('.item-checkbox').removeClass('d-none');
                $('#detailPenerimaanTable thead th:first-child, #detailPenerimaanTable tbody td:first-child').removeClass('d-none');
            }
            
            // Update select all checkbox
            const allChecked = $('.item-checkbox:visible').length > 0 && 
                             $('.item-checkbox:visible').length === $('.item-checkbox:visible:checked').length;
            $('#select-all').prop('checked', allChecked);
        }
    });

    // Tampilkan checkbox dan tombol aksi
    $('#btnShowCheckbox').on('click', function () {
        isCheckboxVisible = true;
        $(this).addClass('d-none');
        $('#btnPrintBarcode').removeClass('d-none');
        $('#btnCancel').removeClass('d-none');
        $('#th-select').removeClass('d-none');
        
        // Show all checkboxes
        $('.item-checkbox').removeClass('d-none');
        $('#detailPenerimaanTable thead th:first-child, #detailPenerimaanTable tbody td:first-child').removeClass('d-none');
        
        // Redraw table untuk memastikan konsistensi
        table.draw();
    });

    // Tombol Batal / Reset
    $('#btnCancel').on('click', function () {
        isCheckboxVisible = false;
        $('#btnShowCheckbox').removeClass('d-none');
        $('#btnPrintBarcode').addClass('d-none');
        $('#btnCancel').addClass('d-none');
        $('#th-select').addClass('d-none');
        $('.item-checkbox').prop('checked', false).addClass('d-none');
        $('#select-all').prop('checked', false);
        selectedIds.clear();
        
        // Hide checkbox column
        $('#detailPenerimaanTable thead th:first-child, #detailPenerimaanTable tbody td:first-child').addClass('d-none');
        
        // Redraw table untuk memastikan konsistensi
        table.draw();
    });

    // Pilih semua checkbox
    $('#select-all').on('change', function () {
        const isChecked = $(this).prop('checked');
        $('.item-checkbox:visible').prop('checked', isChecked);
        
        // Update selectedIds
        $('.item-checkbox:visible').each(function() {
            const id = $(this).val();
            if (isChecked) {
                selectedIds.add(id);
            } else {
                selectedIds.delete(id);
            }
        });
    });

    // Handle perubahan checkbox individual
    $('#detailPenerimaanTable tbody').on('change', '.item-checkbox', function() {
        const id = $(this).val();
        if ($(this).prop('checked')) {
            selectedIds.add(id);
        } else {
            selectedIds.delete(id);
        }
        
        // Update select all checkbox
        const allChecked = $('.item-checkbox:visible').length > 0 && 
                         $('.item-checkbox:visible').length === $('.item-checkbox:visible:checked').length;
        $('#select-all').prop('checked', allChecked);
    });

    // Tombol Print Barcode
        
    $('#btnPrintBarcode').on('click', function () {
        if (selectedIds.size === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Peringatan',
                text: 'Silakan pilih minimal satu item!',
            });
            return;
        }

        window.location.href = "{{ route('barcode.print.multiple') }}?ids=" + Array.from(selectedIds).join(',');
});
});
</script>
@endsection