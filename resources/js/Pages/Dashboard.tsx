import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { 
    Wallet, TrendingUp, TrendingDown, Activity, 
    ArrowUpRight, ArrowDownRight, Clock, Box 
} from 'lucide-react';

export default function Dashboard() {
    // 1. Gider/Gelir Özeti (Aylık)
    const { data: summary, isLoading: isSummaryLoading } = useQuery({
        queryKey: ['dashboard-summary'],
        queryFn: async () => {
            const { data } = await axios.get('/api/dashboard/summary');
            return data;
        },
        staleTime: 60 * 1000,      // 60 saniye boyunca "fresh" sayılır
        gcTime: 5 * 60 * 1000,     // 5 dakika cache'te kalır
    });

    // 2. Portföy Özeti
    const { data: portfolio } = useQuery({
        queryKey: ['portfolio-summary'],
        queryFn: async () => {
            try {
                const { data } = await axios.get('/api/portfolio/summary');
                return data;
            } catch {
                return { total_value: 0, total_profit: 0, profit_percentage: 0 };
            }
        },
        staleTime: 60 * 1000,
        gcTime: 5 * 60 * 1000,
    });

    // 3. Son İşlemler (Karma Feed)
    const { data: activities = [], isLoading: isActivitiesLoading } = useQuery({
        queryKey: ['dashboard-activities'],
        queryFn: async () => {
            const { data } = await axios.get('/api/dashboard/activities');
            return data;
        },
        staleTime: 30 * 1000,      // Aktiviteler daha sık değişebilir
        gcTime: 5 * 60 * 1000,
    });

    // Toplam Net Varlık (Kasa/Banka bakiyesi = Tüm Gelir - Tüm Gider. Şimdilik aylık bakiyeyi alıyoruz ama genelde tüm zamanlar istenir)
    // Şimdilik Net Varlık = Portföy Değeri + Aylık Net Bilanço
    const netWorth = (portfolio?.total_value || 0) + (summary?.monthly_balance || 0);

    const formatMoney = (val: number) => {
        return new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(val || 0);
    };

    return (
        <AuthenticatedLayout
            header={<h2 className="font-semibold text-xl text-gray-900 dark:text-slate-100 leading-tight">Genel Bakış</h2>}
        >
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 flex flex-col gap-8">
                    
                    {/* Üst Kartlar (Tepe Metrikleri) */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        
                        {/* 1. Net Varlık */}
                        <div className="bg-gradient-to-br from-indigo-900 to-slate-900 border border-indigo-500/20 rounded-2xl p-6 shadow-xl relative overflow-hidden">
                            <div className="absolute top-0 right-0 p-4 opacity-10">
                                <Wallet size={120} />
                            </div>
                            <div className="flex items-center gap-3 mb-4">
                                <div className="p-2.5 bg-indigo-500/20 rounded-xl text-indigo-400">
                                    <Wallet size={24} />
                                </div>
                                <h3 className="text-gray-700 dark:text-slate-300 font-medium">Toplam Net Varlık</h3>
                            </div>
                            <div className="text-4xl font-bold text-gray-900 dark:text-white mb-2">
                                {formatMoney(netWorth)}
                            </div>
                            <p className="text-sm text-gray-600 dark:text-slate-400">Portföy Yatırımları + Nakit Bakiye</p>
                        </div>

                        {/* 2. Aylık Bilanço */}
                        <div className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-6 shadow-md relative overflow-hidden">
                            <div className="flex justify-between items-start mb-4">
                                <div className="flex items-center gap-3">
                                    <div className="p-2.5 bg-gray-100 dark:bg-slate-800 rounded-xl text-gray-700 dark:text-slate-300">
                                        <Activity size={24} />
                                    </div>
                                    <h3 className="text-gray-700 dark:text-slate-300 font-medium">Bu Ayki Bilanço</h3>
                                </div>
                                <span className="bg-gray-100 dark:bg-slate-800 text-xs px-2.5 py-1 rounded-md text-gray-600 dark:text-slate-400">TR</span>
                            </div>
                            <div className={`text-3xl font-bold mb-4 ${
                                (summary?.monthly_balance || 0) >= 0 ? 'text-emerald-400' : 'text-rose-400'
                            }`}>
                                {(summary?.monthly_balance || 0) >= 0 ? '+' : ''}{formatMoney(summary?.monthly_balance || 0)}
                            </div>
                            <div className="flex gap-4 text-sm mt-auto">
                                <div className="flex items-center gap-1.5 text-emerald-500">
                                    <ArrowUpRight size={16} /> <span>{formatMoney(summary?.monthly_income || 0)} Gelir</span>
                                </div>
                                <div className="flex items-center gap-1.5 text-rose-500">
                                    <ArrowDownRight size={16} /> <span>{formatMoney(summary?.monthly_expense || 0)} Gider</span>
                                </div>
                            </div>
                        </div>

                        {/* 3. Portföy Durumu (Mock/Muhammed) */}
                        <div className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-6 shadow-md relative overflow-hidden">
                            <div className="flex justify-between items-start mb-4">
                                <div className="flex items-center gap-3">
                                    <div className="p-2.5 bg-gray-100 dark:bg-slate-800 rounded-xl text-gray-700 dark:text-slate-300">
                                        <Box size={24} />
                                    </div>
                                    <h3 className="text-gray-700 dark:text-slate-300 font-medium">Yatırım Portföyü</h3>
                                </div>
                            </div>
                            <div className="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                                {formatMoney(portfolio?.total_value || 0)}
                            </div>
                            <div className="flex items-center gap-2">
                                <span className={`inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-semibold ${
                                    (portfolio?.total_profit || 0) >= 0 ? 'bg-emerald-500/10 text-emerald-500' : 'bg-rose-500/10 text-rose-500'
                                }`}>
                                    {(portfolio?.total_profit || 0) >= 0 ? <TrendingUp size={14}/> : <TrendingDown size={14}/>}
                                    {Math.abs(portfolio?.profit_percentage || 0).toFixed(2)}%
                                </span>
                                <span className="text-sm text-slate-500">Tüm zamanlar PnL</span>
                            </div>
                        </div>

                    </div>

                    {/* Alt Bölge (Grafik & Aktiviteler) */}
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        
                        {/* Sol Grafik (CSS Custom Bar) */}
                        <div className="lg:col-span-2 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl shadow-md p-6">
                            <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-6">Aylık Para Akışı Dağılımı</h3>
                            
                            <div className="flex flex-col gap-8">
                                {/* Gelir Bar */}
                                <div>
                                    <div className="flex justify-between mb-2">
                                        <span className="text-sm font-medium text-emerald-400">Toplam Gelir Hacmi</span>
                                        <span className="text-sm font-medium text-gray-700 dark:text-slate-300">{formatMoney(summary?.monthly_income || 0)}</span>
                                    </div>
                                    <div className="w-full bg-gray-100 dark:bg-slate-800 rounded-full h-4 overflow-hidden shadow-inner">
                                        <div className="bg-emerald-500 h-4 rounded-full transition-all duration-1000" style={{ width: '100%' }}></div>
                                    </div>
                                </div>

                                {/* Gider Segmentleri Bar - Mock Orantısı */}
                                <div>
                                    <div className="flex justify-between mb-2">
                                        <span className="text-sm font-medium text-rose-400">Tüketilen Gider Hacmi (Gelire Oranı)</span>
                                        <span className="text-sm font-medium text-gray-700 dark:text-slate-300">
                                            {summary?.monthly_income ? `${((summary.monthly_expense / summary.monthly_income) * 100).toFixed(1)}%` : '0%'}
                                        </span>
                                    </div>
                                    <div className="w-full bg-gray-100 dark:bg-slate-800 rounded-full h-4 overflow-hidden flex shadow-inner">
                                        {/* CSS Segment Bar logic */}
                                        <div className="bg-gradient-to-r from-rose-600 to-rose-400 h-4 transition-all duration-1000" 
                                             style={{ width: `${Math.min(((summary?.monthly_expense || 0) / (summary?.monthly_income || 1)) * 100, 100)}%` }}>
                                        </div>
                                    </div>
                                    <p className="text-xs text-slate-500 mt-2">Bu çubuk aylık kazancınızın ne kadarını yediğinizi gösterir. Düşük tutmaya çalışın.</p>
                                </div>
                            </div>
                        </div>

                        {/* Sağ Aktivite Akışı */}
                        <div className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl shadow-md flex flex-col h-full max-h-[400px]">
                            <div className="p-5 border-b border-gray-200 dark:border-slate-800">
                                <h3 className="text-md font-medium text-gray-900 dark:text-white flex items-center gap-2">
                                    <Clock size={16} className="text-gray-600 dark:text-slate-400"/> Son Aktiviteler
                                </h3>
                            </div>
                            <div className="p-0 overflow-y-auto flex-1">
                                {isActivitiesLoading ? (
                                    <div className="p-6 text-center text-slate-500">Yükleniyor...</div>
                                ) : activities.length === 0 ? (
                                    <div className="p-6 text-center text-slate-500 text-sm">Hareket bulunamadı.</div>
                                ) : (
                                    <ul className="divide-y divide-slate-800/50">
                                        {activities.map((act: any) => (
                                            <li key={act.id} className={`p-4 hover:bg-gray-100 dark:bg-slate-800/30 transition-colors ${act.is_void ? 'opacity-40' : ''}`}>
                                                <div className="flex items-center justify-between">
                                                    <div className="flex items-center gap-3">
                                                        <div className={`p-2 rounded-lg ${
                                                            act.type === 'INCOME' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400'
                                                        }`}>
                                                            {act.type === 'INCOME' ? <ArrowDownRight size={16}/> : <ArrowUpRight size={16}/>}
                                                        </div>
                                                        <div>
                                                            <p className={`text-sm font-medium ${act.is_void ? 'line-through text-slate-500' : 'text-gray-800 dark:text-slate-200'}`}>
                                                                {act.category?.name || 'Bilinmiyor'}
                                                            </p>
                                                            <p className="text-xs text-slate-500">
                                                                {new Date(act.created_at).toLocaleDateString('tr-TR')}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div className={`text-sm font-medium ${act.type === 'INCOME' ? 'text-emerald-400' : 'text-rose-400'}`}>
                                                        {act.type === 'INCOME' ? '+' : '-'}{Number(act.amount).toLocaleString('tr-TR')}
                                                    </div>
                                                </div>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
