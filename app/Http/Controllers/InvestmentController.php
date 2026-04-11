<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTradeRequest;
use App\Services\InvestmentService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * InvestmentController — Yatırım İşlem Controller'ı
 *
 * Thin Controller: Sadece HTTP request/response yönetimi.
 * Tüm business logic InvestmentService'de.
 */
class InvestmentController extends Controller
{
    public function __construct(
        private InvestmentService $investmentService
    ) {}

    /**
     * Kullanıcının işlem geçmişini listele (paginate, filtreli).
     *
     * GET /investments
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['asset_class', 'asset_id', 'side', 'date_from', 'date_to']);
        $transactions = $this->investmentService->listForUser($request->user(), $filters);

        return Inertia::render('Portfolio/Index', [
            'transactions' => $transactions,
            'filters'      => $filters,
        ]);
    }

    /**
     * Yeni yatırım işlemi oluştur.
     *
     * POST /investments
     */
    public function store(StoreTradeRequest $request)
    {
        $transaction = $this->investmentService->store(
            $request->validated(),
            $request->user()
        );

        return redirect()->back()->with('success', 'İşlem başarıyla eklendi.');
    }

    /**
     * Yatırım işlemini iptal et (void).
     *
     * POST /investments/{id}/void
     */
    public function void(Request $request, string $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $this->investmentService->void(
            $id,
            $request->input('reason'),
            $request->user()
        );

        return redirect()->back()->with('success', 'İşlem iptal edildi.');
    }
}
