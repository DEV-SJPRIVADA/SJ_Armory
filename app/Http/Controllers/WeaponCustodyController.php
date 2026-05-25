<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Weapon;
use App\Services\ResponsibleCustodyPostService;
use App\Services\WeaponCustodyService;
use Illuminate\Http\Request;
use RuntimeException;

class WeaponCustodyController extends Controller
{
    public function __construct(
        private readonly WeaponCustodyService $custody,
        private readonly ResponsibleCustodyPostService $custodyPosts,
    ) {}

    public function moveToArmerillo(Request $request, Weapon $weapon)
    {
        $this->authorize('update', $weapon);

        try {
            $this->custody->moveToArmerillo($weapon, $request->user(), $request->input('reason'));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['custody' => $exception->getMessage()]);
        }

        return redirect()
            ->route('weapons.show', $weapon)
            ->with('status', __('Arma ubicada en el armerillo del responsable.'));
    }

    public function moveToParaMantenimiento(Request $request, Weapon $weapon)
    {
        $this->authorize('update', $weapon);

        try {
            $this->custody->moveToParaMantenimiento($weapon, $request->user(), $request->input('reason'));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['custody' => $exception->getMessage()]);
        }

        return redirect()
            ->route('weapons.show', $weapon)
            ->with('status', __('Arma marcada en armerillo para mantenimiento.'));
    }

    public function moveToArmero(Request $request, Weapon $weapon)
    {
        $this->authorize('update', $weapon);

        $data = $request->validate([
            'post_id' => ['required', 'integer', 'exists:posts,id'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $post = Post::query()->findOrFail($data['post_id']);

        try {
            $this->custody->moveToArmero($weapon, $request->user(), $post, $data['reason'] ?? null);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['custody' => $exception->getMessage()]);
        }

        return redirect()
            ->route('weapons.show', $weapon)
            ->with('status', __('Arma enviada a mantenimiento en el armero seleccionado.'));
    }

    public function storeArmeroPost(Request $request, Weapon $weapon)
    {
        $this->authorize('update', $weapon);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $responsible = $this->custodyPosts->resolveResponsibleForWeapon($weapon);
            $client = $this->custodyPosts->resolveClientForWeapon($weapon);
            $this->custodyPosts->createArmeroPost(
                $responsible,
                $client,
                $data['name'],
                $data['address'] ?? null,
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['custody' => $exception->getMessage()]);
        }

        return redirect()
            ->route('weapons.show', $weapon)
            ->with('status', __('Armero registrado. Asigne el arma desde «Enviar a mantenimiento».'));
    }
}
