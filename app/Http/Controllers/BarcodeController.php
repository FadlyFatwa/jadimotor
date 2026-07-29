<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DetailPenerimaan;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Barryvdh\DomPDF\Facade\Pdf;

class BarcodeController extends Controller
{
    /**
     * Tampilkan halaman maker untuk memilih pengaturan cetak
     */
    public function printMultiple(Request $request)
    {
        $ids = $request->query('ids');
        if (!$ids) {
            abort(400, 'Data tidak ditemukan');
        }

        $idsArray = explode(',', $ids);
        $details = DetailPenerimaan::with(['barang'])
            ->whereIn('ID_detail_penerimaan', $idsArray)
            ->get();

        if ($details->isEmpty()) {
            abort(404, 'Barang tidak ditemukan');
        }

        $generator = new BarcodeGeneratorPNG();
        $items = [];

        foreach ($details as $detail) {
            $items[] = [
                'id' => $detail->ID_detail_penerimaan,
                'barcode' => base64_encode($generator->getBarcode($detail->barang->barcode ?? '', $generator::TYPE_CODE_128)),
                'barcode_number' => $detail->barang->barcode,
                'nama_barang' => $detail->barang->nama_barang,
                'kode_modal' => $detail->barang->kode_modal,
                'tanggal' => $detail->Tanggal,
                'jumlah' => $detail->Jumlah,
            ];
        }

        return view('pages.barcode.maker', compact('items'));
    }

    /**
     * Print template barcode - Label 107
     */
    public function printTemplate107(Request $request)
    {
        $ids = $request->query('ids');
        if (!$ids) abort(400, 'Data tidak ditemukan');

        $idsArray = explode(',', $ids);
        $details = DetailPenerimaan::with(['barang'])->whereIn('ID_detail_penerimaan', $idsArray)->get();

        if ($details->isEmpty()) abort(404, 'Barang tidak ditemukan');
        
        $generator = new BarcodeGeneratorPNG();
        $items = [];
        $start_col = max(1, min(3, (int)$request->input('start_col', 1)));
        $start_row = max(1, min(10, (int)$request->input('start_row', 1)));

        foreach ($details as $detail) {
            $max_characters = $request->input("max_characters.{$detail->ID_detail_penerimaan}", 30);
            $quantity = $request->input("quantity.{$detail->ID_detail_penerimaan}", $detail->Jumlah);
            $date = $request->input("date.{$detail->ID_detail_penerimaan}", $detail->Tanggal);

            for ($i = 0; $i < $quantity; $i++) {
                $items[] = [
                    'id' => $detail->ID_detail_penerimaan,
                    'barcode' => $detail->barang->barcode,
                    'nama_item' => $detail->barang->nama_barang,
                    'kode_modal' => $detail->barang->kode_modal ?? '',
                    'nama_supplier' => $detail->barang->supplier->nama_supplier ?? '',
                    'date' => date('Y-m-d', strtotime($date)),
                    'max_characters' => $max_characters,
                    'nama_unit' => $detail->barang->unit->nama_unit,
                    'pk' => 'PK-' . $detail->ID_detail_penerimaan,
                    'generator' => $generator,
                ];
            }
        }

        $html = view('pages.barcode.printer', compact('items', 'start_col', 'start_row'))->render();
        $pdf = Pdf::loadHTML($html)->setPaper('A4', 'portrait');
        return $pdf->stream('barcode-label-107.pdf', ['Attachment' => 0]);
    }

    /**
     * Print template barcode - Label 101 (Besar)
     */
    public function printTemplate101(Request $request)
    {
        $ids = $request->query('ids');
        if (!$ids) abort(400, 'Data tidak ditemukan');

        $idsArray = explode(',', $ids);
        $details = DetailPenerimaan::with(['barang'])->whereIn('ID_detail_penerimaan', $idsArray)->get();

        if ($details->isEmpty()) abort(404, 'Barang tidak ditemukan');
        
        $generator = new BarcodeGeneratorPNG();
        $items = [];
        $start_col = max(1, min(2, (int)$request->input('start_col', 1)));
        $start_row = max(1, min(3, (int)$request->input('start_row', 1)));

        foreach ($details as $detail) {
            $quantity = $request->input("quantity.{$detail->ID_detail_penerimaan}", $detail->Jumlah);
            $date = $request->input("date.{$detail->ID_detail_penerimaan}", $detail->Tanggal);
            $nama_mobil = $request->input("nama_mobil.{$detail->ID_detail_penerimaan}", '');
            $no_part = $request->input("no_part.{$detail->ID_detail_penerimaan}", '');
            $nama_barang = $request->input("nama_barang2.{$detail->ID_detail_penerimaan}", '');

            for ($i = 0; $i < $quantity; $i++) {
                $items[] = [
                    'id' => $detail->ID_detail_penerimaan,
                    'barcode' => $detail->barang->barcode,
                    'nama_item' => strtoupper($nama_barang),
                    'nama_supplier' => $detail->barang->supplier->nama_supplier ?? '',
                    'date' => date('Y-m-d', strtotime($date)),
                    'nama_mobil' => strtoupper($nama_mobil),
                    'no_part' => strtoupper($no_part),
                    'nama_unit' => $detail->barang->unit->nama_unit,
                    'kode_modal' => $detail->barang->kode_modal ?? '',
                    'generator' => $generator,
                ];
            }
        }

        $html = view('pages.barcode.printer101', compact('items', 'start_col', 'start_row'))->render();
        $pdf = Pdf::loadHTML($html)->setPaper('A4', 'portrait');
        return $pdf->stream('barcode-label-101.pdf', ['Attachment' => 0]);
    }

    /**
     * Print template barcode - Fanbelt
     */
    public function printTemplateFanbelt(Request $request)
    {
        $ids = $request->query('ids');
        if (!$ids) abort(400, 'Data tidak ditemukan');

        $idsArray = explode(',', $ids);
        $details = DetailPenerimaan::with(['barang'])->whereIn('ID_detail_penerimaan', $idsArray)->get();

        if ($details->isEmpty()) abort(404, 'Barang tidak ditemukan');

        $generator = new BarcodeGeneratorPNG();
        $items = [];
        $start_col = max(1, min(2, (int)$request->input('start_col', 1)));
        $start_row = max(1, min(3, (int)$request->input('start_row', 1)));

        foreach ($details as $detail) {
            $quantity = $request->input("quantity.{$detail->ID_detail_penerimaan}", $detail->Jumlah);
            $date = $request->input("date.{$detail->ID_detail_penerimaan}", $detail->Tanggal);
            $tipe = $request->input("tipe.{$detail->ID_detail_penerimaan}", '');
            $nama_mobil = $request->input("nama_mobil.{$detail->ID_detail_penerimaan}", '');
            $nama_barang = $request->input("nama_barang2.{$detail->ID_detail_penerimaan}", '');

            for ($i = 0; $i < $quantity; $i++) {
                $items[] = [
                    'id' => $detail->ID_detail_penerimaan,
                    'barcode' => $detail->barang->barcode,
                    'nama_item' => strtoupper($nama_barang),
                    'nama_supplier' => $detail->barang->supplier->nama_supplier ?? '',
                    'date' => date('Y-m-d', strtotime($date)),
                    'nama_mobil' => strtoupper($nama_mobil),
                    'tipe' => strtoupper($tipe),
                    'nama_unit' => $detail->barang->unit->nama_unit,
                    'kode_modal' => $detail->barang->kode_modal ?? '',
                    'generator' => $generator,
                ];
            }
        }

        $html = view('pages.barcode.printerfanbelt', compact('items', 'start_col', 'start_row'))->render();
        $pdf = Pdf::loadHTML($html)->setPaper('A4', 'portrait');
        return $pdf->stream('barcode-fanbelt.pdf', ['Attachment' => 0]);
    }
}