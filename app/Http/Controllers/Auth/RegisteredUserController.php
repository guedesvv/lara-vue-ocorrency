<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Product; // 🔹 Importa o model Product
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // 🔹 Verifica se o e-mail informado existe na tabela products
        $emailExistsInProducts = \App\Models\Product::where('email', $request->email)->exists();

        // 🔹 Define valores padrão com base nessa verificação
        $userType    = 'Usuario Padrão';
        $approveUser = $emailExistsInProducts ? 'Sim' : 'Nao';

        // 🔹 Criação do usuário
        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'userType'  => $userType,
            'ApproveUser' => $approveUser,
        ]);

        event(new Registered($user));

        // 🔹 Redirecionamento com mensagens diferentes conforme o caso
        if ($emailExistsInProducts) {
            return redirect()->route('login')->with('message', 'Conta criada e aprovada automaticamente! Você já pode acessar.');
        } else {
            return redirect()->route('login')->with('message', 'Conta criada com sucesso! Aguarde aprovação para acessar.');
        }
    }
}