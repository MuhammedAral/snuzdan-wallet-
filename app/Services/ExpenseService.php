<?php

namespace App\Services;

use App\Models\ExpenseTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class ExpenseService
{
    /**
     * Get all active expenses for the given workspace.
     */
    public function getExpensesForWorkspace(string $workspaceId, ?string $month = null)
    {
        $query = ExpenseTransaction::with(['category', 'creator'])
            ->where('workspace_id', $workspaceId)
            ->active()
            ->orderByDesc('expense_date')
            ->orderByDesc('created_at');

        if ($month) {
            // YYYY-MM format expected
            $query->whereRaw("TO_CHAR(expense_date, 'YYYY-MM') = ?", [$month]);
        }

        return $query->get();
    }

    /**
     * Store a new expense transaction.
     * Append-only ledger format: always creates a new record.
     */
    public function store(array $data): ExpenseTransaction
    {
        $user = Auth::user();

        if (!isset($data['workspace_id'])) {
            $data['workspace_id'] = $user->current_workspace_id;
        }

        $data['created_by_user_id'] = $user->id;

        DB::beginTransaction();
        try {
            $expense = ExpenseTransaction::create($data);
            DB::commit();
            
            return $expense->load('category');
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Void a transaction instead of deleting it.
     */
    public function voidTransaction(string $transactionId): ExpenseTransaction
    {
        $user = Auth::user();
        
        $transaction = ExpenseTransaction::where('id', $transactionId)
            ->where('workspace_id', $user->current_workspace_id)
            ->active()
            ->firstOrFail();

        DB::beginTransaction();
        try {
            $transaction->update(['is_void' => true]);
            DB::commit();

            return $transaction;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
