<?php

namespace App\Http\Controllers;

use App\Services\PortfolioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * PortfolioController — Portföy Controller'ı
 *
 * Pozisyon listesi, portföy özeti ve top movers.
 * Akif'in Dashboard sayfası /api/portfolio/summary ve /api/portfolio/movers
 * endpoint'lerini kullanır.
 *
 * @see TASKS.md — Görev M-13
 */
class PortfolioController extends Controller
{
    public function __construct(
        private PortfolioService $portfolioService
    ) {}

    /**
     * Portföy pozisyon listesi (Inertia sayfası).
     *
     * GET /portfolio
     */
    public function index(Request $request): Response
    {
        $positions = $this->portfolioService->getPositions($request->user());
        $summary = $this->portfolioService->getSummary($request->user());

        return Inertia::render('Portfolio/Index', [
            'positions' => $positions,
            'summary'   => $summary,
        ]);
    }

    /**
     * Portföy özeti (JSON API — Dashboard ve mobil app için).
     *
     * GET /api/portfolio/summary
     *
     * Akif'in NetWorthCard component'i bu endpoint'i kullanır.
     */
    public function summary(Request $request): JsonResponse
    {
        $summary = $this->portfolioService->getSummary($request->user());

        return response()->json($summary);
    }

    /**
     * En çok hareket eden pozisyonlar (JSON API — Dashboard için).
     *
     * GET /api/portfolio/movers
     *
     * Akif'in TopMoversCard component'i bu endpoint'i kullanır.
     */
    public function movers(Request $request): JsonResponse
    {
        $movers = $this->portfolioService->getTopMovers($request->user());

        return response()->json($movers);
    }
}
