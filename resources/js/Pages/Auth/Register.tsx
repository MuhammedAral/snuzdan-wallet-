import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
import { Mail, Lock, UserPlus, User, AlertCircle } from 'lucide-react';
import { motion } from 'framer-motion';
import { z } from 'zod';

const registerSchema = z.object({
    name: z.string().min(2, 'Ad Soyad en az 2 karakter olmalıdır.'),
    email: z.string().email('Geçerli bir e-posta adresi giriniz.'),
    password: z.string().min(8, 'Şifre en az 8 karakter olmalıdır.'),
    password_confirmation: z.string(),
}).refine((data) => data.password === data.password_confirmation, {
    message: "Şifreler eşleşmiyor.",
    path: ["password_confirmation"],
});

export default function Register() {
    const { data, setData, post, processing, errors: serverErrors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const [clientErrors, setClientErrors] = useState<Record<string, string>>({});

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        setClientErrors({});

        const result = registerSchema.safeParse(data);
        if (!result.success) {
            const formatted = result.error.format();
            setClientErrors({
                name: formatted.name?._errors[0] || '',
                email: formatted.email?._errors[0] || '',
                password: formatted.password?._errors[0] || '',
                password_confirmation: formatted.password_confirmation?._errors[0] || '',
            });
            return;
        }

        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Kayıt Ol" />

            <div className="text-center mb-8">
                <h1 className="text-3xl font-bold text-white mb-2 tracking-tight">Hesap Oluştur</h1>
                <p className="text-sm text-slate-400">Finansal yolculuğunuza bugün başlayın</p>
            </div>

            <form onSubmit={submit} className="flex flex-col gap-4">
                <div>
                    <label className="block text-sm font-medium text-slate-300 mb-1" htmlFor="name">
                        Ad Soyad
                    </label>
                    <div className="relative">
                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                            <User size={18} />
                        </div>
                        <input
                            id="name"
                            type="text"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            className="bg-slate-900/50 border border-slate-700 text-slate-200 text-sm rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-10 p-3 transition-shadow"
                            placeholder="John Doe"
                            required
                        />
                    </div>
                    {(clientErrors.name || serverErrors.name) && (
                        <p className="mt-1.5 text-xs text-rose-400 flex items-center gap-1">
                            <AlertCircle size={12} /> {clientErrors.name || serverErrors.name}
                        </p>
                    )}
                </div>

                <div>
                    <label className="block text-sm font-medium text-slate-300 mb-1" htmlFor="email">
                        E-posta Adresi
                    </label>
                    <div className="relative">
                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                            <Mail size={18} />
                        </div>
                        <input
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            className="bg-slate-900/50 border border-slate-700 text-slate-200 text-sm rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-10 p-3 transition-shadow"
                            placeholder="ornek@snuzdan.test"
                            required
                        />
                    </div>
                    {(clientErrors.email || serverErrors.email) && (
                        <p className="mt-1.5 text-xs text-rose-400 flex items-center gap-1">
                            <AlertCircle size={12} /> {clientErrors.email || serverErrors.email}
                        </p>
                    )}
                </div>

                <div>
                    <label className="block text-sm font-medium text-slate-300 mb-1" htmlFor="password">
                        Şifre
                    </label>
                    <div className="relative">
                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                            <Lock size={18} />
                        </div>
                        <input
                            id="password"
                            type="password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            className="bg-slate-900/50 border border-slate-700 text-slate-200 text-sm rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-10 p-3 transition-shadow"
                            placeholder="••••••••"
                            required
                        />
                    </div>
                    {(clientErrors.password || serverErrors.password) && (
                        <p className="mt-1.5 text-xs text-rose-400 flex items-center gap-1">
                            <AlertCircle size={12} /> {clientErrors.password || serverErrors.password}
                        </p>
                    )}
                </div>

                <div>
                    <label className="block text-sm font-medium text-slate-300 mb-1" htmlFor="password_confirmation">
                        Şifre Tekrar
                    </label>
                    <div className="relative">
                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                            <Lock size={18} />
                        </div>
                        <input
                            id="password_confirmation"
                            type="password"
                            value={data.password_confirmation}
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                            className="bg-slate-900/50 border border-slate-700 text-slate-200 text-sm rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-10 p-3 transition-shadow"
                            placeholder="••••••••"
                            required
                        />
                    </div>
                    {(clientErrors.password_confirmation || serverErrors.password_confirmation) && (
                        <p className="mt-1.5 text-xs text-rose-400 flex items-center gap-1">
                            <AlertCircle size={12} /> {clientErrors.password_confirmation || serverErrors.password_confirmation}
                        </p>
                    )}
                </div>

                <motion.button
                    whileHover={{ scale: 1.02 }}
                    whileTap={{ scale: 0.98 }}
                    disabled={processing}
                    type="submit"
                    className="w-full mt-4 text-white bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 shadow-lg shadow-emerald-500/30 font-medium rounded-xl text-sm px-5 py-3 text-center flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed transition-all"
                >
                    {processing ? (
                        <span className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                    ) : (
                        <>
                            <UserPlus size={18} /> Kayıt Ol
                        </>
                    )}
                </motion.button>

                <p className="text-sm text-center text-slate-400 mt-2">
                    Zaten hesabınız var mı?{' '}
                    <Link href={route('login')} className="font-medium text-indigo-400 hover:text-indigo-300 underline underline-offset-2">
                        Giriş Yapın
                    </Link>
                </p>
            </form>
        </GuestLayout>
    );
}
