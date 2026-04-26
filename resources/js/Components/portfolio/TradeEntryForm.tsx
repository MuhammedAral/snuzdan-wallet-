import React, { useState, useEffect, useRef } from 'react';
import { useForm } from '@inertiajs/react';
import axios from 'axios';
import { Search, X, ChevronDown } from 'lucide-react';

/**
 * TradeEntryForm — BUY/SELL İşlem Formu (Modal)
 *
 * 3 alan: Adet, Birim Fiyat, Toplam — herhangi 2'si girilince 3.'sü otomatik hesaplanır.
 * Komisyon, tarih, not alanları opsiyonel.
 * Asset seçimi autocomplete dropdown ile yapılır.
 */

interface Asset {
    id: string;
    asset_class: string;
    symbol: string;
    name: string;
    base_currency: string;
}

interface TradeEntryFormProps {
    isOpen: boolean;
    onClose: () => void;
}

const ASSET_CLASS_BADGES: Record<string, { label: string; color: string }> = {
    CRYPTO: { label: 'Kripto', color: 'bg-orange-500/10 text-orange-400 border-orange-500/20' },
    STOCK: { label: 'Hisse', color: 'bg-blue-500/10 text-blue-400 border-blue-500/20' },
    FX: { label: 'Döviz', color: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' },
};

const TradeEntryForm: React.FC<TradeEntryFormProps> = ({ isOpen, onClose }) => {
    const [autoField, setAutoField] = useState<'quantity' | 'unit_price' | 'total_amount' | null>(null);

    // Asset autocomplete state
    const [assetSearch, setAssetSearch] = useState('');
    const [assetResults, setAssetResults] = useState<Asset[]>([]);
    const [selectedAsset, setSelectedAsset] = useState<Asset | null>(null);
    const [showDropdown, setShowDropdown] = useState(false);
    const [searchLoading, setSearchLoading] = useState(false);
    const dropdownRef = useRef<HTMLDivElement>(null);
    const searchTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    // Yeni varlık oluşturma state
    const [showCreateForm, setShowCreateForm] = useState(false);
    const [newAsset, setNewAsset] = useState({ symbol: '', name: '', asset_class: 'CRYPTO' as string, base_currency: 'USD' });
    const [createLoading, setCreateLoading] = useState(false);
    const [createError, setCreateError] = useState('');

    const { data, setData, post, processing, errors, reset } = useForm({
        asset_id: '',
        side: 'BUY' as 'BUY' | 'SELL',
        quantity: '',
        unit_price: '',
        total_amount: '',
        commission: '0',
        transaction_date: new Date().toISOString().split('T')[0],
        note: '',
    });

    // Asset arama — debounce ile
    useEffect(() => {
        if (searchTimeoutRef.current) {
            clearTimeout(searchTimeoutRef.current);
        }

        if (assetSearch.length === 0) {
            // Boş arama — tüm asset'leri getir
            searchTimeoutRef.current = setTimeout(async () => {
                try {
                    setSearchLoading(true);
                    const { data: results } = await axios.get('/api/assets');
                    setAssetResults(results);
                } catch (e) {
                    setAssetResults([]);
                } finally {
                    setSearchLoading(false);
                }
            }, 100);
            return;
        }

        searchTimeoutRef.current = setTimeout(async () => {
            try {
                setSearchLoading(true);
                const { data: results } = await axios.get(`/api/assets?search=${encodeURIComponent(assetSearch)}`);
                setAssetResults(results);
            } catch (e) {
                setAssetResults([]);
            } finally {
                setSearchLoading(false);
            }
        }, 300);

        return () => {
            if (searchTimeoutRef.current) clearTimeout(searchTimeoutRef.current);
        };
    }, [assetSearch]);

    // Dropdown dışına tıklayınca kapat
    useEffect(() => {
        const handleClickOutside = (e: MouseEvent) => {
            if (dropdownRef.current && !dropdownRef.current.contains(e.target as Node)) {
                setShowDropdown(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    const handleAssetSelect = (asset: Asset) => {
        setSelectedAsset(asset);
        setData('asset_id', asset.id);
        setAssetSearch('');
        setShowDropdown(false);
    };

    const clearAssetSelection = () => {
        setSelectedAsset(null);
        setData('asset_id', '');
        setAssetSearch('');
    };

    const handleCreateAsset = async () => {
        if (!newAsset.symbol.trim() || !newAsset.name.trim()) {
            setCreateError('Sembol ve ad alanları zorunludur.');
            return;
        }
        setCreateLoading(true);
        setCreateError('');
        try {
            const { data: created } = await axios.post('/api/assets', {
                asset_class: newAsset.asset_class,
                symbol: newAsset.symbol.toUpperCase(),
                name: newAsset.name,
                base_currency: newAsset.base_currency,
            });
            // Oluşturulan asset'i seç
            handleAssetSelect(created);
            setShowCreateForm(false);
            setNewAsset({ symbol: '', name: '', asset_class: 'CRYPTO', base_currency: 'USD' });
        } catch (err: any) {
            setCreateError(err.response?.data?.message || 'Varlık oluşturulamadı.');
        } finally {
            setCreateLoading(false);
        }
    };

    // 2-of-3 otomatik hesaplama
    const handleFieldChange = (field: string, value: string) => {
        const newData = { ...data, [field]: value };
        setData(field as any, value);

        const qty = parseFloat(field === 'quantity' ? value : newData.quantity) || 0;
        const price = parseFloat(field === 'unit_price' ? value : newData.unit_price) || 0;
        const total = parseFloat(field === 'total_amount' ? value : newData.total_amount) || 0;

        if (field === 'quantity' || field === 'unit_price') {
            if (qty > 0 && price > 0) {
                const calc = (qty * price).toFixed(2);
                setData((prev: any) => ({ ...prev, [field]: value, total_amount: calc }));
                setAutoField('total_amount');
            }
        } else if (field === 'total_amount') {
            if (total > 0 && qty > 0) {
                const calc = (total / qty).toFixed(8);
                setData((prev: any) => ({ ...prev, total_amount: value, unit_price: calc }));
                setAutoField('unit_price');
            } else if (total > 0 && price > 0) {
                const calc = (total / price).toFixed(8);
                setData((prev: any) => ({ ...prev, total_amount: value, quantity: calc }));
                setAutoField('quantity');
            }
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('investments.store'), {
            onSuccess: () => {
                reset();
                setSelectedAsset(null);
                setAssetSearch('');
                onClose();
            },
        });
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center">
            {/* Overlay */}
            <div className="absolute inset-0 bg-black/50 backdrop-blur-sm" onClick={onClose} />

            {/* Modal */}
            <div className="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg mx-4 p-6 border border-gray-200 dark:border-gray-700">
                <div className="flex items-center justify-between mb-6">
                    <h2 className="text-xl font-bold text-gray-900 dark:text-white">
                        İşlem Ekle
                    </h2>
                    <button onClick={onClose} className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form onSubmit={handleSubmit} className="space-y-4">
                    {/* BUY / SELL Toggle */}
                    <div className="flex gap-2 p-1 bg-gray-100 dark:bg-gray-700 rounded-lg">
                        <button
                            type="button"
                            onClick={() => setData('side', 'BUY')}
                            className={`flex-1 py-2.5 rounded-md text-sm font-semibold transition-all ${
                                data.side === 'BUY'
                                    ? 'bg-emerald-500 text-gray-900 dark:text-white shadow-sm'
                                    : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'
                            }`}
                        >
                            AL (BUY)
                        </button>
                        <button
                            type="button"
                            onClick={() => setData('side', 'SELL')}
                            className={`flex-1 py-2.5 rounded-md text-sm font-semibold transition-all ${
                                data.side === 'SELL'
                                    ? 'bg-red-500 text-gray-900 dark:text-white shadow-sm'
                                    : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'
                            }`}
                        >
                            SAT (SELL)
                        </button>
                    </div>

                    {/* Asset Autocomplete */}
                    <div ref={dropdownRef} className="relative">
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Varlık
                        </label>

                        {selectedAsset ? (
                            // Seçili asset kartı
                            <div className="flex items-center gap-3 px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg">
                                <div className="w-8 h-8 rounded-full bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-600 dark:to-gray-500 flex items-center justify-center">
                                    <span className="text-xs font-bold text-gray-700 dark:text-gray-200">{selectedAsset.symbol.slice(0, 2)}</span>
                                </div>
                                <div className="flex-1 min-w-0">
                                    <div className="flex items-center gap-2">
                                        <span className="text-sm font-semibold text-gray-900 dark:text-white">{selectedAsset.symbol}</span>
                                        <span className={`text-xs px-1.5 py-0.5 rounded border font-medium ${ASSET_CLASS_BADGES[selectedAsset.asset_class]?.color || 'bg-gray-100 text-gray-400'}`}>
                                            {ASSET_CLASS_BADGES[selectedAsset.asset_class]?.label || selectedAsset.asset_class}
                                        </span>
                                    </div>
                                    <p className="text-xs text-gray-500 dark:text-gray-400 truncate">{selectedAsset.name}</p>
                                </div>
                                <button
                                    type="button"
                                    onClick={clearAssetSelection}
                                    className="text-gray-400 hover:text-rose-400 transition-colors p-1"
                                >
                                    <X size={16} />
                                </button>
                            </div>
                        ) : (
                            // Arama inputu
                            <div className="relative">
                                <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                                <input
                                    type="text"
                                    value={assetSearch}
                                    onChange={(e) => {
                                        setAssetSearch(e.target.value);
                                        setShowDropdown(true);
                                    }}
                                    onFocus={() => setShowDropdown(true)}
                                    placeholder="Varlık ara... (BTC, AAPL, EUR)"
                                    className="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                />
                                <ChevronDown size={16} className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400" />
                            </div>
                        )}

                        {/* Dropdown sonuçları */}
                        {showDropdown && !selectedAsset && (
                            <div className="absolute z-20 w-full mt-1 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-xl max-h-64 overflow-y-auto">
                                {showCreateForm ? (
                                    /* ── Yeni Varlık Oluşturma Formu ── */
                                    <div className="p-3 space-y-3">
                                        <div className="flex items-center justify-between">
                                            <h4 className="text-sm font-semibold text-gray-900 dark:text-white">Yeni Varlık Ekle</h4>
                                            <button type="button" onClick={() => { setShowCreateForm(false); setCreateError(''); }} className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                                <X size={14} />
                                            </button>
                                        </div>

                                        {createError && (
                                            <div className="text-xs text-red-400 bg-red-400/10 p-2 rounded">{createError}</div>
                                        )}

                                        {/* Sınıf Seçimi */}
                                        <div className="flex gap-1.5">
                                            {(['CRYPTO', 'STOCK', 'FX'] as const).map((cls) => (
                                                <button
                                                    key={cls}
                                                    type="button"
                                                    onClick={() => setNewAsset(prev => ({ ...prev, asset_class: cls }))}
                                                    className={`flex-1 py-1.5 text-xs font-medium rounded-md transition-all border ${
                                                        newAsset.asset_class === cls
                                                            ? ASSET_CLASS_BADGES[cls].color + ' border-current'
                                                            : 'text-gray-400 border-gray-200 dark:border-gray-600 hover:border-gray-400'
                                                    }`}
                                                >
                                                    {ASSET_CLASS_BADGES[cls].label}
                                                </button>
                                            ))}
                                        </div>

                                        {/* Sembol + İsim */}
                                        <div className="grid grid-cols-2 gap-2">
                                            <input
                                                type="text"
                                                value={newAsset.symbol}
                                                onChange={(e) => setNewAsset(prev => ({ ...prev, symbol: e.target.value.toUpperCase() }))}
                                                placeholder="Sembol (BTC)"
                                                className="px-2.5 py-2 bg-gray-50 dark:bg-gray-600 border border-gray-200 dark:border-gray-500 rounded-md text-xs text-gray-900 dark:text-white focus:ring-1 focus:ring-blue-500"
                                            />
                                            <input
                                                type="text"
                                                value={newAsset.name}
                                                onChange={(e) => setNewAsset(prev => ({ ...prev, name: e.target.value }))}
                                                placeholder="Ad (Bitcoin)"
                                                className="px-2.5 py-2 bg-gray-50 dark:bg-gray-600 border border-gray-200 dark:border-gray-500 rounded-md text-xs text-gray-900 dark:text-white focus:ring-1 focus:ring-blue-500"
                                            />
                                        </div>

                                        {/* Para Birimi */}
                                        <select
                                            value={newAsset.base_currency}
                                            onChange={(e) => setNewAsset(prev => ({ ...prev, base_currency: e.target.value }))}
                                            className="w-full px-2.5 py-2 bg-gray-50 dark:bg-gray-600 border border-gray-200 dark:border-gray-500 rounded-md text-xs text-gray-900 dark:text-white focus:ring-1 focus:ring-blue-500"
                                        >
                                            <option value="USD">USD</option>
                                            <option value="TRY">TRY</option>
                                            <option value="EUR">EUR</option>
                                        </select>

                                        <button
                                            type="button"
                                            onClick={handleCreateAsset}
                                            disabled={createLoading}
                                            className="w-full py-2 bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold rounded-md transition-colors disabled:opacity-60"
                                        >
                                            {createLoading ? 'Oluşturuluyor...' : 'Oluştur ve Seç'}
                                        </button>
                                    </div>
                                ) : (
                                    /* ── Normal Arama Sonuçları ── */
                                    <>
                                        {searchLoading ? (
                                            <div className="p-3 text-center text-sm text-gray-400 animate-pulse">Aranıyor...</div>
                                        ) : assetResults.length === 0 ? (
                                            <div className="p-4 text-center">
                                                <p className="text-sm text-gray-400 mb-2">
                                                    {assetSearch ? `"${assetSearch}" bulunamadı` : 'Henüz varlık yok'}
                                                </p>
                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        setShowCreateForm(true);
                                                        setNewAsset(prev => ({ ...prev, symbol: assetSearch.toUpperCase() }));
                                                    }}
                                                    className="text-xs text-blue-400 hover:text-blue-300 font-medium"
                                                >
                                                    + Yeni varlık olarak ekle
                                                </button>
                                            </div>
                                        ) : (
                                            assetResults.map((asset) => (
                                                <button
                                                    key={asset.id}
                                                    type="button"
                                                    onClick={() => handleAssetSelect(asset)}
                                                    className="w-full flex items-center gap-3 px-3 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors text-left"
                                                >
                                                    <div className="w-7 h-7 rounded-full bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-600 dark:to-gray-500 flex items-center justify-center shrink-0">
                                                        <span className="text-xs font-bold text-gray-700 dark:text-gray-200">{asset.symbol.slice(0, 2)}</span>
                                                    </div>
                                                    <div className="flex-1 min-w-0">
                                                        <span className="text-sm font-medium text-gray-900 dark:text-white">{asset.symbol}</span>
                                                        <span className="text-xs text-gray-500 dark:text-gray-400 ml-2 truncate">{asset.name}</span>
                                                    </div>
                                                    <span className={`text-xs px-1.5 py-0.5 rounded border font-medium shrink-0 ${ASSET_CLASS_BADGES[asset.asset_class]?.color || 'bg-gray-100 text-gray-400'}`}>
                                                        {ASSET_CLASS_BADGES[asset.asset_class]?.label || asset.asset_class}
                                                    </span>
                                                </button>
                                            ))
                                        )}

                                        {/* Alt kısım: Yeni Varlık Ekle butonu */}
                                        <div className="border-t border-gray-200 dark:border-gray-600">
                                            <button
                                                type="button"
                                                onClick={() => {
                                                    setShowCreateForm(true);
                                                    setNewAsset(prev => ({ ...prev, symbol: assetSearch.toUpperCase() }));
                                                }}
                                                className="w-full px-3 py-2.5 text-left text-xs font-medium text-blue-400 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors flex items-center gap-2"
                                            >
                                                <span className="w-5 h-5 rounded-full bg-blue-500/10 flex items-center justify-center text-blue-400 text-sm">+</span>
                                                Özel varlık ekle
                                            </button>
                                        </div>
                                    </>
                                )}
                            </div>
                        )}

                        {errors.asset_id && <p className="mt-1 text-xs text-red-400">{errors.asset_id}</p>}
                    </div>

                    {/* 2-of-3 Alanlar */}
                    <div className="grid grid-cols-3 gap-3">
                        <div>
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Adet
                                {autoField === 'quantity' && <span className="ml-1 text-blue-400">(oto)</span>}
                            </label>
                            <input
                                type="number"
                                step="any"
                                value={data.quantity}
                                onChange={(e) => handleFieldChange('quantity', e.target.value)}
                                placeholder="0.00"
                                className={`w-full px-3 py-2.5 bg-gray-50 dark:bg-gray-700 border rounded-lg text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all ${
                                    autoField === 'quantity' ? 'border-blue-400 bg-blue-50/50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-600'
                                }`}
                            />
                            {errors.quantity && <p className="mt-1 text-xs text-red-400">{errors.quantity}</p>}
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Birim Fiyat
                                {autoField === 'unit_price' && <span className="ml-1 text-blue-400">(oto)</span>}
                            </label>
                            <input
                                type="number"
                                step="any"
                                value={data.unit_price}
                                onChange={(e) => handleFieldChange('unit_price', e.target.value)}
                                placeholder="0.00"
                                className={`w-full px-3 py-2.5 bg-gray-50 dark:bg-gray-700 border rounded-lg text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all ${
                                    autoField === 'unit_price' ? 'border-blue-400 bg-blue-50/50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-600'
                                }`}
                            />
                            {errors.unit_price && <p className="mt-1 text-xs text-red-400">{errors.unit_price}</p>}
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Toplam
                                {autoField === 'total_amount' && <span className="ml-1 text-blue-400">(oto)</span>}
                            </label>
                            <input
                                type="number"
                                step="any"
                                value={data.total_amount}
                                onChange={(e) => handleFieldChange('total_amount', e.target.value)}
                                placeholder="0.00"
                                className={`w-full px-3 py-2.5 bg-gray-50 dark:bg-gray-700 border rounded-lg text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all ${
                                    autoField === 'total_amount' ? 'border-blue-400 bg-blue-50/50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-600'
                                }`}
                            />
                            {errors.total_amount && <p className="mt-1 text-xs text-red-400">{errors.total_amount}</p>}
                        </div>
                    </div>

                    {/* Komisyon + Tarih */}
                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Komisyon</label>
                            <input
                                type="number"
                                step="any"
                                value={data.commission}
                                onChange={(e) => setData('commission', e.target.value)}
                                className="w-full px-3 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all"
                            />
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Tarih</label>
                            <input
                                type="date"
                                value={data.transaction_date}
                                onChange={(e) => setData('transaction_date', e.target.value)}
                                className="w-full px-3 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all"
                            />
                            {errors.transaction_date && <p className="mt-1 text-xs text-red-400">{errors.transaction_date}</p>}
                        </div>
                    </div>

                    {/* Not */}
                    <div>
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Not (opsiyonel)</label>
                        <textarea
                            value={data.note}
                            onChange={(e) => setData('note', e.target.value)}
                            placeholder="İşlem hakkında not..."
                            rows={2}
                            className="w-full px-3 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all resize-none"
                        />
                    </div>

                    {/* Submit */}
                    <button
                        type="submit"
                        disabled={processing}
                        className={`w-full py-3 rounded-lg text-gray-900 dark:text-white font-semibold text-sm transition-all ${
                            data.side === 'BUY'
                                ? 'bg-emerald-500 hover:bg-emerald-600 disabled:bg-emerald-300'
                                : 'bg-red-500 hover:bg-red-600 disabled:bg-red-300'
                        }`}
                    >
                        {processing ? 'Kaydediliyor...' : data.side === 'BUY' ? 'Alış Yap' : 'Satış Yap'}
                    </button>
                </form>
            </div>
        </div>
    );
};

export default TradeEntryForm;
