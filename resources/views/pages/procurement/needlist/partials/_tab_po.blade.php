{{-- ========================= TAB PURCHASE ORDER ========================= --}}
<div class="tab-pane fade" id="pane-po" role="tabpanel">

    {{-- Tombol Terbitkan PO — hanya muncul saat selection_in_progress --}}
    @if($needlist->status === 'selection_in_progress')
        @php
            $anySelected = collect($groupedItems)->flatten(1)
                ->contains(fn($r) => $r['item']->status === 'selected');
            $hasEmptyPoPrice = collect($groupedItems)->flatten(1)
                ->filter(fn($r) => $r['item']->status === 'selected')
                ->contains(fn($r) => empty($r['item']->harga_penawaran));
        @endphp
        <div class="card mb-3 shadow-sm border-success">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:.75rem;">
                    <div>
                        <h6 class="mb-1 text-success">
                            <i class="fas fa-file-invoice mr-1"></i> Terbitkan Purchase Order
                        </h6>
                        <small class="text-muted">
                            PO akan dibuat per supplier dari pilihan yang sudah disimpan.
                        </small>
                    </div>
                    <div class="text-right">
                        @if(!$anySelected)
                            <span class="d-block text-warning mb-1" style="font-size:.8rem;">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                Belum ada supplier yang dipilih di tab Pemilihan Supplier
                            </span>
                        @elseif($hasEmptyPoPrice)
                            <span class="d-block text-warning mb-1" style="font-size:.8rem;">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                Ada item terpilih dengan harga belum diisi
                            </span>
                        @endif
                        <form id="formBuatPo" method="POST"
                              action="{{ route('supplier.create.po', $needlist->id) }}">
                            @csrf
                            {{-- hidden: kirim selected_items dari form pemilihan via JS --}}
                        </form>
                        <button type="button" id="btnBuatPo"
                                class="btn btn-success"
                                {{ (!$anySelected || $hasEmptyPoPrice) ? 'disabled' : '' }}>
                            <i class="fas fa-file-invoice mr-1"></i> Terbitkan Purchase Order
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-header">
            <strong><i class="fas fa-file-invoice mr-1"></i> Daftar Purchase Order</strong>
        </div>
        <div class="card-body">
            @if($purchaseOrders->isEmpty())
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle me-1"></i>
                    Belum ada Purchase Order yang dibuat.
                    @if($needlist->status === 'selection_in_progress')
                        Pilih supplier di tab <strong>Pemilihan Supplier</strong>, lalu klik <strong>Terbitkan Purchase Order</strong> di atas.
                    @endif
                </div>
            @else
                <table class="table table-bordered table-striped table-sm">
                    <thead class="text-center bg-light">
                        <tr>
                            <th>#</th>
                            <th>No PO</th>
                            <th>Supplier</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($purchaseOrders as $i => $po)
                            <tr>
                                <td class="text-center">{{ $i + 1 }}</td>
                                <td>{{ $po->kode_po }}</td>
                                <td>{{ $po->supplier->nama_supplier }}</td>
                                <td>{{ $po->created_at->format('d M Y') }}</td>
                                <td class="text-center">
                                    <span class="badge badge-{{ $po->status === 'open' ? 'warning' : 'success' }}">
                                        {{ ucfirst($po->status) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('purchase-order.show', $po->id) }}"
                                       class="btn btn-outline-info btn-sm">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('purchase-order.print', $po->id) }}"
                                       class="btn btn-outline-secondary btn-sm" target="_blank">
                                        <i class="fas fa-print"></i>
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
