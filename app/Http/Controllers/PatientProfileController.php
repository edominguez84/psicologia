<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PatientProfileController extends Controller
{
    /**
     * Siempre opera sobre Auth::user() — sin route-model-binding de otro
     * usuario, mismo criterio que TwoFactorSettingsController, para que no
     * exista ninguna URL que permita ver/editar el perfil de otra persona.
     */
    public function edit(): View
    {
        return view('profile.edit', [
            'user' => Auth::user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:40'],
            'birth_date' => ['required', 'date', 'before:today'],
        ]);

        Auth::user()->update($data);

        return back()->with('status', 'Perfil actualizado.');
    }

    public function updatePhoto(Request $request): RedirectResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'max:2048', 'dimensions:max_width=2000,max_height=2000'],
        ], [
            'photo.image' => 'Debe ser una imagen (PNG, JPG o similar).',
            'photo.max' => 'La imagen no debe superar 2 MB.',
            'photo.dimensions' => 'La imagen no debe superar 2000×2000 píxeles.',
        ]);

        $user = Auth::user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $path = $request->file('photo')->store('avatars', 'public');
        $user->forceFill(['avatar_path' => $path])->save();

        return back()->with('status', 'Foto de perfil actualizada.');
    }

    public function destroyPhoto(): RedirectResponse
    {
        $user = Auth::user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->forceFill(['avatar_path' => null])->save();
        }

        return back()->with('status', 'Foto de perfil eliminada.');
    }
}
