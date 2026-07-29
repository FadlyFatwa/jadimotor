@extends('layouts.main')

@section('title', 'Pemilihan Supplier - ' . $needlist->kode_needlist)

@section('content')
<div class="container-fluid">

    @php
        $statusBadge = [
            'inquiry_created'       => ['primary', 'Konfirmasi Harga Dibuat'],
            'selection_in_progress' => ['info',    'Pemilihan Supplier'],
            'po_issued'             => ['dark',    'Surat Pesanan Diterbitkan'],
            'completed'             => ['success', 'Selesai'],
        ][$needlist->status] ?? ['secondary', $needlist->status];

        $isPoIssued   = in_array($needlist->status, ['po_issued', 'completed']);
        $isPreInquiry = in_array($needlist->status, ['draft', 'submitted', 'approved', 'rejected']);

        $groupsCollection = collect($groups);
        $groupsNeedingSaw = $groupsCollection->where('unique_supplier_count', '>=', 2);

        $byMasterBarang = $groupsCollection->groupBy('master_barang_id');

        $tierColorMap  = ['OEM' => 'primary', 'Original' => 'success', 'Aftermarket' => 'warning', 'KW' => 'secondary'];
        $tierBorderMap = ['primary' => '#007bff', 'success' => '#28a745', 'warning' => '#e6a817', 'secondary' => '#6c757d'];
    @endphp

    <div class="card mb-3 shadow-sm">
        <div class="card-body py-2 px-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:.5rem;">
                <div>
                    <span class="font-weight-bold" style="font-size:1rem;">
                        <i class="fas fa-handshake mr-1 text-primary"></i> Pemilihan Supplier
                    </span>
                    <span class="badge badge-{{ $statusBadge[0] }} ml-2" style="font-size:.7rem;">{{ $statusBadge[1] }}</span>
                    <div class="text-muted" style="font-size:.75rem; margin-top:1px;">
                        {{ $needlist->kode_needlist }} &mdash; {{ $needlist->created_at->format('d M Y') }}
                    </div>
                </div>
                <div class="flex-shrink-0 text-nowrap">
                    @if(!$isPoIssued && !$isPreInquiry && $groupsNeedingSaw->isNotEmpty())
                        <button type="button" id="btnRekomendasiSemua" class="btn btn-sm btn-outline-info"
                                data-needlist-id="{{ $needlist->id }}" title="Hitung ulang rekomendasi memakai data konfirmasi harga terbaru">
                            <i class="fas fa-calculator mr-1"></i>
                            <span class="btn-label">Hitung Ulang Rekomendasi</span>
                        </button>
                    @endif
                    <a href="{{ route('needlist.show', $needlist->id) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-clipboard-list mr-1"></i> Detail Needlist
                    </a>
                    <a href="{{ route('pemilihan-supplier.ringkasan', $needlist->id) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali ke Ringkasan
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if($sawError)
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            <strong>Rekomendasi SAW belum bisa dihitung:</strong> {{ $sawError }}
            @if(auth()->user()->role !== 'procurement')
                Periksa halaman <a href="{{ route('saw.kriteria.index') }}">Kriteria &amp; Bobot</a>.
            @else
                Hubungi Bagian yang mengelola Kriteria &amp; Bobot SAW.
            @endif
        </div>
    @endif

    @if($isPoIssued)
        <div class="alert alert-info">
            <i class="fas fa-file-invoice mr-1"></i>
            <strong>Purchase Order sudah diterbitkan.</strong> Pemilihan tidak dapat diubah.
        </div>
    @elseif($isPreInquiry)
        <div class="alert alert-secondary">
            <i class="fas fa-info-circle mr-1"></i> Silakan lengkapi konfirmasi harga supplier terlebih dahulu.
        </div>
    @endif

    <form method="POST" id="formPemilihan">
        @csrf
        @if($groupsCollection->isEmpty())
            <div class="alert alert-secondary">
                <i class="fas fa-info-circle mr-1"></i> Tidak ada item yang memerlukan pemilihan supplier.
            </div>
        @else
            @foreach($byMasterBarang as $masterGroups)
                @php
                    $masterNama          = $masterGroups->first()['master_barang_nama'];
                    $byCluster           = $masterGroups->groupBy('cluster_idx');
                    $hasMultipleClusters = $byCluster->count() > 1;

                    // Nama kendaraan kompatibel — dirangkum dari semua cluster/tier
                    // milik master barang ini, ditampilkan di header card.
                    $allKendaraanNames = $masterGroups->flatMap(fn ($g) => $g['rows'])
                        ->flatMap(fn ($r) => $r['item']->variasi->vehicleGenerations ?? collect())
                        ->map(fn ($vg) => $vg->vehicle->name ?? null)
                        ->filter()->unique()->sort()->values();
                @endphp
                <div class="card mb-4 shadow-sm">
                    <div class="card-header py-2">
                        <strong><i class="fas fa-box mr-1 text-secondary"></i> {{ $masterNama }}</strong>
                        @if($allKendaraanNames->isNotEmpty())
                            <small class="text-muted ml-2" style="font-size:.78rem;">
                                <i class="fas fa-car mr-1"></i>{{ $allKendaraanNames->implode(' · ') }}
                            </small>
                        @endif
                    </div>

                    @foreach($byCluster as $clusterGroups)
                        @if($hasMultipleClusters)
                            <div class="px-3 py-2 d-flex align-items-center border-top">
                                <i class="fas fa-car mr-2 text-muted" style="font-size:.85rem"></i>
                                <span class="font-weight-bold" style="font-size:.82rem; text-transform:uppercase; letter-spacing:.03em;">
                                    {{ $clusterGroups->first()['cluster_label'] }}
                                </span>
                            </div>
                        @endif

                        @foreach($clusterGroups as $group)
                            @php
                                $tierColor       = $tierColorMap[$group['tier']] ?? 'secondary';
                                $tierBorderColor = $tierBorderMap[$tierColor] ?? '#6c757d';
                                $needsSaw        = $group['unique_supplier_count'] >= 2;
                                $existingPerh    = $perhitunganByTierKey[$group['tier_key']] ?? null;
                                $recDetail       = $existingPerh?->details->firstWhere('is_recommended', 1);

                                // Exclude menyisakan tepat 1 kandidat (alternatif lain belum
                                // punya historis) — ditetapkan langsung tanpa SawPerhitungan.
                                $autoAssign = $autoAssignedByTierKey[$group['tier_key']] ?? null;

                                $rowsSorted = $group['rows']->sortBy(fn ($r) => $r['item']->variasi->nama_variasi ?? '')->values();
                                $variasiRowSpans = $rowsSorted->groupBy(fn ($r) => $r['item']->id_variasi)->map->count();
                                $prevVariasiId   = null;

                                // Cuma 1 supplier terpilih boleh aktif per KELOMPOK (panel), bukan per
                                // variasi — beberapa variasi/merk dalam satu grade group adalah alternatif
                                // yang saling bersaing untuk kebutuhan yang sama, jadi "sudah tersimpan"
                                // dicek untuk seluruh kelompok, bukan per variasi.
                                $groupHasSavedSelection = $rowsSorted->contains(fn ($r) => $r['item']->status === 'selected');

                                // Label kelompok untuk pesan validasi ("belum lengkap") di JS.
                                $groupLabel = $masterNama . ' — ' . $group['tier_label']
                                    . ($hasMultipleClusters ? ' (' . $group['cluster_label'] . ')' : '');

                                // Jumlah supplier unik (yang sudah isi harga) per variasi — dipakai untuk
                                // badge "Multi Supplier" (info tambahan per variasi, tidak memengaruhi
                                // status terpilih).
                                $variasiSupplierCount = $rowsSorted
                                    ->groupBy(fn ($r) => $r['item']->id_variasi)
                                    ->map(fn ($rows) => $rows
                                        ->filter(fn ($r) => !empty($r['item']->harga_penawaran))
                                        ->unique(fn ($r) => $r['supplier']->id_supplier)
                                        ->count());
                            @endphp

                            <div class="mx-3 mb-3 mt-3 rounded border"
                                 style="border-left:4px solid {{ $tierBorderColor }} !important;">

                                <div class="px-3 py-2 d-flex align-items-center justify-content-between flex-wrap border-bottom" style="gap:.4rem;">
                                    <div class="d-flex align-items-center flex-wrap" style="gap:.4rem;">
                                        <span class="badge badge-{{ $tierColor }}" style="font-size:.78rem;">{{ $group['tier_label'] }}</span>
                                        <small class="text-muted" style="font-size:.78rem;">
                                            {{ count($group['variasi_ids']) }} variasi &middot; {{ $group['rows']->count() }} supplier
                                        </small>
                                    </div>
                                    @if($needsSaw)
                                        <small class="saw-result-line" style="font-size:.8rem;" data-panel-key="{{ $group['panel_key'] }}">
                                            @if($autoAssign)
                                                <i class="fas fa-check-circle text-info mr-1"></i>
                                                Ditetapkan otomatis: <strong>{{ $autoAssign['recommended']['nama'] }}</strong>
                                                &mdash; Rp {{ number_format($autoAssign['recommended']['harga'], 0, ',', '.') }}
                                                <span class="text-muted">(alternatif lain belum memiliki data kinerja)</span>
                                            @elseif($recDetail)
                                               Direkomendasikan: <strong>{{ $recDetail->supplier->nama_supplier ?? '-' }}</strong>
                                                &mdash; Rp {{ number_format($recDetail->nilai_c1, 0, ',', '.') }}
                                                &middot; estimasi tiba {{ (int) round($recDetail->nilai_c3) }} hari
                                            @else
                                                <span class="text-muted"><i class="fas fa-lock mr-1"></i>Belum bisa dihitung otomatis &mdash; lengkapi data konfirmasi harga lalu klik "Hitung Ulang Rekomendasi"</span>
                                            @endif
                                        </small>
                                    @endif
                                </div>

                                <div class="px-3 py-3">
                                    <table class="table table-bordered table-hover table-sm mb-0">
                                        <thead class="text-center bg-light">
                                            <tr>
                                                <th>Variasi</th>
                                                <th>Supplier</th>
                                                <th style="width:8%">Qty</th>
                                                <th style="width:15%">Harga Konfirmasi</th>
                                                <th style="width:15%">Estimasi Tiba</th>
                                                <th style="width:12%">Pilih</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($rowsSorted as $row)
                                                @php
                                                    $item       = $row['item'];
                                                    $idVariasi  = $item->id_variasi;
                                                    $disableRow = empty($item->harga_penawaran) || $isPoIssued;
                                                    // "Pilih" terkunci sampai rekomendasi kelompok ini dijalankan
                                                    // (hanya berlaku untuk kelompok dengan >=2 supplier yang berkompetisi)
                                                    // — kecuali kelompok ini sudah ditetapkan otomatis lewat exclude
                                                    // (tidak pernah punya SawPerhitungan, jadi $existingPerh selalu null).
                                                    $pilihLocked = $needsSaw && !$existingPerh && !$autoAssign && !$isPoIssued;

                                                    $isFirstOfVariasi = $idVariasi !== $prevVariasiId;
                                                    $prevVariasiId    = $idVariasi;

                                                    $rowDetail = $existingPerh?->details->first(
                                                        fn ($d) => (int) $d->supplier_id === (int) $row['supplier']->id_supplier
                                                            && (int) $d->id_variasi === (int) $idVariasi
                                                    );
                                                    $isRecommendedRow = (bool) ($rowDetail?->is_recommended);

                                                    // Baris ini yang ditetapkan otomatis (exclude menyisakan 1
                                                    // kandidat) untuk kelompok ini.
                                                    $isAutoAssignedRow = $autoAssign
                                                        && (int) $autoAssign['recommended']['supplier_id'] === (int) $row['supplier']->id_supplier
                                                        && (int) $autoAssign['recommended']['id_variasi'] === (int) $idVariasi;

                                                    // Supplier belum punya data historis kinerja sama sekali →
                                                    // dikecualikan dari perbandingan SAW (tidak bisa jadi kandidat
                                                    // maupun direkomendasikan/ditetapkan otomatis).
                                                    $rowHasHistoris = isset($supplierIdsWithHistoris[$row['supplier']->id_supplier]);
                                                    $isExcludedRow  = $needsSaw && !empty($item->harga_penawaran) && !$rowHasHistoris;

                                                    // Cuma 1 supplier (total) di SELURUH kelompok ini → tidak ada
                                                    // pilihan lain, jadi ditandai otomatis ("Supplier Tunggal")
                                                    // tanpa perlu rekomendasi SAW. Digating oleh !$needsSaw supaya
                                                    // kelompok dengan >=2 supplier (walau tersebar di variasi
                                                    // berbeda) tidak pernah punya baris "tunggal" palsu.
                                                    $isSingleSupplierRow = !$needsSaw && !empty($item->harga_penawaran);

                                                    // Sudah benar-benar disimpan ('selected') → pakai itu. Kalau
                                                    // belum pernah disimpan tapi baris ini rekomendasinya (SAW,
                                                    // ditetapkan otomatis via exclude, atau satu-satunya supplier
                                                    // tersedia di seluruh kelompok) → anggap tercentang juga
                                                    // (default sebelum user simpan/ubah manual). Dicek terhadap
                                                    // seluruh KELOMPOK, bukan per variasi, supaya cuma 1 baris
                                                    // yang tercentang di antara variasi-variasi yang bersaing.
                                                    $isChecked = $item->status === 'selected'
                                                        || (($isRecommendedRow || $isAutoAssignedRow || $isSingleSupplierRow) && !$groupHasSavedSelection);

                                                    // Pakai resolver yang sama dengan perhitungan SAW (historis lead
                                                    // time kalau ada, fallback ke estimasi konfirmasi harga) untuk SEMUA
                                                    // baris — supaya tidak ada baris yang "ketinggalan" memakai
                                                    // tanggal mentah hanya karena belum sempat ikut dihitung SAW.
                                                    $estimasiTiba = $item->harga_penawaran
                                                        ? $leadTimeResolver->estimasiTiba(
                                                            $row['supplier']->id_supplier,
                                                            $item->estimasi_pengiriman,
                                                            $row['inquiry']->created_at
                                                        )['tanggal']
                                                        : '-';

                                                    // Data untuk modal detail — hanya dibangun di baris pertama tiap variasi
                                                    $variasiDetailData = null;
                                                    if ($isFirstOfVariasi) {
                                                        $allRowsForVariasi = $rowsSorted->filter(
                                                            fn ($r) => $r['item']->id_variasi === $idVariasi
                                                        )->values();
                                                        $variasiDetailData = [
                                                            'nama_variasi'    => $item->variasi->nama_variasi ?? '-',
                                                            'barcode'         => $item->variasi->barcode ?? '-',
                                                            'part_number'     => $item->variasi->part_number ?? '-',
                                                            'tier'            => $item->variasi->tier,
                                                            'stock'           => (int) ($item->variasi->stock ?? 0),
                                                            'harga_jual'      => (float) ($item->variasi->harga_jual ?? 0),
                                                            'master_nama'     => $item->variasi->m_barang->nama_barang ?? '-',
                                                            'master_kode'     => $item->variasi->m_barang->kode_barang ?? '-',
                                                            'master_kategori' => optional($item->variasi->m_barang->kategori)->nama_kategori ?? '-',
                                                            'master_desc'     => $item->variasi->m_barang->description ?? '-',
                                                            'kendaraan'       => ($item->variasi->vehicleGenerations ?? collect())->map(fn ($g) => [
                                                                'nama'        => $g->vehicle->name ?? '-',
                                                                'manufacturer'=> $g->vehicle->manufacturer ?? '-',
                                                                'generasi'    => $g->code ?? '',
                                                                'tahun_mulai' => $g->start_year,
                                                                'tahun_akhir' => $g->end_year,
                                                            ])->values()->toArray(),
                                                            'qty'            => $item->qty,
                                                            'supplier_count' => $variasiSupplierCount[$idVariasi] ?? 0,
                                                            'suppliers'      => $allRowsForVariasi->map(fn ($r) => [
                                                                'nama'           => $r['supplier']->nama_supplier ?? '-',
                                                                'kode'           => $r['supplier']->kode_supplier ?? '-',
                                                                'harga'          => $r['item']->harga_penawaran,
                                                                'estimasi'       => $r['item']->estimasi_pengiriman,
                                                                'is_recommended' => (bool) ($existingPerh?->details->contains(
                                                                    fn ($d) => (int) $d->supplier_id === (int) $r['supplier']->id_supplier
                                                                        && (int) $d->id_variasi   === (int) $idVariasi
                                                                        && $d->is_recommended
                                                                )),
                                                            ])->values()->toArray(),
                                                        ];
                                                    }
                                                @endphp
                                                <tr class="saw-supplier-row
                                                    {{ $isChecked ? 'table-success' : ($disableRow ? 'table-secondary' : '') }}"
                                                    data-supplier-id="{{ $row['supplier']->id_supplier }}"
                                                    data-item-id="{{ $item->id }}"
                                                    data-variasi-id="{{ $idVariasi }}"
                                                    data-panel-key="{{ $group['panel_key'] }}"
                                                    data-group-label="{{ $groupLabel }}"
                                                    data-is-recommended="{{ ($isRecommendedRow || $isAutoAssignedRow) ? '1' : '0' }}">
                                                    @if($isFirstOfVariasi)
                                                        <td rowspan="{{ $variasiRowSpans[$idVariasi] }}" class="align-middle bg-white" style="min-width:130px;">
                                                            <div class="d-flex align-items-start justify-content-between" style="gap:.3rem;">
                                                                <span><i class="fas fa-tag mr-1 text-muted"></i>{{ $item->variasi->nama_variasi ?? '-' }}</span>
                                                                <button type="button"
                                                                        class="btn btn-xs btn-outline-info btn-detail-variasi flex-shrink-0"
                                                                        data-detail='@json($variasiDetailData)'
                                                                        title="Lihat detail item">
                                                                    <i class="fas fa-info-circle"></i>
                                                                </button>
                                                            </div>
                                                                            @if(($variasiSupplierCount[$idVariasi] ?? 0) > 1)
                                                                <span class="badge badge-info mt-1" style="font-size:.62rem;">
                                                                    <i class="fas fa-users mr-1"></i>Multi Supplier
                                                                </span>
                                                            @endif
                                                        </td>
                                                    @endif
                                                    <td class="cell-supplier-name">
                                                        {{ $row['supplier']->nama_supplier }}
                                                        @if($isAutoAssignedRow)
                                                            <span class="badge badge-info ml-1" style="font-size:.65rem;"
                                                                  title="Alternatif lain pada kelompok ini belum memiliki data kinerja (historis), jadi supplier ini ditetapkan langsung tanpa perhitungan SAW.">
                                                                <i class="fas fa-check-circle mr-1"></i>Ditetapkan Otomatis
                                                            </span>
                                                        @elseif($isRecommendedRow)
                                                            <span class="badge badge-warning ml-1 badge-rekomendasi" style="font-size:.65rem;">Direkomendasikan</span>
                                                        @elseif($isSingleSupplierRow)
                                                            <span class="badge badge-info ml-1" style="font-size:.65rem;">Supplier Tunggal</span>
                                                        @endif
                                                        @if($isExcludedRow)
                                                            <span class="badge badge-secondary ml-1" style="font-size:.65rem;"
                                                                  title="Supplier ini belum memiliki data historis kinerja (C2/C4/C5/C6), sehingga dikecualikan dari perbandingan SAW untuk kelompok ini. Bisa tetap dipilih manual.">
                                                                <i class="fas fa-info-circle mr-1"></i>Belum Ada Riwayat
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">{{ $item->qty }}</td>
                                                    <td>
                                                        @if($item->harga_penawaran)
                                                            Rp {{ number_format($item->harga_penawaran, 0, ',', '.') }}
                                                        @else
                                                            <span class="text-danger fst-italic">Belum diisi</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center cell-estimasi-tiba">{{ $estimasiTiba }}</td>
                                                    <td class="text-center">
                                                        @if(!$disableRow)
                                                            <button type="button"
                                                                    class="btn btn-sm btn-pilih-supplier {{ $isChecked ? 'btn-success' : 'btn-outline-secondary' }}"
                                                                    data-supplier-id="{{ $row['supplier']->id_supplier }}"
                                                                    data-variasi-id="{{ $idVariasi }}"
                                                                    data-panel-key="{{ $group['panel_key'] }}"
                                                                    data-item-id="{{ $item->id }}"
                                                                    @if($pilihLocked) disabled title="Data konfirmasi harga belum lengkap untuk dihitung otomatis" @endif>
                                                                @if($isChecked)
                                                                    <i class="fas fa-check mr-1"></i>Dipilih
                                                                @else
                                                                    Pilih
                                                                @endif
                                                            </button>
                                                        @else
                                                            <span class="text-muted">&mdash;</span>
                                                        @endif
                                                        <input type="checkbox"
                                                               name="selected_items[]"
                                                               value="{{ $item->id }}"
                                                               class="saw-checkbox d-none"
                                                               data-supplier-id="{{ $row['supplier']->id_supplier }}"
                                                               data-variasi-id="{{ $idVariasi }}"
                                                               data-panel-key="{{ $group['panel_key'] }}"
                                                               {{ $isChecked ? 'checked' : '' }}
                                                               {{ $disableRow ? 'disabled' : '' }}>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            @endforeach

            @if(!$isPoIssued && !$isPreInquiry)
                <div class="card mt-3 shadow-sm" style="position:sticky; bottom:0; z-index:99;">
                    <div class="card-body py-2 d-flex justify-content-between align-items-center flex-wrap" style="gap:.5rem;">
                        <button type="button" id="btnSimpanPilihan"
                                data-formaction="{{ route('supplier.selection.save', $needlist->id) }}"
                                class="btn btn-outline-primary">
                            <i class="fas fa-save mr-1"></i> Simpan Pilihan
                        </button>
                        <span class="text-muted" style="font-size:.82rem;">
                            Setelah simpan, terbitkan PO di
                            <a href="{{ route('needlist.show', $needlist->id) }}#pane-po">tab Surat Pesanan</a>.
                        </span>
                    </div>
                </div>
            @endif

            @if($isPoIssued)
                <div class="alert alert-success mt-3 mb-0">
                    <i class="fas fa-check-circle mr-1"></i> Purchase Order sudah diterbitkan.
                </div>
            @endif
        @endif
    </form>
</div>
{{-- ===== MODAL DETAIL ITEM ===== --}}
<div class="modal fade" id="modalDetailVariasi" tabindex="-1" role="dialog" aria-labelledby="modalDetailVariasiLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header py-2 bg-light">
                <h6 class="modal-title font-weight-bold" id="modalDetailVariasiLabel">
                    <i class="fas fa-info-circle mr-1 text-secondary"></i>Detail Item
                </h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="modalDetailVariasiBody" style="font-size:.85rem;"></div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@include('pages.procurement.pemilihan_supplier._scripts')
