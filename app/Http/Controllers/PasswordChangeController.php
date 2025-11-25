<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;

class PasswordChangeController extends Controller
{
    /**
     * Mostra o formulário para o usuário alterar a senha.
     */
    public function showChangeForm()
    {
        return Inertia::render('Auth/ForcePasswordChange');
    }

    /**
     * Atualiza a senha do usuário e o redireciona.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = Auth::user();

        $user->password = Hash::make($request->password);
        $user->password_change_required = false;
        $user->save();

        return redirect()->route('dashboard')->with('success', 'Sua senha foi alterada com sucesso.');
    }
}
