import React, { useEffect, useState } from 'react';
import axios from 'axios';
import { Wallet, Building2, CreditCard, Smartphone } from 'lucide-react';

interface Account {
    id: string;
    name: string;
    type: 'CASH' | 'BANK' | 'CREDIT_CARD' | 'E_WALLET';
    currency: string;
    balance: string;
    color: string | null;
}

interface AccountPickerProps {
    selectedId?: string;
    onChange: (id: string) => void;
    error?: string;
}

export default function AccountPicker({ selectedId, onChange, error }: AccountPickerProps) {
    const [accounts, setAccounts] = useState<Account[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        axios.get('/api/accounts')
            .then(res => {
                setAccounts(res.data);
                setLoading(false);
            })
            .catch(() => setLoading(false));
    }, []);

    const getTypeIcon = (type: string) => {
        switch (type) {
            case 'CASH': return <Wallet size={18} className="text-emerald-400" />;
            case 'BANK': return <Building2 size={18} className="text-blue-400" />;
            case 'CREDIT_CARD': return <CreditCard size={18} className="text-amber-400" />;
            case 'E_WALLET': return <Smartphone size={18} className="text-purple-400" />;
            default: return <Wallet size={18} />;
        }
    };

    if (loading) {
        return <div className="h-12 bg-slate-800 animate-pulse rounded-lg flex items-center px-4"><span className="text-xs text-slate-500">Hesaplar yükleniyor...</span></div>;
    }

    if (accounts.length === 0) {
        return (
            <div className="p-4 border border-dashed border-rose-500/50 rounded-lg bg-rose-500/10 text-rose-400 text-sm">
                Hiç hesabınız bulunmuyor. İşlem yapabilmek için önce bir hesap oluşturun.
            </div>
        );
    }

    return (
        <div>
            <div className="grid grid-cols-2 md:grid-cols-3 gap-3">
                {accounts.map(account => {
                    const isSelected = selectedId === account.id;
                    return (
                        <button
                            key={account.id}
                            type="button"
                            onClick={() => onChange(account.id)}
                            className={`flex items-center gap-3 p-3 rounded-xl border text-left transition-colors ${
                                isSelected 
                                ? 'border-indigo-500 bg-indigo-500/10' 
                                : 'border-slate-700 bg-slate-900 hover:bg-slate-800'
                            }`}
                        >
                            <div className="shrink-0 bg-slate-800 p-2 rounded-lg">
                                {getTypeIcon(account.type)}
                            </div>
                            <div className="truncate">
                                <p className={`text-sm font-medium truncate ${isSelected ? 'text-indigo-400' : 'text-slate-200'}`}>
                                    {account.name}
                                </p>
                                <p className="text-xs text-slate-500">
                                    {Number(account.balance).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} {account.currency}
                                </p>
                            </div>
                        </button>
                    )
                })}
            </div>
            {error && <p className="text-xs text-rose-500 mt-2">{error}</p>}
        </div>
    );
}
