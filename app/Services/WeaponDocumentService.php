<?php

namespace App\Services;

use App\Models\File;
use App\Models\Weapon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use RuntimeException;

class WeaponDocumentService
{
    public function __construct(
        private readonly RevalidationDocumentBuilder $builder,
    ) {
    }

    public function syncPermitDocument(Weapon $weapon): void
    {
        $permitFile = $weapon->permitFile()->first();
        if (!$permitFile) {
            return;
        }

        $document = $weapon->documents()->where('is_permit', true)->first();
        $payload = [
            'file_id' => $permitFile->id,
            'document_name' => 'Permiso',
            'document_number' => $weapon->permit_number,
            'permit_kind' => $weapon->permit_type,
            'valid_until' => $weapon->permit_expires_at,
            'observations' => null,
            'status' => null,
            'is_permit' => true,
            'is_renewal' => false,
        ];

        if ($document) {
            $document->update($payload);
        } else {
            $weapon->documents()->create($payload);
        }
    }

    public function syncRenewalDocument(Weapon $weapon): void
    {
        $document = $weapon->documents()->where('is_renewal', true)->first();

        $fileName = 'revalidacion_' . $weapon->internal_code . '.docx';
        $path = 'weapons/' . $weapon->id . '/documents/' . $fileName;
        $absolutePath = Storage::disk('local')->path($path);

        $this->builder->buildForWeapon($weapon, $absolutePath);

        DB::transaction(function () use ($weapon, $document, $path, $fileName) {
            $storedFile = File::create([
                'disk' => 'local',
                'path' => $path,
                'original_name' => $fileName,
                'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'size' => Storage::disk('local')->size($path),
                'checksum' => hash_file('sha256', Storage::disk('local')->path($path)),
                'uploaded_by' => null,
            ]);

            $payload = [
                'file_id' => $storedFile->id,
                'document_name' => 'Revalidación',
                'document_number' => null,
                'permit_kind' => $weapon->permit_type,
                'valid_until' => $weapon->permit_expires_at,
                'observations' => null,
                'status' => null,
                'is_permit' => false,
                'is_renewal' => true,
            ];

            if ($document) {
                $oldFile = $document->file;
                $document->update($payload);

                if ($oldFile) {
                    $samePath = $oldFile->disk === $storedFile->disk
                        && $oldFile->path === $storedFile->path;

                    if (!$samePath) {
                        Storage::disk($oldFile->disk)->delete($oldFile->path);
                    }

                    $oldFile->delete();
                }
            } else {
                $weapon->documents()->create($payload);
            }
        });
    }

    public function buildBatchDocument(iterable $weapons): array
    {
        $fileName = 'revalidacion_masiva_' . now()->format('Ymd_His_u') . '_' . Str::lower(Str::random(6)) . '.docx';
        $absolutePath = storage_path('app/tmp/' . $fileName);

        $this->builder->buildForWeapons($weapons, $absolutePath);

        return [
            'file_name' => $fileName,
            'path' => $absolutePath,
        ];
    }

    public function hasPdfPreviewSupport(): bool
    {
        return class_exists(Dompdf::class);
    }

    public function buildBatchPreviewPdf(iterable $weapons): array
    {
        if (!$this->hasPdfPreviewSupport()) {
            throw new RuntimeException('La vista previa PDF no está disponible en este momento.');
        }

        $previewDir = $this->createTempDirectory('sj-armory-preview-');
        $fileName = 'revalidacion_masiva_' . now()->format('Ymd_His_u') . '_' . Str::lower(Str::random(6));
        $pdfPath = $previewDir . DIRECTORY_SEPARATOR . $fileName . '.pdf';
        $generatedAt = now();

        $html = View::make(
            'alerts.preview-pdf',
            array_merge(
                ['generatedAt' => $generatedAt],
                $this->builder->buildPreviewData($weapons, $generatedAt)
            )
        )->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Times New Roman');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();

        file_put_contents($pdfPath, $dompdf->output());

        return [
            'file_name' => $fileName . '.pdf',
            'path' => $pdfPath,
        ];
    }

    private function createTempDirectory(string $prefix): string
    {
        $base = rtrim(sys_get_temp_dir(), '\\/');
        $path = $base . DIRECTORY_SEPARATOR . $prefix . Str::uuid();

        if (!is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
            throw new RuntimeException('No se pudo preparar la vista previa PDF.');
        }

        return $path;
    }
}
