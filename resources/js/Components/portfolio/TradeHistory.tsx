import React from 'react';
import { InvestmentTransaction } from '@/types/models';
import { useForm } from '@inertiajs/react';

/**
 * TradeHistory — İşlem Geçmişi Tablosu
 *
 * Her satır: tarih, varlık, BUY/SELL badge, adet, fiyat, toplam, iptal butonu.
 * İptal edilen kayıtlar üstü çizgili ve soluk görünür.
 */

interface TradeHistoryProps {
    transactions: InvestmentTransaction[];
}

const TradeHistory: React.FC<TradeHistoryProps> = ({ transactions }) => {
    if (transactions.length === 0) {
        return (
            <div className="text-center py-12 text-gray-500 dark:text-gray-400">
                <span className="text-3xl mb-2 block">📋</span>
                Henüz işlem geçmişi yok.
            </div>
        );
    }

    return (
        <div className="overflow-x-auto">
            <table className="w-full text-sm">
                <thead>
                    <tr className="border-b border-gray-200 dark:border-gray-700">
                        <th className="text-left py-3 px-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Tarih</th>
                        <th className="text-left py-3 px-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Varlık</th>
                        <th className="text-left py-3 px-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Yön</th>
                        <th className="text-right py-3 px-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Adet</th>
                        <th className="text-right py-3 px-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Fiyat</th>
                        <th className="text-right py-3 px-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Toplam</th>
                        <th className="text-right py-3 px-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    {transactions.map((tx) => (
                        <TradeHistoryRow key={tx.id} transaction={tx} />
                    ))}
                </tbody>
            </table>
        </div>
    );
};

/**
 * TradeHistoryRow — Tek işlem satırı
 */
const TradeHistoryRow: React.FC<{ transaction: InvestmentTransaction }> = ({ transaction }) => {
    const { data, setData, post, processing } = useForm({ reason: '' });
    const [showVoidModal, setShowVoidModal] = React.useState(false);

    const handleVoid = () => {
        post(route('investments.void', { id: transaction.id }), {
            onSuccess: () => setShowVoidModal(false),
        });
    };

    const isVoid = transaction.is_void;
    const rowClass = isVoid ? 'opacity-40 line-through' : '';

    const formatNum = (val: number, dec: number = 2) =>
        val.toLocaleString('en-US', { minimumFractionDigits: dec, maximumFractionDigits: dec });

    return (
        <>
            <tr className={`border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors ${rowClass}`}>
                <td className="py-3 px-4 text-gray-600 dark:text-gray-300">
                    {new Date(transaction.transaction_date).toLocaleDateString('tr-TR')}
                </td>
                <td className="py-3 px-4">
                    <span className="font-medium text-gray-900 dark:text-white">
                        {transaction.asset?.symbol ?? '—'}
                    </span>
                </td>
                <td className="py-3 px-4">
                    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold ${
                        transaction.side === 'BUY'
                            ? 'bg-emerald-500/10 text-emerald-400'
                            : 'bg-red-500/10 text-red-400'
                    }`}>
                        {transaction.side === 'BUY' ? 'AL' : 'SAT'}
                    </span>
                </td>
                <td className="py-3 px-4 text-right font-mono text-gray-900 dark:text-white">
                    {formatNum(transaction.quantity, 4)}
                </td>
                <td className="py-3 px-4 text-right font-mono text-gray-900 dark:text-white">
                    {new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(transaction.unit_price)}
                </td>
                <td className="py-3 px-4 text-right font-mono font-medium text-gray-900 dark:text-white">
                    {new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(transaction.total_amount)}
                </td>
                <td className="py-3 px-4 text-right">
                    {!isVoid && (
                        <button
                            onClick={() => setShowVoidModal(true)}
                            className="text-xs text-red-400 hover:text-red-300 font-medium transition-colors"
                        >
                            İptal
                        </button>
                    )}
                    {isVoid && (
                        <span className="text-xs text-gray-400">İptal edildi</span>
                    )}
                </td>
            </tr>

            {/* Void onay modal */}
            {showVoidModal && (
                <tr>
                    <td colSpan={7} className="p-0">
                        <div className="bg-red-500/5 border border-red-500/20 rounded-lg m-2 p-4">
                            <p className="text-sm text-gray-700 dark:text-gray-300 mb-2">
                                Bu işlemi iptal etmek istediğinize emin misiniz?
                            </p>
                            <input
                                type="text"
                                value={data.reason}
                                onChange={(e) => setData('reason', e.target.value)}
                                placeholder="İptal sebebi yazın..."
                                className="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm mb-2"
                            />
                            <div className="flex gap-2 justify-end">
                                <button
                                    onClick={() => setShowVoidModal(false)}
                                    className="px-3 py-1.5 text-xs text-gray-500 hover:text-gray-700 transition-colors"
                                >
                                    Vazgeç
                                </button>
                                <button
                                    onClick={handleVoid}
                                    disabled={processing || !data.reason}
                                    className="px-3 py-1.5 text-xs bg-red-500 text-gray-900 dark:text-white rounded-lg hover:bg-red-600 disabled:opacity-50 transition-all"
                                >
                                    İptal Et
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
            )}
        </>
    );
};

export default TradeHistory;
