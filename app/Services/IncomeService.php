<?php

namespace App\Services;

use App\Models\IncomeTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class IncomeService
{
    /**
     * Get all active incomes for the given workspace.
     */
    public function getIncomesForWorkspace(string $workspaceId, ?string $month = null)
    {
        $query = IncomeTransaction::with(['category', 'creator'])
            ->where('workspace_id', $workspaceId)
            ->active()
            ->orderByDesc('income_date')
            ->orderByDesc('created_at');

        if ($month) {
            // YYYY-MM format expected
            $query->whereRaw("TO_CHAR(income_date, 'YYYY-MM') = ?", [$month]);
        }

        return $query->get();
    }

    /**
     * Store a new income transaction.
     * Append-only ledger format: always creates a new record.
     */
    public function store(array $data): IncomeTransaction
    {
        $user = Auth::user();

        if (!isset($data['workspace_id'])) {
            $data['workspace_id'] = $user->current_workspace_id;
        }

        $data['created_by_user_id'] = $user->id;

        DB::beginTransaction();
        try {
            $income = IncomeTransaction::create($data);
            DB::commit();
            
            return $income->load('category');
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Void a transaction instead of deleting it.
     */
    public function voidTransaction(string $transactionId): IncomeTransaction
    {
        $user = Auth::user();
        
        $transaction = IncomeTransaction::where('id', $transactionId)
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
