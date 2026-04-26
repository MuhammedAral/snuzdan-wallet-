import React, { useState } from 'react';
import { useForm } from '@inertiajs/react';

/**
 * TradeEntryForm — BUY/SELL İşlem Formu (Modal)
 *
 * 3 alan: Adet, Birim Fiyat, Toplam — herhangi 2'si girilince 3.'sü otomatik hesaplanır.
 * Komisyon, tarih, not alanları opsiyonel.
 */

interface TradeEntryFormProps {
    isOpen: boolean;
    onClose: () => void;
}

const TradeEntryForm: React.FC<TradeEntryFormProps> = ({ isOpen, onClose }) => {
    const [autoField, setAutoField] = useState<'quantity' | 'unit_price' | 'total_amount' | null>(null);

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

                    {/* Asset ID */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Varlık ID
                        </label>
                        <input
                            type="text"
                            value={data.asset_id}
                            onChange={(e) => setData('asset_id', e.target.value)}
                            placeholder="Varlık seçin..."
                            className="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                        />
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
