import React from 'react';
import { Position } from '@/types/models';
import LivePriceTicker from './LivePriceTicker';

/**
 * PositionCard — Tek Pozisyon Kartı
 *
 * Varlık adı, miktar, ortalama maliyet, anlık fiyat ve unrealized PnL gösterir.
 * PnL pozitifse yeşil, negatifse kırmızı.
 */

interface PositionCardProps {
    position: Position;
}

const PositionCard: React.FC<PositionCardProps> = ({ position }) => {
    const isProfitable = position.unrealized_pnl >= 0;

    const formatCurrency = (value: number, decimals: number = 2): string => {
        return value.toLocaleString('en-US', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        });
    };

    const assetClassBadge = {
        CRYPTO: { label: 'Kripto', color: 'bg-orange-500/10 text-orange-400' },
        STOCK: { label: 'Hisse', color: 'bg-blue-500/10 text-blue-400' },
        FX: { label: 'Döviz', color: 'bg-emerald-500/10 text-emerald-400' },
    };

    const badge = assetClassBadge[position.asset_class];

    return (
        <div className="group relative bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-lg hover:border-gray-300 dark:hover:border-gray-600 transition-all duration-200">
            {/* Üst satır: Sembol + İsim + Badge */}
            <div className="flex items-center justify-between mb-4">
                <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 flex items-center justify-center">
                        <span className="text-sm font-bold text-gray-700 dark:text-gray-200">
                            {position.symbol.slice(0, 2)}
                        </span>
                    </div>
                    <div>
                        <h3 className="font-semibold text-gray-900 dark:text-white text-sm">
                            {position.symbol}
                        </h3>
                        <p className="text-xs text-gray-500 dark:text-gray-400 truncate max-w-[120px]">
                            {position.name}
                        </p>
                    </div>
                </div>
                <span className={`text-xs px-2 py-1 rounded-full font-medium ${badge.color}`}>
                    {badge.label}
                </span>
            </div>

            {/* Fiyat bilgileri */}
            <div className="grid grid-cols-2 gap-3 mb-4">
                <div>
                    <p className="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Miktar</p>
                    <p className="text-sm font-medium text-gray-900 dark:text-white">
                        {formatCurrency(position.net_quantity, 4)}
                    </p>
                </div>
                <div>
                    <p className="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Ort. Maliyet</p>
                    <p className="text-sm font-medium text-gray-900 dark:text-white">
                        {new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(position.avg_cost)}
                    </p>
                </div>
                <div>
                    <p className="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Güncel Fiyat</p>
                    <LivePriceTicker 
                        symbol={position.symbol} 
                        initialPrice={position.current_price} 
                        showChange={false}
                        className="-ml-2"
                    />
                </div>
                <div>
                    <p className="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Toplam Değer</p>
                    <p className="text-sm font-medium text-gray-900 dark:text-white">
                        {new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(position.current_price * position.net_quantity)}
                    </p>
                </div>
            </div>

            {/* PnL */}
            <div className={`flex items-center justify-between p-3 rounded-lg ${isProfitable ? 'bg-emerald-500/5' : 'bg-red-500/5'}`}>
                <span className="text-xs text-gray-500 dark:text-gray-400">Unrealized PnL</span>
                <div className="flex items-center gap-2">
                    <span className={`text-sm font-semibold ${isProfitable ? 'text-emerald-400' : 'text-red-400'}`}>
                        {isProfitable ? '+' : ''}{new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(position.unrealized_pnl)}
                    </span>
                    <span className={`text-xs px-1.5 py-0.5 rounded ${isProfitable ? 'bg-emerald-500/10 text-emerald-400' : 'bg-red-500/10 text-red-400'}`}>
                        {isProfitable ? '▲' : '▼'} {Math.abs(position.unrealized_pnl_percent).toFixed(2)}%
                    </span>
                </div>
            </div>

            {/* İşlem sayısı */}
            <div className="mt-3 flex items-center justify-between text-xs text-gray-400">
                <span>{position.trade_count} işlem</span>
                <span>Son: {new Date(position.last_trade).toLocaleDateString('tr-TR')}</span>
            </div>
        </div>
    );
};

export default PositionCard;
