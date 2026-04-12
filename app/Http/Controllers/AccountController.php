<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AccountController extends Controller
{
    /**
     * Display a listing of the accounts.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user->current_workspace_id) {
            return response()->json([]);
        }

        $accounts = Account::where('workspace_id', $user->current_workspace_id)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($accounts);
    }

    /**
     * Store a newly created account in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        if (!$user->current_workspace_id) {
            return redirect()->back()->withErrors(['workspace' => 'Lütfen geçerli bir çalışma alanı seçin.']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => ['required', Rule::in(['CASH', 'BANK', 'CREDIT_CARD', 'E_WALLET'])],
            'currency' => ['required', 'string', 'size:3'],
            'balance' => 'required|numeric',
            'color' => 'nullable|string|max:7',
        ]);

        $validated['workspace_id'] = $user->current_workspace_id;
        $validated['created_by_user_id'] = $user->id;

        Account::create($validated);

        return redirect()->back()->with('success', 'Hesap başarıyla oluşturuldu.');
    }

    /**
     * Soft-delete/deactivate the given account.
     */
    public function destroy(string $id, Request $request)
    {
        $user = $request->user();
        if (!$user->current_workspace_id) {
            abort(403);
        }

        $account = Account::where('workspace_id', $user->current_workspace_id)->findOrFail($id);
        
        $account->is_active = false;
        $account->save();

        return redirect()->back()->with('success', 'Hesap pasife alındı.');
    }
}
