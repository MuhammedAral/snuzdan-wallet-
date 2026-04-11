<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PriceUpdated — Fiyat Güncelleme Event'i
 *
 * Yeni fiyat geldiğinde Reverb üzerinden frontend'e broadcast edilir.
 * LivePriceTicker component'i bu event'i dinler.
 *
 * Channel: public 'prices.{symbol}'
 *
 * @see TASKS.md — Görev M-15
 */
class PriceUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Varlık sembolü (örn: 'BTCUSDT')
     */
    public string $symbol;

    /**
     * Anlık fiyat
     */
    public float $price;

    /**
     * Son 24 saat yüzde değişimi
     */
    public float $change_percent;

    /**
     * Fiyatın geldiği zaman
     */
    public string $updated_at;

    /**
     * Create a new event instance.
     */
    public function __construct(string $symbol, float $price, float $changePercent)
    {
        $this->symbol = $symbol;
        $this->price = $price;
        $this->change_percent = $changePercent;
        $this->updated_at = now()->toISOString();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * Public channel: Herkes dinleyebilir (fiyat verisi gizli değil).
     */
    public function broadcastOn(): Channel
    {
        return new Channel('prices.' . $this->symbol);
    }

    /**
     * Event'in broadcast adı.
     */
    public function broadcastAs(): string
    {
        return 'price.updated';
    }
}
