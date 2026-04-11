import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { Mail, Lock, LogIn, Chrome } from 'lucide-react';
import { motion } from 'framer-motion';

export default function Login({
    status,
    canResetPassword,
}: {
    status?: string;
    canResetPassword: boolean;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false as boolean,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Giriş Yap" />

            <div className="text-center mb-8">
                <h1 className="text-3xl font-bold text-white mb-2 tracking-tight">Hoş Geldiniz</h1>
                <p className="text-sm text-slate-400">Snuzdan hesabınıza giriş yapın</p>
            </div>

            {status && (
                <div className="mb-4 text-sm font-medium text-emerald-400 bg-emerald-400/10 p-3 rounded-lg border border-emerald-400/20 text-center">
                    {status}
                </div>
            )}

            <form onSubmit={submit} className="flex flex-col gap-5">
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
                    {errors.email && <p className="mt-2 text-sm text-rose-400">{errors.email}</p>}
                </div>

                <div>
                    <div className="flex items-center justify-between mb-1">
                        <label className="block text-sm font-medium text-slate-300" htmlFor="password">
                            Şifre
                        </label>
                        {canResetPassword && (
                            <Link
                                href={route('password.request')}
                                className="text-xs text-indigo-400 hover:text-indigo-300 transition-colors"
                            >
                                Şifremi unuttum?
                            </Link>
                        )}
                    </div>
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
                    {errors.password && <p className="mt-2 text-sm text-rose-400">{errors.password}</p>}
                </div>

                <div className="flex items-center gap-2 mt-1">
                    <input
                        id="remember"
                        type="checkbox"
                        checked={data.remember}
                        onChange={(e) => setData('remember', e.target.checked)}
                        className="w-4 h-4 text-indigo-600 bg-slate-900/50 border-slate-700 rounded focus:ring-indigo-600 focus:ring-2 focus:ring-offset-slate-950"
                    />
                    <label htmlFor="remember" className="text-sm font-medium text-slate-400 select-none cursor-pointer hover:text-slate-300">
                        Beni hatırla
                    </label>
                </div>

                <motion.button
                    whileHover={{ scale: 1.02 }}
                    whileTap={{ scale: 0.98 }}
                    disabled={processing}
                    type="submit"
                    className="w-full mt-2 text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 shadow-lg shadow-indigo-500/30 font-medium rounded-xl text-sm px-5 py-3 text-center flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed transition-all"
                >
                    {processing ? (
                        <span className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                    ) : (
                        <>
                            <LogIn size={18} /> Giriş Yap
                        </>
                    )}
                </motion.button>

                <div className="relative flex items-center py-2">
                    <div className="flex-grow border-t border-slate-700/80"></div>
                    <span className="flex-shrink-0 mx-4 text-slate-500 text-xs">veya</span>
                    <div className="flex-grow border-t border-slate-700/80"></div>
                </div>

                <motion.a
                    whileHover={{ scale: 1.02 }}
                    whileTap={{ scale: 0.98 }}
                    href="/auth/google"
                    className="w-full text-slate-200 bg-slate-800 hover:bg-slate-700 border border-slate-700 hover:border-slate-600 font-medium rounded-xl text-sm px-5 py-3 text-center flex items-center justify-center gap-3 transition-colors shadow-sm"
                >
                    <Chrome size={18} className="text-rose-400" />
                    Google ile Devam Et
                </motion.a>

                <p className="text-sm text-center text-slate-400 mt-4">
                    Hesabınız yok mu?{' '}
                    <Link href={route('register')} className="font-medium text-indigo-400 hover:text-indigo-300 underline underline-offset-2">
                        Kayıt Olun
                    </Link>
                </p>
            </form>
        </GuestLayout>
    );
}
