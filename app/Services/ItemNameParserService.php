<?php

namespace App\Services;

use App\Models\Vehicle;
use Illuminate\Support\Collection;

/**
 * Parsing teks bebas di nama_variasi (mis. "Kit P/S LOW Kuda 1.6/Kuda Dsl/Grandia Hakusei
 * MR-418172") menjadi saran: tipe part (calon nama_barang), nomor part, dan kecocokan
 * kendaraan (vehicle_generations) — dipakai untuk menyarankan kategorisasi item yang masih
 * di bucket "BELUM DIKATEGORIKAN". Hasilnya SELALU berupa saran yang bisa diedit admin,
 * bukan keputusan final — teks sumber terlalu beragam untuk parsing 100% akurat.
 */
class ItemNameParserService
{
    /** @var array<int, string> id generasi => kode generasi */
    private array $generationPatterns = [];

    /** @var array<int, string> id kendaraan => nama kendaraan */
    private array $vehiclePatterns = [];

    /** @var array<int, array<int, int>> id kendaraan => daftar id generasi miliknya */
    private array $vehicleGenerationIds = [];

    /** @var array<int, array{0: string, 1: int, 2: string}> [type, id, pattern], terurut panjang pattern menurun */
    private array $sortedCandidates = [];

    public function __construct()
    {
        $vehicles = Vehicle::with('generations')->get();

        foreach ($vehicles as $vehicle) {
            // Nama kendaraan murni angka (mis. Mazda "2", "3", "6") dibuang dari matcher —
            // kalau tidak, ia akan salah menangkap angka cc mesin ("2.4", "1.6", dst) di teks bebas.
            if (!ctype_digit((string) $vehicle->name)) {
                $this->vehiclePatterns[$vehicle->id] = $vehicle->name;
            }
            $this->vehicleGenerationIds[$vehicle->id] = $vehicle->generations->pluck('id')->all();

            foreach ($vehicle->generations as $generation) {
                if ($generation->code) {
                    $this->generationPatterns[$generation->id] = $generation->code;
                }
            }
        }

        foreach ($this->generationPatterns as $genId => $code) {
            $this->sortedCandidates[] = ['gen', $genId, $code];
        }
        foreach ($this->vehiclePatterns as $vehId => $name) {
            $this->sortedCandidates[] = ['veh', $vehId, $name];
        }
        usort($this->sortedCandidates, fn ($a, $b) => mb_strlen($b[2]) <=> mb_strlen($a[2]));
    }

    /**
     * @return array{tipe_part: string, part_number: ?string, generation_ids: array<int,int>}
     */
    public function parse(string $namaVariasi): array
    {
        $work = ' ' . $namaVariasi . ' ';
        $work = preg_replace("/'([A-Za-z])'/", ' ', $work);

        [$work, $matchedGenerationIds] = $this->removeVehicleTokens($work);

        // Nomor part bisa diawali huruf (MR527979, B-MCM-S13) atau angka (04465-YZZS5),
        // jadi dicari sebagai "rangkaian alnum tersambung dash" yang mengandung minimal 1 digit
        // dan panjang total (tanpa dash) >= 5 — lalu buang noise rentang tahun ("06-On", "02-12").
        $partNumber = null;
        if (preg_match_all('/\b[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*\b/', $work, $m) && !empty($m[0])) {
            foreach (array_reverse($m[0]) as $candidate) {
                if (!preg_match('/\d/', $candidate)) {
                    continue;
                }
                if (preg_match('/^\d{1,2}-(on|up|now|\d{1,2})$/i', $candidate)) {
                    continue;
                }
                if (mb_strlen(str_replace('-', '', $candidate)) >= 5) {
                    $partNumber = $candidate;
                    $work = preg_replace('/' . preg_quote($candidate, '/') . '/', ' ', $work, 1);
                    break;
                }
            }
        }

        $work = preg_replace('/[\/,()]+/', ' ', $work);
        $work = preg_replace('/\s{2,}/', ' ', $work);
        $tipePart = trim($work);

        if ($tipePart === '') {
            $tipePart = trim($namaVariasi);
        }

        return [
            'tipe_part' => $tipePart,
            'part_number' => $partNumber,
            'generation_ids' => $matchedGenerationIds,
        ];
    }

    /**
     * @return Collection daftar kendaraan + generasinya, dikelompokkan per manufacturer -> nama kendaraan,
     *         dipakai untuk render picker "tambah kendaraan lain" di form saran kategorisasi.
     */
    public function vehicleOptionsGrouped(): Collection
    {
        return Vehicle::with('generations')->orderBy('manufacturer')->orderBy('name')->get()
            ->groupBy(fn ($v) => $v->manufacturer ?: 'Lainnya')
            ->map(fn ($vehicles) => $vehicles->keyBy('name'));
    }

    /**
     * Setelah admin menetapkan nama_barang & kendaraan untuk item target, info itu sudah pindah
     * ke MBarang & product_variant_compatibility — jadi teks kendaraan & tipe part itu harus
     * hilang dari nama_variasi (supaya hasilnya konsisten dengan item yang sudah benar:
     * "[merk] [tag grade] [no part]"). Tag grade dan nomor part TIDAK dibuang, supaya tetap ada
     * di nama_variasi sesuai konvensi data yang sudah berjalan.
     */
    public function buildCleanNamaVariasi(string $original, string $namaBarangFinal, array $generationIds): string
    {
        $work = ' ' . $original . ' ';

        foreach ($this->sortedCandidates as [$type, $id, $pattern]) {
            if ($pattern === '') {
                continue;
            }
            $isMatchedGeneration = $type === 'gen' && in_array($id, $generationIds, true);
            $isMatchedVehicle = $type === 'veh' && !empty(array_intersect($this->vehicleGenerationIds[$id] ?? [], $generationIds));
            if ($isMatchedGeneration || $isMatchedVehicle) {
                $regex = '/(?<![A-Za-z0-9])' . preg_quote($pattern, '/') . '(?![A-Za-z0-9])/i';
                $work = preg_replace($regex, ' ', $work);
            }
        }

        foreach (preg_split('/\s+/', trim($namaBarangFinal)) as $word) {
            if ($word === '') {
                continue;
            }
            $regex = '/(?<![A-Za-z0-9])' . preg_quote($word, '/') . '(?![A-Za-z0-9])/iu';
            $work = preg_replace($regex, ' ', $work, 1);
        }

        $work = preg_replace('/[\/,()]+/', ' ', $work);
        $work = preg_replace('/\s{2,}/', ' ', $work);
        $cleaned = trim($work);

        return $cleaned !== '' ? $cleaned : $original;
    }

    /**
     * @return array{0: string, 1: array<int,int>} [teks setelah token kendaraan dibuang, generation_ids yang ketemu]
     */
    private function removeVehicleTokens(string $work): array
    {
        $matchedGenerationIds = [];
        $matchedVehicleIds = [];

        foreach ($this->sortedCandidates as [$type, $id, $pattern]) {
            if ($pattern === '') {
                continue;
            }
            $regex = '/(?<![A-Za-z0-9])' . preg_quote($pattern, '/') . '(?![A-Za-z0-9])/i';
            if (preg_match($regex, $work)) {
                $work = preg_replace($regex, ' ', $work);
                if ($type === 'gen') {
                    $matchedGenerationIds[] = $id;
                } else {
                    $matchedVehicleIds[] = $id;
                }
            }
        }

        foreach ($matchedVehicleIds as $vehId) {
            $genIds = $this->vehicleGenerationIds[$vehId] ?? [];
            $hasSpecificMatch = !empty(array_intersect($genIds, $matchedGenerationIds));
            if (!$hasSpecificMatch) {
                $matchedGenerationIds = array_merge($matchedGenerationIds, $genIds);
            }
        }

        return [$work, array_values(array_unique($matchedGenerationIds))];
    }
}
