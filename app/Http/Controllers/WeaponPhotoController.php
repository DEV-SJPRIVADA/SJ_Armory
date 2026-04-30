<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\File;
use App\Models\Weapon;
use App\Models\WeaponPhoto;
use App\Services\WeaponDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Throwable;

class WeaponPhotoController extends Controller
{
    public function store(Request $request, Weapon $weapon, WeaponDocumentService $documentService)
    {
        $this->authorize('updatePhotos', $weapon);

        $descriptions = array_keys(WeaponPhoto::DESCRIPTIONS);
        $data = $request->validate([
            'photo' => ['required', 'image', 'max:5120'],
            'description' => ['required', Rule::in($descriptions)],
        ]);

        $file = $data['photo'];
        $path = $file->store('weapons/' . $weapon->id . '/photos', 'public');

        try {
            DB::transaction(function () use ($data, $file, $path, $request, $weapon) {
                $existingPhoto = $weapon->photos()->with('file')
                    ->where('description', $data['description'])
                    ->first();
                if ($existingPhoto) {
                    if ($existingPhoto->file) {
                        Storage::disk($existingPhoto->file->disk)->delete($existingPhoto->file->path);
                        $existingPhoto->file->delete();
                    }
                    $existingPhoto->delete();
                }

                $storedFile = File::create([
                    'disk' => 'public',
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                    'checksum' => hash_file('sha256', $file->getRealPath()),
                    'uploaded_by' => $request->user()?->id,
                ]);

                $photo = $weapon->photos()->create([
                    'file_id' => $storedFile->id,
                    'description' => $data['description'],
                ]);

                AuditLog::create([
                    'user_id' => $request->user()?->id,
                    'action' => 'upload_photo',
                    'auditable_type' => Weapon::class,
                    'auditable_id' => $weapon->id,
                    'before' => null,
                    'after' => [
                        'photo_id' => $photo->id,
                        'file_id' => $storedFile->id,
                        'description' => $photo->description,
                    ],
                ]);
            });
        } catch (Throwable $e) {
            Storage::disk('public')->delete($path);
            throw $e;
        }

        $documentService->syncRenewalDocument($weapon);

        return redirect()->route('weapons.show', $weapon)->with('status', 'Foto cargada.');
    }

    public function destroy(Weapon $weapon, WeaponPhoto $photo, WeaponDocumentService $documentService)
    {
        $this->authorize('updatePhotos', $weapon);

        if ($photo->weapon_id !== $weapon->id) {
            abort(404);
        }

        $file = $photo->file;
        $photo->delete();

        if ($file) {
            Storage::disk($file->disk)->delete($file->path);
            $file->delete();
        }

        $documentService->syncRenewalDocument($weapon);

        return redirect()->route('weapons.show', $weapon)->with('status', 'Foto eliminada.');
    }

    public function update(Request $request, Weapon $weapon, WeaponPhoto $photo, WeaponDocumentService $documentService)
    {
        $this->authorize('updatePhotos', $weapon);

        if ($photo->weapon_id !== $weapon->id) {
            abort(404);
        }

        $data = $request->validate([
            'photo' => ['required', 'image', 'max:5120'],
        ]);

        $file = $data['photo'];
        $path = $file->store('weapons/' . $weapon->id . '/photos', 'public');

        try {
            DB::transaction(function () use ($file, $path, $request, $photo) {
                $oldFile = $photo->file;
                if ($oldFile) {
                    Storage::disk($oldFile->disk)->delete($oldFile->path);
                    $oldFile->delete();
                }

                $storedFile = File::create([
                    'disk' => 'public',
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                    'checksum' => hash_file('sha256', $file->getRealPath()),
                    'uploaded_by' => $request->user()?->id,
                ]);

                $photo->update([
                    'file_id' => $storedFile->id,
                ]);

                AuditLog::create([
                    'user_id' => $request->user()?->id,
                    'action' => 'update_photo',
                    'auditable_type' => Weapon::class,
                    'auditable_id' => $photo->weapon_id,
                    'before' => null,
                    'after' => [
                        'photo_id' => $photo->id,
                        'file_id' => $storedFile->id,
                        'description' => $photo->description,
                    ],
                ]);
            });
        } catch (Throwable $e) {
            Storage::disk('public')->delete($path);
            throw $e;
        }

        $documentService->syncRenewalDocument($weapon);

        return response()->json(['ok' => true]);
    }
}

