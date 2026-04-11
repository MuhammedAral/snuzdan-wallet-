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
        $currentMonth = date('Y-m');

        // Aylık toplam gider
        $totalExpense = ExpenseTransaction::where('workspace_id', $workspaceId)
            ->active()
            ->whereRaw("TO_CHAR(expense_date, 'YYYY-MM') = ?", [$currentMonth])
            ->sum('amount');

        // Aylık toplam gelir
        $totalIncome = IncomeTransaction::where('workspace_id', $workspaceId)
            ->active()
            ->whereRaw("TO_CHAR(income_date, 'YYYY-MM') = ?", [$currentMonth])
            ->sum('amount');

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
}
