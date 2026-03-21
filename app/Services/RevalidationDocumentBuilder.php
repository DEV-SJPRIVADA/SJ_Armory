<?php

namespace App\Services;

use App\Models\File;
use App\Models\Weapon;
use App\Models\WeaponPhoto;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File as FileFacade;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;
use PhpOffice\PhpWord\Style\Language;
use ZipArchive;

class RevalidationDocumentBuilder
{
    private const TEMPLATE_BASE = 'resources/templates/PORTADA.docx';
    private const PAGE_WIDTH_CM = 21.59;
    private const BRANDING_HEIGHT_CM = 2.98;
    private const WEAPON_PHOTO_WIDTH_CM = 7.7;
    private const WEAPON_PHOTO_HEIGHT_CM = 5.5;
    private const WEAPON_PHOTO_GAP_CM = 0.15;
    private const IMPRINT_WIDTH_CM = 5.2;
    private const IMPRINT_HEIGHT_CM = 1.2;
    private const PERMIT_WIDTH_CM = 7.0;
    private const PERMIT_HEIGHT_CM = 5.0;
    private const BATCH_PERMIT_WIDTH_CM = 5.8;
    private const BATCH_PERMIT_HEIGHT_CM = 4.0;
    private const SECTION_STYLE = [
        'pageSizeW' => 12240,
        'pageSizeH' => 15840,
        'marginTop' => 1701,
        'marginRight' => 1701,
        'marginBottom' => 1418,
        'marginLeft' => 1701,
    ];
    private const SIGNER_NAME = 'WILFREDO VELEZ CEDEÑO';
    private const SIGNER_ID = 'C.C. No 94.506.540 DE CALI';
    private const SIGNER_ROLE = 'REPRESENTANTE LEGAL';
    private const COMPANY_NAME = 'SJ SEGURIDAD PRIVADA LTDA';
    private const COMPANY_NIT = 'NIT: 900.576.718-6';

    public function buildForWeapon(Weapon $weapon, string $outputPath): void
    {
        $this->buildForWeapons(collect([$weapon]), $outputPath);
    }

    public function buildForWeapons(iterable $weapons, string $outputPath): void
    {
        $weapons = $this->normalizeWeapons($weapons);

        if ($weapons->isEmpty()) {
            throw new \RuntimeException('No hay armas para generar el documento de revalidación.');
        }

        FileFacade::ensureDirectoryExists(dirname($outputPath));

        Settings::setOutputEscapingEnabled(true);

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(11);
        $language = (new Language(Language::ES_ES))
            ->setLangId(Language::ES_ES_ID)
            ->setEastAsia('es-ES')
            ->setBidirectional('es-ES');
        $phpWord->getSettings()->setThemeFontLang($language);

        $this->addCoverSection($phpWord, $weapons);

        foreach ($weapons as $weapon) {
            $this->addWeaponSection($phpWord, $weapon);
        }

        $this->addPermitSection($phpWord, $weapons);

        IOFactory::createWriter($phpWord, 'Word2007')->save($outputPath);
    }

    public function buildPreviewData(iterable $weapons, ?CarbonInterface $generatedAt = null): array
    {
        $generatedAt ??= now();
        $weapons = $this->normalizeWeapons($weapons);

        if ($weapons->isEmpty()) {
            throw new \RuntimeException('No hay armas para generar el documento de revalidación.');
        }

        return [
            'branding' => [
                'header' => $this->pathToDataUri($this->extractTemplateMedia('word/media/image1.jpg')),
                'footer' => $this->pathToDataUri($this->extractTemplateMedia('word/media/image2.jpg')),
            ],
            'date_line' => $this->coverDateLine($generatedAt),
            'recipient_lines' => $this->coverRecipientLines(),
            'reference' => $this->coverReference($weapons),
            'greeting' => 'Cordial saludo,',
            'body' => $this->coverBody($weapons),
            'summary_headers' => collect($this->coverHeaders())->map(fn (array $header) => $header[0])->all(),
            'summary_rows' => $weapons->map(fn (Weapon $weapon) => $this->coverRowValues($weapon))->all(),
            'closing_lines' => [
                'Agradezco de antemano la atención a la presente solicitud,',
                'Cordialmente,',
            ],
            'signature_lines' => [
                self::SIGNER_NAME,
                self::SIGNER_ID,
                self::SIGNER_ROLE,
                self::COMPANY_NAME,
                self::COMPANY_NIT,
            ],
            'annex' => $weapons->count() > 1
                ? 'ANEXO: Registro fotográfico, improntas y copia de los salvoconductos.'
                : null,
            'weapon_pages' => $weapons->map(fn (Weapon $weapon) => [
                'title' => $this->upper(trim($weapon->weapon_type . ' ' . $weapon->brand . ' ' . $weapon->serial_number)),
                'photos' => collect($this->weaponPhotoDescriptions())
                    ->map(fn (string $description) => [
                        'label' => WeaponPhoto::DESCRIPTIONS[$description] ?? $description,
                        'src' => $this->pathToDataUri($this->getPhotoPath($weapon, $description)),
                    ])->all(),
                'imprint' => $this->pathToDataUri($this->getPhotoPath($weapon, 'impronta')),
            ])->all(),
            'permit_mode' => $weapons->count() === 1 ? 'single' : 'batch',
            'single_permit' => $weapons->count() === 1
                ? $this->pathToDataUri($this->getPermitPath($weapons->first()))
                : null,
            'permit_pages' => $weapons->count() === 1
                ? []
                : $weapons->values()->chunk(8)->map(function (Collection $pageWeapons) {
                    return $pageWeapons->chunk(2)->map(function (Collection $pair) {
                        return $pair->values()->map(fn (Weapon $weapon) => [
                            'src' => $this->pathToDataUri($this->getPermitPath($weapon)),
                        ])->all();
                    })->all();
                })->all(),
        ];
    }

    private function normalizeWeapons(iterable $weapons): Collection
    {
        $items = $weapons instanceof Collection ? $weapons : collect($weapons);

        return $items
            ->filter(fn ($weapon) => $weapon instanceof Weapon)
            ->map(function (Weapon $weapon) {
                return $weapon->loadMissing([
                    'photos.file',
                    'permitFile',
                    'documents.file',
                ]);
            })
            ->values();
    }

    private function addCoverSection(PhpWord $phpWord, Collection $weapons): void
    {
        $section = $phpWord->addSection(self::SECTION_STYLE);
        $this->applyBranding($section);

        $section->addText($this->coverDateLine(now()), [], ['spaceAfter' => 220]);

        foreach ($this->coverRecipientLines() as $line) {
            $section->addText($line, [], ['spaceAfter' => 0]);
        }

        $section->addTextBreak();
        $section->addText($this->coverReference($weapons), [], ['spaceAfter' => 220]);
        $section->addText('Cordial saludo,', [], ['spaceAfter' => 220]);
        $section->addText($this->coverBody($weapons), [], ['alignment' => Jc::BOTH, 'spaceAfter' => 220]);

        $table = $section->addTable([
            'width' => 100 * 50,
            'unit' => 'pct',
            'alignment' => JcTable::CENTER,
            'borderSize' => 4,
            'borderColor' => '000000',
            'cellMarginLeft' => 60,
            'cellMarginRight' => 60,
        ]);

        $table->addRow($this->cmToTwip(1.1));

        foreach ($this->coverHeaders() as [$label, $width]) {
            $table->addCell($width, [
                'bgColor' => 'BFBFBF',
                'valign' => 'center',
            ])->addText($label, ['size' => 9], ['alignment' => Jc::CENTER]);
        }

        foreach ($weapons as $weapon) {
            $table->addRow($this->cmToTwip(0.6));

            foreach ($this->coverRowValues($weapon) as $index => $value) {
                $width = $this->coverHeaders()[$index][1];
                $table->addCell($width, ['valign' => 'center'])
                    ->addText($value, ['size' => 9], ['alignment' => Jc::CENTER]);
            }
        }

        $section->addTextBreak(2);
        $section->addText('Agradezco de antemano la atención a la presente solicitud,', [], ['spaceAfter' => 220]);
        $section->addText('Cordialmente,', [], ['spaceAfter' => 700]);
        $section->addText(self::SIGNER_NAME, [], ['spaceAfter' => 0]);
        $section->addText(self::SIGNER_ID, [], ['spaceAfter' => 0]);
        $section->addText(self::SIGNER_ROLE, [], ['spaceAfter' => 0]);
        $section->addText(self::COMPANY_NAME, [], ['spaceAfter' => 0]);
        $section->addText(self::COMPANY_NIT, [], ['spaceAfter' => 0]);

        if ($weapons->count() > 1) {
            $section->addText(
                'ANEXO: Registro fotográfico, improntas y copia de los salvoconductos.',
                ['size' => 10],
                ['spaceBefore' => 160, 'spaceAfter' => 0]
            );
        }
    }

    private function addWeaponSection(PhpWord $phpWord, Weapon $weapon): void
    {
        $section = $phpWord->addSection(self::SECTION_STYLE);
        $this->applyBranding($section);

        $photoPaths = collect($this->weaponPhotoDescriptions())
            ->map(fn (string $description) => $this->getPhotoPath($weapon, $description))
            ->values();

        $section->addText(
            $this->upper(trim($weapon->weapon_type . ' ' . $weapon->brand . ' ' . $weapon->serial_number)),
            ['bold' => true, 'lang' => 'es-ES'],
            ['spaceAfter' => 220]
        );

        $table = $section->addTable([
            'alignment' => JcTable::CENTER,
            'layout' => 'fixed',
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'borderInsideHSize' => 0,
            'borderInsideVSize' => 0,
            'cellMargin' => 0,
            'cellSpacing' => $this->cmToTwip(self::WEAPON_PHOTO_GAP_CM),
        ]);

        foreach ([0, 2] as $rowStart) {
            $table->addRow($this->cmToTwip(self::WEAPON_PHOTO_HEIGHT_CM));

            for ($index = $rowStart; $index < $rowStart + 2; $index++) {
                $cell = $table->addCell($this->cmToTwip(self::WEAPON_PHOTO_WIDTH_CM), [
                    'valign' => 'center',
                    'borderSize' => 0,
                    'borderColor' => 'FFFFFF',
                ]);

                $cell->addImage($photoPaths[$index] ?: $this->blankImage(), [
                    'width' => $this->cmToPt(self::WEAPON_PHOTO_WIDTH_CM),
                    'height' => $this->cmToPt(self::WEAPON_PHOTO_HEIGHT_CM),
                    'alignment' => Jc::CENTER,
                ]);
            }
        }

        $section->addTextBreak();
        $section->addImage($this->getPhotoPath($weapon, 'impronta') ?: $this->blankImage(), [
            'width' => $this->cmToPt(self::IMPRINT_WIDTH_CM),
            'height' => $this->cmToPt(self::IMPRINT_HEIGHT_CM),
            'alignment' => Jc::CENTER,
        ]);
        $section->addText('IMPRONTA', ['lang' => 'es-ES'], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
    }

    private function addPermitSection(PhpWord $phpWord, Collection $weapons): void
    {
        if ($weapons->count() === 1) {
            $section = $phpWord->addSection(self::SECTION_STYLE);
            $this->applyBranding($section);
            $permitPath = $this->getPermitPath($weapons->first());

            if ($permitPath) {
                $section->addTextBreak(2);
                $section->addImage($permitPath, [
                    'width' => $this->cmToPt(self::PERMIT_WIDTH_CM),
                    'height' => $this->cmToPt(self::PERMIT_HEIGHT_CM),
                    'alignment' => Jc::CENTER,
                ]);
            } else {
                $section->addText('Sin salvoconducto', [], ['alignment' => Jc::CENTER]);
            }

            return;
        }

        foreach ($weapons->values()->chunk(8) as $pageWeapons) {
            $section = $phpWord->addSection(self::SECTION_STYLE);
            $this->applyBranding($section);
            $section->addTextBreak(2);

            $table = $section->addTable([
                'alignment' => JcTable::CENTER,
                'layout' => 'fixed',
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'cellMargin' => 0,
                'cellSpacing' => $this->cmToTwip(0.25),
            ]);

            foreach ($pageWeapons->chunk(2) as $pair) {
                $pair = $pair->values();
                $table->addRow($this->cmToTwip(self::BATCH_PERMIT_HEIGHT_CM));

                foreach ([0, 1] as $index) {
                    $weapon = $pair->get($index);
                    $cell = $table->addCell($this->cmToTwip(6.4), [
                        'valign' => 'center',
                        'borderSize' => 0,
                        'borderColor' => 'FFFFFF',
                    ]);

                    if (!$weapon) {
                        $cell->addText('');
                        continue;
                    }

                    $permitPath = $this->getPermitPath($weapon);
                    if ($permitPath) {
                        $cell->addImage($permitPath, [
                            'width' => $this->cmToPt(self::BATCH_PERMIT_WIDTH_CM),
                            'height' => $this->cmToPt(self::BATCH_PERMIT_HEIGHT_CM),
                            'alignment' => Jc::CENTER,
                        ]);
                    } else {
                        $cell->addText('Sin salvoconducto', [], ['alignment' => Jc::CENTER]);
                    }
                }
            }
        }
    }

    private function coverDateLine(CarbonInterface $generatedAt): string
    {
        return 'Santiago de Cali, ' . $generatedAt->locale('es')->translatedFormat('j \d\e F \d\e Y');
    }

    private function coverRecipientLines(): array
    {
        return [
            'Señor Coronel:',
            'Director Departamento Control y Comercio de Armas y Explosivos',
            'Ministerio de Defensa Nacional',
            'Colombia.',
        ];
    }

    private function coverReference(Collection $weapons): string
    {
        return 'Ref.: ' . ($weapons->count() > 1 ? 'Solicitud Revalidación Armas' : 'Autorización Trámites');
    }

    private function coverBody(Collection $weapons): string
    {
        return 'Yo, ' . self::SIGNER_NAME . ', identificado con cédula de ciudadanía No. 94.506.540 de Cali, '
            . 'en mi calidad de representante legal de la compañía, ' . self::COMPANY_NAME . '. '
            . 'Con Nit. 900.576.718-6, comedidamente me permito solicitar al señor Director del DCCA, '
            . 'autorice al Señor ' . self::SIGNER_NAME . ', identificado con cédula de ciudadanía No. 94.506.540 de Cali, '
            . 'para que en mi nombre y en representación de la compañía realice los trámites de revalidación de '
            . ($weapons->count() === 1 ? 'la siguiente arma' : 'las siguientes armas')
            . ', así:';
    }

    private function weaponPhotoDescriptions(): array
    {
        return [
            'lado_derecho',
            'lado_izquierdo',
            'canon_disparador_marca',
            'serie',
        ];
    }

    private function applyBranding($section): void
    {
        $headerImage = $this->extractTemplateMedia('word/media/image1.jpg');
        $footerImage = $this->extractTemplateMedia('word/media/image2.jpg');

        if ($headerImage) {
            $header = $section->addHeader();
            $header->addWatermark($headerImage, $this->brandingStyle('top'));
        }

        if ($footerImage) {
            $footer = $section->addFooter();
            $footer->addImage($footerImage, $this->brandingStyle('bottom'), true);
        }
    }

    private function brandingStyle(string $verticalPosition): array
    {
        return [
            'width' => $this->cmToPt(self::PAGE_WIDTH_CM),
            'height' => $this->cmToPt(self::BRANDING_HEIGHT_CM),
            'marginLeft' => 0,
            'marginTop' => 0,
            'positioning' => 'absolute',
            'posHorizontal' => 'left',
            'posHorizontalRel' => 'page',
            'posVertical' => $verticalPosition,
            'posVerticalRel' => 'page',
            'wrappingStyle' => 'behind',
        ];
    }

    private function coverHeaders(): array
    {
        return [
            ['TIPO DE ARMA', 1638],
            ['MARCA ARMA', 1694],
            ['No. SERIE', 1156],
            ['CALIBRE', 1107],
            ['CAP', 589],
            ['TIPO PERMISO', 1046],
            ['No. PERMISO', 1134],
            ['VENCIMIENTO', 1529],
        ];
    }

    private function coverRowValues(Weapon $weapon): array
    {
        return [
            $this->upper($weapon->weapon_type ?? '-'),
            $this->upper($weapon->brand ?? '-'),
            $weapon->serial_number ?? '-',
            $this->upper($weapon->caliber ?? '-'),
            $weapon->capacity ?? '-',
            $weapon->permit_type ? $this->upper($weapon->permit_type) : '-',
            $weapon->permit_number ?? '-',
            $weapon->permit_expires_at?->format('j/n/Y') ?? '-',
        ];
    }

    private function getPhotoPath(Weapon $weapon, string $description): ?string
    {
        $photo = $weapon->photos->firstWhere('description', $description);
        $file = $photo?->file;

        return $this->getWordReadyPath($file, 'photo_' . $weapon->id . '_' . $description);
    }

    private function getPermitPath(Weapon $weapon): ?string
    {
        return $this->getWordReadyPath($weapon->permitFile, 'permit_' . $weapon->id);
    }

    private function getWordReadyPath(?File $file, string $cacheKey): ?string
    {
        if (!$file || !Storage::disk($file->disk)->exists($file->path)) {
            return null;
        }

        $path = Storage::disk($file->disk)->path($file->path);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp'], true)) {
            return $path;
        }

        if ($extension === 'webp') {
            return $this->convertWebpToPng($path, $cacheKey) ?: $path;
        }

        return $path;
    }

    private function convertWebpToPng(string $sourcePath, string $cacheKey): ?string
    {
        if (!function_exists('imagecreatefromwebp')) {
            return null;
        }

        $targetPath = storage_path('app/tmp/' . $cacheKey . '.png');
        if (file_exists($targetPath)) {
            return $targetPath;
        }

        FileFacade::ensureDirectoryExists(dirname($targetPath));

        $image = @imagecreatefromwebp($sourcePath);
        if (!$image) {
            return null;
        }

        imagepng($image, $targetPath);
        imagedestroy($image);

        return $targetPath;
    }

    private function extractTemplateMedia(string $entryName): ?string
    {
        $targetPath = storage_path('app/tmp/template-assets/' . basename($entryName));
        if (file_exists($targetPath)) {
            return $targetPath;
        }

        FileFacade::ensureDirectoryExists(dirname($targetPath));

        $zip = new ZipArchive();
        $templatePath = base_path(self::TEMPLATE_BASE);
        if ($zip->open($templatePath) !== true) {
            return null;
        }

        $stream = $zip->getStream($entryName);
        if (!$stream) {
            $zip->close();
            return null;
        }

        file_put_contents($targetPath, stream_get_contents($stream));
        fclose($stream);
        $zip->close();

        return $targetPath;
    }

    private function pathToDataUri(?string $path): ?string
    {
        if (!$path || !is_file($path)) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'application/octet-stream';
        $content = file_get_contents($path);

        if ($content === false) {
            return null;
        }

        return 'data:' . $mime . ';base64,' . base64_encode($content);
    }

    private function blankImage(): string
    {
        $path = storage_path('app/tmp/blank.png');
        if (!file_exists($path)) {
            FileFacade::ensureDirectoryExists(dirname($path));
            file_put_contents($path, base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/xcAAn8B9x2XWAAAAABJRU5ErkJggg=='
            ));
        }

        return $path;
    }

    private function cmToTwip(float $cm): int
    {
        return (int) round(Converter::cmToTwip($cm));
    }

    private function cmToPt(float $cm): float
    {
        return ($cm / 2.54) * 72;
    }

    private function upper(string $value): string
    {
        return function_exists('mb_strtoupper')
            ? mb_strtoupper($value, 'UTF-8')
            : strtoupper($value);
    }
}
