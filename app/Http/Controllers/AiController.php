<?php

namespace App\Http\Controllers;

use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AiController extends Controller
{
    private AiService $aiService;

    public function __construct(AiService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Parse natural language transaction via AI.
     */
    public function parseTransaction(Request $request): JsonResponse
    {
        $request->validate([
            'prompt' => 'required|string|max:1000'
        ]);

        $result = $this->aiService->parseTransaction($request->input('prompt'), Auth::user());

        if ($result) {
            return response()->json([
                'success' => true,
                'parsed' => $result
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Yapay zeka bu işlemi anlayamadı. Lütfen daha açık yazın.'
        ], 422);
    }
}
