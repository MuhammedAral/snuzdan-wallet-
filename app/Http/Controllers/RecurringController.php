<?php

namespace App\Http\Controllers;

use App\Models\RecurringTransaction;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class RecurringController extends Controller
{
    /**
     * Store a newly created recurring transaction in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user->current_workspace_id) {
            return redirect()->back()->withErrors(['workspace' => 'Lütfen geçerli bir çalışma alanı seçin.']);
        }

        $validated = $request->validate([
            'account_id' => 'required|uuid|exists:accounts,id',
            'category_id' => 'required|uuid|exists:categories,id',
            'direction' => ['required', Rule::in(['INCOME', 'EXPENSE'])],
            'amount' => 'required|numeric|min:0.01',
            'currency' => ['required', 'string', 'size:3'],
            'period' => ['required', Rule::in(['DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY'])],
            'note' => 'nullable|string|max:500',
            'next_run_date' => 'required|date|after_or_equal:today',
        ]);

        $validated['workspace_id'] = $user->current_workspace_id;
        $validated['created_by_user_id'] = $user->id;

        RecurringTransaction::create($validated);

        return redirect()->back()->with('success', 'Düzenli işlem oluşturuldu.');
    }

    /**
     * Cancel/Deactivate a recurring transaction.
     */
    public function destroy(string $id, Request $request)
    {
        $user = $request->user();
        if (!$user->current_workspace_id) {
            abort(403);
        }

        $recurring = RecurringTransaction::where('workspace_id', $user->current_workspace_id)->findOrFail($id);
        
        $recurring->is_active = false;
        $recurring->save();

        return redirect()->back()->with('success', 'Düzenli işlem iptal edildi.');
    }
}
