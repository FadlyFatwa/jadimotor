{{-- ========================= TAB PERMINTAAN KONFIRMASI HARGA ========================= --}}
<div class="tab-pane fade" id="pane-inquiry" role="tabpanel">
    @php
        $isPoIssued   = in_array($needlist->status, ['po_issued', 'completed']);
        $isPreInquiry = in_array($needlist->status, ['draft', 'submitted', 'approved', 'rejected']);
        $hasEmptyPrice = collect($groupedItems)
            ->flatten(1)
            ->contains(fn($row) => empty($row['item']->harga_penawaran));
    @endphp

    @if($isPoIssued)
        <div class="alert alert-info">
            <i class="fas fa-file-invoice me-1"></i>
            <strong>Surat Pesanan sudah diterbitkan.</strong> Permintaan konfirmasi harga tidak dapat diubah.
        </div>
    @elseif($isPreInquiry)
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-1"></i>
            Daftar kebutuhan belum disetujui. Silakan ajukan dan tunggu persetujuan sebelum membuat permintaan konfirmasi harga.
        </div>
    @elseif($hasEmptyPrice)
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-1"></i>
            Semua supplier wajib <strong>mengonfirmasi harga</strong> sebelum lanjut ke pemilihan.
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-header">
            <strong>Daftar Permintaan Konfirmasi Harga</strong>
        </div>
        <div class="card-body p-2">
            @if($needlist->supplierInquiries->isEmpty())
                <p class="text-muted text-center py-3">Belum ada permintaan konfirmasi harga dibuat.</p>
            @else
                <table class="table table-bordered table-striped table-sm mb-0">
                    <thead class="text-center bg-light">
                        <tr>
                            <th>Supplier</th>
                            <th>Status</th>
                            <th style="width:200px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($needlist->supplierInquiries as $inq)
                            <tr>
                                <td>{{ $inq->supplier->nama_supplier }}</td>
                                <td class="text-center">
                                    <span class="badge badge-{{ $inq->status === 'responded' ? 'success' : 'warning' }}">
                                        {{ $inq->status === 'responded' ? 'Sudah Diisi' : 'Menunggu' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($inq->status !== 'responded' && !$isPoIssued)
                                        <button class="btn btn-sm btn-primary open-fill-modal"
                                                data-id="{{ $inq->id }}">
                                            <i class="fas fa-edit me-1"></i> Konfirmasi Harga
                                        </button>
                                    @endif
                                    <button class="btn btn-sm btn-outline-info open-preview-modal"
                                            data-id="{{ $inq->id }}">
                                        <i class="fas fa-eye me-1"></i> Lihat
                                    </button>
                                    <a href="{{ route('inquiry.pdf', $inq->id) }}"
                                       class="btn btn-sm btn-outline-secondary" target="_blank">
                                        <i class="fas fa-file-pdf me-1"></i> PDF
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
