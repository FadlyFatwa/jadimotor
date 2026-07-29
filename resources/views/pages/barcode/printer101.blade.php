<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Print Multiple Barcode - 101</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
        }
        @page {
            margin-top: 0.8cm;
            margin-right: 0cm;
        }
        .barcode-container {
            font-family: Arial, sans-serif;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: flex-start;
            justify-content: flex-end;
        }
        table {
            border-collapse: collapse;
            margin-left: auto;
            margin-right: 0;
            margin-top: 0;
            margin-bottom: 0;
        }
        td {
            width: 10cm;
            height: 5cm;
            padding: 2px;
            text-align: center;
            vertical-align: top;
        }
        td.spacer {
            width: 0.4cm;
            border: none;
        }
        .barcode-item {
            text-align: center;
            margin-bottom: 5px;
            margin-bottom : 0;
        }
        .barcode-item-name {
            line-height: 0.8;
            word-wrap: break-word;
            max-width: 100%;
            text-align: center;
            margin-top: 5px;
            margin-bottom : 0;
            font-size: 24px;
            font-weight: bold;
            margin-left: 10px;
            margin-bottom : 0;
            text-align: center;
        }
        .barcode-item-mobil {
            font-size: 24px;
            margin-left: 10px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 0px;
            margin-top: 0px;
        }
        .barcode-item-no_part {
            font-size: 22px;
            font-weight: bold;
            margin-left: 10px;
            text-align: center;
            margin-bottom: 0px;
            margin-top: 0px;
        }
        .barcode-text {
            margin-top: 3px;
            font-size: 12px;
            margin-bottom : 0px;
        }
        .barcode-details {
            font-size: 16px;
            margin-bottom: 0px;
        }
        .barcode-img {
            width: 3cm;
            height: 0.7cm;
            margin-top: 10px;
        }
    </style>
</head>
<body>
<?php
use Picqer\Barcode\BarcodeGeneratorPNG;
$generator = new BarcodeGeneratorPNG();
$width = 2;
$height = 50;

$start_col = isset($start_col) ? max(0, (int)$start_col - 1) : 0;
$start_row = isset($start_row) ? max(0, (int)$start_row - 1) : 0;

$labels = [];
foreach ($items as $item) {
    $current_date = date('d/m/y', strtotime($item['date']));
    $labels[] = [
        'barcode' => $item['barcode'],
        'nama_item' => strtoupper($item['nama_item']),
        'nama_mobil' => strtoupper($item['nama_mobil']),
        'no_part' => strtoupper($item['no_part']),
        'kode_modal' => $item['kode_modal'],
        'nama_supplier' => $item['nama_supplier'],
        'nama_unit' => $item['nama_unit'],
        'date' => $current_date,
    ];
}

$first_page_offset = ($start_row * 2) + $start_col;
$first_page_labels = array_slice($labels, 0, 6 - $first_page_offset);
$remaining_labels = array_slice($labels, 6 - $first_page_offset);
$pages = array_merge([$first_page_labels], array_chunk($remaining_labels, 6));
?>

@foreach ($pages as $page_index => $page_labels)
    <div class="barcode-container">
        <table>
            <?php $counter = 0; ?>
            @foreach (range(0, 2) as $row) <!-- 3 baris per halaman -->
                <tr>
                    @foreach (range(0, 1) as $col) <!-- 2 kolom per baris -->
                        @if ($page_index == 0 && ($row < $start_row || ($row == $start_row && $col < $start_col)))
                            <td class="spacer"></td>
                        @elseif(isset($page_labels[$counter]))
                            <td>
                                <div class="barcode-item">
                                    <div class="barcode-item-name">{{ $page_labels[$counter]['nama_item'] }}</div>
                                    <div class="barcode-item-mobil">{{ $page_labels[$counter]['nama_mobil'] }}</div>
                                    <div class="barcode-item-no_part">{{ $page_labels[$counter]['no_part'] }}</div>

                                    <div class="barcode-text">
                                        <img class="barcode-img"
                                             src="data:image/png;base64,{{ base64_encode($generator->getBarcode($page_labels[$counter]['barcode'], $generator::TYPE_CODE_128, $width, $height)) }}">
                                        <br>{{ $page_labels[$counter]['barcode'] }} / {{ $page_labels[$counter]['nama_unit'] }}
                                    </div>

                                    <div class="barcode-details">
                                        {{ $page_labels[$counter]['kode_modal'] }} | 
                                        {{ $page_labels[$counter]['nama_supplier'] }} | 
                                        {{ $page_labels[$counter]['date'] }}
                                    </div>
                                </div>
                            </td>
                            <?php $counter++; ?>
                        @else
                            <td class="spacer"></td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
        </table>
    </div>
    @if ($page_index < count($pages) - 1)
        <div style="page-break-after: always;"></div>
    @endif
@endforeach
</body>
</html>