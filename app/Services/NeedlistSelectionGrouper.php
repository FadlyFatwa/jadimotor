<?php

namespace App\Services;

use Illuminate\Support\Collection;

/**
 * Mengelompokkan item supplier inquiry per master barang -> cluster kendaraan
 * dominan -> tier (OEM/Original/Aftermarket/KW), mengikuti logic yang sebelumnya
 * inline di tab "Pemilihan Supplier" lama, agar tampilan dan perhitungan SAW
 * batch memakai pengelompokan yang konsisten.
 */
class NeedlistSelectionGrouper
{
    private static array $tierOrder = ['OEM' => 0, 'Original' => 1, 'Aftermarket' => 2, 'KW' => 3];

    /**
     * @param Collection $groupedItems hasil groupBy id_barang (lihat NeedlistController::show())
     * @param array $referenceVariasiIds id_variasi yang dijadikan referensi (dikeluarkan dari penilaian)
     * @return array<int, array{
     *   master_barang_id:int, master_barang_nama:string,
     *   cluster_idx:int, cluster_label:string,
     *   tier:string, tier_label:string,
     *   panel_key:string, tier_key:string,
     *   variasi_ids:array, rows:Collection, unique_supplier_count:int,
     * }>
     */
    public function buildGroups(Collection $groupedItems, array $referenceVariasiIds = []): array
    {
        $groups = [];

        foreach ($groupedItems as $rows) {
            $masterBarangId   = $rows->first()['master']->id_barang;
            $masterBarangNama = $rows->first()['master']->nama_barang;

            $activeRows = $rows->filter(
                fn ($r) => !in_array($r['item']->id_variasi, $referenceVariasiIds)
            );

            $byMfr = [];
            foreach ($activeRows as $r) {
                $gens = $r['item']->variasi->vehicleGenerations ?? collect();
                $mfr  = $this->dominantManufacturer($gens) ?? '__universal__';
                $byMfr[$mfr][] = $r;
            }
            ksort($byMfr);

            $clusterIdx = 0;
            foreach ($byMfr as $mfrKey => $mfrRows) {
                foreach ($this->clusterByVehicleGeneration($mfrRows) as $cluster) {
                    $clusterVehicles = collect($cluster)
                        ->flatMap(fn ($r) => $r['item']->variasi->vehicleGenerations ?? collect())
                        ->map(fn ($g) => $g->vehicle->name ?? '')->unique()->filter()->sort()->values()
                        ->implode(' / ');
                    $clusterLabel = $clusterVehicles ?: ($mfrKey === '__universal__' ? 'Universal' : $mfrKey);

                    $tierGroups = [];
                    foreach ($cluster as $r) {
                        $t = $r['item']->variasi->tier ?? '__universal__';
                        $tierGroups[$t][] = $r;
                    }
                    uksort($tierGroups, fn ($a, $b) => (self::$tierOrder[$a] ?? 99) <=> (self::$tierOrder[$b] ?? 99));

                    foreach ($tierGroups as $tierKeyLabel => $tierRows) {
                        $tierRows   = collect($tierRows);
                        $variasiIds = $tierRows->pluck('item.id_variasi')->unique()->values()->toArray();

                        $uniqueSupplierCount = $tierRows
                            ->filter(fn ($r) => !empty($r['item']->harga_penawaran))
                            ->unique(fn ($r) => $r['supplier']->id_supplier)
                            ->count();

                        $sorted = $variasiIds;
                        sort($sorted);

                        $groups[] = [
                            'master_barang_id'     => $masterBarangId,
                            'master_barang_nama'   => $masterBarangNama,
                            'cluster_idx'           => $clusterIdx,
                            'cluster_label'         => $clusterLabel,
                            'tier'                  => $tierKeyLabel,
                            'tier_label'            => $tierKeyLabel === '__universal__' ? 'Universal' : $tierKeyLabel,
                            'panel_key'             => $masterBarangId . '-' . $clusterIdx . '-' . $tierKeyLabel,
                            'tier_key'              => md5(implode(',', $sorted)),
                            'variasi_ids'           => $variasiIds,
                            'rows'                  => $tierRows,
                            'unique_supplier_count' => $uniqueSupplierCount,
                        ];
                    }

                    $clusterIdx++;
                }
            }
        }

        return $groups;
    }

    private function dominantManufacturer(Collection $vehicleGenerations): ?string
    {
        if ($vehicleGenerations->isEmpty()) {
            return null;
        }

        return $vehicleGenerations
            ->groupBy(fn ($g) => $g->vehicle->manufacturer ?? 'Lainnya')
            ->map->count()
            ->sortByDesc(fn ($c) => $c)
            ->keys()->first();
    }

    /**
     * Connected-components: gabungkan item yang berbagi generasi kendaraan yang sama.
     */
    private function clusterByVehicleGeneration(array $items): array
    {
        $n       = count($items);
        $items   = array_values($items);
        $parent  = range(0, $n - 1);
        $genSets = [];
        foreach ($items as $idx => $r) {
            $genSets[$idx] = ($r['item']->variasi->vehicleGenerations ?? collect())->pluck('id')->toArray();
        }

        $find = function (int $x) use (&$parent, &$find): int {
            while ($parent[$x] !== $x) {
                $parent[$x] = $parent[$parent[$x]];
                $x = $parent[$x];
            }
            return $x;
        };

        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                if (!empty(array_intersect($genSets[$i], $genSets[$j]))) {
                    $ri = $find($i);
                    $rj = $find($j);
                    if ($ri !== $rj) {
                        $parent[$ri] = $rj;
                    }
                }
            }
        }

        $clusters = [];
        foreach ($items as $idx => $r) {
            $clusters[$find($idx)][] = $r;
        }

        return array_values($clusters);
    }
}
