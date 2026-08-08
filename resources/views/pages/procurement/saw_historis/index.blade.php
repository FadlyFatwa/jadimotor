@extends('layouts.main')
@section('title', 'Data Historis Kinerja Supplier')

@php
    // Bagian Pembelian (role 'procurement') hanya boleh melihat data ini.
    $canManageHistoris = auth()->user()->role !== 'procurement';
@endphp

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h2 class="font-weight-bold mb-1" style="font-size:1.5rem">
                <i class="fas fa-history mr-2 text-primary" style="font-size:1.3rem"></i>Data Historis Kinerja
            </h2>
            <p class="text-muted mb-0" style="font-size:.875rem">
                Data C2–C6 sebagai input kriteria evaluasi per supplier
            </p>
        </div>
        @if($canManageHistoris)
            <div class="d-flex" style="gap:.5rem">
                <a href="{{ route('saw.historis.create') }}" class="btn btn-danger">
                    <i class="fas fa-plus mr-1"></i>Tambah Data
                </a>
            </div>
        @else
            <span class="badge badge-light border text-muted align-self-center" style="font-size:.75rem;">
                <i class="fas fa-eye mr-1"></i>Mode Lihat Saja — Bagian Pembelian
            </span>
        @endif
    </div>

    <div class="alert alert-info py-2 mb-3">
        <i class="fas fa-info-circle mr-1"></i>
        <strong>C2</strong> Termin Pembayaran &nbsp;|&nbsp;
        <strong>C3</strong> Lead Time (hari) &nbsp;|&nbsp;
        <strong>C4</strong> Akurasi Kuantitas (%) &nbsp;|&nbsp;
        <strong>C5</strong> Tingkat Pemenuhan (%) &nbsp;|&nbsp;
        <strong>C6</strong> Komunikasi
        @foreach($kriteriaCustom as $k)
            &nbsp;|&nbsp; <strong>{{ $k->kode }}</strong> {{ $k->nama }}
        @endforeach
        &nbsp;—&nbsp;
        <small>C1 (Harga) diambil dari Supplier Inquiry.</small>
    </div>

    <div class="card shadow-sm">
        <div class="card-body pb-0 pt-3 px-4">
            <form method="GET" class="d-flex align-items-center" style="gap:.5rem">
                <select name="supplier_id" class="form-control form-control-sm" style="width:220px; border-radius:6px">
                    <option value="">— Semua Supplier —</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id_supplier }}" {{ request('supplier_id') == $s->id_supplier ? 'selected' : '' }}>
                            {{ $s->nama_supplier }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="fas fa-search mr-1"></i>Filter
                </button>
                @if(request('supplier_id'))
                    <a href="{{ route('saw.historis.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                @endif
            </form>
        </div>
        <div class="card-body px-4 pt-3">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="sku-thead text-center">
                        <tr>
                            <th class="px-3 py-3 text-secondary" style="width:4%">#</th>
                            <th class="py-3 text-secondary text-left">Supplier</th>
                            <th class="py-3 text-secondary">Periode</th>
                            <th class="py-3 text-secondary">C2 Termin</th>
                            <th class="py-3 text-secondary">C3 Lead Time</th>
                            <th class="py-3 text-secondary">C4 Akurasi</th>
                            <th class="py-3 text-secondary">C5 Pemenuhan</th>
                            <th class="py-3 text-secondary">C6 Komunikasi</th>
                            @foreach($kriteriaCustom as $k)
                                <th class="py-3 text-secondary">{{ $k->kode }} {{ $k->nama }}</th>
                            @endforeach
                            <th class="py-3 text-secondary">Transaksi</th>
                            <th class="py-3 text-secondary" style="width:110px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $terminLabel = [1=>'Cash/Muka', 2=>'14 Hari', 3=>'30 Hari', 4=>'60 Hari', 5=>'90+ Hari'];
                            $komLabel    = [1=>'Sangat Buruk', 2=>'Buruk', 3=>'Cukup', 4=>'Baik', 5=>'Sangat Baik'];
                            $fmtNum = function ($v) {
                                $s = rtrim(rtrim(number_format((float) $v, 4, '.', ''), '0'), '.');
                                return $s === '' ? '0' : $s;
                            };
                        @endphp
                        @forelse($historis as $i => $h)
                            @php
                                $c2 = $h->details->first(fn($d) => $d->kriteria?->kode === 'C2');
                                $c3 = $h->details->first(fn($d) => $d->kriteria?->kode === 'C3');
                                $c4 = $h->details->first(fn($d) => $d->kriteria?->kode === 'C4');
                                $c5 = $h->details->first(fn($d) => $d->kriteria?->kode === 'C5');
                                $c6 = $h->details->first(fn($d) => $d->kriteria?->kode === 'C6');
                            @endphp
                            <tr>
                                <td class="text-center">{{ $historis->firstItem() + $i }}</td>
                                <td>{{ $h->supplier->nama_supplier ?? '-' }}</td>
                                <td class="text-center" style="white-space:nowrap; font-size:.82rem;">
                                    {{ \Carbon\Carbon::parse($h->periode_mulai)->format('d/m/Y') }}<br>
                                    <small class="text-muted">s/d</small><br>
                                    {{ \Carbon\Carbon::parse($h->periode_akhir)->format('d/m/Y') }}
                                </td>
                                <td class="text-center">
                                    @if($c2 !== null)
                                        {{ $terminLabel[(int) $c2->nilai] ?? '-' }}
                                    @else <span class="text-muted">-</span> @endif
                                </td>
                                <td class="text-center">
                                    @if($c3 !== null)
                                        {{ $fmtNum($c3->nilai) }} hari
                                    @else <span class="text-muted">-</span> @endif
                                </td>
                                <td class="text-center">
                                    @if($c4 !== null)
                                        <span class="{{ $c4->nilai < 70 ? 'text-danger font-weight-bold' : '' }}">{{ $fmtNum($c4->nilai) }}%</span>
                                    @else <span class="text-muted">-</span> @endif
                                </td>
                                <td class="text-center">
                                    @if($c5 !== null)
                                        <span class="{{ $c5->nilai < 70 ? 'text-danger font-weight-bold' : '' }}">{{ $fmtNum($c5->nilai) }}%</span>
                                    @else <span class="text-muted">-</span> @endif
                                </td>
                                <td class="text-center">
                                    @if($c6 !== null)
                                        <span class="{{ (int) $c6->nilai <= 2 ? 'text-danger font-weight-bold' : '' }}">{{ $komLabel[(int) $c6->nilai] ?? '-' }}</span>
                                    @else <span class="text-muted">-</span> @endif
                                </td>
                                @foreach($kriteriaCustom as $k)
                                    @php $dCustom = $h->details->firstWhere('kriteria_id', $k->id); @endphp
                                    <td class="text-center">
                                        @if($dCustom)
                                            {{ $fmtNum($dCustom->nilai) }}
                                        @else <span class="text-muted">-</span> @endif
                                    </td>
                                @endforeach
                                <td class="text-center">{{ $h->jumlah_transaksi }}</td>
                                <td class="text-center">
                                    @if($canManageHistoris)
                                        <a href="{{ route('saw.historis.edit', $h->id) }}"
                                           class="btn btn-xs btn-outline-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" action="{{ route('saw.historis.destroy', $h->id) }}" class="d-inline"
                                              data-confirm="Hapus data kinerja supplier {{ $h->supplier->nama_supplier ?? '' }}? Data ini dipakai sebagai input perhitungan SAW.">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-xs btn-outline-danger" title="Hapus"><i class="fas fa-trash"></i></button>
                                        </form>
                                    @else
                                        <span class="text-muted">&mdash;</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 10 + $kriteriaCustom->count() }}" class="text-center text-muted py-5">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                    Belum ada data historis.
                                    @if($canManageHistoris)
                                        <a href="{{ route('saw.historis.create') }}">Tambah sekarang</a>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $historis->links() }}</div>
        </div>
    </div>

</div>
@endsection
