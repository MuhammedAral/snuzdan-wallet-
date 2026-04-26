import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import AssetClassTabs from '@/Components/portfolio/AssetClassTabs';
import PositionList from '@/Components/portfolio/PositionList';
import TradeEntryForm from '@/Components/portfolio/TradeEntryForm';
import TradeHistory from '@/Components/portfolio/TradeHistory';
import AllocationPieChart from '@/Components/portfolio/AllocationPieChart';
import { Position, PortfolioSummary, InvestmentTransaction, AssetClass, PaginatedData } from '@/types/models';

/**
 * Portfolio/Index — Portföy Ana Sayfası
 *
 * Üst: Özet kartları (toplam değer, unrealized PnL, realized PnL)
 * Orta: AssetClassTabs + PositionList + AllocationPieChart
 * Alt: TradeHistory tablosu
 * Modal: TradeEntryForm (İşlem Ekle butonu ile açılır)
 *
 * @see TASKS.md — Görev M-18
 */

interface PortfolioPageProps {
    positions: Position[];
    summary: PortfolioSummary;
    transactions?: PaginatedData<InvestmentTransaction>;
}

export default function Index({ positions = [], summary, transactions }: PortfolioPageProps) {
    const [activeTab, setActiveTab] = useState<AssetClass | 'ALL'>('ALL');
    const [showTradeForm, setShowTradeForm] = useState(false);

    const formatCurrency = (val: number) =>
        val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const isProfitable = (summary?.total_unrealized ?? 0) >= 0;

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        💼 Portföy
                    </h2>
                    <button
                        onClick={() => setShowTradeForm(true)}
                        className="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-gray-900 dark:text-white text-sm font-medium rounded-lg transition-colors shadow-sm"
                    >
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                        </svg>
                        İşlem Ekle
                    </button>
                </div>
            }
        >
            <Head title="Portföy" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

                    {/* Özet Kartları */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        {/* Toplam Değer */}
                        <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                            <p className="text-sm text-gray-500 dark:text-gray-400 mb-1">Toplam Portföy Değeri</p>
                            <p className="text-2xl font-bold text-gray-900 dark:text-white">
                                ${formatCurrency(summary?.total_value ?? 0)}
                            </p>
                            <p className="text-xs text-gray-400 mt-1">
                                {summary?.position_count ?? 0} açık pozisyon
                            </p>
                        </div>

                        {/* Unrealized PnL */}
                        <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                            <p className="text-sm text-gray-500 dark:text-gray-400 mb-1">Gerçekleşmemiş K/Z</p>
                            <p className={`text-2xl font-bold ${isProfitable ? 'text-emerald-400' : 'text-red-400'}`}>
                                {isProfitable ? '+' : ''}${formatCurrency(summary?.total_unrealized ?? 0)}
                            </p>
                            <p className="text-xs text-gray-400 mt-1">Açık pozisyonlar</p>
                        </div>

                        {/* Realized PnL */}
                        <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                            <p className="text-sm text-gray-500 dark:text-gray-400 mb-1">Gerçekleşmiş K/Z (FIFO)</p>
                            <p className={`text-2xl font-bold ${(summary?.total_realized ?? 0) >= 0 ? 'text-emerald-400' : 'text-red-400'}`}>
                                {(summary?.total_realized ?? 0) >= 0 ? '+' : ''}${formatCurrency(summary?.total_realized ?? 0)}
                            </p>
                            <p className="text-xs text-gray-400 mt-1">Kapatılan pozisyonlar</p>
                        </div>
                    </div>

                    {/* Tabs + Dağılım */}
                    <div className="flex flex-col lg:flex-row gap-6">
                        {/* Sol: Tabs + Pozisyonlar */}
                        <div className="flex-1 space-y-4">
                            <AssetClassTabs activeTab={activeTab} onChange={setActiveTab} />
                            <PositionList positions={positions} activeTab={activeTab} />
                        </div>

                        {/* Sağ: Dağılım Pie Chart */}
                        <div className="lg:w-80">
                            <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                                <h3 className="text-sm font-semibold text-gray-900 dark:text-white mb-4">
                                    Portföy Dağılımı
                                </h3>
                                <AllocationPieChart
                                    allocation={summary?.allocation ?? {}}
                                    totalValue={summary?.total_value ?? 0}
                                />
                            </div>
                        </div>
                    </div>

                    {/* İşlem Geçmişi */}
                    <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                        <h3 className="text-sm font-semibold text-gray-900 dark:text-white mb-4">
                            📋 İşlem Geçmişi
                        </h3>
                        <TradeHistory transactions={transactions?.data ?? []} />
                    </div>

                </div>
            </div>

            {/* Trade Entry Modal */}
            <TradeEntryForm isOpen={showTradeForm} onClose={() => setShowTradeForm(false)} />
        </AuthenticatedLayout>
    );
}
