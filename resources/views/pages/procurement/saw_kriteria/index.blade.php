@extends('layouts.main')
@section('title', 'Kriteria & Bobot Pemilihan Supplier')

@php
    // Kriteria & Bobot hanya boleh diubah oleh Manager Toko (supervisor) atau admin/owner.
    $canManageKriteria = in_array(auth()->user()->role, ['owner', 'admin', 'supervisor'], true);
@endphp

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h2 class="font-weight-bold mb-1" style="font-size:1.5rem">
                <i class="fas fa-sliders-h mr-2 text-primary" style="font-size:1.3rem"></i>Kriteria & Bobot Pemilihan Supplier
            </h2>
            <p class="text-muted mb-0" style="font-size:.875rem">
                Konfigurasi kriteria penilaian supplier dan bobot tiap kriteria
            </p>
        </div>
        @if($canManageKriteria)
            <a href="{{ route('saw.kriteria.create') }}" class="btn btn-danger">
                <i class="fas fa-plus mr-1"></i>Tambah Kriteria
            </a>
        @else
            <span class="badge badge-light border text-muted align-self-center" style="font-size:.75rem;">
                <i class="fas fa-eye mr-1"></i>Mode Lihat Saja — Bagian Pembelian
            </span>
        @endif
    </div>

    @php
        $totalPct = round($totalBobotAktif * 100, 2);
        $isBalanced = abs($totalBobotAktif - 1.0) <= 0.0001;
        // Saran penyesuaian dibulatkan ke kelipatan 5% (langkah bobot 0.05) biar gampang dipraktikkan,
        // minimal 5% kalau selisihnya masih ada walau kecil.
        $bulatkanKe5 = function ($pct) {
            $bulat = round($pct / 5) * 5;
            return $bulat == 0 && $pct > 0 ? 5 : $bulat;
        };
    @endphp

    <div class="alert {{ $isBalanced ? 'alert-success' : 'alert-warning' }} d-flex justify-content-between align-items-center py-2 mb-3">
        <div>
            <i class="fas {{ $isBalanced ? 'fa-check-circle' : 'fa-exclamation-triangle' }} mr-2"></i>
            Total bobot kriteria <strong>aktif</strong>: <strong>{{ $totalPct }}%</strong>
            @if(!$isBalanced)
                — harus tepat <strong>100%</strong> agar perhitungan SAW valid.
                @if($totalBobotAktif > 1.0)
                    Kurangi bobot sebesar <strong>{{ $bulatkanKe5(round(($totalBobotAktif - 1.0) * 100, 2)) }}%</strong> (dibagi ke kriteria aktif, kelipatan 5%).
                @elseif($totalBobotAktif > 0)
                    Tambahkan bobot sebesar <strong>{{ $bulatkanKe5(round((1.0 - $totalBobotAktif) * 100, 2)) }}%</strong> (dibagi ke kriteria aktif, kelipatan 5%).
                @else
                    Belum ada kriteria aktif dengan bobot.
                @endif
            @else
                Siap dipakai untuk perhitungan SAW.
            @endif
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body px-4 pt-3">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="sku-thead text-center">
                        <tr>
                            <th class="py-3 text-secondary" style="width:6%">Urutan</th>
                            <th class="py-3 text-secondary" style="width:8%">Kode</th>
                            <th class="py-3 text-secondary text-left">Nama Kriteria</th>
                            <th class="py-3 text-secondary">Jenis</th>
                            <th class="py-3 text-secondary">Bobot</th>
                            <th class="py-3 text-secondary">Satuan</th>
                            <th class="py-3 text-secondary">Status</th>
                            <th class="py-3 text-secondary" style="width:110px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($kriterias as $k)
                            <tr>
                                <td class="text-center">{{ $k->urutan }}</td>
                                <td class="text-center"><span class="badge badge-primary">{{ $k->kode }}</span></td>
                                <td>{{ $k->nama }}</td>
                                <td class="text-center">
                                    <span class="badge badge-{{ $k->isCost() ? 'danger' : 'success' }}">
                                        {{ $k->isCost() ? 'Cost' : 'Benefit' }}
                                    </span>
                                </td>
                                <td class="text-center">{{ round($k->bobot * 100, 2) }}%</td>
                                <td class="text-center">{{ $k->satuan ?: '-' }}</td>
                                <td class="text-center">
                                    <span class="badge badge-{{ $k->is_active ? 'success' : 'secondary' }}">
                                        {{ $k->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($canManageKriteria)
                                        <a href="{{ route('saw.kriteria.edit', $k->id) }}"
                                           class="btn btn-xs btn-outline-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" action="{{ route('saw.kriteria.destroy', $k->id) }}" class="d-inline"
                                              data-confirm="Hapus kriteria {{ $k->kode }} ({{ $k->nama }})? Jika kriteria ini termasuk C1-C6 bawaan, perhitungan SAW yang masih memakai kode tersebut bisa terdampak.">
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
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                    Belum ada kriteria SAW.
                                    @if($canManageKriteria)
                                        <a href="{{ route('saw.kriteria.create') }}">Tambah sekarang</a>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
