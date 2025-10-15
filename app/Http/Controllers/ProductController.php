<?php

namespace App\Http\Controllers;

use App\Models\PdfHistory;
use App\Models\Product;
use App\Models\User; // ✅ Correto!
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
        $users = User::select('id', 'name', 'email')->get();

        return Inertia::render('products/Index', [
            'products' => $products,
            'users' => $users,
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
            'pdf' => 'nullable|file|mimes:pdf|max:999048',
        ]);

        if ($request->hasFile('pdf')) {
            $data['pdf_path'] = $request->file('pdf')->store('products_pdfs', 'public');
        }

        unset($data['pdf']);

        // 🔹 Adiciona informações do criador
        $data['emailCreator'] = auth()->user()->email;
        $data['nameCreator'] = auth()->user()->name;

        Product::create($data);

        return redirect()->route('products.index')->with('message', 'Ocorrência registrada com sucesso!');
    }

    public function edit(Product $product)
    {
        return Inertia::render('products/Edit', [
            'product' => $product,
        ]);
    }

    public function editPdf(Product $product)
    {
        return Inertia::render('products/EditPDF', [
            'product' => $product,
        ]);
    }

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

    public function updatePdf(Request $request, Product $product)
    {
        $data = $request->validate([
            'pdf' => 'required|file|mimes:pdf|max:8048',
        ]);

        $data['pdf_path'] = $request->file('pdf')->store('products_pdfs', 'public');
        $data['confirmEvidency'] = null;
        $data['reason'] = null;
        $data['reasonDateTime'] = null;
        $data['lastPdfUpload'] = now();
        $data['evidencyUpploader'] = auth()->user()->name;

        $product->update($data);

        return redirect()
            ->route('products.editPdf', $product->id)
            ->with('message', '✅ PDF atualizado com sucesso! Status resetado para pendente.');
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
            'evidencyApprover' => auth()->user()->name,
            'reasonDateTime' => now(),
        ]);

        return redirect()
            ->route('products.editPdf', $product->id)
            ->with('message', '✅ Evidência aprovada com sucesso!');
    }

    public function rejectEvidency(Request $request, Product $product)
    {
        $data = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $product->update([
            'confirmEvidency' => 'Reprovado',
            'reason' => $data['reason'],
            'evidencyApprover' => auth()->user()->name,
            'reasonDateTime' => now(),
        ]);

        PdfHistory::create([
            'ocorrencyId' => $product->id,
            'pdf_path' => $product->pdf_path,
            'uploadDate' => $product->lastPdfUpload,
            'evidencyUploader' => $product->evidencyUpploader,
            'reason' => $product->reason,
            'reasonDateTime' => $product->reasonDateTime,
            'evidencyApprover' => $product->evidencyApprover,
        ]);

        return redirect()
            ->route('products.editPdf', $product->id)
            ->with('message', '❌ Evidência reprovada com motivo registrado e histórico salvo!');
    }

    public function dashboard()
    {
        $products = Product::all();
        $now = now();

        $statusCounts = [
            'Atrasado' => 0,
            'Pendente' => 0,
            'Pendente Aprovação' => 0,
            'Finalizado' => 0,
            'Evidência Recusada' => 0,
        ];

        $items = [];

        foreach ($products as $product) {
            $status = '';

            if (! $product->pdf_path && $product->dueDate < $now) {
                $status = 'Atrasado';
            } elseif (! $product->pdf_path && $product->dueDate >= $now) {
                $status = 'Pendente';
            } elseif ($product->pdf_path && is_null($product->confirmEvidency)) {
                $status = 'Pendente Aprovação';
            } elseif ($product->pdf_path && $product->confirmEvidency === 'Aprovado') {
                $status = 'Finalizado';
            } elseif ($product->pdf_path && $product->confirmEvidency === 'Reprovado') {
                $status = 'Evidência Recusada';
            }

            if ($status) {
                $statusCounts[$status]++;
            }

            $items[] = [
                'cr' => $product->cr,
                'origin' => $product->origin,
                'status' => $status,
            ];
        }

        $crCounts = Product::selectRaw('cr, COUNT(*) as total')
            ->groupBy('cr')
            ->pluck('total', 'cr');

        $originCounts = Product::selectRaw('origin, COUNT(*) as total')
            ->groupBy('origin')
            ->pluck('total', 'origin');

        return Inertia::render('Dashboard', [
            'statusCounts' => $statusCounts,
            'crCounts' => $crCounts,
            'originCounts' => $originCounts,
            'items' => $items,
        ]);
    }

    public function pdfHistory(Product $product)
    {
        // Busca todas as recusas da ocorrência
        $history = PdfHistory::where('ocorrencyId', $product->id)
            ->orderBy('reasonDateTime', 'desc')
            ->get();

        return response()->json($history);
    }
}
