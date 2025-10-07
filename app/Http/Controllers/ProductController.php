<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $query = Product::query();

        if ($user->userType === 'Usuario Padrão') {
            $query->where('email', $user->email);
        } elseif ($user->userType === 'Usuario Plus') {
            $query->where(function ($q) use ($user) {
                $q->where('email', $user->email)
                    ->orWhere('emailCreator', $user->email);
            });
        }

        $products = $query->latest()->get();
        $users = User::select('id', 'name', 'email')->get(); // 🔹 pega os usuários

        return Inertia::render('products/Index', [
            'products' => $products,
            'users' => $users, // 🔹 passa para o Vue
        ]);
    }

    public function create()
    {
        return Inertia::render('products/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'cr' => 'required|string|max:255',
            'ocorrency' => 'required|string|max:255',
            'origin' => 'required|string|max:255',
            'action' => 'required|string|max:255',
            'startDate' => 'required|date',
            'dueDate' => 'required|date',
            'email' => 'required|email|max:255',
            'pdf' => 'nullable|file|mimes:pdf|max:2048',
        ]);

        if ($request->hasFile('pdf')) {
            $data['pdf_path'] = $request->file('pdf')->store('products_pdfs', 'public');
        }

        unset($data['pdf']);

        // salva também quem criou a ocorrência
        $data['emailCreator'] = auth()->user()->email;

        Product::create($data);

        return redirect()->route('products.index')->with('message', 'Ocorrência registrada com sucesso!');
    }

    /**
     * Abre tela de edição de dados
     */
    public function edit(Product $product)
    {
        return Inertia::render('products/Edit', [
            'product' => $product,
        ]);
    }

    /**
     * Abre tela de edição de PDF
     */
    public function editPdf(Product $product)
    {
        return Inertia::render('products/EditPDF', [
            'product' => $product,
        ]);
    }

    /**
     * Atualiza somente os dados (sem PDF)
     */
    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'cr' => 'required|string|max:255',
            'ocorrency' => 'required|string|max:255',
            'origin' => 'required|string|max:255',
            'action' => 'required|string|max:255',
            'startDate' => 'required|date',
            'dueDate' => 'required|date',
            'email' => 'required|email|max:255',
        ]);

        $product->update($data);

        return redirect()->route('products.index')->with('message', 'Ocorrência atualizada com sucesso!');
    }

    /**
     * Atualiza somente o PDF
     */
    public function updatePdf(Request $request, Product $product)
    {
        $data = $request->validate([
            'pdf' => 'required|file|mimes:pdf|max:8048',
        ]);

        $data['pdf_path'] = $request->file('pdf')->store('products_pdfs', 'public');

        // 🔹 Reseta status e motivo ao reenviar evidência
        $data['confirmEvidency'] = null;
        $data['reason'] = null;

        // 🔹 Registra infos adicionais
        $data['lastPdfUpload'] = now(); // data e hora do upload
        $data['evidencyUpploader'] = auth()->user()->name; // nome do usuário logado

        $product->update($data);

        return redirect()
            ->route('products.index')
            ->with('message', 'PDF atualizado com sucesso! Status resetado para pendente.');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('products.index')->with('message', 'Ocorrência excluída!');
    }

    public function approveEvidency(Product $product)
    {
        $product->update([
            'confirmEvidency' => 'Aprovado',
            'reason' => null,
            'evidencyApprover' => auth()->user()->name, // nome de quem aprovou
        ]);

        return redirect()->route('products.index')->with('message', '✅ Evidência aprovada com sucesso!');
    }

    public function rejectEvidency(Request $request, Product $product)
    {
        $data = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $product->update([
            'confirmEvidency' => 'Reprovado',
            'reason' => $data['reason'],
            'evidencyApprover' => auth()->user()->name, // 👈 salva quem recusou
        ]);

        return redirect()->route('products.index')->with('message', '❌ Evidência reprovada com motivo registrado!');
    }

    public function dashboard()
    {
        $products = Product::all();
        $now = now();

        // ===== 1) Status das ocorrências =====
        $statusCounts = [
            'Atrasado' => 0,
            'Pendente' => 0,
            'Pendente Aprovação' => 0,
            'Finalizado' => 0,
            'Evidência Recusada' => 0,
        ];

        foreach ($products as $product) {
            if (! $product->pdf_path && $product->dueDate < $now) {
                $statusCounts['Atrasado']++;
            } elseif (! $product->pdf_path && $product->dueDate >= $now) {
                $statusCounts['Pendente']++;
            } elseif ($product->pdf_path && is_null($product->confirmEvidency)) {
                $statusCounts['Pendente Aprovação']++;
            } elseif ($product->pdf_path && $product->confirmEvidency === 'Aprovado') {
                $statusCounts['Finalizado']++;
            } elseif ($product->pdf_path && $product->confirmEvidency === 'Reprovado') {
                $statusCounts['Evidência Recusada']++;
            }
        }

        // ===== 2) Quantidade por CR =====
        $crCounts = Product::selectRaw('cr, COUNT(*) as total')
            ->groupBy('cr')
            ->pluck('total', 'cr');
        // Pluck -> vira um array tipo ["16749 - SP - LOG ..." => 5, ...]

        // ===== 3) Quantidade por Origem =====
        $originCounts = Product::selectRaw('origin, COUNT(*) as total')
            ->groupBy('origin')
            ->pluck('total', 'origin');

        // ===== Render =====
        return Inertia::render('Dashboard', [
            'statusCounts' => $statusCounts,
            'crCounts' => $crCounts,
            'originCounts' => $originCounts,
        ]);
    }
}
