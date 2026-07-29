@extends('layouts.main')

@section('title', 'Review Needlist #' . $needlist->kode_needlist)

@section('content')
<div class="container-fluid">

    {{-- HEADER --}}
    <div class="card mb-3 shadow-sm">
        <div class="card-body d-flex justify-content-between align-items-center">

            <div>
                <h4 class="mb-1">
                    <i class="fas fa-user-check me-2 text-primary"></i>
                    Review Needlist
                </h4>
                <small class="text-muted">
                    Needlist #{{ $needlist->kode_needlist }} — Proses persetujuan supervisor
                </small>
            </div>

            <div>
                @php
                    $badge_class = [
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'submitted' => 'warning',
                        'draft' => 'secondary'
                    ][$needlist->status] ?? 'secondary';
                @endphp

                <span class="badge bg-{{ $badge_class }} fs-6">
                    {{ strtoupper($needlist->status) }}
                </span>
            </div>

        </div>
    </div>

    @php
        // Status approve/reject yang masih draft disimpan di session (keyed by detail id).
        // Data tampilan (barcode, variasi, stock, harga, supplier) SELALU dihitung fresh
        // dari relasi DB di bawah ini, supaya tidak ada data basi yang ter-cache.
        $draftStatus = $draftStatus ?? session()->get("review_needlist_{$needlist->id}", []);

        $details = $needlist->details->map(function ($d) use ($draftStatus) {
            $draft = $draftStatus[$d->id] ?? null;

            return [
                'id' => $d->id,
                'id_variasi' => $d->id_variasi,
                'barcode' => $d->variasi->barcode ?? '-',
                'nama_master' => $d->variasi->m_barang->nama_barang ?? '-',
                'nama_variasi' => $d->variasi->nama_variasi ?? '-',
                'supplier' => $d->supplierBarang->supplier->nama_supplier ?? '-',
                'harga_beli' => $d->supplierBarang->harga_beli ?? 0,
                'qty' => $d->qty,
                'stock' => $d->variasi->stock ?? 0,
                'status' => $draft['status'] ?? $d->status,
                'rejected_reason' => $draft['rejected_reason'] ?? $d->rejected_reason ?? null,
            ];
        });

        // Kelompokkan berdasarkan master
        $grouped = $details->groupBy('nama_master');

        // Cek apakah semua sudah direview
        $semuaSudahDireview = $details->every(fn($d) => in_array($d['status'], ['approved', 'rejected']));
    @endphp

    {{-- LIST ITEM PER MASTER --}}
    @foreach ($grouped as $master => $items)
        <div class="card mb-3 shadow-sm">

            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>
                    <i class="fas fa-box me-1 text-secondary"></i>
                    {{ $master }}
                </strong>
                <span class="badge bg-info">
                    {{ $items->count() }} item
                </span>
            </div>

            <div class="card-body p-2">
                <table class="table table-bordered table-striped table-hover table-sm mb-0">

                    <thead class="text-center">
                        <tr>
                            <th>Barcode</th>
                            <th>Variasi</th>
                            <th style="width:8%">Qty</th>
                            <th style="width:8%">Stock</th>
                            <th>Harga Beli</th>
                            <th>Supplier</th>
                            <th style="width:10%">Status</th>
                            <th style="width:15%">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($items as $detail)
                            <tr>
                                <td>{{ $detail['barcode'] }}</td>
                                <td>{{ $detail['nama_variasi'] }}</td>
                                <td class="text-center">
                                    <span class="badge bg-secondary">
                                        {{ $detail['qty'] }}
                                    </span>
                                </td>
                                <td class="text-center">{{ $detail['stock'] ?? '-' }}</td>
                                <td>{{ number_format($detail['harga_beli'] ?? 0, 0, ',', '.') }}</td>
                                <td>{{ $detail['supplier'] ?? '-' }}</td>
                                <td class="text-center">
                                    @if ($detail['status'] == 'approved')
                                        <span class="badge bg-success">Approved</span>
                                    @elseif ($detail['status'] == 'rejected')
                                        <span class="badge bg-danger">Rejected</span>
                                        @if($detail['rejected_reason'])
                                            <div class="text-muted small mt-1">
                                                {{ $detail['rejected_reason'] }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="badge bg-secondary">Pending</span>
                                    @endif
                                </td>
                                <td class="text-center">

                                    @if ($detail['status'] == 'pending')

                                        {{-- APPROVE --}}
                                        <form method="POST" action="{{ route('needlist.approveTemp', $needlist->id) }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="detail_id" value="{{ $detail['id'] }}">
                                            <button class="btn btn-outline-success btn-sm" title="Approve">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>

                                        {{-- REJECT --}}
                                        <form method="POST" action="{{ route('needlist.rejectTemp', $needlist->id) }}" 
                                              class="d-inline mt-1">
                                            @csrf
                                            <input type="hidden" name="detail_id" value="{{ $detail['id'] }}">
                                            <input type="text" 
                                                   name="rejected_reason" 
                                                   class="form-control form-control-sm mt-1" 
                                                   placeholder="Alasan tolak" 
                                                   required>
                                            <button class="btn btn-outline-danger btn-sm mt-1" title="Reject">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>

                                    @else
                                        <span class="text-muted small">
                                            Tindakan selesai
                                        </span>
                                    @endif

                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                </table>
            </div>
        </div>
    @endforeach

    {{-- TOOLBAR SIMPAN REVIEW FINAL --}}
    @if($semuaSudahDireview && $needlist->status == 'submitted')
        <div class="card mt-3">
            <div class="card-body d-flex justify-content-end">

                <form method="POST" action="{{ route('needlist.submitReview', $needlist->id) }}">
                    @csrf
                    <button class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Simpan Hasil Review
                    </button>
                </form>

            </div>
        </div>
    @endif

</div>
@endsection
