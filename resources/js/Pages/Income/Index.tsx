import { Head, usePage } from '@inertiajs/react';
import { useState, FormEvent } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import CategoryPicker from '@/Components/expenses/CategoryPicker';
import { Save, AlertCircle, Ban } from 'lucide-react';
import DatePicker, { registerLocale } from 'react-datepicker';
import { tr } from 'date-fns/locale/tr';
import 'react-datepicker/dist/react-datepicker.css';

registerLocale('tr', tr);

interface Income {
    id: string;
    amount: string;
    currency: string;
    income_date: string;
    notes: string | null;
    is_void: boolean;
    category: {
        id: string;
        name: string;
        icon: string | null;
        color: string | null;
    };
    created_at: string;
}

export default function IncomeIndex() {
    const queryClient = useQueryClient();
    const [amount, setAmount] = useState('');
    const [currency, setCurrency] = useState('TRY');
    const [categoryId, setCategoryId] = useState<string | null>(null);
    const [incomeDate, setIncomeDate] = useState<Date>(new Date());
    const [notes, setNotes] = useState('');
    const [error, setError] = useState('');

    const { data: incomes = [], isLoading } = useQuery<Income[]>({
        queryKey: ['incomes'],
        queryFn: async () => {
            const { data } = await axios.get('/api/incomes');
            return data;
        },
    });

    const createMutation = useMutation({
        mutationFn: async (payload: any) => {
            const { data } = await axios.post('/api/incomes', payload);
            return data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['incomes'] });
            setAmount('');
            setNotes('');
            setError('');
        },
        onError: (err: any) => {
            setError(err.response?.data?.message || err.response?.data?.error || 'Kayıt başarısız.');
        }
    });

    const voidMutation = useMutation({
        mutationFn: async (id: string) => {
            const { data } = await axios.post(`/api/incomes/${id}/void`);
            return data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['incomes'] });
        }
    });

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        setError('');

        if (!categoryId) {
            setError('Lütfen bir kategori seçiniz.');
            return;
        }

        if (Number(amount) <= 0) {
            setError('Tutar sıfırdan büyük olmalıdır.');
            return;
        }

        createMutation.mutate({
            amount,
            currency,
            category_id: categoryId,
            income_date: incomeDate.toISOString().split('T')[0],
            notes
        });
    };

    return (
        <AuthenticatedLayout header={<h2 className="font-semibold text-xl text-gray-900 dark:text-slate-100 leading-tight">Gelir Yönetimi</h2>}>
            <Head title="Gelirler" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 flex flex-col lg:flex-row gap-6">
                    
                    {/* Left: Input Form */}
                    <div className="w-full lg:w-1/3">
                        <div className="bg-white dark:bg-slate-900 overflow-hidden shadow-sm shadow-emerald-900/20 sm:rounded-2xl border border-emerald-900/30">
                            <div className="p-6">
                                <h3 className="text-lg font-medium text-emerald-400 mb-6">Yeni Gelir Ekle</h3>

                                {error && (
                                    <div className="mb-6 bg-rose-500/10 border border-rose-500/20 rounded-xl p-4 flex gap-3 text-rose-400">
                                        <AlertCircle size={20} className="shrink-0" />
                                        <span className="text-sm">{error}</span>
                                    </div>
                                )}

                                <form onSubmit={handleSubmit} className="flex flex-col gap-5">
                                    <div className="flex gap-3">
                                        <div className="flex-1">
                                            <label className="block text-sm font-medium text-gray-600 dark:text-slate-400 mb-1">Tutar</label>
                                            <input 
                                                type="number"
                                                step="0.01"
                                                value={amount}
                                                onChange={e => setAmount(e.target.value)}
                                                className="bg-emerald-950/20 border-emerald-900/50 text-emerald-200 text-lg rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block w-full outline-none transition-all py-3 px-4"
                                                placeholder="0.00"
                                                required
                                            />
                                        </div>
                                        <div className="w-24">
                                            <label className="block text-sm font-medium text-gray-600 dark:text-slate-400 mb-1">Döviz</label>
                                            <select
                                                value={currency}
                                                onChange={e => setCurrency(e.target.value)}
                                                className="bg-emerald-950/20 border-emerald-900/50 text-emerald-200 text-lg rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block w-full outline-none transition-all py-3 px-4"
                                            >
                                                <option value="TRY">TRY</option>
                                                <option value="USD">USD</option>
                                                <option value="EUR">EUR</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div>
                                        <CategoryPicker 
                                            direction="INCOME" 
                                            selectedId={categoryId} 
                                            onChange={setCategoryId} 
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-600 dark:text-slate-400 mb-1">Tarih</label>
                                        <DatePicker
                                            selected={incomeDate}
                                            onChange={(date: Date | null) => setIncomeDate(date || new Date())}
                                            dateFormat="dd.MM.yyyy"
                                            locale="tr"
                                            className="bg-white dark:bg-slate-900/50 border border-gray-300 dark:border-slate-700 text-gray-800 dark:text-slate-200 text-sm rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 block w-full p-3 transition-shadow"
                                            wrapperClassName="w-full"
                                            required
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-600 dark:text-slate-400 mb-1">Notlar (Opsiyonel)</label>
                                        <textarea
                                            value={notes}
                                            onChange={e => setNotes(e.target.value)}
                                            rows={2}
                                            className="bg-gray-50 dark:bg-slate-950/50 border-gray-200 dark:border-slate-800 text-gray-800 dark:text-slate-200 text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block w-full p-3 resize-none"
                                            placeholder="Detaylar..."
                                        />
                                    </div>

                                    <button
                                        type="submit"
                                        disabled={createMutation.isPending}
                                        className="w-full mt-2 text-gray-900 dark:text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 shadow-lg shadow-emerald-500/20 font-medium rounded-xl text-sm px-5 py-3.5 text-center flex items-center justify-center gap-2 transition-all disabled:opacity-70"
                                    >
                                        {createMutation.isPending ? 'Ekleniyor...' : <><Save size={18} /> Gelir Ekle</>}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    {/* Right: Ledger Data Table */}
                    <div className="w-full lg:w-2/3">
                        <div className="bg-white dark:bg-slate-900 shadow-sm shadow-emerald-900/10 sm:rounded-2xl border border-gray-200 dark:border-slate-800 h-full flex flex-col">
                            <div className="px-6 py-5 border-b border-gray-200 dark:border-slate-800 flex justify-between items-center">
                                <h3 className="text-lg font-medium text-gray-900 dark:text-white">Gelir Geçmişi</h3>
                            </div>
                            
                            <div className="flex-1 overflow-auto p-0">
                                {isLoading ? (
                                    <div className="p-6 text-center text-slate-500 animate-pulse">Yükleniyor...</div>
                                ) : incomes.length === 0 ? (
                                    <div className="p-12 text-center text-slate-500">
                                        <div className="text-4xl mb-4">💰</div>
                                        <p>Henüz hiçbir gelir kaydı bulunmuyor.</p>
                                    </div>
                                ) : (
                                    <table className="w-full text-left border-collapse">
                                        <thead>
                                            <tr className="border-b border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900/50 text-gray-600 dark:text-slate-400 text-xs uppercase tracking-wider">
                                                <th className="px-6 py-4 font-medium">Tarih</th>
                                                <th className="px-6 py-4 font-medium">Kategori</th>
                                                <th className="px-6 py-4 font-medium text-right">Tutar</th>
                                                <th className="px-6 py-4 font-medium">Durum</th>
                                                <th className="px-6 py-4 font-medium text-right">İşlemler</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-800/50 text-sm">
                                            {incomes.map((inc: Income) => (
                                                <tr key={inc.id} className={`group transition-colors hover:bg-gray-100 dark:bg-slate-800/30 ${inc.is_void ? 'opacity-50' : ''}`}>
                                                    <td className="px-6 py-4 whitespace-nowrap text-gray-700 dark:text-slate-300">
                                                        {new Date(inc.income_date).toLocaleDateString('tr-TR')}
                                                    </td>
                                                    <td className="px-6 py-4">
                                                        <div className="flex items-center gap-2">
                                                            <div 
                                                                className="w-8 h-8 rounded-lg flex items-center justify-center text-lg"
                                                                style={{ backgroundColor: `${inc.category?.color}20` || '#333' }}
                                                            >
                                                                {inc.category?.icon || '📌'}
                                                            </div>
                                                            <div>
                                                                <div className={`font-medium ${inc.is_void ? 'line-through text-slate-500' : 'text-gray-800 dark:text-slate-200'}`}>
                                                                    {inc.category?.name || 'Bilinmiyor'}
                                                                </div>
                                                                {inc.notes && (
                                                                    <div className="text-xs text-slate-500 truncate max-w-[150px]">
                                                                    {inc.notes}
                                                                    </div>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 text-right whitespace-nowrap font-medium text-emerald-400">
                                                        +{Number(inc.amount).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} {inc.currency}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        {inc.is_void ? (
                                                            <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-slate-400 border border-gray-300 dark:border-slate-700">
                                                                İptal Edildi
                                                            </span>
                                                        ) : (
                                                            <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                                                Geçerli
                                                            </span>
                                                        )}
                                                    </td>
                                                    <td className="px-6 py-4 text-right whitespace-nowrap">
                                                        {!inc.is_void && (
                                                            <button
                                                                onClick={() => {
                                                                    if(confirm('Append-only mimarisi gereği bu işlem silinmez, İPTAL EDİLMİŞ (void) olarak işaretlenir. Emin misiniz?')) {
                                                                        voidMutation.mutate(inc.id);
                                                                    }
                                                                }}
                                                                disabled={voidMutation.isPending}
                                                                className="text-slate-500 hover:text-rose-400 transition-colors p-2 rounded-lg hover:bg-gray-100 dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-rose-500/50"
                                                                title="İşlemi İptal Et (Void)"
                                                            >
                                                                <Ban size={18} />
                                                            </button>
                                                        )}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                )}
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </AuthenticatedLayout>
    );
}
