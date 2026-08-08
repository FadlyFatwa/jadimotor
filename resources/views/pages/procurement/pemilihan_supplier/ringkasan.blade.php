@extends('layouts.main')

@section('title', 'Daftar Kebutuhan - ' . $needlist->kode_needlist)

@section('content')
<div class="container-fluid">

    @php
        $statusBadge = [
            'inquiry_created'       => ['primary', 'Konfirmasi Harga Dibuat'],
            'selection_in_progress' => ['info',    'Pemilihan Supplier'],
            'po_issued'             => ['dark',    'Surat Pesanan Diterbitkan'],
            'completed'             => ['success', 'Selesai'],
        ][$needlist->status] ?? ['secondary', $needlist->status];

        $tierColorMap = ['OEM' => 'primary', 'Original' => 'success', 'Aftermarket' => 'warning', 'KW' => 'secondary'];

        // Lookup: id_variasi => [{supplier_nama, kode, harga, qty, estimasi}, ...]
        $inquiryByVariasi = collect();
        foreach ($needlist->supplierInquiries as $inq) {
            foreach ($inq->items as $inqItem) {
                if (!$inquiryByVariasi->has($inqItem->id_variasi)) {
                    $inquiryByVariasi->put($inqItem->id_variasi, collect());
                }
                $inquiryByVariasi->get($inqItem->id_variasi)->push([
                    'supplier_nama' => $inq->supplier->nama_supplier ?? '-',
                    'kode'          => $inq->supplier->kode_supplier ?? '-',
                    'harga'         => $inqItem->harga_penawaran,
                    'qty'           => $inqItem->qty,
                    'estimasi'      => $inqItem->estimasi_pengiriman,
                ]);
            }
        }

        // ── SAW Grouping untuk indikator visual ────────────────────────────
        // Mengelompokkan item berdasarkan: master_barang + irisan vehicle generation + tier.
        // Dipakai untuk memberi tanda pada baris yang akan masuk satu kelompok perbandingan SAW.
        $itemArr = $items->values()->all();
        $n       = count($itemArr);
        $parent  = range(0, $n - 1);

        $find = function (int $x) use (&$parent, &$find): int {
            while ($parent[$x] !== $x) { $parent[$x] = $parent[$parent[$x]]; $x = $parent[$x]; }
            return $x;
        };

        $genSets = [];
        foreach ($itemArr as $idx => $detail) {
            $genSets[$idx] = ($detail->variasi->vehicleGenerations ?? collect())->pluck('id')->toArray();
        }

        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                $di = $itemArr[$i]; $dj = $itemArr[$j];
                if (($di->variasi->m_barang->id_barang ?? null) !== ($dj->variasi->m_barang->id_barang ?? null)) continue;
                if (($di->variasi->tier ?? null) !== ($dj->variasi->tier ?? null)) continue;
                if (empty(array_intersect($genSets[$i], $genSets[$j]))) continue;
                $ri = $find($i); $rj = $find($j);
                if ($ri !== $rj) $parent[$ri] = $rj;
            }
        }

        $groupIdByIdx = [];
        $groupRootMap = [];
        $gCounter = 0;
        for ($i = 0; $i < $n; $i++) {
            $root = $find($i);
            if (!isset($groupRootMap[$root])) $groupRootMap[$root] = $gCounter++;
            $groupIdByIdx[$i] = $groupRootMap[$root];
        }

        $groupSizes = array_count_values($groupIdByIdx);

        $groupEffSuppliers = [];
        for ($i = 0; $i < $n; $i++) {
            $gid = $groupIdByIdx[$i];
            $vid = $itemArr[$i]->id_variasi;
            $confirmed = ($inquiryByVariasi->get($vid) ?? collect())->filter(fn($s) => !empty($s['harga']))->count();
            $groupEffSuppliers[$gid] = ($groupEffSuppliers[$gid] ?? 0) + $confirmed;
        }

        $sawPalette      = ['#3498db','#e67e22','#2ecc71','#9b59b6','#e74c3c','#1abc9c','#f39c12'];
        $multiGroupColor = [];
        $multiGroupLabel = [];
        $ci = 0; $ml = 1;
        foreach ($groupSizes as $gid => $sz) {
            if ($sz > 1) {
                $multiGroupColor[$gid] = $sawPalette[$ci++ % count($sawPalette)];
                $multiGroupLabel[$gid] = $ml++;
            }
        }
    @endphp

    {{-- Header --}}
    <div class="card mb-3 shadow-sm">
        <div class="card-body py-2 px-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:.5rem;">
                <div>
                    <span class="font-weight-bold" style="font-size:1rem;">
                        <i class="fas fa-clipboard-check mr-1 text-primary"></i> Daftar Kebutuhan
                    </span>
                    <span class="badge badge-{{ $statusBadge[0] }} ml-2" style="font-size:.7rem;">{{ $statusBadge[1] }}</span>
                    <div class="text-muted" style="font-size:.75rem; margin-top:1px;">
                        {{ $needlist->kode_needlist }} &mdash; {{ $needlist->created_at->format('d M Y') }}
                    </div>
                </div>
                <a href="{{ route('pemilihan-supplier.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali
                </a>
            </div>
        </div>
    </div>

    {{-- Tabel Item Kebutuhan --}}
    <div class="card shadow-sm mb-3">
        <div class="card-header py-2 bg-light d-flex align-items-center">
            <strong style="font-size:.9rem;">
                <i class="fas fa-clipboard-list mr-1 text-secondary"></i> Daftar Item Kebutuhan
            </strong>
            <span class="badge badge-secondary ml-2">{{ $items->count() }}</span>
            <small class="text-muted ml-auto" style="font-size:.75rem;">
                Klik <i class="fas fa-search fa-xs"></i> untuk detail item
            </small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0" style="font-size:.85rem;">
                    <thead class="bg-light">
                        <tr>
                            <th class="px-3 text-center" style="width:4%">#</th>
                            <th style="width:22%">Barang</th>
                            <th style="width:25%">Variasi</th>
                            <th class="text-center" style="width:7%">Qty</th>
                            <th class="text-center" style="width:16%">Supplier</th>
                            <th class="text-center" style="width:16%">Konfirmasi Harga</th>
                            <th class="text-center" style="width:5%"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $i => $detail)
                            @php
                                $v = $detail->variasi;
                                $isConfirmed    = $confirmedVariasiIds->contains($detail->id_variasi);
                                $inqSuppliers   = $inquiryByVariasi->get($detail->id_variasi) ?? collect();
                                $confirmedCount = $inqSuppliers->filter(fn ($s) => !empty($s['harga']))->count();
                                $totalInqCount  = $inqSuppliers->count();

                                // SAW group info
                                $gid          = $groupIdByIdx[$i];
                                $isMultiGroup = ($groupSizes[$gid] ?? 1) > 1;
                                $gColor       = $multiGroupColor[$gid] ?? null;
                                $gLabel       = $multiGroupLabel[$gid] ?? null;
                                $effSupCount  = $groupEffSuppliers[$gid] ?? $confirmedCount;

                                $svList = $v->suppliervariasi ?? collect();

                                $kendaraanList = ($v->vehicleGenerations ?? collect())->map(fn ($g) => [
                                    'nama'        => $g->vehicle->name ?? '-',
                                    'manufacturer'=> $g->vehicle->manufacturer ?? '-',
                                    'generasi'    => $g->code ?? '',
                                    'tahun_mulai' => $g->start_year,
                                    'tahun_akhir' => $g->end_year,
                                ])->values()->toArray();

                                $supplierRegistered = $svList->map(fn ($sv) => [
                                    'nama'       => $sv->supplier->nama_supplier ?? '-',
                                    'kode'       => $sv->supplier->kode_supplier ?? '-',
                                    'harga_beli' => $sv->harga_beli,
                                ])->values()->toArray();

                                $itemDetailData = [
                                    'nama_variasi'        => $v->nama_variasi ?? '-',
                                    'barcode'             => $v->barcode ?? '-',
                                    'part_number'         => $v->part_number ?? '-',
                                    'tier'                => $v->tier,
                                    'stock'               => (int) ($v->stock ?? 0),
                                    'harga_jual'          => (float) ($v->harga_jual ?? 0),
                                    'master_nama'         => $v->m_barang->nama_barang ?? '-',
                                    'master_kode'         => $v->m_barang->kode_barang ?? '-',
                                    'master_kategori'     => optional($v->m_barang->kategori)->nama_kategori ?? '-',
                                    'master_desc'         => $v->m_barang->description ?? '-',
                                    'kendaraan'           => $kendaraanList,
                                    'qty'                 => $detail->qty,
                                    'confirmed_count'     => $confirmedCount,
                                    'total_inq_count'     => $totalInqCount,
                                    'supplier_registered' => $supplierRegistered,
                                    'supplier_inquiry'    => $inqSuppliers->values()->toArray(),
                                ];
                            @endphp
                            <tr style="{{ $isMultiGroup ? 'border-left:3px solid '.$gColor.';' : 'border-left:3px solid transparent;' }}">
                                <td class="px-3 text-center text-muted">{{ $i + 1 }}</td>
                                <td>
                                    {{ $v->m_barang->nama_barang ?? '-' }}
                                </td>
                                <td>
                                    {{ $v->nama_variasi ?? '-' }}
                                    @if($v->tier)
                                        <span class="text-muted" style="font-size:.78rem;">&middot; {{ $v->tier }}</span>
                                    @endif
                                    @if($v->barcode)
                                        <br><span class="text-muted" style="font-size:.75rem;">{{ $v->barcode }}</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $detail->qty }}</td>
                                <td class="text-center">
                                    @if($effSupCount > 1)
                                        <span class="text-info" style="font-size:.8rem;">
                                            <i class="fas fa-users mr-1"></i>Multi ({{ $effSupCount }})
                                        </span>
                                    @elseif($effSupCount === 1)
                                        <span class="text-muted" style="font-size:.8rem;">
                                            <i class="fas fa-user mr-1"></i>Tunggal
                                        </span>
                                    @else
                                        <span class="text-muted" style="font-size:.8rem;">—</span>
                                    @endif
                                    @if($isMultiGroup)
                                        <br><small style="color:{{ $gColor }}; font-size:.7rem;">
                                            <i class="fas fa-layer-group mr-1"></i>Kel. {{ $gLabel }}
                                        </small>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($isConfirmed)
                                        <span class="text-success" style="font-size:.8rem;">
                                            <i class="fas fa-check mr-1"></i>Ada
                                        </span>
                                    @else
                                        <span class="text-muted" style="font-size:.8rem;">Belum</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <button type="button"
                                            class="btn btn-xs btn-outline-secondary btn-detail-ringkasan"
                                            data-detail='@json($itemDetailData)'
                                            title="Lihat detail">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-3">Tidak ada item.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if(!empty($multiGroupLabel))
                <div class="px-3 py-2 border-top" style="font-size:.75rem; color:#6c757d;">
                    <i class="fas fa-layer-group mr-1"></i>
                    <strong>Kelompok SAW:</strong>
                    @foreach($multiGroupLabel as $gid => $label)
                        <span class="mr-3">
                            <span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:{{ $multiGroupColor[$gid] }};margin-right:3px;"></span>
                            Kel. {{ $label }} — item dalam satu kelompok perbandingan supplier
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="card shadow-sm" style="position:sticky; bottom:0; z-index:1020;">
        <div class="card-body py-3 d-flex justify-content-end">
            <a href="{{ route('pemilihan-supplier.show', $needlist->id) }}" class="btn btn-primary">
                Lanjut ke Pilih Supplier <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
    </div>

</div>

{{-- Modal Detail Item --}}
<div class="modal fade" id="modalDetailRingkasan" tabindex="-1" role="dialog" aria-labelledby="modalDetailRingkasanLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header py-2 bg-light">
                <h6 class="modal-title" id="modalDetailRingkasanLabel">Detail Item</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="modalDetailRingkasanBody" style="font-size:.85rem;"></div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
    var tierColors = { OEM: 'primary', Original: 'success', Aftermarket: 'warning', KW: 'secondary' };

    function fmt(n) {
        if (n === null || n === undefined || n === '') return '-';
        return parseInt(n).toLocaleString('id-ID');
    }
    function fmtDate(s) {
        if (!s) return '-';
        try {
            var d = new Date(s);
            var m = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'];
            return d.getDate() + ' ' + m[d.getMonth()] + ' ' + d.getFullYear();
        } catch (e) { return s; }
    }
    function trow(label, val) {
        return '<tr>'
            + '<td class="text-muted align-middle py-1" style="width:44%;font-size:.82rem;">' + label + '</td>'
            + '<td class="align-middle py-1">' + val + '</td>'
            + '</tr>';
    }
    function sec(title) {
        return '<p class="mb-1 mt-3 text-secondary border-bottom pb-1" style="font-size:.8rem;font-weight:600;">' + title + '</p>';
    }

    $(document).on('click', '.btn-detail-ringkasan', function () {
        var d = $(this).data('detail');
        if (!d) return;

        var tierBadge = d.tier
            ? '<span class="badge badge-' + (tierColors[d.tier] || 'secondary') + ' ml-1" style="font-size:.65rem;">' + d.tier + '</span>'
            : '';

        var supLabel = d.confirmed_count > 1
            ? '<span class="text-info"><i class="fas fa-users mr-1"></i>Multi Supplier (' + d.confirmed_count + ')</span>'
            : (d.confirmed_count === 1
                ? '<span class="text-muted"><i class="fas fa-user mr-1"></i>Supplier Tunggal</span>'
                : '<span class="text-muted">Belum ada konfirmasi</span>');

        var stockText = d.stock <= 0
            ? '<span class="text-danger">Habis</span>'
            : (d.stock <= 5
                ? '<span class="text-warning">' + d.stock + ' unit</span>'
                : d.stock + ' unit');

        $('#modalDetailRingkasanLabel').html(
            '<strong>' + d.nama_variasi + '</strong>' + (tierBadge ? ' ' + tierBadge : '')
        );

        // ── Kiri ────────────────────────────────────────────────────────────
        var left = sec('Informasi Barang');
        left += '<table class="table table-xs table-sm mb-0"><tbody>';
        left += trow('Master Barang', '<strong>' + d.master_nama + '</strong>');
        left += trow('Kode', d.master_kode || '-');
        left += trow('Kategori', d.master_kategori || '-');
        left += trow('Deskripsi', d.master_desc || '<span class="text-muted">-</span>');
        left += '</tbody></table>';

        left += sec('Detail Variasi');
        left += '<table class="table table-xs table-sm mb-0"><tbody>';
        left += trow('Nama Variasi', d.nama_variasi);
        left += trow('Barcode', d.barcode && d.barcode !== '-' ? d.barcode : '-');
        left += trow('No. Part', d.part_number && d.part_number !== '-' ? d.part_number : '-');
        left += trow('Grade / Tier', tierBadge || '-');
        left += trow('Harga Jual', d.harga_jual > 0 ? 'Rp ' + fmt(d.harga_jual) : '-');
        left += trow('Stok', stockText);
        left += trow('Qty Order', (d.qty || '-') + ' pcs');
        left += '</tbody></table>';

        // ── Kanan ────────────────────────────────────────────────────────────
        var right = sec('Kompatibilitas Kendaraan');
        if (d.kendaraan && d.kendaraan.length > 0) {
            right += '<ul class="list-unstyled mb-0" style="font-size:.82rem;">';
            d.kendaraan.forEach(function (k) {
                var tahun = k.tahun_mulai
                    ? ' (' + String(k.tahun_mulai).slice(-2) + '–' + (k.tahun_akhir ? String(k.tahun_akhir).slice(-2) : 'skrg') + ')'
                    : '';
                right += '<li class="mb-1 text-dark">'
                    + k.nama + (k.generasi ? ' · ' + k.generasi : '') + tahun
                    + '</li>';
            });
            right += '</ul>';
        } else {
            right += '<span class="text-muted" style="font-size:.82rem;">Semua kendaraan / Tidak spesifik</span>';
        }

        right += sec('Supplier Terdaftar');
        if (d.supplier_registered && d.supplier_registered.length > 0) {
            right += '<table class="table table-xs table-sm table-bordered mb-0" style="font-size:.8rem;">'
                + '<thead class="thead-light"><tr><th>Supplier</th><th>Kode</th><th class="text-right">Harga Beli (Ref)</th></tr></thead><tbody>';
            d.supplier_registered.forEach(function (s) {
                right += '<tr><td>' + s.nama + '</td><td class="text-muted">' + (s.kode || '-')
                    + '</td><td class="text-right">Rp ' + fmt(s.harga_beli) + '</td></tr>';
            });
            right += '</tbody></table>';
        } else {
            right += '<span class="text-muted" style="font-size:.82rem;">Belum ada.</span>';
        }

        right += sec('Konfirmasi Harga — ' + supLabel);
        if (d.supplier_inquiry && d.supplier_inquiry.length > 0) {
            right += '<table class="table table-xs table-sm table-bordered mb-0" style="font-size:.8rem;">'
                + '<thead class="thead-light"><tr><th>Supplier</th><th class="text-right">Harga</th>'
                + '<th class="text-center">Qty</th></tr></thead><tbody>';
                    d.supplier_inquiry.forEach(function (s) {
                var harga = s.harga
                    ? 'Rp ' + fmt(s.harga)
                    : '<span class="text-muted">Belum diisi</span>';
                right += '<tr><td>' + s.supplier_nama + '</td><td class="text-right">' + harga
                    + '</td><td class="text-center">' + (s.qty || '-')
                    + '</td></tr>';
            });
            right += '</tbody></table>';
        } else {
            right += '<span class="text-muted" style="font-size:.82rem;">Belum ada konfirmasi harga untuk item ini.</span>';
        }

        var body = '<div class="row">'
            + '<div class="col-md-5">' + left + '</div>'
            + '<div class="col-md-7">' + right + '</div>'
            + '</div>';

        $('#modalDetailRingkasanBody').html(body);
        $('#modalDetailRingkasan').modal('show');
    });
})();
</script>
@endsection
