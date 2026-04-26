<?php

namespace App\Http\Controllers;

use App\Models\ExpenseTransaction;
use App\Models\IncomeTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Render the React page.
     */
    public function page()
    {
        return Inertia::render('Dashboard');
    }

    /**
     * Get summary metrics for the dashboard.
     */
    public function summary(): JsonResponse
    {
        $workspaceId = Auth::user()->current_workspace_id;

        if (!$workspaceId) {
            return response()->json([
                'monthly_income' => 0,
                'monthly_expense' => 0,
                'monthly_balance' => 0,
            ]);
        }

        $currentMonth = date('Y-m');

        // İşlemleri çek (Kurları hesaplamak için koleksiyon alıyoruz)
        $expenses = ExpenseTransaction::where('workspace_id', $workspaceId)
            ->active()
            ->whereRaw("TO_CHAR(expense_date, 'YYYY-MM') = ?", [$currentMonth])
            ->get();

        $incomes = IncomeTransaction::where('workspace_id', $workspaceId)
            ->active()
            ->whereRaw("TO_CHAR(income_date, 'YYYY-MM') = ?", [$currentMonth])
            ->get();

        $totalExpense = 0;
        foreach ($expenses as $exp) {
            $totalExpense += $this->convertToTry($exp->amount, $exp->currency);
        }

        $totalIncome = 0;
        foreach ($incomes as $inc) {
            $totalIncome += $this->convertToTry($inc->amount, $inc->currency);
        }

        return response()->json([
            'monthly_income' => (float) $totalIncome,
            'monthly_expense' => (float) $totalExpense,
            'monthly_balance' => (float) ($totalIncome - $totalExpense)
        ]);
    }

    /**
     * Get the recent activity feed combining multiple transaction types.
     */
    public function recentActivity(): JsonResponse
    {
        $workspaceId = Auth::user()->current_workspace_id;

        if (!$workspaceId) {
            return response()->json([]);
        }

        $expenses = ExpenseTransaction::with('category')
            ->where('workspace_id', $workspaceId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => 'exp_' . $item->id,
                    'type' => 'EXPENSE',
                    'amount' => $item->amount,
                    'currency' => $item->currency,
                    'date' => $item->expense_date,
                    'category' => $item->category,
                    'notes' => $item->notes,
                    'is_void' => $item->is_void,
                    'created_at' => $item->created_at,
                ];
            });

        $incomes = IncomeTransaction::with('category')
            ->where('workspace_id', $workspaceId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => 'inc_' . $item->id,
                    'type' => 'INCOME',
                    'amount' => $item->amount,
                    'currency' => $item->currency,
                    'date' => $item->income_date,
                    'category' => $item->category,
                    'notes' => $item->notes,
                    'is_void' => $item->is_void,
                    'created_at' => $item->created_at,
                ];
            });

        // Birleştir ve sırala
        $activities = $expenses->concat($incomes)->sortByDesc('created_at')->take(10)->values();

        return response()->json($activities);
    }

    /**
     * Convert an amount to TRY based on latest FxRateSnapshot or fallback.
     */
    private function convertToTry($amount, $currency)
    {
        if ($currency === 'TRY') {
            return (float) $amount;
        }

        $rateRecord = \App\Models\FxRateSnapshot::where('base_currency', $currency)
            ->where('quote_currency', 'TRY')
            ->orderByDesc('fetched_at')
            ->first();

        // Fallbacks
        $fallbackRates = [
            'USD' => 32.50,
            'EUR' => 35.00,
            'GBP' => 40.50
        ];

        $rate = $rateRecord ? $rateRecord->rate : ($fallbackRates[$currency] ?? 1.0);

        return (float) $amount * $rate;
    }
}
