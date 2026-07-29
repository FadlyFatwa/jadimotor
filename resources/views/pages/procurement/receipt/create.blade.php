@extends('layouts.main')

@section('content')
<div class="container">

    <h4 class="mb-3">Terima Barang - {{ $po->kode_po }}</h4>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <strong>Supplier</strong><br>
                    {{ $po->supplier->nama_supplier ?? '-' }}
                </div>
                <div class="col-md-4">
                    <strong>Tanggal PO</strong><br>
                    {{ \Carbon\Carbon::parse($po->tanggal_po)->format('d-m-Y') }}
                </div>
                <div class="col-md-4">
                    <strong>Status PO</strong><br>
                    {{ strtoupper(str_replace('_', ' ', $po->status)) }}
                </div>
            </div>
        </div>
    </div>

    @if($riwayat->isNotEmpty())
        <div class="card mb-3">
            <div class="card-header">
                <strong>Riwayat Penerimaan</strong>
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Kode</th>
                            <th>Tanggal Terima</th>
                            <th>Diterima Oleh</th>
                            <th class="text-center">Jumlah Item</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($riwayat as $r)
                            <tr>
                                <td>{{ $r->kode_receipt }}</td>
                                <td>{{ \Carbon\Carbon::parse($r->tanggal_terima)->format('d-m-Y') }}</td>
                                <td>{{ $r->user->name ?? '-' }}</td>
                                <td class="text-center">{{ $r->items->count() }}</td>
                                <td class="text-center">
                                    <a href="{{ route('receipts.show', $r->id) }}" class="btn btn-outline-info btn-sm">
                                        <i class="fas fa-eye"></i> Lihat
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('receipts.store', $po->id) }}">
        @csrf

        <div class="card mb-3">
            <div class="card-body">
                <label class="font-weight-bold">Tanggal Terima</label>
                <input type="date" name="tanggal_terima" class="form-control"
                       style="max-width:220px;"
                       value="{{ old('tanggal_terima', now()->format('Y-m-d')) }}" required>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <strong>Daftar Item PO</strong>
            </div>

            <div class="card-body p-0">
                <table class="table table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Variasi</th>
                            <th class="text-center">Qty Order</th>
                            <th class="text-center">Sudah Diterima</th>
                            <th class="text-center">Sisa</th>
                            <th class="text-center">Qty Terima Sekarang</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($po->items as $item)
                            @php
                                $received = $item->receiptItems->sum('qty_received');
                                $sisa = $item->qty_order - $received;
                            @endphp
                            <tr>
                                <td>
                                    {{ $item->variasi->m_barang->nama_barang ?? '' }}
                                    — {{ $item->variasi->nama_variasi ?? '-' }}
                                </td>
                                <td class="text-center">{{ $item->qty_order }}</td>
                                <td class="text-center">{{ $received }}</td>
                                <td class="text-center">{{ $sisa }}</td>
                                <td class="text-center">
                                    @if($sisa > 0)
                                        <input type="number"
                                               name="items[{{ $item->id }}][qty_received]"
                                               class="form-control text-center"
                                               min="0"
                                               max="{{ $sisa }}"
                                               value="{{ old('items.'.$item->id.'.qty_received', 0) }}">
                                    @else
                                        <span class="text-success fst-italic">
                                            Selesai
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="card-footer d-flex justify-content-between">
                <a href="{{ route('receipts.index') }}" class="btn btn-secondary">
                    ← Kembali
                </a>

                <div>
                    @if($po->status === 'partial_received')
                        <button type="button" class="btn btn-outline-danger" data-toggle="modal" data-target="#modalTutupPo">
                            Tutup PO
                        </button>
                    @endif

                    <button type="submit" class="btn btn-primary">
                        Simpan Penerimaan
                    </button>
                </div>
            </div>
        </div>
    </form>

    @if($po->status === 'partial_received')
        <div class="modal fade" id="modalTutupPo" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <form method="POST" action="{{ route('receipts.tutup', $po->id) }}">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Tutup PO {{ $po->kode_po }}</h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p class="text-danger">
                                PO ini belum terpenuhi 100%. Menutup PO akan menandainya
                                sebagai selesai dan tidak bisa menerima barang lagi.
                                Tindakan ini tidak bisa dibatalkan.
                            </p>
                            <label class="font-weight-bold">Alasan / Catatan</label>
                            <textarea name="catatan_tutup" class="form-control" rows="3" required
                                      placeholder="Contoh: supplier tidak bisa mengirim sisa barang"></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger">Tutup PO</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>
@endsection
