import { Head, Link, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import { FormEventHandler, useState } from 'react';
import { User, Mail, Lock, Eye, EyeOff, UserPlus } from 'lucide-react';
import { z } from 'zod';

const registerSchema = z.object({
    display_name: z.string().min(2, 'Ad Soyad en az 2 karakter olmalıdır.').max(100, 'Ad Soyad en fazla 100 karakter olabilir.'),
    email: z.string().email('Geçerli bir e-posta adresi giriniz.'),
    password: z.string().min(8, 'Şifre en az 8 karakter olmalıdır.'),
    password_confirmation: z.string(),
}).refine((data) => data.password === data.password_confirmation, {
    message: 'Şifreler eşleşmiyor.',
    path: ['password_confirmation'],
});

type ZodErrors = Partial<Record<keyof z.infer<typeof registerSchema>, string>>;

export default function Register() {
    const { data, setData, post, processing, errors: serverErrors, reset } = useForm({
        display_name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const [zodErrors, setZodErrors] = useState<ZodErrors>({});
    const [showPassword, setShowPassword] = useState(false);

    const errors = { ...zodErrors, ...serverErrors } as Record<string, string>;

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        const result = registerSchema.safeParse(data);
        if (!result.success) {
            const fieldErrors: ZodErrors = {};
            result.error.errors.forEach((err) => {
                const key = err.path[0] as keyof ZodErrors;
                if (!fieldErrors[key]) fieldErrors[key] = err.message;
            });
            setZodErrors(fieldErrors);
            return;
        }

        setZodErrors({});
        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Kayıt Ol" />

            <div className="text-center mb-8">
                <h1 className="text-3xl font-bold text-white mb-2 tracking-tight">Hesap Oluştur</h1>
                <p className="text-sm text-slate-400">Finanslarınızı kontrol altına alın</p>
            </div>

            <form onSubmit={submit} className="flex flex-col gap-5">
                {/* Display Name */}
                <div>
                    <label className="block text-sm font-medium text-slate-300 mb-1" htmlFor="display_name">
                        Ad Soyad
                    </label>
                    <div className="relative">
                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                            <User size={18} />
                        </div>
                        <input
                            id="display_name"
                            type="text"
                            value={data.display_name}
                            onChange={(e) => setData('display_name', e.target.value)}
                            className="bg-slate-900/50 border border-slate-700 text-slate-200 text-sm rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-10 p-3 transition-shadow"
                            placeholder="Akif Cebe"
                            required
                        />
                    </div>
                    {errors.display_name && <p className="mt-2 text-sm text-rose-400">{errors.display_name}</p>}
                </div>

                {/* Email */}
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

                {/* Password */}
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
                            type={showPassword ? 'text' : 'password'}
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            className="bg-slate-900/50 border border-slate-700 text-slate-200 text-sm rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-10 pr-10 p-3 transition-shadow"
                            placeholder="••••••••"
                            required
                        />
                        <button
                            type="button"
                            onClick={() => setShowPassword(!showPassword)}
                            className="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-500 hover:text-slate-300 transition-colors"
                        >
                            {showPassword ? <EyeOff size={18} /> : <Eye size={18} />}
                        </button>
                    </div>
                    {errors.password && <p className="mt-2 text-sm text-rose-400">{errors.password}</p>}
                </div>

                {/* Password Confirmation */}
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
                            type={showPassword ? 'text' : 'password'}
                            value={data.password_confirmation}
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                            className="bg-slate-900/50 border border-slate-700 text-slate-200 text-sm rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-10 p-3 transition-shadow"
                            placeholder="••••••••"
                            required
                        />
                    </div>
                    {errors.password_confirmation && <p className="mt-2 text-sm text-rose-400">{errors.password_confirmation}</p>}
                </div>

                <button
                    disabled={processing}
                    type="submit"
                    className="w-full mt-2 text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 shadow-lg shadow-indigo-500/30 font-medium rounded-xl text-sm px-5 py-3 text-center flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed transition-all active:scale-[0.98]"
                >
                    {processing ? (
                        <span className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                    ) : (
                        <>
                            <UserPlus size={18} /> Kayıt Ol
                        </>
                    )}
                </button>

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
