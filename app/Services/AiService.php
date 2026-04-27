<?php

namespace App\Services;

use App\Models\AiInteraction;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    private string $apiKey;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY', '');
    }

    /**
     * Parse natural language transaction input into structured JSON data.
     */
    public function parseTransaction(string $userText, User $user): ?array
    {
        if (empty($this->apiKey)) {
            Log::warning('GEMINI_API_KEY bulunamadı, AI servisi devre dışı.');
            return null;
        }

        $categories = \App\Models\Category::all(['id', 'name', 'direction'])
            ->map(fn($c) => "{$c->name} (ID: {$c->id}, Tip: {$c->direction})")
            ->implode(', ');

        $prompt = "Sen bir finans asistanısın. Kullanıcının harcama/gelir bilgisini ayrıştırıp SADECE JSON formatında dönmen gerekiyor. Asla markdown veya ekstra yazı kullanma, direk { ile başla.
Girdi: \"{$userText}\"
Mevcut Kategoriler: {$categories}

Beklenen JSON Şeması:
{
  \"type\": \"EXPENSE\" VEYA \"INCOME\",
  \"amount\": float (örnek 150.50),
  \"currency\": \"TRY\", \"USD\", \"EUR\" (varsayılan TRY),
  \"category_id\": \"Mevcut kategorilerden işleme en uygun olanın ID'si. Hiçbiri uymuyorsa null bırak.\",
  \"category_name\": \"Eğer category_id null ise, senin önereceğin kısa bir kategori ismi.\",
  \"date\": \"YYYY-MM-DD\" (bugünün tarihini baz al, şu an: " . date('Y-m-d') . "),
  \"note\": \"Orijinal cümleden damıtılmış mantıklı bir tek satır not\"
}";

        $payload = [
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ]
        ];

        try {
            $response = Http::post("{$this->baseUrl}?key={$this->apiKey}", $payload);
            
            if ($response->successful()) {
                $content = $response->json('candidates.0.content.parts.0.text', '');
                
                // Temizleme: Eğer gemini markdown json block olarak dönerse
                $content = str_replace(['```json', '```'], '', $content);
                $content = trim($content);
                
                $parsedData = json_decode($content, true);

                // Etkileşimi kaydet
                if ($parsedData) {
                    AiInteraction::create([
                        'user_id' => $user->id,
                        'prompt' => $userText,
                        'response' => $parsedData,
                        'action_type' => 'PARSE_TRANSACTION',
                    ]);
                    
                    return $parsedData;
                }
            } else {
                Log::error('Gemini API Error: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('AiService Exception: ' . $e->getMessage());
        }

        return null;
    }
}
