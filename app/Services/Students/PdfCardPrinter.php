<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Contracts\Students\StudentIdCardPrinter;
use App\Models\Students\StudentIdCardRequest;
use App\Models\Students\StudentIdCardSetting;
use App\Support\Students\StudentIdCardFace;
use App\Support\Students\StudentIdCardPrintResult;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Picqer\Barcode\BarcodeGeneratorPNG;
use RuntimeException;

class PdfCardPrinter implements StudentIdCardPrinter
{
    /** CR80 width in points (85.6mm). */
    public const CARD_WIDTH_PT = 242.65;

    /** CR80 height in points (53.98mm). */
    public const CARD_HEIGHT_PT = 153.07;

    public function print(StudentIdCardRequest $request): StudentIdCardPrintResult
    {
        $payload = $this->cardPayload($request);

        $pdf = Pdf::loadView('students.id-card', $payload);
        $this->configurePdf($pdf);

        $fileName = sprintf('student-id-%s.pdf', $payload['studentNumber'] !== '' ? $payload['studentNumber'] : $request->id);

        return new StudentIdCardPrintResult(
            serialNumber: $payload['serialNumber'],
            driver: 'pdf',
            pdfBinary: $pdf->output(),
            fileName: $fileName,
        );
    }

    public function printMany(iterable $requests): StudentIdCardPrintResult
    {
        $cards = [];
        foreach ($requests as $request) {
            $cards[] = $this->cardPayload($request);
        }

        if ($cards === []) {
            throw new RuntimeException('At least one ID card is required to print.');
        }

        $pdf = Pdf::loadView('students.id-card-sheet', [
            'cards' => $cards,
            'cardWidthPt' => self::CARD_WIDTH_PT,
            'cardHeightPt' => self::CARD_HEIGHT_PT,
        ]);
        $this->configurePdf($pdf);

        return new StudentIdCardPrintResult(
            serialNumber: 'bulk',
            driver: 'pdf',
            pdfBinary: $pdf->output(),
            fileName: 'student-id-cards.pdf',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function cardPayload(StudentIdCardRequest $request): array
    {
        $request->loadMissing([
            ...StudentIdCardFace::requestRelations(),
            'photo',
        ]);

        $serial = (string) $request->serial_number;
        if ($serial === '') {
            throw new RuntimeException('ID card serial number is required before printing.');
        }

        $student = $request->student;
        $settings = StudentIdCardSetting::resolveForTenant(
            $student?->tenant_id !== null ? (int) $student->tenant_id : null,
        );
        $face = StudentIdCardFace::fromStudent($student, settings: $settings);

        $generator = new BarcodeGeneratorPNG;

        return [
            ...$face->toArray(),
            'serialNumber' => $serial,
            'barcode' => base64_encode($generator->getBarcode($serial, $generator::TYPE_CODE_128, 1, 12)),
            'qrSrc' => $this->qrSrc((string) ($student?->student_number ?? ''), $serial),
            'photoSrc' => $this->photoSrc($request),
            'logoSrc' => $this->logoSrc($settings->logoPath()),
            'signatureSrc' => $this->imageDataUri($settings->signaturePath()),
            'cardWidthPt' => self::CARD_WIDTH_PT,
            'cardHeightPt' => self::CARD_HEIGHT_PT,
        ];
    }

    private function configurePdf(object $pdf): void
    {
        $pdf->setOption('dpi', 72);
        $pdf->setOption('isRemoteEnabled', true);
        $pdf->setPaper([0, 0, self::CARD_WIDTH_PT, self::CARD_HEIGHT_PT], 'portrait');
    }

    private function qrSrc(string $studentNumber, string $serial): ?string
    {
        $studentNumber = trim($studentNumber);
        $payload = $studentNumber !== '' && $serial !== ''
            ? $studentNumber.'|'.$serial
            : ($studentNumber !== '' ? $studentNumber : $serial);

        if ($payload === '') {
            return null;
        }

        $qrCode = new QrCode(data: $payload, size: 96, margin: 0);
        $png = (new PngWriter)->write($qrCode)->getString();

        return 'data:image/png;base64,'.base64_encode($png);
    }

    private function photoSrc(StudentIdCardRequest $request): ?string
    {
        $path = $this->photoPath($request);
        if ($path === null) {
            return null;
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            return null;
        }

        $source = @imagecreatefromstring($contents);
        if ($source === false) {
            return 'data:image/jpeg;base64,'.base64_encode($contents);
        }

        $width = 114;
        $height = 147;
        $canvas = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($canvas, 245, 247, 252);
        imagefilledrectangle($canvas, 0, 0, $width, $height, $white);
        imagecopyresampled(
            $canvas,
            $source,
            0,
            0,
            0,
            0,
            $width,
            $height,
            imagesx($source),
            imagesy($source),
        );

        ob_start();
        imagejpeg($canvas, null, 85);
        $binary = (string) ob_get_clean();
        imagedestroy($source);
        imagedestroy($canvas);

        return 'data:image/jpeg;base64,'.base64_encode($binary);
    }

    private function logoSrc(?string $path = null): ?string
    {
        $path ??= public_path(StudentIdCardSetting::FALLBACK_LOGO_PATH);
        if ($path === null || ! is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            return null;
        }

        $source = imagecreatefromstring($contents);
        if ($source === false) {
            return null;
        }

        $size = 78;
        $canvas = imagecreatetruecolor($size, $size);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefilledrectangle($canvas, 0, 0, $size, $size, $transparent);

        imagealphablending($canvas, true);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        if ($white !== false) {
            imagefilledellipse($canvas, (int) ($size / 2), (int) ($size / 2), $size - 1, $size - 1, $white);
        }

        $box = (int) round($size * 0.72);
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $scale = min($box / $sourceWidth, $box / $sourceHeight);
        $destWidth = max(1, (int) round($sourceWidth * $scale));
        $destHeight = max(1, (int) round($sourceHeight * $scale));
        $destX = (int) round(($size - $destWidth) / 2);
        $destY = (int) round(($size - $destHeight) / 2);

        imagecopyresampled(
            $canvas,
            $source,
            $destX,
            $destY,
            0,
            0,
            $destWidth,
            $destHeight,
            $sourceWidth,
            $sourceHeight,
        );

        ob_start();
        imagepng($canvas);
        $binary = (string) ob_get_clean();
        imagedestroy($source);
        imagedestroy($canvas);

        return 'data:image/png;base64,'.base64_encode($binary);
    }

    private function imageDataUri(?string $path): ?string
    {
        if ($path === null || ! is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }

    private function photoPath(StudentIdCardRequest $request): ?string
    {
        $media = $request->photo ?? $request->getFirstMedia(StudentIdCardRequest::MEDIA_COLLECTION);

        if ($media === null) {
            return null;
        }

        $path = $media->getPath('card');
        if (is_string($path) && is_file($path)) {
            return $path;
        }

        $path = $media->getPath();

        return is_string($path) && is_file($path) ? $path : null;
    }
}
