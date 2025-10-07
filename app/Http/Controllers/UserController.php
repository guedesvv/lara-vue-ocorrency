<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Lista todos os usuários
     */
    public function index()
    {
        $users = User::all();
        return Inertia::render('Users/Index', compact('users'));
    }

    /**
     * Atualiza os dados do usuário
     */
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'        => 'nullable|string|max:255',
            'email'       => 'nullable|email|max:255|unique:users,email,' . $user->id,
            'userType'    => 'nullable|string|max:30',
            'ApproveUser' => 'nullable|string|max:30',
        ]);

        $user->update($data);

        return redirect()->back()->with('message', 'Usuário atualizado com sucesso!');
    }

    /**
     * Remove um usuário do sistema
     */
    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->back()->with('message', 'Usuário excluído com sucesso!');
    }
}