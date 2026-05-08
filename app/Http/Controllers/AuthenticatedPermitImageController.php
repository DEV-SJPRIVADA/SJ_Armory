<?php

namespace App\Http\Controllers;

use App\Models\PermitAuthenticatedTemplate;
use App\Models\Weapon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AuthenticatedPermitImageController extends Controller
{
    public function show(Request $request, string $permitKind)
    {
        abort_unless(in_array($permitKind, ['porte', 'tenencia'], true), 404);

        $this->authorize('viewAny', Weapon::class);

        $template = PermitAuthenticatedTemplate::query()
            ->where('permit_kind', $permitKind)
            ->with('file')
            ->first();

        if (! $template?->file) {
            abort(404);
        }

        $file = $template->file;

        return Storage::disk($file->disk)->response(
            $file->path,
            $file->original_name ?? 'permiso-autenticado-'.$permitKind
        );
    }
}
