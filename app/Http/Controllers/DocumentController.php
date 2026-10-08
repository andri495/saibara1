<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\InstallmentTransaction;
use App\Models\MasterChief;
use App\Models\Transaksi;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Html;
use Tymon\JWTAuth\Facades\JWTAuth;

class DocumentController extends Controller
{
    /**
     * Display the specified resource.
     */
    public function getDocument(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (! $user) {
            return response()->json(['status' => 201, 'message' => "You're not authenticated"]);
        }
        $opdId = $request->master_opd_id;
        $documents = Document::whereHas('transaksi.transaksiObject.object', function ($query) use ($opdId, $user) {
            $query->where('master_opd_id', $opdId);
            if ($user->master_lokasi_id != 0) {
                $query->where('master_lokasi_id', $user->master_lokasi_id);
            }
        })
            ->orderBy('created_at', 'desc')
            ->paginate(8, ['*'], 'documents');
        $count = $documents->count();
        $documents->appends(['count' => $count]);

        return response()->json(['status' => 200, 'message' => 'Successfully get documents', 'data' => $documents]);
    }

    public function getAllDocument()
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (! $user) {
            return response()->json(['status' => 201, 'message' => "You're not authenticated"]);
        }
        $documents = Document::orderBy('created_at', 'desc')
            ->paginate(8, ['*'], 'documents');
        $count = $documents->count();
        $documents->appends(['count' => $count]);

        return response()->json(['status' => 200, 'message' => 'Successfully get documents', 'data' => $documents]);
    }

    private function safeFilename(string $filename): string
    {
        // Replace path separators first
        $filename = preg_replace('#[\\/]+#', '-', $filename);
        // Remove other reserved characters
        $filename = preg_replace('/[<>:"|?*]/', '-', $filename);
        // Collapse whitespace to single spaces, then replace spaces with dashes
        $filename = preg_replace('/\s+/', ' ', trim($filename));
        $filename = str_replace(' ', '-', $filename);
        // Keep only safe characters
        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '-', $filename);
        // Collapse multiple dashes
        $filename = preg_replace('/-+/', '-', $filename);
        // Limit length
        if (strlen($filename) > 200) {
            $filename = substr($filename, 0, 200);
        }

        return $filename;
    }

    private function replaceTagExcel($sheet, $tag, $value)
    {
        foreach ($sheet->getRowIterator() as $row) {
            foreach ($row->getCellIterator() as $cell) {
                // PASTIKAN ini single cell, bukan range
                $cellCoordinate = $cell->getCoordinate();

                // Debug: Cek jika cell coordinate mengandung ":"
                if (str_contains($cellCoordinate, ':')) {
                    continue; // Skip ranges
                }

                $cellValue = $cell->getValue();

                if (is_string($cellValue) && str_contains($cellValue, $tag)) {
                    $newValue = str_replace($tag, $value, $cellValue);
                    $cell->setValue($newValue);

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Generate QR code as base64 encoded image with optional logo overlay
     *
     * @param  string  $data  Data to encode in QR code
     * @param  int  $size  Size of QR code
     * @param  string|null  $logoPath  Path to logo image (optional)
     * @return string Base64 encoded QR code image
     */
    private function generateQRCodeBase64($data, $size = 200, $logoPath = null)
    {
        $writer = new Writer(
            new ImageRenderer(
                new RendererStyle($size),
                new \BaconQrCode\Renderer\Image\ImagickImageBackEnd
            )
        );

        $qrCodeString = $writer->writeString($data);

        // If no logo, return QR code as-is
        if (! $logoPath || ! file_exists($logoPath)) {
            return 'data:image/png;base64,' . base64_encode($qrCodeString);
        }

        // Create QR code image from string
        $qrImage = imagecreatefromstring($qrCodeString);
        if ($qrImage === false) {
            return 'data:image/png;base64,' . base64_encode($qrCodeString);
        }

        // Load logo image
        $logoImage = $this->createImageFromFile($logoPath);
        if ($logoImage === false) {
            $qrImage = null;

            return 'data:image/png;base64,' . base64_encode($qrCodeString);
        }

        // Calculate logo size (20% of QR code size)
        $logoSize = $size * 0.2;
        $logoResized = imagescale($logoImage, $logoSize, $logoSize);

        // Calculate position to center the logo
        $qrWidth = imagesx($qrImage);
        $qrHeight = imagesy($qrImage);
        $logoWidth = imagesx($logoResized);
        $logoHeight = imagesy($logoResized);
        $logoX = ($qrWidth - $logoWidth) / 2;
        $logoY = ($qrHeight - $logoHeight) / 2;

        // Add white background behind logo for better visibility
        $white = imagecolorallocate($qrImage, 255, 255, 255);
        $padding = 5;
        imagefilledrectangle(
            $qrImage,
            $logoX - $padding,
            $logoY - $padding,
            $logoX + $logoWidth + $padding,
            $logoY + $logoHeight + $padding,
            $white
        );

        // Overlay logo on QR code
        imagecopy($qrImage, $logoResized, $logoX, $logoY, 0, 0, $logoWidth, $logoHeight);

        // Clean up
        $logoImage = null;
        $logoResized = null;

        // Convert to PNG and encode
        ob_start();
        imagepng($qrImage);
        $finalImage = ob_get_clean();
        $qrImage = null;

        return 'data:image/png;base64,' . base64_encode($finalImage);
    }

    /**
     * Create GD image resource from file (supports PNG, JPG, GIF)
     */
    private function createImageFromFile($path)
    {
        $imageInfo = getimagesize($path);
        if ($imageInfo === false) {
            return false;
        }

        switch ($imageInfo[2]) {
            case IMAGETYPE_PNG:
                return imagecreatefrompng($path);
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($path);
            case IMAGETYPE_GIF:
                return imagecreatefromgif($path);
            default:
                return false;
        }
    }

    public function openDocument($filename)
    {

        $user = JWTAuth::parseToken()->authenticate();
        if (! $user) {
            return response()->json(['status' => 201, 'message' => "You're not authenticated"]);
        }
        $filePath = 'private/documents/' . $filename;
        $fullPath = storage_path('app/' . $filePath);

        if (! Storage::disk('local')->exists($filePath)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return response()->file($fullPath);
    }

    public function generateSPdORD($no_reference)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (! $user) {
            return response()->json(['status' => 201, 'message' => "You're not authenticated"]);
        }
        // Get transactions, documents, and objects
        $transaction = Transaksi::with([
            'transaksiObject.object.opd:id,nama_opd',
            'transaksiObject.object.lokasi:id,nama_lokasi',
            'transaksiObject.object.retribusi:id,nama_retribusi',
            'transaksiObject.object.rincianRetribusi:id,rincian_retribusi',
            'transaksiObject.object.subRincianRetribusi:id,nama_sub_rincian_retribusi',
            'transaksiObject.object.tpj:id,nama_tpj',
            'transaksiObject.object.satuan:id,nama_satuan',
            'subject',
            'subject.masterOpd:id,nama_opd',
            'tipePembayaran',
        ])->where('reference_no', $no_reference)->first();
        $document = Document::where('transaksi_id', $transaction->reference_no)->where('master_tipe_dokumen_id', 1)->first();
        $kaban = MasterChief::with('masterOpd', 'masterLokasi')->where('id', $document->master_chief_id)->first();
        $locationChief = $kaban->master_lokasi_id == 0 ? $kaban->masterOpd->nama_opd : $kaban->masterLokasi->nama_lokasi;
        $opdName = $transaction->transaksiObject->first()->object->opd->nama_opd;
        $document_number = $document->document_number;
        $issueDate = $document->issue_date;
        $subjectName = $transaction->subject_name;
        $subjectOpd = '';
        if ($transaction->nama_instansi != null && $transaction->nama_instansi != 'null') {
            $subjectOpd = $transaction->nama_instansi;
        } else {
            $subjectOpd = 'PERORANGAN';
        }
        $subjectAddress = $transaction->subject_address;
        $transaksi_id = $transaction->reference_no;
        $lokasiName = $transaction->transaksiObject->first()->object->lokasi->nama_lokasi;
        $retribusiName = $transaction->transaksiObject->first()->object->retribusi->nama_retribusi;
        $keterangan = [];
        foreach ($transaction->transaksiObject as $value) {
            $keterangan[] = $value->object->sub_rincian_objek ?? '-';
        }
        $keterangan = implode(' - ', $keterangan);
        try {
            // Load template Excel
            $templatePath = storage_path('app/templates/spdord_template.xlsx');
            $spreadsheet = IOFactory::load($templatePath);
            $sheet = $spreadsheet->getActiveSheet();

            $this->replaceTagExcel($sheet, '{opd}', $opdName);
            $this->replaceTagExcel($sheet, '{opd_ttd}', $locationChief);
            $this->replaceTagExcel($sheet, '{opd_dinas}', $opdName);
            $this->replaceTagExcel($sheet, '{nomor_pendaftaran}', $document_number);
            $this->replaceTagExcel($sheet, '{tanggal}', $issueDate);
            $this->replaceTagExcel($sheet, '{nama}', $subjectName);
            $this->replaceTagExcel($sheet, '{instansi}', $subjectOpd);
            $this->replaceTagExcel($sheet, '{alamat}', $subjectAddress);
            $this->replaceTagExcel($sheet, '{id_objek}', $transaksi_id);
            $this->replaceTagExcel($sheet, '{lokasi}', $lokasiName);
            $this->replaceTagExcel($sheet, '{uraian_retribusi}', $retribusiName);
            $this->replaceTagExcel($sheet, '{keterangan}', $keterangan);
            $this->replaceTagExcel($sheet, '{chief}', $kaban->chief_name);
            $this->replaceTagExcel($sheet, '{nip}', $kaban->chief_nip);

            // Generate QR code with kaban information
            $qrCodeData = 'https://saibara.lampungprov.go.id/api/chief/information/' . $kaban->id;
            $qrCodeBase64 = $this->generateQRCodeBase64($qrCodeData, 300);

            // Replace {ttd} tag with QR code image
            foreach ($sheet->getRowIterator() as $row) {
                foreach ($row->getCellIterator() as $cell) {
                    $cellCoordinate = $cell->getCoordinate();
                    if (str_contains($cellCoordinate, ':')) {
                        continue;
                    }
                    $cellValue = $cell->getValue();
                    if (is_string($cellValue) && str_contains($cellValue, '{ttd}')) {
                        try {
                            $drawing = new Drawing;
                            $drawing->setName('QRCode');
                            $drawing->setDescription('Signature QR Code');
                            $qrCodeImage = base64_decode(str_replace('data:image/png;base64,', '', $qrCodeBase64));
                            $tempFile = tempnam(sys_get_temp_dir(), 'qrcode_') . '.png';
                            file_put_contents($tempFile, $qrCodeImage);
                            $drawing->setPath($tempFile);
                            $drawing->setCoordinates($cellCoordinate);
                            $drawing->setHeight(120);
                            $drawing->setWidth(120);
                            $drawing->setOffsetX(5);
                            $drawing->setOffsetY(5);
                            $drawing->setWorksheet($sheet);
                            $cell->setValue('');
                        } catch (\Exception $e) {
                            $cell->setValue('');
                        }
                        break 2;
                    }
                }
            }

            // Page setup and print area for SKRD installment
            $pageSetup = $sheet->getPageSetup();
            $pageSetup->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
            $pageSetup->setPaperSize(PageSetup::PAPERSIZE_A4);
            $pageSetup->setFitToWidth(1);
            $pageSetup->setFitToHeight(1);
            $sheet->getPageSetup()->setPrintArea('A1:I44');

            $sheet->getPageMargins()
                ->setTop(0.5)
                ->setBottom(0)
                ->setLeft(0.5)
                ->setRight(0.5);

            // Convert Excel to HTML
            $spreadsheet->setActiveSheetIndex(0);
            $htmlWriter = new \PhpOffice\PhpSpreadsheet\Writer\Html($spreadsheet);
            ob_start();
            $htmlWriter->save('php://output');
            $excelHtml = ob_get_clean();

            // Remove borders in HTML
            $excelHtml = preg_replace('/\s*border="[^"]*"/i', '', $excelHtml);
            $excelHtml = preg_replace('/border\s*:\s*[^;\"]+;?/i', '', $excelHtml);

            // Prepare temp dir for mPDF
            $preferredTemp = storage_path('app/mpdf_temp');
            $mpdfTemp = null;
            try {
                if (! file_exists($preferredTemp)) {
                    mkdir($preferredTemp, 0777, true);
                }
                if (! file_exists($preferredTemp . '/mpdf')) {
                    mkdir($preferredTemp . '/mpdf', 0777, true);
                }
                @chmod($preferredTemp, 0777);
                @chmod($preferredTemp . '/mpdf', 0777);
                if (is_writable($preferredTemp . '/mpdf')) {
                    $mpdfTemp = $preferredTemp;
                }
            } catch (\Throwable $t) {
                $mpdfTemp = null;
            }
            if ($mpdfTemp === null || ! is_writable($mpdfTemp . '/mpdf')) {
                $sys = sys_get_temp_dir();
                if (! file_exists($sys . '/mpdf')) {
                    @mkdir($sys . '/mpdf', 0777, true);
                    @chmod($sys . '/mpdf', 0777);
                }
                if (is_writable($sys . '/mpdf')) {
                    $mpdfTemp = $sys;
                } else {
                    if (is_writable($preferredTemp)) {
                        $mpdfTemp = $preferredTemp;
                    }
                }
            }
            if ($mpdfTemp === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No writable temp directory available for mPDF. Please ensure storage/app/mpdf_temp is writable.',
                ], 500);
            }

            // Create PDF
            $mpdf = new \Mpdf\Mpdf([
                'format' => 'A4',
                'margin_top' => 10,
                'margin_bottom' => 5,
                'margin_left' => 10,
                'margin_right' => 10,
                'tempDir' => $mpdfTemp,
                'autoPageBreak' => true,
                'shrink_tables_to_fit' => 1,
            ]);

            $mpdf->WriteHTML('<style>
                    body, table, td { font-family: Arial, sans-serif; font-size: 10pt; line-height: 1.1; }
                    table { border-collapse: collapse; width: 100%; }
                    td { padding: 2px 4px; }
                </style>', \Mpdf\HTMLParserMode::HEADER_CSS);
            $mpdf->WriteHTML($excelHtml);

            $filename = $document->document_number .
                '-' . Carbon::now()->format('Y-m-d') . '.xlsx';
            $filename = $this->safeFilename($filename);
            $outputPath = storage_path('app/private/documents/' . $filename);

            if (! file_exists(dirname($outputPath))) {
                Storage::makeDirectory('private/documents');
            }

            $mpdf->Output($outputPath, 'F');

            $filePath = 'private/documents/' . $filename;
            $fullPath = storage_path('app/' . $filePath);

            if (! Storage::disk('local')->exists($filePath)) {
                return response()->json(['message' => 'File not found'], 404);
            }
            $document->update([
                'download_link' => $filePath,
            ]);

            return response()->file($fullPath);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error generating report: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function generateSKRD($no_reference)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (! $user) {
            return response()->json(['status' => 201, 'message' => "You're not authenticated"]);
        }
        // Get transactions, documents, and objects
        $transaction = Transaksi::with([
            'transaksiObject.object.opd:id,nama_opd',
            'transaksiObject.object.lokasi:id,nama_lokasi',
            'transaksiObject.object.retribusi:id,nama_retribusi',
            'transaksiObject.object.rincianRetribusi:id,rincian_retribusi',
            'transaksiObject.object.subRincianRetribusi:id,nama_sub_rincian_retribusi',
            'transaksiObject.object.tpj:id,nama_tpj',
            'transaksiObject.object.satuan:id,nama_satuan',
            'subject',
            'subject.masterOpd:id,nama_opd',
            'tipePembayaran',
        ])->where('reference_no', $no_reference)->first();
        $document = Document::where('transaksi_id', $transaction->reference_no)->where('master_tipe_dokumen_id', 2)->first();
        $spdordDocument = Document::where('transaksi_id', $transaction->reference_no)->where('master_tipe_dokumen_id', 1)->first();
        $kaban = MasterChief::with('masterOpd', 'masterLokasi')->where('id', $document->master_chief_id)->first();
        $locationChief = $kaban->master_lokasi_id == 0 ? $kaban->masterOpd->nama_opd : $kaban->masterLokasi->nama_lokasi;
        $opdName = $transaction->transaksiObject->first()->object->opd->nama_opd;
        $issueDate = $document->issue_date;
        $subjectName = $transaction->subject_name;
        $subjectOpd = '';
        if ($transaction->nama_instansi == null || $transaction->nama_instansi == 'null') {
            $subjectOpd = 'PERORANGAN';
        } else {
            $subjectOpd = $transaction->nama_instansi;
        }
        $subjectAddress = $transaction->subject_address;
        $retribusiName = $transaction->transaksiObject->first()->object->retribusi->nama_retribusi;
        $rincianRetribusi = $transaction->transaksiObject->first()->object->rincianRetribusi->rincian_retribusi ?? '';
        $lokasiName = $transaction->transaksiObject->first()->object->lokasi->nama_lokasi;
        $uraian = $transaction->transaksiObject->first()->object->uraian_objek ?? '';
        $keterangan = $transaction->transaksiObject->first()->object->rincian_objek ?? '';
        $subRincianRetribusi = $transaction->transaksiObject->first()->object->subRincianRetribusi->nama_sub_rincian_retribusi ?? '';
        $dateNow = Carbon::parse($document->issue_date)->format('Y-m-d');
        try {
            // Load template Excel
            $templatePath = storage_path('app/templates/skrd_template.xlsx');
            $spreadsheet = IOFactory::load($templatePath);
            $sheet = $spreadsheet->getActiveSheet();

            $this->replaceTagExcel($sheet, '{document_number}', $document->document_number);
            $this->replaceTagExcel($sheet, '{retribusi}', $retribusiName);

            $this->replaceTagExcel($sheet, '{issue_date}', $issueDate);
            $this->replaceTagExcel($sheet, '{spdord_number}', $spdordDocument->document_number ?? '-');
            $this->replaceTagExcel($sheet, '{name}', $subjectName);
            $this->replaceTagExcel($sheet, '{opd_wr}', $subjectOpd);
            $this->replaceTagExcel($sheet, '{address}', $subjectAddress);
            $this->replaceTagExcel($sheet, '{lokasi}', $lokasiName);
            $this->replaceTagExcel($sheet, '{uraian}', $uraian);
            $this->replaceTagExcel($sheet, '{keterangan}', $keterangan);
            $this->replaceTagExcel($sheet, '{rincian_retribusi}', $rincianRetribusi);
            $this->replaceTagExcel($sheet, '{sub_rincian_retribusi}', $subRincianRetribusi);
            $this->replaceTagExcel($sheet, '{date}', $dateNow);
            $this->replaceTagExcel($sheet, '{opd}', $opdName);
            $this->replaceTagExcel($sheet, '{opd_dinas}', $opdName);
            $this->replaceTagExcel($sheet, '{chief}', $kaban->chief_name);
            $this->replaceTagExcel($sheet, '{nip}', $kaban->chief_nip);

            // Add header row for object loop at row 26
            $headerRow = 28;

            // Set column widths for better layout
            $sheet->getColumnDimension('A')->setWidth(5);  // No - narrow
            $sheet->getColumnDimension('B')->setWidth(20); // Rincian Objek part 1
            $sheet->getColumnDimension('C')->setWidth(20); // Rincian Objek part 2
            $sheet->getColumnDimension('D')->setWidth(15); // TPJ
            $sheet->getColumnDimension('E')->setWidth(12); // Satuan
            $sheet->getColumnDimension('F')->setWidth(12); // Tarif
            $sheet->getColumnDimension('G')->setWidth(10); // Jumlah
            $sheet->getColumnDimension('H')->setWidth(15); // Total

            $sheet->setCellValue('A' . $headerRow, 'No');
            // Merge B:C for Rincian Objek to avoid long wrap
            $sheet->mergeCells('B' . $headerRow . ':C' . $headerRow);
            $sheet->setCellValue('B' . $headerRow, 'Rincian Objek');
            $sheet->setCellValue('D' . $headerRow, 'TPJ');
            $sheet->setCellValue('E' . $headerRow, 'Satuan');
            $sheet->setCellValue('F' . $headerRow, 'Tarif');
            $sheet->setCellValue('G' . $headerRow, 'Jumlah');
            $sheet->setCellValue('H' . $headerRow, 'Total');

            // Apply bold formatting to header
            $headerStyle = [
                'font' => [
                    'bold' => true,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ];
            $sheet->getStyle('A' . $headerRow . ':H' . $headerRow)->applyFromArray($headerStyle);

            // Clear rows 27-35 to remove template content that will interfere with dynamic data
            for ($clearRow = 29; $clearRow <= 35; $clearRow++) {
                for ($col = 'A'; $col <= 'I'; $col++) {
                    $sheet->setCellValue($col . $clearRow, '');
                }
            }

            // Loop through transaction objects starting from row 27
            $startRow = 29;
            $currentRow = $startRow;
            $totalJumlah = 0;

            foreach ($transaction->transaksiObject as $index => $transaksiObj) {
                $rincianObjek = $transaksiObj->object->sub_rincian_objek ?? '-';
                $tpj = $transaksiObj->object->tpj->nama_tpj ?? '-';
                $tpj_qty = $transaksiObj->tpj_quantity ?? 0;
                $satuan_qty = $transaksiObj->satuan_quantity ?? 0;
                $satuan = $transaksiObj->object->satuan->nama_satuan ?? '-';
                $tarif = $transaksiObj->object->harga_objek ?? 0;
                $jumlah = $transaksiObj->quantity ?? 0;
                $totalBayar = $transaksiObj->total_amount;
                $totalJumlah += $totalBayar;
                $sheet->setCellValue('A' . $currentRow, ($index + 1));
                // Merge B:C for Rincian Objek
                $sheet->mergeCells('B' . $currentRow . ':C' . $currentRow);
                $sheet->setCellValue('B' . $currentRow, $rincianObjek);
                $sheet->setCellValue('D' . $currentRow, $tpj_qty . ' ' . $tpj);
                $sheet->setCellValue('E' . $currentRow, $satuan_qty . ' ' . $satuan);
                $value = round($totalBayar / $jumlah, 2);

                $sheet->setCellValue(
                    'F' . $currentRow,
                    $value

                );
                $sheet->setCellValueExplicit('G' . $currentRow, $jumlah, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->setCellValue('H' . $currentRow, $totalBayar);
                // $sheet->getStyle('H' . $currentRow)->getNumberFormat()->setFormatCode('0');
                $sheet->getStyle('H' . $currentRow)
                    ->getNumberFormat()
                    ->setFormatCode('"Rp" #,##0.00');

                $sheet->getStyle('F' . $currentRow)
                    ->getNumberFormat()
                    ->setFormatCode('"Rp" #,##0.00');

                $currentRow++;
            }

            // Add total row at the bottom
            $totalRow = $currentRow;
            $sheet->setCellValue('A' . $totalRow, '');
            $sheet->mergeCells('B' . $totalRow . ':C' . $totalRow);
            $sheet->setCellValue('B' . $totalRow, 'Total Retribusi Terutang');
            $sheet->setCellValue('D' . $totalRow, '');
            $sheet->setCellValue('E' . $totalRow, '');
            $sheet->setCellValue('F' . $totalRow, '');
            $sheet->setCellValue('G' . $totalRow, '');
            $sheet->setCellValue('H' . $totalRow, $totalJumlah);
            $sheet->getStyle('H' . $currentRow)
                ->getNumberFormat()
                ->setFormatCode('"Rp" #,##0.00');
            // Add border to the entire table (header + data + total)
            $tableRange = 'A' . $headerRow . ':H' . $totalRow;
            $borderStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
            ];
            $sheet->getStyle($tableRange)->applyFromArray($borderStyle);

            // Center align all cells in the table
            $sheet->getStyle($tableRange)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);

            // Write content after the table - following template structure exactly
            $contentStartRow = $totalRow + 2; // Leave 1 blank row after total

            // Row: Tanggal Jatuh Tempo SKRD : LUNAS (merge A:I to prevent wrap)
            $sheet->mergeCells('A' . $contentStartRow . ':I' . $contentStartRow);
            $sheet->setCellValue('A' . $contentStartRow, 'Tanggal Jatuh Tempo SKRD : LUNAS' . ' pada ' . $transaction->payment_date);
            $sheet->getStyle('A' . $contentStartRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('A' . $contentStartRow)->getFont()->setBold(true);
            $contentStartRow += 3; // Skip 2 rows (extra space before D. PEMBAYARAN)

            // D. PEMBAYARAN section (merge A:I to prevent wrap)
            $sheet->mergeCells('A' . $contentStartRow . ':I' . $contentStartRow);
            $sheet->setCellValue('A' . $contentStartRow, 'D. PEMBAYARAN');
            $sheet->getStyle('A' . $contentStartRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('A' . $contentStartRow)->getFont()->setBold(true);
            $contentStartRow++;

            // Point 1 - merge B:I for long text
            $sheet->setCellValue('A' . $contentStartRow, '1.');
            $sheet->mergeCells('B' . $contentStartRow . ':I' . $contentStartRow);
            $sheet->setCellValue('B' . $contentStartRow, 'Pembayaran melalui Bendaharawan Penerima/Bendaharawan Penerima Pembantu dan/atau UPTSKPD dan dilaksanakan secara non tunai melalui Rekening Kas Umum Daerah Pemerintah Provinsi Lampung (Bank Lampung 380.00.06.00001.1)');
            $contentStartRow++;

            // Point 2 - merge B:I for long text
            $sheet->setCellValue('A' . $contentStartRow, '2.');
            $sheet->mergeCells('B' . $contentStartRow . ':I' . $contentStartRow);
            $sheet->setCellValue('B' . $contentStartRow, 'Keterlambatan pembayaran dapat dikenakan sanksi administrasi berupa bunga sebesar 1% per bulan.');
            $contentStartRow += 4; // Skip to signature section (extra space after point 2)

            // Signature section - center aligned (merge G:I)
            $sheet->mergeCells('G' . $contentStartRow . ':I' . $contentStartRow);
            $sheet->setCellValue('G' . $contentStartRow, $dateNow);
            $sheet->getStyle('G' . $contentStartRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $contentStartRow++;

            $sheet->mergeCells('G' . $contentStartRow . ':I' . $contentStartRow);
            $sheet->setCellValue('G' . $contentStartRow, 'KEPALA');
            $sheet->getStyle('G' . $contentStartRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $contentStartRow++;

            $sheet->mergeCells('G' . $contentStartRow . ':I' . $contentStartRow);
            $sheet->setCellValue('G' . $contentStartRow, $locationChief);
            $sheet->getStyle('G' . $contentStartRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $contentStartRow += 2; // Space for QR/TTD

            // This row will have {ttd} tag for QR code
            $sheet->mergeCells('G' . $contentStartRow . ':I' . $contentStartRow);
            $sheet->setCellValue('G' . $contentStartRow, '{ttd}');
            $sheet->getStyle('G' . $contentStartRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $contentStartRow += 2;

            $sheet->mergeCells('G' . $contentStartRow . ':I' . $contentStartRow);
            $sheet->setCellValue('G' . $contentStartRow, strtoupper($kaban->chief_name));
            $sheet->getStyle('G' . $contentStartRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('G' . $contentStartRow)->getFont()->setBold(true);
            $contentStartRow++;

            // NIP - merge to prevent wrap
            $sheet->mergeCells('G' . $contentStartRow . ':I' . $contentStartRow);
            $sheet->setCellValue('G' . $contentStartRow, 'NIP. ' . $kaban->chief_nip);
            $sheet->getStyle('G' . $contentStartRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $contentStartRow += 2; // Skip to Keterangan section

            // Keterangan section
            $sheet->setCellValue('A' . $contentStartRow, 'Keterangan');
            $contentStartRow++;
            $sheet->mergeCells('A' . $contentStartRow . ':I' . $contentStartRow);
            $sheet->setCellValue('A' . $contentStartRow, 'Lembar I : Wajib Retribusi');
            $sheet->getStyle('A' . $contentStartRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP);

            $contentStartRow++;
            $sheet->mergeCells('A' . $contentStartRow . ':I' . $contentStartRow);
            $sheet->setCellValue('A' . $contentStartRow, 'Lembar II : Seksi/Petugas yang menangani retribusi');
            $sheet->getStyle('A' . $contentStartRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP);

            $contentStartRow++;
            $sheet->mergeCells('A' . $contentStartRow . ':I' . $contentStartRow);
            $sheet->setCellValue('A' . $contentStartRow, 'Lembar III : Arsip');
            $sheet->getStyle('A' . $contentStartRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP);

            // Generate QR code with kaban information
            $qrCodeData = 'https://saibara.lampungprov.go.id/api/chief/information/' . $kaban->id;
            $qrCodeBase64 = $this->generateQRCodeBase64($qrCodeData, 300);

            // Replace {ttd} tag with QR code image
            foreach ($sheet->getRowIterator() as $row) {
                foreach ($row->getCellIterator() as $cell) {
                    $cellCoordinate = $cell->getCoordinate();
                    if (str_contains($cellCoordinate, ':')) {
                        continue;
                    }
                    $cellValue = $cell->getValue();
                    if (is_string($cellValue) && str_contains($cellValue, '{ttd}')) {
                        try {
                            $drawing = new Drawing;
                            $drawing->setName('QRCode');
                            $drawing->setDescription('Signature QR Code');
                            $qrCodeImage = base64_decode(str_replace('data:image/png;base64,', '', $qrCodeBase64));
                            $tempFile = tempnam(sys_get_temp_dir(), 'qrcode_') . '.png';
                            file_put_contents($tempFile, $qrCodeImage);
                            $drawing->setPath($tempFile);
                            $drawing->setCoordinates($cellCoordinate);
                            $drawing->setHeight(120);
                            $drawing->setWidth(120);
                            $drawing->setOffsetX(5);
                            $drawing->setOffsetY(5);
                            $drawing->setWorksheet($sheet);
                            $cell->setValue('');
                        } catch (\Exception $e) {
                            $cell->setValue('');
                        }
                        break 2;
                    }
                }
            }

            // Page setup and print area for SKRD - dynamically adjust based on content
            $pageSetup = $sheet->getPageSetup();
            $pageSetup->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
            $pageSetup->setPaperSize(PageSetup::PAPERSIZE_A4);
            $pageSetup->setFitToWidth(1);
            $pageSetup->setFitToHeight(0); // Allow multiple pages if needed

            // Set print area dynamically to include all content
            $lastRow = $contentStartRow; // Use actual content end row
            $sheet->getPageSetup()->setPrintArea('A1:I' . $lastRow);

            $sheet->getPageMargins()
                ->setTop(0.5)
                ->setBottom(0)
                ->setLeft(0.5)
                ->setRight(0.5);

            // Convert Excel to HTML
            $spreadsheet->setActiveSheetIndex(0);
            $htmlWriter = new \PhpOffice\PhpSpreadsheet\Writer\Html($spreadsheet);
            ob_start();
            $htmlWriter->save('php://output');
            $excelHtml = ob_get_clean();

            // Remove borders in HTML
            $excelHtml = preg_replace('/\s*border="[^"]*"/i', '', $excelHtml);
            $excelHtml = preg_replace('/border\s*:\s*[^;\"]+;?/i', '', $excelHtml);

            // Prepare temp dir for mPDF
            $preferredTemp = storage_path('app/mpdf_temp');
            $mpdfTemp = null;
            try {
                if (! file_exists($preferredTemp)) {
                    mkdir($preferredTemp, 0777, true);
                }
                if (! file_exists($preferredTemp . '/mpdf')) {
                    mkdir($preferredTemp . '/mpdf', 0777, true);
                }
                @chmod($preferredTemp, 0777);
                @chmod($preferredTemp . '/mpdf', 0777);
                if (is_writable($preferredTemp . '/mpdf')) {
                    $mpdfTemp = $preferredTemp;
                }
            } catch (\Throwable $t) {
                $mpdfTemp = null;
            }
            if ($mpdfTemp === null || ! is_writable($mpdfTemp . '/mpdf')) {
                $sys = sys_get_temp_dir();
                if (! file_exists($sys . '/mpdf')) {
                    @mkdir($sys . '/mpdf', 0777, true);
                    @chmod($sys . '/mpdf', 0777);
                }
                if (is_writable($sys . '/mpdf')) {
                    $mpdfTemp = $sys;
                } else {
                    if (is_writable($preferredTemp)) {
                        $mpdfTemp = $preferredTemp;
                    }
                }
            }
            if ($mpdfTemp === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No writable temp directory available for mPDF. Please ensure storage/app/mpdf_temp is writable.',
                ], 500);
            }

            // Create PDF
            $mpdf = new \Mpdf\Mpdf([
                'format' => 'A4',
                'margin_top' => 10,
                'margin_bottom' => 5,
                'margin_left' => 10,
                'margin_right' => 10,
                'tempDir' => $mpdfTemp,
                'autoPageBreak' => true,
                'shrink_tables_to_fit' => 1,
            ]);

            $mpdf->WriteHTML('<style>
                    body, table, td { font-family: Arial, sans-serif; font-size: 10pt; line-height: 1.1; }
                    table { border-collapse: collapse; width: 100%; }
                    td { padding: 2px 4px; }
                </style>', \Mpdf\HTMLParserMode::HEADER_CSS);
            $mpdf->WriteHTML($excelHtml);

            // Save output with timestamp to avoid cache
            $filename = $document->document_number . '-' . $transaction->reference_no . '-' . date('Ymd_His') . '.pdf';
            $filename = $this->safeFilename($filename);
            $outputPath = storage_path('app/private/documents/' . $filename);

            if (! file_exists(dirname($outputPath))) {
                Storage::makeDirectory('private/documents');
            }

            $mpdf->Output($outputPath, 'F');

            $filePath = 'private/documents/' . $filename;
            $fullPath = storage_path('app/' . $filePath);

            if (! Storage::disk('local')->exists($filePath)) {
                return response()->json(['message' => 'File not found'], 404);
            }
            $document->update([
                'download_link' => $filePath,
            ]);

            return response()->file($fullPath);

            // return response()->download($fullPath);
            // return response()->download($fullPath, $filename);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error generating report: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function generateSKRDInstallment($no_reference, $id)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (! $user) {
            return response()->json(['status' => 201, 'message' => "You're not authenticated"]);
        }
        // Get transactions, documents, and objects
        $transaction = Transaksi::with([
            'transaksiObject.object.opd:id,nama_opd',
            'transaksiObject.object.lokasi:id,nama_lokasi',
            'transaksiObject.object.retribusi:id,nama_retribusi',
            'transaksiObject.object.rincianRetribusi:id,rincian_retribusi',
            'transaksiObject.object.subRincianRetribusi:id,nama_sub_rincian_retribusi',
            'transaksiObject.object.tpj:id,nama_tpj',
            'transaksiObject.object.satuan:id,nama_satuan',
            'subject',
            'subject.masterOpd:id,nama_opd',
            'tipePembayaran',
        ])->where('reference_no', $no_reference)->first();

        if (! $transaction) {
            return response()->json(['status' => 404, 'message' => 'Transaction not found'], 404);
        }
        $installment = InstallmentTransaction::where('transaction_id', $transaction->reference_no)
            ->where('id', $id)
            ->first();

        if (! $installment) {
            return response()->json(['status' => 404, 'message' => 'Installment not found'], 404);
        }

        $document = Document::where('transaksi_id', $transaction->reference_no)->where('master_tipe_dokumen_id', 2)->where('installment_id', $installment->id)->first();
        $spdordDocument = Document::where('transaksi_id', $transaction->reference_no)->where('master_tipe_dokumen_id', 1)->first();
        // $kaban = MasterChief::with('masterOpd', 'masterLokasi')->where('master_opd_id', $transaction->transaksiObject->first()->object->master_opd_id)->where('master_lokasi_id', $transaction->transaksiObject->first()->object->master_lokasi_id)->where('is_active', 1)->first();
        // if ($kaban == null) {
        //     $kaban = MasterChief::where('master_opd_id', $transaction->transaksiObject->first()->object->master_opd_id)->where('is_active', 1)->first();
        // }
        $kaban = MasterChief::with('masterOpd', 'masterLokasi')->where('id', $document->master_chief_id)->first();

        $locationChief = $kaban->master_lokasi_id == 0 ? $kaban->masterOpd->nama_opd : $kaban->masterLokasi->nama_lokasi;
        $opdName = $transaction->transaksiObject->first()->object->opd->nama_opd;
        $issueDate = $document->issue_date;
        $subjectName = $transaction->subject_name;
        $subjectOpd = '';
        if ($transaction->nama_instansi == null || $transaction->nama_instansi == 'null') {
            $subjectOpd = 'PERORANGAN';
        } else {
            $subjectOpd = $transaction->nama_instansi;
        }
        $subjectAddress = $transaction->subject_address;
        $retribusiName = $transaction->transaksiObject->first()->object->retribusi->nama_retribusi;
        $rincianRetribusi = $transaction->transaksiObject->first()->object->rincianRetribusi->rincian_retribusi ?? '';
        $lokasiName = $transaction->transaksiObject->first()->object->lokasi->nama_lokasi;
        $uraian = $transaction->transaksiObject->first()->object->retribusi->nama_retribusi ?? '';
        $keterangan = $transaction->transaksiObject->first()->object->rincian_objek ?? '';
        $subRincianRetribusi = $transaction->transaksiObject->first()->object->subRincianRetribusi->nama_sub_rincian_retribusi ?? '';
        $dateNow = Carbon::now()->format('Y-m-d');
        try {
            // Load template Excel
            $templatePath = storage_path('app/templates/skrd_template.xlsx');
            $spreadsheet = IOFactory::load($templatePath);
            $sheet = $spreadsheet->getActiveSheet();

            $this->replaceTagExcel($sheet, '{document_number}', $document->document_number);
            $this->replaceTagExcel($sheet, '{retribusi}', $retribusiName);
            $this->replaceTagExcel($sheet, '{issue_date}', $issueDate);
            $this->replaceTagExcel($sheet, '{spdord_number}', $spdordDocument->document_number ?? '-');
            $this->replaceTagExcel($sheet, '{name}', $subjectName);
            $this->replaceTagExcel($sheet, '{opd_wr}', $subjectOpd);
            $this->replaceTagExcel($sheet, '{address}', $subjectAddress);
            $this->replaceTagExcel($sheet, '{lokasi}', $lokasiName);
            $this->replaceTagExcel($sheet, '{uraian}', $uraian);
            $this->replaceTagExcel($sheet, '{keterangan}', $keterangan);
            $this->replaceTagExcel($sheet, '{rincian_retribusi}', $rincianRetribusi);
            $this->replaceTagExcel($sheet, '{sub_rincian_retribusi}', $subRincianRetribusi);
            $this->replaceTagExcel($sheet, '{date}', $dateNow);
            $this->replaceTagExcel($sheet, '{opd}', $opdName);
            $this->replaceTagExcel($sheet, '{opd_dinas}', $opdName);
            $this->replaceTagExcel($sheet, '{chief}', $kaban->chief_name);
            $this->replaceTagExcel($sheet, '{nip}', $kaban->chief_nip);

            // Add header row for object loop at row 26
            $headerRow = 28;

            $sheet->getColumnDimension('A')->setWidth(5);  // No - narrow
            $sheet->getColumnDimension('B')->setWidth(20); // Rincian Objek part 1
            $sheet->getColumnDimension('C')->setWidth(20); // Rincian Objek part 2
            $sheet->getColumnDimension('D')->setWidth(15); // TPJ
            $sheet->getColumnDimension('E')->setWidth(12); // Satuan
            $sheet->getColumnDimension('F')->setWidth(12); // Tarif
            $sheet->getColumnDimension('G')->setWidth(10); // Jumlah
            $sheet->getColumnDimension('H')->setWidth(15); // Total

            $sheet->setCellValue('A' . $headerRow, 'No');
            // Merge B:C for Rincian Objek to avoid long wrap
            $sheet->mergeCells('B' . $headerRow . ':C' . $headerRow);
            $sheet->setCellValue('B' . $headerRow, 'Rincian Objek');
            $sheet->setCellValue('D' . $headerRow, 'TPJ');
            $sheet->setCellValue('E' . $headerRow, 'Satuan');
            $sheet->setCellValue('F' . $headerRow, 'Tarif');
            $sheet->setCellValue('G' . $headerRow, 'Jumlah');
            $sheet->setCellValue('H' . $headerRow, 'Total');

            // Apply bold formatting to header
            $headerStyle = [
                'font' => [
                    'bold' => true,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ];
            $sheet->getStyle('A' . $headerRow . ':H' . $headerRow)->applyFromArray($headerStyle);

            // Clear rows 27-35 to remove template content that will interfere with dynamic data
            for ($clearRow = 29; $clearRow <= 35; $clearRow++) {
                for ($col = 'A'; $col <= 'H'; $col++) {
                    $sheet->setCellValue($col . $clearRow, '');
                }
            }

            // Loop through transaction objects starting from row 27
            $startRow = 29;
            $currentRow = $startRow;
            $totalJumlah = 0;

            foreach ($transaction->transaksiObject as $index => $transaksiObj) {
                if ($index > 0) {
                    $sheet->insertNewRowBefore($currentRow + 1, 1);
                    $currentRow++;
                }

                $rincianObjek = $transaksiObj->object->sub_rincian_objek ?? '-';
                $tpj = isset($transaksiObj->object->tpj) ? $transaksiObj->object->tpj->nama_tpj : ($transaksiObj->tpj ?? '-');
                $satuan = isset($transaksiObj->object->satuan) ? $transaksiObj->object->satuan->nama_satuan : ($transaksiObj->satuan ?? '-');
                // $tarif = $transaksiObj->price ?? 0;
                // $jumlah = $transaksiObj->quantity ?? 0;
                $totalBayar = $transaksiObj->total_amount;
                $totalJumlah += $totalBayar;

                $sheet->setCellValue('A' . $currentRow, ($index + 1));
                $sheet->setCellValue('B' . $currentRow, $rincianObjek);
                $sheet->setCellValue('C' . $currentRow, $tpj);
                $sheet->setCellValue('D' . $currentRow, $satuan);
                $sheet->setCellValue('E' . $currentRow, $installment->payment_amount);
                $sheet->setCellValue('F' . $currentRow, 1);
                $sheet->setCellValue('G' . $currentRow, $installment->payment_amount);

                $sheet->setCellValue('A' . $currentRow, ($index + 1));
                // Merge B:C for Rincian Objek
                $sheet->mergeCells('B' . $currentRow . ':C' . $currentRow);
                $sheet->setCellValue('B' . $currentRow, $rincianObjek);
                $sheet->setCellValue('D' . $currentRow, $tpj);
                $sheet->setCellValue('E' . $currentRow, $satuan);
                $sheet->setCellValue('F' . $currentRow, $installment->payment_amount);
                $sheet->setCellValue('G' . $currentRow, 1);
                $sheet->setCellValue('H' . $currentRow, $installment->payment_amount);
                $currentRow++;
            }

            // Add total row at the bottom
            $totalRow = $currentRow;
            $sheet->setCellValue('A' . $totalRow, '');
            $sheet->mergeCells('B' . $totalRow . ':C' . $totalRow);
            $sheet->setCellValue('B' . $totalRow, 'Total Retribusi Terutang');
            $sheet->setCellValue('D' . $totalRow, '');
            $sheet->setCellValue('E' . $totalRow, '');
            $sheet->setCellValue('F' . $totalRow, '');
            $sheet->setCellValue('G' . $totalRow, '');
            $sheet->setCellValue('H' . $totalRow, $installment->payment_amount);

            // Add border to the entire table (header + data + total)
            $tableRange = 'A' . $headerRow . ':H' . $totalRow;
            $borderStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
            ];
            $sheet->getStyle($tableRange)->applyFromArray($borderStyle);

            // Center align all cells in the table
            $sheet->getStyle($tableRange)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);

            // Write content after the table - following template structure exactly
            $contentStartRow = $totalRow + 4; // Leave 1 blank row after total

            // Row: Tanggal Jatuh Tempo SKRD : LUNAS (merge A:I to prevent wrap)
            $sheet->mergeCells('A' . $contentStartRow . ':I' . $contentStartRow);
            $sheet->setCellValue('A' . $contentStartRow, 'Tanggal Jatuh Tempo SKRD : LUNAS' . ' pada ' . $installment->paid_date);
            $sheet->getStyle('A' . $contentStartRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('A' . $contentStartRow)->getFont()->setBold(true);
            $contentStartRow += 3; // Skip 2 rows (extra space before D. PEMBAYARAN)

            // D. PEMBAYARAN section (merge A:I to prevent wrap)
            $sheet->mergeCells('A' . $contentStartRow . ':I' . $contentStartRow);
            $sheet->setCellValue('A' . $contentStartRow, 'D. PEMBAYARAN');
            $sheet->getStyle('A' . $contentStartRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('A' . $contentStartRow)->getFont()->setBold(true);
            $contentStartRow++;

            // Point 1 - merge B:I for long text
            $sheet->setCellValue('A' . $contentStartRow, '1.');
            $sheet->mergeCells('B' . $contentStartRow . ':I' . $contentStartRow);
            $sheet->setCellValue('B' . $contentStartRow, 'Pembayaran melalui Bendaharawan Penerima/Bendaharawan Penerima Pembantu dan/atau UPTSKPD dan dilaksanakan secara non tunai melalui Rekening Kas Umum Daerah Pemerintah Provinsi Lampung (Bank Lampung 380.00.06.00001.1)');
            $contentStartRow++;

            // Point 2 - merge B:I for long text
            $sheet->setCellValue('A' . $contentStartRow, '2.');
            $sheet->mergeCells('B' . $contentStartRow . ':I' . $contentStartRow);
            $sheet->setCellValue('B' . $contentStartRow, 'Keterlambatan pembayaran dapat dikenakan sanksi administrasi berupa bunga sebesar 1% per bulan.');
            $contentStartRow += 4; // Skip to signature section (extra space after point 2)

            // Signature section - center aligned (merge G:I)
            $sheet->mergeCells('G' . $contentStartRow . ':I' . $contentStartRow);
            $sheet->setCellValue('G' . $contentStartRow, $dateNow);
            $sheet->getStyle('G' . $contentStartRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $contentStartRow++;

            $sheet->mergeCells('G' . $contentStartRow . ':I' . $contentStartRow);
            $sheet->setCellValue('G' . $contentStartRow, 'KEPALA');
            $sheet->getStyle('G' . $contentStartRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $contentStartRow++;

            $sheet->mergeCells('G' . $contentStartRow . ':I' . $contentStartRow);
            $sheet->setCellValue('G' . $contentStartRow, $locationChief);
            $sheet->getStyle('G' . $contentStartRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $contentStartRow += 2; // Space for QR/TTD

            // This row will have {ttd} tag for QR code
            $sheet->mergeCells('G' . $contentStartRow . ':I' . $contentStartRow);
            $sheet->setCellValue('G' . $contentStartRow, '{ttd}');
            $sheet->getStyle('G' . $contentStartRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $contentStartRow += 2;

            $sheet->mergeCells('G' . $contentStartRow . ':I' . $contentStartRow);
            $sheet->setCellValue('G' . $contentStartRow, strtoupper($kaban->chief_name));
            $sheet->getStyle('G' . $contentStartRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('G' . $contentStartRow)->getFont()->setBold(true);
            $contentStartRow++;

            // NIP - merge to prevent wrap
            $sheet->mergeCells('G' . $contentStartRow . ':I' . $contentStartRow);
            $sheet->setCellValue('G' . $contentStartRow, 'NIP. ' . $kaban->chief_nip);
            $sheet->getStyle('G' . $contentStartRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $contentStartRow += 2; // Skip to Keterangan section

            // Keterangan section
            $sheet->setCellValue('A' . $contentStartRow, 'Keterangan');
            $contentStartRow++;
            $sheet->mergeCells('A' . $contentStartRow . ':I' . $contentStartRow);
            $sheet->setCellValue('A' . $contentStartRow, 'Lembar I : Wajib Retribusi');
            $sheet->getStyle('A' . $contentStartRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP);

            $contentStartRow++;
            $sheet->mergeCells('A' . $contentStartRow . ':I' . $contentStartRow);
            $sheet->setCellValue('A' . $contentStartRow, 'Lembar II : Seksi/Petugas yang menangani retribusi');
            $sheet->getStyle('A' . $contentStartRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP);

            $contentStartRow++;
            $sheet->mergeCells('A' . $contentStartRow . ':I' . $contentStartRow);
            $sheet->setCellValue('A' . $contentStartRow, 'Lembar III : Arsip');
            $sheet->getStyle('A' . $contentStartRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP);

            // Generate QR code with kaban information
            $qrCodeData = 'https://saibara.lampungprov.go.id/api/chief/information/' . $kaban->id;
            $qrCodeBase64 = $this->generateQRCodeBase64($qrCodeData, 300);
            // Replace {ttd} tag with QR code image
            $ttdFound = false;
            foreach ($sheet->getRowIterator() as $row) {
                foreach ($row->getCellIterator() as $cell) {
                    $cellCoordinate = $cell->getCoordinate();
                    if (str_contains($cellCoordinate, ':')) {
                        continue;
                    }
                    $cellValue = $cell->getValue();
                    if (is_string($cellValue) && str_contains($cellValue, '{ttd}')) {
                        $ttdFound = true;
                        try {

                            // Create drawing for QR code
                            $drawing = new Drawing;
                            $drawing->setName('QRCode');
                            $drawing->setDescription('Signature QR Code');

                            // Save base64 to temp file
                            $qrCodeImage = base64_decode(str_replace('data:image/png;base64,', '', $qrCodeBase64));
                            $tempFile = tempnam(sys_get_temp_dir(), 'qrcode_') . '.png';
                            file_put_contents($tempFile, $qrCodeImage);

                            $drawing->setPath($tempFile);
                            $drawing->setCoordinates($cellCoordinate);
                            $drawing->setHeight(120); // Larger for better scanning
                            $drawing->setWidth(120);
                            $drawing->setOffsetX(5);
                            $drawing->setOffsetY(5);
                            $drawing->setWorksheet($sheet);

                            // Clear the cell text
                            $cell->setValue('');
                        } catch (\Exception $e) {
                            // If QR code fails, just clear the tag
                            $cell->setValue('');
                        }
                        break 2;
                    }
                }
            }

            // If {ttd} tag wasn't found, log it (optional for debugging)
            if (! $ttdFound) {
                dd('Ttd not found');
            }
            // Page setup and print area for SKRD Installment - dynamically adjust based on content
            $pageSetup = $sheet->getPageSetup();
            $pageSetup->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
            $pageSetup->setPaperSize(PageSetup::PAPERSIZE_A4);
            $pageSetup->setFitToWidth(1);
            $pageSetup->setFitToHeight(0); // Allow multiple pages if needed

            // Set print area dynamically to include all content
            $lastRow = $contentStartRow; // Use actual content end row
            $sheet->getPageSetup()->setPrintArea('A1:I' . $lastRow);

            $sheet->getPageMargins()
                ->setTop(0.5)
                ->setBottom(0)
                ->setLeft(0.5)
                ->setRight(0.5);

            // Convert Excel to HTML
            $spreadsheet->setActiveSheetIndex(0);
            $htmlWriter = new \PhpOffice\PhpSpreadsheet\Writer\Html($spreadsheet);
            ob_start();
            $htmlWriter->save('php://output');
            $excelHtml = ob_get_clean();

            // Remove borders in HTML
            $excelHtml = preg_replace('/\s*border="[^"]*"/i', '', $excelHtml);
            $excelHtml = preg_replace('/border\s*:\s*[^;\"]+;?/i', '', $excelHtml);

            // Prepare temp dir for mPDF
            $preferredTemp = storage_path('app/mpdf_temp');
            $mpdfTemp = null;
            try {
                if (! file_exists($preferredTemp)) {
                    mkdir($preferredTemp, 0777, true);
                }
                if (! file_exists($preferredTemp . '/mpdf')) {
                    mkdir($preferredTemp . '/mpdf', 0777, true);
                }
                @chmod($preferredTemp, 0777);
                @chmod($preferredTemp . '/mpdf', 0777);
                if (is_writable($preferredTemp . '/mpdf')) {
                    $mpdfTemp = $preferredTemp;
                }
            } catch (\Throwable $t) {
                $mpdfTemp = null;
            }
            if ($mpdfTemp === null || ! is_writable($mpdfTemp . '/mpdf')) {
                $sys = sys_get_temp_dir();
                if (! file_exists($sys . '/mpdf')) {
                    @mkdir($sys . '/mpdf', 0777, true);
                    @chmod($sys . '/mpdf', 0777);
                }
                if (is_writable($sys . '/mpdf')) {
                    $mpdfTemp = $sys;
                } else {
                    if (is_writable($preferredTemp)) {
                        $mpdfTemp = $preferredTemp;
                    }
                }
            }
            if ($mpdfTemp === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No writable temp directory available for mPDF. Please ensure storage/app/mpdf_temp is writable.',
                ], 500);
            }

            // Create PDF
            $mpdf = new \Mpdf\Mpdf([
                'format' => 'A4',
                'margin_top' => 10,
                'margin_bottom' => 5,
                'margin_left' => 10,
                'margin_right' => 10,
                'tempDir' => $mpdfTemp,
                'autoPageBreak' => true,
                'shrink_tables_to_fit' => 1,
            ]);

            $mpdf->WriteHTML('<style>
                    body, table, td { font-family: Arial, sans-serif; font-size: 10pt; line-height: 1.1; }
                    table { border-collapse: collapse; width: 100%; }
                    td { padding: 2px 4px; }
                </style>', \Mpdf\HTMLParserMode::HEADER_CSS);
            $mpdf->WriteHTML($excelHtml);

            // Save output with timestamp to avoid cache
            $filename = $document->document_number . '-' . $installment->transaction_id . '-' . $installment->id . '-' . date('Ymd_His') . '.pdf';
            $filename = $this->safeFilename($filename);
            $outputPath = storage_path('app/private/documents/' . $filename);

            if (! file_exists(dirname($outputPath))) {
                Storage::makeDirectory('private/documents');
            }

            $mpdf->Output($outputPath, 'F');

            $filePath = 'private/documents/' . $filename;
            $fullPath = storage_path('app/' . $filePath);

            if (! Storage::disk('local')->exists($filePath)) {
                return response()->json(['message' => 'File not found'], 404);
            }
            $document->update([
                'download_link' => $filePath,
            ]);

            return response()->file($fullPath);

            // return response()->download($fullPath);
            // return response()->download($fullPath, $filename);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error generating report: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function generateSTRD($no_reference, $id)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (! $user) {
            return response()->json(['status' => 201, 'message' => "You're not authenticated"]);
        }
        // Get transactions, documents, and objects
        $transaction = Transaksi::with([
            'transaksiObject.object.opd:id,nama_opd',
            'transaksiObject.object.lokasi:id,nama_lokasi',
            'transaksiObject.object.retribusi:id,nama_retribusi',
            'transaksiObject.object.rincianRetribusi:id,rincian_retribusi',
            'transaksiObject.object.subRincianRetribusi:id,nama_sub_rincian_retribusi',
            'transaksiObject.object.tpj:id,nama_tpj',
            'transaksiObject.object.satuan:id,nama_satuan',
            'subject',
            'subject.masterOpd:id,nama_opd',
            'tipePembayaran',
        ])->where('reference_no', $no_reference)->first();

        if (! $transaction) {
            return response()->json(['status' => 404, 'message' => 'Transaction not found'], 404);
        }
        $installment = InstallmentTransaction::where('transaction_id', $transaction->reference_no)
            ->where('id', $id)
            ->first();

        if (! $installment) {
            return response()->json(['status' => 404, 'message' => 'Installment not found'], 404);
        }
        $document = Document::where('transaksi_id', $transaction->reference_no)
            ->where('installment_id', $installment->id)
            ->where('master_tipe_dokumen_id', 3)
            ->first();

        if (! $document) {
            $document = Document::create([
                'master_tipe_dokumen_id' => 3,
                'transaksi_id' => $transaction->reference_no,
                'installment_id' => $installment->id,
                'document_number' => 'STRD',
                'linked_document_number' => 'STRD',
                'issue_date' => Carbon::now('Asia/Jakarta'),
            ]);
        }

        $spdordDocument = Document::where('transaksi_id', $transaction->reference_no)->where('master_tipe_dokumen_id', 1)->first();
        $skrdDocument = Document::where('transaksi_id', $transaction->reference_no)->where('master_tipe_dokumen_id', 2)->where('installment_id', $installment->id)->first();
        $kaban = MasterChief::with('masterOpd', 'masterLokasi')->where('id', $document->master_chief_id)->first();

        $locationChief = $kaban->master_lokasi_id == 0 ? $kaban->masterOpd->nama_opd : $kaban->masterLokasi->nama_lokasi;
        $opdName = $transaction->transaksiObject->first()->object->opd->nama_opd;
        $issueDate = $document->issue_date;
        $subjectName = $transaction->subject_name;
        $subjectOpd = '';

        if ($transaction->nama_instansi == null || $transaction->nama_instansi == 'null') {
            $subjectOpd = 'PERORANGAN';
        } else {
            $subjectOpd = $transaction->nama_instansi;
        }
        $subjectAddress = $transaction->subject_address;
        $retribusiName = $transaction->transaksiObject->first()->object->retribusi->nama_retribusi;
        $rincianRetribusi = $transaction->transaksiObject->first()->object->rincianRetribusi->rincian_retribusi ?? '';
        $lokasiName = $transaction->transaksiObject->first()->object->lokasi->nama_lokasi;
        $uraian = $transaction->transaksiObject->first()->object->retribusi->nama_retribusi ?? '';
        $keterangan = $transaction->transaksiObject->first()->object->rincian_objek ?? '';
        $subRincianRetribusi = $transaction->transaksiObject->first()->object->subRincianRetribusi->nama_sub_rincian_retribusi ?? '';
        $dateNow = Carbon::now()->format('Y-m-d');
        try {
            // Load template Excel
            $templatePath = storage_path('app/templates/strd_template.xlsx');
            $spreadsheet = IOFactory::load($templatePath);
            $sheet = $spreadsheet->getActiveSheet();
            $formattedId = str_pad((string) $document->id, 5, '0', STR_PAD_LEFT);

            $this->replaceTagExcel($sheet, '{document_number}', 'STRD-' . $opdName . '-' . $lokasiName . '-' . $dateNow . '-' . $formattedId);

            $this->replaceTagExcel($sheet, '{document_number}', $document->document_number);
            $this->replaceTagExcel($sheet, '{retribusi}', $retribusiName);
            $this->replaceTagExcel($sheet, '{issue_date}', $issueDate);
            $this->replaceTagExcel($sheet, '{spdord_number}', $spdordDocument->document_number ?? '-');
            $this->replaceTagExcel($sheet, '{skrd_number}', $skrdDocument->document_number ?? '-');
            $this->replaceTagExcel($sheet, '{name}', $subjectName);
            $this->replaceTagExcel($sheet, '{opd_wr}', $subjectOpd);
            $this->replaceTagExcel($sheet, '{address}', $subjectAddress);
            $this->replaceTagExcel($sheet, '{lokasi}', $lokasiName);
            $this->replaceTagExcel($sheet, '{uraian}', $uraian);
            $this->replaceTagExcel($sheet, '{keterangan}', $keterangan);
            $this->replaceTagExcel($sheet, '{rincian_retribusi}', $rincianRetribusi);
            $this->replaceTagExcel($sheet, '{sub_rincian_retribusi}', $subRincianRetribusi);
            $this->replaceTagExcel($sheet, '{date}', $dateNow);
            $this->replaceTagExcel($sheet, '{opd}', $opdName);
            $this->replaceTagExcel($sheet, '{opd_dinas}', $opdName);
            $this->replaceTagExcel($sheet, '{chief}', $kaban->chief_name);
            $this->replaceTagExcel($sheet, '{nip}', $kaban->chief_nip);

            // Add header row for object loop at row 28 (with 2 rows spacing)
            $headerRow = 26;

            // Set column widths for better layout
            $sheet->getColumnDimension('A')->setWidth(5);  // No - narrow
            $sheet->getColumnDimension('B')->setWidth(20); // Jenis Denda Retribusi part 1
            $sheet->getColumnDimension('C')->setWidth(20); // Jenis Denda Retribusi part 2
            $sheet->getColumnDimension('D')->setWidth(20); // Jenis Denda Retribusi part 3
            $sheet->getColumnDimension('E')->setWidth(10); // Volume
            $sheet->getColumnDimension('F')->setWidth(12); // Satuan
            $sheet->getColumnDimension('G')->setWidth(15); // Nominal SKRD Pokok
            $sheet->getColumnDimension('H')->setWidth(15); // Jumlah

            $sheet->setCellValue('A' . $headerRow, 'No');
            // Merge B:D for Jenis Denda Retribusi to avoid long wrap
            $sheet->mergeCells('B' . $headerRow . ':D' . $headerRow);
            $sheet->setCellValue('B' . $headerRow, 'Jenis Denda Retribusi');
            $sheet->setCellValue('E' . $headerRow, 'Volume');
            $sheet->setCellValue('F' . $headerRow, 'Satuan');
            $sheet->setCellValue('G' . $headerRow, 'Nominal SKRD Pokok');
            $sheet->setCellValue('H' . $headerRow, 'Jumlah');

            // Apply bold formatting to header
            $headerStyle = [
                'font' => [
                    'bold' => true,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ];
            $sheet->getStyle('A' . $headerRow . ':H' . $headerRow)->applyFromArray($headerStyle);

            // Clear rows 29-37 to remove template content that will interfere with dynamic data
            for ($clearRow = 27; $clearRow <= 37; $clearRow++) {
                for ($col = 'A'; $col <= 'I'; $col++) {
                    $sheet->setCellValue($col . $clearRow, '');
                }
            }

            // Loop through transaction objects starting from row 29
            $startRow = 27;
            $currentRow = $startRow;
            $totalJumlah = 0;

            foreach ($transaction->transaksiObject as $index => $transaksiObj) {
                if ($index > 0) {
                    $sheet->insertNewRowBefore($currentRow + 1, 1);
                    $currentRow++;
                }

                $rincianObjek = $transaksiObj->object->sub_rincian_objek ?? '-';
                $satuan = isset($transaksiObj->object->satuan) ? $transaksiObj->object->satuan->nama_satuan : ($transaksiObj->satuan ?? '-');
                // $tarif = $transaksiObj->price ?? 0;
                // $jumlah = $transaksiObj->quantity ?? 0;
                $totalBayar = $transaksiObj->total_amount;
                $totalJumlah += $totalBayar;

                $sheet->setCellValue('A' . $currentRow, ($index + 1));
                // Merge B:D for Jenis Denda Retribusi
                $sheet->mergeCells('B' . $currentRow . ':D' . $currentRow);
                $sheet->setCellValue('B' . $currentRow, $rincianObjek);
                $sheet->setCellValue('E' . $currentRow, 1);
                $sheet->setCellValue('F' . $currentRow, $satuan);
                $sheet->setCellValue('G' . $currentRow, $installment->payment_amount);
                $sheet->setCellValue('H' . $currentRow, $installment->penalty_amount);

                $currentRow++;
            }

            // Add total row at the bottom
            $totalRow = $currentRow;
            $sheet->setCellValue('A' . $totalRow, '');
            $sheet->mergeCells('B' . $totalRow . ':D' . $totalRow);
            $sheet->setCellValue('B' . $totalRow, 'Total Tagihan Retribusi Terutang');
            $sheet->setCellValue('E' . $totalRow, '');
            $sheet->setCellValue('F' . $totalRow, '');
            $sheet->setCellValue('G' . $totalRow, '');
            $sheet->setCellValue('H' . $totalRow, $installment->penalty_amount);

            // Add border to the entire table (header + data + total)
            $tableRange = 'A' . $headerRow . ':H' . $totalRow;
            $borderStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
            ];
            $sheet->getStyle($tableRange)->applyFromArray($borderStyle);

            // Center align all cells in the table
            $sheet->getStyle($tableRange)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);

            // Write content after the table - following template structure exactly
            $contentStartRow = $totalRow + 2; // Leave 1 blank row after total

            // Row: Tanggal Jatuh Tempo SKRD : LUNAS (merge A:I to prevent wrap)
            $sheet->mergeCells('A' . $contentStartRow . ':I' . $contentStartRow);
            $sheet->setCellValue('A' . $contentStartRow, 'Tanggal Jatuh Tempo STRD : LUNAS' . ' pada ' . $installment->paid_date);
            $sheet->getStyle('A' . $contentStartRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('A' . $contentStartRow)->getFont()->setBold(true);
            $contentStartRow += 2; // Skip 1 row

            // D. PEMBAYARAN section (merge A:I to prevent wrap)
            $sheet->mergeCells('A' . $contentStartRow . ':I' . $contentStartRow);
            $sheet->setCellValue('A' . $contentStartRow, 'D. PEMBAYARAN');
            $sheet->getStyle('A' . $contentStartRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('A' . $contentStartRow)->getFont()->setBold(true);
            $contentStartRow++;

            // Point 1 - merge B:I for long text
            $sheet->setCellValue('A' . $contentStartRow, '1.');
            $sheet->mergeCells('B' . $contentStartRow . ':I' . $contentStartRow);
            $sheet->setCellValue('B' . $contentStartRow, 'Pembayaran melalui Bendaharawan Penerima/Bendaharawan Penerima Pembantu dan/atau UPTSKPD dan dilaksanakan secara non tunai melalui Rekening Kas Umum Daerah Pemerintah Provinsi Lampung (Bank Lampung 380.00.06.00001.1)');
            $contentStartRow++;

            // Point 2 - merge B:I for long text
            $sheet->setCellValue('A' . $contentStartRow, '2.');
            $sheet->mergeCells('B' . $contentStartRow . ':I' . $contentStartRow);
            $sheet->setCellValue('B' . $contentStartRow, 'Keterlambatan pembayaran STRD dapat dikenakan sanksi administrasi kumulatif berupa bunga sebesar 1% per bulan.');
            $contentStartRow += 3; // Skip to signature section

            // Signature section - center aligned (merge G:I)
            $sheet->mergeCells('G' . $contentStartRow . ':I' . $contentStartRow);
            $sheet->setCellValue('G' . $contentStartRow, $dateNow);
            $sheet->getStyle('G' . $contentStartRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $contentStartRow++;

            $sheet->mergeCells('G' . $contentStartRow . ':I' . $contentStartRow);
            $sheet->setCellValue('G' . $contentStartRow, 'KEPALA');
            $sheet->getStyle('G' . $contentStartRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $contentStartRow++;

            $sheet->mergeCells('G' . $contentStartRow . ':I' . $contentStartRow);
            $sheet->setCellValue('G' . $contentStartRow, $locationChief);
            $sheet->getStyle('G' . $contentStartRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $contentStartRow += 2; // Space for QR/TTD

            // This row will have {ttd} tag for QR code
            $sheet->mergeCells('G' . $contentStartRow . ':I' . $contentStartRow);
            $sheet->setCellValue('G' . $contentStartRow, '{ttd}');
            $sheet->getStyle('G' . $contentStartRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $contentStartRow += 2;

            $sheet->mergeCells('G' . $contentStartRow . ':I' . $contentStartRow);
            $sheet->setCellValue('G' . $contentStartRow, strtoupper($kaban->chief_name));
            $sheet->getStyle('G' . $contentStartRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('G' . $contentStartRow)->getFont()->setBold(true);
            $contentStartRow++;

            // NIP - merge to prevent wrap
            $sheet->mergeCells('G' . $contentStartRow . ':I' . $contentStartRow);
            $sheet->setCellValue('G' . $contentStartRow, 'NIP. ' . $kaban->chief_nip);
            $sheet->getStyle('G' . $contentStartRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('G' . $contentStartRow)->getFont()->setBold(false);
            $contentStartRow += 2; // Skip to Keterangan section

            // Keterangan section
            $sheet->setCellValue('A' . $contentStartRow, 'Keterangan');
            $contentStartRow++;
            $sheet->mergeCells('A' . $contentStartRow . ':I' . $contentStartRow);
            $sheet->setCellValue('A' . $contentStartRow, 'Lembar I : Wajib Retribusi');
            $sheet->getStyle('A' . $contentStartRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP);

            $contentStartRow++;
            $sheet->mergeCells('A' . $contentStartRow . ':I' . $contentStartRow);
            $sheet->setCellValue('A' . $contentStartRow, 'Lembar II : Seksi/Petugas yang menangani retribusi');
            $sheet->getStyle('A' . $contentStartRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP);

            $contentStartRow++;
            $sheet->mergeCells('A' . $contentStartRow . ':I' . $contentStartRow);
            $sheet->setCellValue('A' . $contentStartRow, 'Lembar III : Arsip');
            $sheet->getStyle('A' . $contentStartRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP);

            // Generate QR code with kaban information
            $qrCodeData = 'https://saibara.lampungprov.go.id/api/chief/information/' . $kaban->id;
            $qrCodeBase64 = $this->generateQRCodeBase64($qrCodeData, 300);
            // Replace {ttd} tag with QR code image
            $ttdFound = false;
            foreach ($sheet->getRowIterator() as $row) {
                foreach ($row->getCellIterator() as $cell) {
                    $cellCoordinate = $cell->getCoordinate();
                    if (str_contains($cellCoordinate, ':')) {
                        continue;
                    }
                    $cellValue = $cell->getValue();
                    if (is_string($cellValue) && str_contains($cellValue, '{ttd}')) {
                        $ttdFound = true;
                        try {

                            // Create drawing for QR code
                            $drawing = new Drawing;
                            $drawing->setName('QRCode');
                            $drawing->setDescription('Signature QR Code');

                            // Save base64 to temp file
                            $qrCodeImage = base64_decode(str_replace('data:image/png;base64,', '', $qrCodeBase64));
                            $tempFile = tempnam(sys_get_temp_dir(), 'qrcode_') . '.png';
                            file_put_contents($tempFile, $qrCodeImage);

                            $drawing->setPath($tempFile);
                            $drawing->setCoordinates($cellCoordinate);
                            $drawing->setHeight(120); // Larger for better scanning
                            $drawing->setWidth(120);
                            $drawing->setOffsetX(5);
                            $drawing->setOffsetY(5);
                            $drawing->setWorksheet($sheet);

                            // Clear the cell text
                            $cell->setValue('');
                        } catch (\Exception $e) {
                            // If QR code fails, just clear the tag
                            $cell->setValue('');
                        }
                        break 2;
                    }
                }
            }

            // Page setup and print area for SKRD Installment - dynamically adjust based on content
            $pageSetup = $sheet->getPageSetup();
            $pageSetup->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
            $pageSetup->setPaperSize(PageSetup::PAPERSIZE_A4);
            $pageSetup->setFitToWidth(1);
            $pageSetup->setFitToHeight(0); // Allow multiple pages if needed

            // Set print area dynamically to include all content
            $lastRow = $contentStartRow; // Use actual content end row
            $sheet->getPageSetup()->setPrintArea('A1:I' . $lastRow);

            $sheet->getPageMargins()
                ->setTop(0.5)
                ->setBottom(0)
                ->setLeft(0.5)
                ->setRight(0.5);

            // Convert Excel to HTML
            $spreadsheet->setActiveSheetIndex(0);
            $htmlWriter = new \PhpOffice\PhpSpreadsheet\Writer\Html($spreadsheet);
            ob_start();
            $htmlWriter->save('php://output');
            $excelHtml = ob_get_clean();

            // Prepare temp dir for mPDF
            $preferredTemp = storage_path('app/mpdf_temp');
            $mpdfTemp = null;
            try {
                if (! file_exists($preferredTemp)) {
                    mkdir($preferredTemp, 0777, true);
                }
                if (! file_exists($preferredTemp . '/mpdf')) {
                    mkdir($preferredTemp . '/mpdf', 0777, true);
                }
                @chmod($preferredTemp, 0777);
                @chmod($preferredTemp . '/mpdf', 0777);
                if (is_writable($preferredTemp . '/mpdf')) {
                    $mpdfTemp = $preferredTemp;
                }
            } catch (\Throwable $t) {
                $mpdfTemp = null;
            }
            if ($mpdfTemp === null || ! is_writable($mpdfTemp . '/mpdf')) {
                $sys = sys_get_temp_dir();
                if (! file_exists($sys . '/mpdf')) {
                    @mkdir($sys . '/mpdf', 0777, true);
                    @chmod($sys . '/mpdf', 0777);
                }
                if (is_writable($sys . '/mpdf')) {
                    $mpdfTemp = $sys;
                } else {
                    if (is_writable($preferredTemp)) {
                        $mpdfTemp = $preferredTemp;
                    }
                }
            }
            if ($mpdfTemp === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No writable temp directory available for mPDF. Please ensure storage/app/mpdf_temp is writable.',
                ], 500);
            }

            // Create PDF
            $mpdf = new \Mpdf\Mpdf([
                'format' => 'A4',
                'margin_top' => 10,
                'margin_bottom' => 5,
                'margin_left' => 10,
                'margin_right' => 10,
                'tempDir' => $mpdfTemp,
                'autoPageBreak' => true,
                'shrink_tables_to_fit' => 1,
            ]);

            $mpdf->WriteHTML('<style>
                    body, table, td { font-family: Arial, sans-serif; font-size: 10pt; line-height: 1.1; }
                    table { border-collapse: collapse; width: 100%; }
                    td { padding: 2px 4px; }
                </style>', \Mpdf\HTMLParserMode::HEADER_CSS);
            $mpdf->WriteHTML($excelHtml);

            // Save output with timestamp to avoid cache
            $filename = $document->document_number . '-' . $installment->transaction_id . '-' . $installment->id . '-' . date('Ymd_His') . '.pdf';
            $filename = $this->safeFilename($filename);
            $outputPath = storage_path('app/private/documents/' . $filename);

            if (! file_exists(dirname($outputPath))) {
                Storage::makeDirectory('private/documents');
            }

            $mpdf->Output($outputPath, 'F');

            $filePath = 'private/documents/' . $filename;
            $fullPath = storage_path('app/' . $filePath);

            if (! Storage::disk('local')->exists($filePath)) {
                return response()->json(['message' => 'File not found'], 404);
            }
            $document->update([
                'download_link' => $filePath,
            ]);

            return response()->file($fullPath);

            // return response()->download($fullPath);
            // return response()->download($fullPath, $filename);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error generating report: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function searchDocument(Request $request): JsonResponse
    {
        $user = JWTAutH::parseToken()->authenticate();
        if ($user) {
            // code...
            try {
                // Get search term from request
                $searchTerm = $request->input('search', '');
                $opdId = $request->opd_id;
                // Start building the query with eager loading
                $query = Document::whereHas('transaksi.transaksiObject.object', function ($query) use ($opdId) {
                    $query->where('master_opd_id', $opdId);
                });
                if (! empty($searchTerm)) {
                    $query->where(function ($q) use ($searchTerm) {
                        $q->where('transaksi_id', 'like', "%{$searchTerm}%")
                            ->orWhere('document_number', 'like', "%{$searchTerm}%")
                            ->orWhere('issue_date', 'like', "%{$searchTerm}%");
                    });
                }
                // Order by latest first and paginate results
                $documents = $query->paginate($perPage = 8, $columns = ['*'], $pageName = 'doc_search');

                // Return JSON response
                return response()->json([
                    'status' => 200,
                    'message' => 'Search completed successfully',
                    'data' => $documents->items(),
                    'pagination' => [
                        'current_page' => $documents->currentPage(),
                        'per_page' => $documents->perPage(),
                        'total' => $documents->total(),
                        'last_page' => $documents->lastPage(),
                        'next_page_url' => $documents->nextPageUrl(), // Add this
                        'prev_page_url' => $documents->previousPageUrl(),
                    ],
                    'search_term' => $searchTerm,
                ]);
            } catch (\Exception $e) {
                // Handle exceptions
                return response()->json([
                    'status' => 200,
                    'message' => 'Failed to perform search',
                    'error' => $e->getMessage(),
                ], 500);
            }
        } else {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }
    }
}
