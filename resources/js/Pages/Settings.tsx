import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, usePage } from '@inertiajs/react';
import { Settings, User, Globe, Moon, Sun, Link as LinkIcon, Trash2, CheckCircle2 } from 'lucide-react';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import DangerButton from '@/Components/DangerButton';
import SecondaryButton from '@/Components/SecondaryButton';

interface OauthAccount {
    id: string;
    provider: string;
    provider_id: string;
    created_at: string;
}

interface PageProps {
    auth: {
        user: {
            id: string;
            display_name: string;
            email: string;
            avatar_url: string | null;
            base_currency: string;
            theme: string;
        };
    };
    oauthAccounts: OauthAccount[];
    flash: {
        success?: string;
    }
}

export default function SettingsPage() {
    const { auth, oauthAccounts, flash, errors: pageErrors } = usePage<PageProps>().props;

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center gap-3">
                    <Settings className="text-slate-400" />
                    <h2 className="font-semibold text-xl text-slate-100 leading-tight">Ayarlar & Profil</h2>
                </div>
            }
        >
            <Head title="Ayarlar" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                    
                    {flash.success && (
                        <div className="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 p-4 rounded-xl flex items-center gap-3">
                            <CheckCircle2 size={20} />
                            <span>{flash.success}</span>
                        </div>
                    )}
                    
                    {pageErrors?.oauth && (
                        <div className="bg-rose-500/10 border border-rose-500/20 text-rose-400 p-4 rounded-xl flex items-center gap-3">
                            <Trash2 size={20} />
                            <span>{pageErrors.oauth}</span>
                        </div>
                    )}

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <div className="space-y-8">
                            <ProfileForm />
                            <ThemeToggle />
                        </div>
                        <div className="space-y-8">
                            <BaseCurrencySelector />
                            <LinkedAccountsList oauthAccounts={oauthAccounts} />
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function ProfileForm() {
    const user = usePage<PageProps>().props.auth.user;
    const { data, setData, patch, errors, processing, recentlySuccessful } = useForm({
        display_name: user.display_name || '',
        avatar_url: user.avatar_url || '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        patch(route('settings.profile.update'));
    };

    return (
        <div className="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-md">
            <h3 className="text-lg font-medium text-white mb-6 flex items-center gap-2">
                <User size={20} className="text-indigo-400" /> Profil Bilgileri
            </h3>
            
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <InputLabel htmlFor="display_name" value="Görüntülenen Ad" className="text-slate-300" />
                    <TextInput
                        id="display_name"
                        className="mt-1 block w-full bg-slate-950 border-slate-700 text-slate-100 focus:border-indigo-500 focus:ring-indigo-500"
                        value={data.display_name}
                        onChange={(e) => setData('display_name', e.target.value)}
                        required
                    />
                    <InputError className="mt-2" message={errors.display_name} />
                </div>

                <div>
                    <InputLabel htmlFor="avatar_url" value="Avatar URL" className="text-slate-300" />
                    <div className="flex gap-4 items-center mt-1">
                        {data.avatar_url ? (
                            <img src={data.avatar_url} alt="Avatar" className="w-12 h-12 rounded-full border border-slate-700 object-cover" />
                        ) : (
                            <div className="w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center border border-slate-700 text-slate-500">
                                <User size={24} />
                            </div>
                        )}
                        <TextInput
                            id="avatar_url"
                            type="url"
                            placeholder="https://..."
                            className="block w-full bg-slate-950 border-slate-700 text-slate-100 focus:border-indigo-500 focus:ring-indigo-500"
                            value={data.avatar_url}
                            onChange={(e) => setData('avatar_url', e.target.value)}
                        />
                    </div>
                    <InputError className="mt-2" message={errors.avatar_url} />
                </div>

                <div className="flex items-center gap-4 mt-6">
                    <PrimaryButton disabled={processing} className="bg-indigo-600 hover:bg-indigo-500">
                        Kaydet
                    </PrimaryButton>
                    {recentlySuccessful && <p className="text-sm text-slate-400">Kaydedildi.</p>}
                </div>
            </form>
        </div>
    );
}

function BaseCurrencySelector() {
    const user = usePage<PageProps>().props.auth.user;
    const { data, setData, patch, processing } = useForm({
        base_currency: user.base_currency || 'TRY',
    });

    const currencies = [
        { code: 'TRY', label: 'Türk Lirası (₺)' },
        { code: 'USD', label: 'US Dollar ($)' },
        { code: 'EUR', label: 'Euro (€)' },
    ];

    const changeCurrency = (code: string) => {
        setData('base_currency', code);
        // Automatically submit when changed
        setTimeout(() => {
            patch(route('settings.currency.update'), {
                preserveScroll: true
            });
        }, 100);
    };

    return (
        <div className="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-md">
            <h3 className="text-lg font-medium text-white mb-6 flex items-center gap-2">
                <Globe size={20} className="text-emerald-400" /> Taban Para Birimi
            </h3>
            <p className="text-sm text-slate-400 mb-6">
                Tüm net varlık hesaplamaları ve genel özetler bu para birimi üzerinden gösterilecektir. Gösterim amaçlıdır, geçmiş verilerinizi değiştirmez.
            </p>

            <div className="flex flex-col gap-3">
                {currencies.map(c => (
                    <label key={c.code} className={`flex items-center gap-4 p-4 rounded-xl border cursor-pointer transition-colors ${
                        data.base_currency === c.code 
                        ? 'border-emerald-500/50 bg-emerald-500/10' 
                        : 'border-slate-800 bg-slate-950 hover:bg-slate-800/50'
                    }`}>
                        <input
                            type="radio"
                            name="base_currency"
                            value={c.code}
                            checked={data.base_currency === c.code}
                            onChange={() => changeCurrency(c.code)}
                            className="text-emerald-500 focus:ring-emerald-500 bg-slate-900 border-slate-700"
                        />
                        <span className={`font-medium ${data.base_currency === c.code ? 'text-emerald-400' : 'text-slate-300'}`}>
                            {c.code} — {c.label}
                        </span>
                    </label>
                ))}
            </div>
            {processing && <p className="text-sm text-emerald-500 mt-4 animate-pulse">Güncelleniyor...</p>}
        </div>
    );
}

function ThemeToggle() {
    const user = usePage<PageProps>().props.auth.user;
    const { data, setData, patch, processing } = useForm({
        theme: user.theme || 'dark',
    });

    const toggleTheme = (newTheme: 'light' | 'dark') => {
        setData('theme', newTheme);
        
        if (newTheme === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }

        setTimeout(() => {
            patch(route('settings.theme.update'), { preserveScroll: true });
        }, 100);
    };

    return (
        <div className="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-md">
            <h3 className="text-lg font-medium text-white mb-6 flex items-center gap-2">
                {data.theme === 'dark' ? <Moon size={20} className="text-indigo-400"/> : <Sun size={20} className="text-amber-400"/>}
                Tema Görünümü
            </h3>
            
            <div className="grid grid-cols-2 gap-4">
                <button
                    onClick={() => toggleTheme('dark')}
                    className={`p-4 rounded-xl border flex flex-col items-center gap-3 transition-colors ${
                        data.theme === 'dark' 
                        ? 'border-indigo-500/50 bg-indigo-500/10 text-indigo-400' 
                        : 'border-slate-800 bg-slate-950 text-slate-400 hover:bg-slate-800/50'
                    }`}
                >
                    <Moon size={28} />
                    <span className="font-medium">Karanlık Tema</span>
                </button>

                <button
                    onClick={() => toggleTheme('light')}
                    className={`p-4 rounded-xl border flex flex-col items-center gap-3 transition-colors ${
                        data.theme === 'light' 
                        ? 'border-amber-500/50 bg-amber-500/10 text-amber-500' 
                        : 'border-slate-800 bg-slate-950 text-slate-400 hover:bg-slate-800/50'
                    }`}
                >
                    <Sun size={28} />
                    <span className="font-medium">Aydınlık Tema</span>
                </button>
            </div>
            {processing && <p className="text-sm text-indigo-400 mt-4 animate-pulse">Tema kaydediliyor...</p>}
        </div>
    );
}

function LinkedAccountsList({ oauthAccounts }: { oauthAccounts: OauthAccount[] }) {
    const { delete: destroy, processing } = useForm();
    const user = usePage<PageProps>().props.auth.user;

    const removeAccount = (id: string, provider: string) => {
        if (confirm(`Emin misiniz? ${provider} bağlantısı kaldırılacak.`)) {
            destroy(route('settings.linked-accounts.destroy', id), {
                preserveScroll: true
            });
        }
    };

    return (
        <div className="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-md">
            <h3 className="text-lg font-medium text-white mb-6 flex items-center gap-2">
                <LinkIcon size={20} className="text-blue-400" /> Bağlı Hesaplar
            </h3>
            
            <div className="space-y-4">
                <div className="flex items-center gap-4 p-4 rounded-xl border border-slate-800 bg-slate-950">
                    <div className="bg-slate-800 p-3 rounded-full text-slate-300">
                        <User size={24} />
                    </div>
                    <div className="flex-1">
                        <p className="text-slate-200 font-medium">E-Posta (Yerel Hesap)</p>
                        <p className="text-sm text-slate-500">{user.email}</p>
                    </div>
                    <span className="text-xs font-semibold bg-indigo-500/20 text-indigo-400 px-2.5 py-1 rounded">Ana Hesap</span>
                </div>

                {oauthAccounts && oauthAccounts.map(account => (
                    <div key={account.id} className="flex items-center gap-4 p-4 rounded-xl border border-slate-800 bg-slate-950">
                        <div className="bg-white p-3 rounded-full">
                            {/* Simple Google G icon placeholder */}
                            <svg className="w-6 h-6 text-slate-800" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12.545,10.239v3.821h5.445c-0.712,2.315-2.647,3.972-5.445,3.972c-3.332,0-6.033-2.701-6.033-6.032s2.701-6.032,6.033-6.032c1.498,0,2.866,0.549,3.921,1.453l2.814-2.814C17.503,2.988,15.139,2,12.545,2C7.021,2,2.543,6.477,2.543,12s4.478,10,10.002,10c8.396,0,10.249-7.85,9.426-11.748L12.545,10.239z"/>
                            </svg>
                        </div>
                        <div className="flex-1">
                            <p className="text-slate-200 font-medium capitalize">{account.provider} ile Bağlı</p>
                            <p className="text-sm text-slate-500">Bağlantı Tarihi: {new Date(account.created_at).toLocaleDateString('tr-TR')}</p>
                        </div>
                        <DangerButton 
                            onClick={() => removeAccount(account.id, account.provider)}
                            disabled={processing}
                            className="bg-rose-500/10 text-rose-500 hover:bg-rose-500/20 border border-rose-500/20 shadow-none px-3 py-1.5 text-xs font-medium"
                        >
                            <Trash2 size={16} className="mr-1" /> Kaldır
                        </DangerButton>
                    </div>
                ))}
                
                {(!oauthAccounts || oauthAccounts.length === 0) && (
                    <div className="text-center p-6 border border-dashed border-slate-800 rounded-xl">
                        <p className="text-sm text-slate-500">Başka bağlı hesap bulunmuyor.</p>
                        <a href="/auth/google" className="mt-4 inline-flex items-center px-4 py-2 bg-white border border-transparent rounded-md font-semibold text-xs text-slate-800 uppercase tracking-widest hover:bg-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <svg className="w-4 h-4 mr-2" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12.545,10.239v3.821h5.445c-0.712,2.315-2.647,3.972-5.445,3.972c-3.332,0-6.033-2.701-6.032-6.032s2.701-6.032,6.033-6.032c1.498,0,2.866,0.549,3.921,1.453l2.814-2.814C17.503,2.988,15.139,2,12.545,2C7.021,2,2.543,6.477,2.543,12s4.478,10,10.002,10c8.396,0,10.249-7.85,9.426-11.748L12.545,10.239z"/>
                            </svg>
                            Google ile Bağla
                        </a>
                    </div>
                )}
            </div>
        </div>
    );
}
