import React, { useState, useEffect, useRef } from 'react';
import echo from '@/echo';

/**
 * LivePriceTicker — Canlı Fiyat Göstergesi
 *
 * Laravel Echo ile Reverb'e bağlanıp gerçek zamanlı fiyat güncellemelerini gösterir.
 * Fiyat yukarı gidince yeşil, aşağı gidince kırmızı animasyon.
 *
 * Props:
 *   - symbol: Binance pair sembolü (örn: "BTCUSDT")
 *   - initialPrice: Başlangıç fiyatı (opsiyonel)
 *   - showChange: Yüzde değişimi göster/gizle
 *   - className: Ek CSS sınıfı
 *
 * @see TASKS.md — Görev M-17
 */

interface LivePriceTickerProps {
    symbol: string;
    initialPrice?: number;
    showChange?: boolean;
    className?: string;
}

interface PriceData {
    symbol: string;
    price: number;
    change_percent: number;
    updated_at: string;
}

const LivePriceTicker: React.FC<LivePriceTickerProps> = ({
    symbol,
    initialPrice = 0,
    showChange = true,
    className = '',
}) => {
    const [price, setPrice] = useState<number>(initialPrice);
    const [changePercent, setChangePercent] = useState<number>(0);
    const [direction, setDirection] = useState<'up' | 'down' | 'neutral'>('neutral');
    const [flash, setFlash] = useState<boolean>(false);
    const previousPriceRef = useRef<number>(initialPrice);

    useEffect(() => {
        // Reverb channel'a subscribe ol
        const channel = echo.channel(`prices.${symbol}`);

        channel.listen('.price.updated', (data: PriceData) => {
            const prevPrice = previousPriceRef.current;

            // Yön belirle
            if (data.price > prevPrice) {
                setDirection('up');
            } else if (data.price < prevPrice) {
                setDirection('down');
            } else {
                setDirection('neutral');
            }

            // Fiyatı güncelle
            setPrice(data.price);
            setChangePercent(data.change_percent);
            previousPriceRef.current = data.price;

            // Flash animasyonu tetikle
            setFlash(true);
            setTimeout(() => setFlash(false), 600);
        });

        // Cleanup: unmount'ta unsubscribe ol
        return () => {
            echo.leave(`prices.${symbol}`);
        };
    }, [symbol]);

    /**
     * Fiyatı formatla (büyük sayılar için K/M suffix)
     */
    const formatPrice = (value: number): string => {
        if (value >= 1000) {
            return value.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        }
        return value.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 8,
        });
    };

    // Renk sınıfları
    const colorClass = direction === 'up'
        ? 'text-emerald-400'
        : direction === 'down'
            ? 'text-red-400'
            : 'text-gray-300';

    const flashClass = flash
        ? direction === 'up'
            ? 'bg-emerald-500/20'
            : direction === 'down'
                ? 'bg-red-500/20'
                : ''
        : '';

    const changeIcon = direction === 'up' ? '▲' : direction === 'down' ? '▼' : '●';

    return (
        <div
            className={`inline-flex items-center gap-2 px-3 py-1.5 rounded-lg transition-all duration-300 ${flashClass} ${className}`}
        >
            {/* Sembol */}
            <span className="text-xs font-medium text-gray-400 uppercase">
                {symbol.replace('USDT', '')}
            </span>

            {/* Fiyat */}
            <span className={`font-mono font-semibold text-sm transition-colors duration-300 ${colorClass}`}>
                ${formatPrice(price)}
            </span>

            {/* Yüzde değişim */}
            {showChange && (
                <span
                    className={`text-xs font-medium transition-colors duration-300 ${colorClass}`}
                >
                    {changeIcon} {Math.abs(changePercent).toFixed(2)}%
                </span>
            )}
        </div>
    );
};

export default LivePriceTicker;
