import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { Check, RefreshCw } from 'lucide-react';

/**
 * PriceUpdatePanel — Manuel Fiyat Güncelleme Paneli
 *
 * Kullanıcının portföydeki varlıkların güncel fiyatlarını
 * manuel olarak girmesini sağlar. price_snapshots tablosuna
 * source='manual' olarak kaydeder.
 */

interface AssetPrice {
    id: string;
    symbol: string;
    name: string;
    asset_class: string;
    base_currency: string;
    current_price: number | null;
    last_source: string | null;
}

interface PriceUpdatePanelProps {
    isOpen: boolean;
    onClose: () => void;
    onPriceUpdated?: () => void;
}

const ASSET_CLASS_BADGES: Record<string, { label: string; emoji: string; color: string }> = {
    CRYPTO: { label: 'Kripto', emoji: '₿', color: 'text-orange-400' },
    STOCK:  { label: 'Hisse', emoji: '📊', color: 'text-blue-400' },
    FX:     { label: 'Döviz', emoji: '💱', color: 'text-emerald-400' },
};

const PriceUpdatePanel: React.FC<PriceUpdatePanelProps> = ({ isOpen, onClose, onPriceUpdated }) => {
    const [assets, setAssets] = useState<AssetPrice[]>([]);
    const [prices, setPrices] = useState<Record<string, string>>({});
    const [saving, setSaving] = useState<Record<string, boolean>>({});
    const [saved, setSaved] = useState<Record<string, boolean>>({});
    const [loading, setLoading] = useState(true);

    // Tüm asset'leri ve son fiyatlarını yükle
    useEffect(() => {
        if (!isOpen) return;

        const fetchAssets = async () => {
            setLoading(true);
            try {
                const { data: assetList } = await axios.get('/api/assets');

                // Her asset için son fiyatı getir
                const withPrices: AssetPrice[] = await Promise.all(
                    assetList.map(async (a: any) => {
                        try {
                            const { data: priceData } = await axios.get(`/api/assets/${a.id}/price`);
                            return {
                                ...a,
                                current_price: priceData.price,
                                last_source: priceData.source,
                            };
                        } catch {
                            return { ...a, current_price: null, last_source: null };
                        }
                    })
                );

                setAssets(withPrices);

                // Mevcut fiyatları input'lara doldur
                const initialPrices: Record<string, string> = {};
                withPrices.forEach((a) => {
                    if (a.current_price !== null) {
                        initialPrices[a.id] = String(a.current_price);
                    }
                });
                setPrices(initialPrices);
            } catch (e) {
                console.error('Asset listesi alınamadı:', e);
            } finally {
                setLoading(false);
            }
        };

        fetchAssets();
    }, [isOpen]);

    const handleSavePrice = async (assetId: string) => {
        const priceStr = prices[assetId];
        if (!priceStr || isNaN(parseFloat(priceStr)) || parseFloat(priceStr) <= 0) return;

        setSaving((p) => ({ ...p, [assetId]: true }));
        try {
            await axios.post(`/api/assets/${assetId}/price`, {
                price: parseFloat(priceStr),
            });
            setSaved((p) => ({ ...p, [assetId]: true }));
            setTimeout(() => setSaved((p) => ({ ...p, [assetId]: false })), 2000);
            onPriceUpdated?.();
        } catch (e) {
            console.error('Fiyat kaydedilemedi:', e);
        } finally {
            setSaving((p) => ({ ...p, [assetId]: false }));
        }
    };

    const handleSaveAll = async () => {
        const assetsToSave = assets.filter(
            (a) => prices[a.id] && parseFloat(prices[a.id]) > 0
        );

        for (const asset of assetsToSave) {
            await handleSavePrice(asset.id);
        }
    };

    if (!isOpen) return null;

    // Gruplanmış assets
    const grouped = {
        CRYPTO: assets.filter((a) => a.asset_class === 'CRYPTO'),
        STOCK: assets.filter((a) => a.asset_class === 'STOCK'),
        FX: assets.filter((a) => a.asset_class === 'FX'),
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center">
            <div className="absolute inset-0 bg-black/50 backdrop-blur-sm" onClick={onClose} />

            <div className="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-[80vh] flex flex-col border border-gray-200 dark:border-gray-700">
                {/* Header */}
                <div className="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                    <div>
                        <h2 className="text-lg font-bold text-gray-900 dark:text-white">
                            💰 Güncel Fiyat Güncelle
                        </h2>
                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Varlıklarınızın güncel fiyatlarını manuel olarak girin
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <button
                            type="button"
                            onClick={handleSaveAll}
                            className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-500 text-white text-xs font-medium rounded-lg transition-colors"
                        >
                            <RefreshCw size={12} />
                            Tümünü Kaydet
                        </button>
                        <button onClick={onClose} className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                {/* Body */}
                <div className="flex-1 overflow-y-auto p-6">
                    {loading ? (
                        <div className="flex items-center justify-center py-12">
                            <div className="animate-spin w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full" />
                        </div>
                    ) : assets.length === 0 ? (
                        <div className="text-center py-12 text-gray-400">
                            <p className="text-sm">Henüz varlık eklenmemiş.</p>
                        </div>
                    ) : (
                        <div className="space-y-6">
                            {Object.entries(grouped).map(([cls, items]) => {
                                if (items.length === 0) return null;
                                const badge = ASSET_CLASS_BADGES[cls];

                                return (
                                    <div key={cls}>
                                        <h3 className={`text-sm font-semibold mb-3 flex items-center gap-2 ${badge.color}`}>
                                            <span>{badge.emoji}</span>
                                            {badge.label}
                                            <span className="text-xs text-gray-400 font-normal">({items.length})</span>
                                        </h3>

                                        <div className="space-y-2">
                                            {items.map((asset) => (
                                                <div
                                                    key={asset.id}
                                                    className="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-100 dark:border-gray-700"
                                                >
                                                    {/* Sembol + İsim */}
                                                    <div className="flex-1 min-w-0">
                                                        <div className="flex items-center gap-2">
                                                            <span className="text-sm font-semibold text-gray-900 dark:text-white">
                                                                {asset.symbol}
                                                            </span>
                                                            <span className="text-xs text-gray-400 truncate">
                                                                {asset.name}
                                                            </span>
                                                        </div>
                                                        {asset.current_price !== null && (
                                                            <span className="text-xs text-gray-400">
                                                                Önceki: ${asset.current_price.toLocaleString('en-US', { maximumFractionDigits: 8 })}
                                                                {asset.last_source && ` (${asset.last_source})`}
                                                            </span>
                                                        )}
                                                    </div>

                                                    {/* Fiyat Input */}
                                                    <div className="flex items-center gap-2">
                                                        <span className="text-xs text-gray-400">{asset.base_currency}</span>
                                                        <input
                                                            type="number"
                                                            step="any"
                                                            value={prices[asset.id] || ''}
                                                            onChange={(e) =>
                                                                setPrices((p) => ({ ...p, [asset.id]: e.target.value }))
                                                            }
                                                            onKeyDown={(e) => {
                                                                if (e.key === 'Enter') handleSavePrice(asset.id);
                                                            }}
                                                            placeholder="0.00"
                                                            className="w-32 px-3 py-1.5 bg-white dark:bg-gray-600 border border-gray-200 dark:border-gray-500 rounded-lg text-sm text-right text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                                        />
                                                        <button
                                                            type="button"
                                                            onClick={() => handleSavePrice(asset.id)}
                                                            disabled={saving[asset.id] || !prices[asset.id]}
                                                            className={`p-1.5 rounded-lg transition-all ${
                                                                saved[asset.id]
                                                                    ? 'bg-emerald-500 text-white'
                                                                    : 'bg-gray-200 dark:bg-gray-600 text-gray-500 dark:text-gray-400 hover:bg-blue-500 hover:text-white disabled:opacity-40'
                                                            }`}
                                                        >
                                                            {saving[asset.id] ? (
                                                                <div className="w-4 h-4 border-2 border-current border-t-transparent rounded-full animate-spin" />
                                                            ) : saved[asset.id] ? (
                                                                <Check size={16} />
                                                            ) : (
                                                                <Check size={16} />
                                                            )}
                                                        </button>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};

export default PriceUpdatePanel;
