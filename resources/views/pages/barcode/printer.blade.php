<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Print Barcode Multiple</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        @page {
            size: A4 portrait;
            margin: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        td {
            width: 5.05cm;
            height: 2.06cm;
            padding: 1px;
            vertical-align: top;
            box-sizing: border-box;
        }
        .barcode-item {
            margin-bottom: 3px;
            text-align: left;
        }
        .barcode-item-name .line-1 {
            font-size: 12px;
            margin-bottom: 0;
        }
        .barcode-item-name .line-2 {
            font-size: 8px;
            margin-bottom: 0;
        }
        .barcode-text {
            display: flex;
            align-items: center;
            font-size: 12px;
            margin-top: 2px;
            margin-bottom: 2px;
        }
        .barcode-img {
            width: 120px;
            height: 30px;
            vertical-align: bottom;
            margin-right: 5px;
        }
        .barcode-details {
            font-size: 10px;
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <table>
        <?php
        use Picqer\Barcode\BarcodeGeneratorPNG;
        $generator = new BarcodeGeneratorPNG();
        $current_col = 1;
        ?>
        @foreach ($items as $item)
            @for ($i = 0; $i < 1; $i++)
                @if ($current_col == 1) <tr> @endif

                <td>
                    <div class="barcode-item">
                        <!-- Nama Barang -->
                        <div class="barcode-item-name">
                            <span class="line-1">{{ substr($item['nama_item'], 0, $item['max_characters']) }}</span><br>
                            @if(strlen($item['nama_item']) > $item['max_characters'])
                                <span class="line-2">{{ substr($item['nama_item'], $item['max_characters']) }}</span>
                            @endif
                        </div>

                        <!-- Barcode Image -->
                        <div class="barcode-text">
                            <img class="barcode-img" src="data:image/png;base64,{{ base64_encode($generator->getBarcode($item['barcode'], $generator::TYPE_CODE_128)) }}" />
                            {{ $item['barcode'] }} / {{ $item['nama_unit'] }}
                        </div>

                        <!-- Detail Bawah -->
                        <div class="barcode-details">
                            {{ $item['kode_modal'] }} | {{ $item['nama_supplier'] }} | {{ date('d/m/y', strtotime($item['date'])) }}
                        </div>
                    </div>
                </td>

                <?php $current_col++; ?>

                @if ($current_col > 3)
                    </tr>
                    <?php $current_col = 1; ?>
                @endif
            @endfor
        @endforeach

        @while ($current_col <= 3)
            <td></td>
            <?php $current_col++; ?>
        @endwhile
    </table>
</body>
</html>