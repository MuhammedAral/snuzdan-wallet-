import { Head, Link, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import { FormEventHandler } from 'react';
import { MailCheck, Send } from 'lucide-react';

export default function VerifyEmail({ status }: { status?: string }) {
    const { post, processing } = useForm({});

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('verification.send'));
    };

    return (
        <GuestLayout>
            <Head title="E-posta Doğrulama" />

            <div className="text-center mb-6">
                <div className="mx-auto w-16 h-16 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center mb-4">
                    <MailCheck size={32} className="text-indigo-400" />
                </div>
                <h1 className="text-2xl font-bold text-white mb-2 tracking-tight">E-postanızı Doğrulayın</h1>
                <p className="text-sm text-slate-400 leading-relaxed max-w-sm mx-auto">
                    Kayıt olduğunuz e-posta adresine bir doğrulama bağlantısı gönderdik.
                    Lütfen gelen kutunuzu kontrol edin.
                </p>
            </div>

            {status === 'verification-link-sent' && (
                <div className="mb-4 text-sm font-medium text-emerald-400 bg-emerald-400/10 p-3 rounded-lg border border-emerald-400/20 text-center">
                    Yeni bir doğrulama bağlantısı e-posta adresinize gönderildi.
                </div>
            )}

            <form onSubmit={submit} className="mt-4 flex flex-col gap-4">
                <button
                    disabled={processing}
                    type="submit"
                    className="w-full text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 shadow-lg shadow-indigo-500/30 font-medium rounded-xl text-sm px-5 py-3 text-center flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed transition-all active:scale-[0.98]"
                >
                    {processing ? (
                        <span className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                    ) : (
                        <>
                            <Send size={18} /> Tekrar Gönder
                        </>
                    )}
                </button>

                <Link
                    href={route('logout')}
                    method="post"
                    as="button"
                    className="w-full text-slate-400 hover:text-slate-300 text-sm text-center py-2 transition-colors"
                >
                    Çıkış Yap
                </Link>
            </form>
        </GuestLayout>
    );
}
