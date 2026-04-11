import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { motion } from 'framer-motion';
import { MailCheck, RefreshCcw, LogOut } from 'lucide-react';

export default function VerifyEmail({ status }: { status?: string }) {
    const { post, processing } = useForm({});

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('verification.send'));
    };

    return (
        <GuestLayout>
            <Head title="E-posta Doğrulama" />

            <div className="flex flex-col items-center text-center mb-6">
                <div className="h-16 w-16 bg-indigo-500/20 rounded-full flex items-center justify-center mb-4">
                    <MailCheck size={32} className="text-indigo-400" />
                </div>
                <h1 className="text-2xl font-bold text-white mb-2 tracking-tight">E-postanızı Doğrulayın</h1>
                <p className="text-sm text-slate-400 leading-relaxed">
                    Aramıza katıldığınız için teşekkürler! Başlamadan önce, size gönderdiğimiz bağlantıya tıklayarak e-posta adresinizi doğrulayabilir misiniz? Eğer e-postayı almadıysanız, size yeni bir tane göndermekten memnuniyet duyarız.
                </p>
            </div>

            {status === 'verification-link-sent' && (
                <motion.div 
                    initial={{ opacity: 0, scale: 0.95 }}
                    animate={{ opacity: 1, scale: 1 }}
                    className="mb-6 text-sm font-medium text-emerald-400 bg-emerald-400/10 p-4 rounded-xl border border-emerald-400/20 text-center"
                >
                    Kayıt sırasında verdiğiniz e-posta adresine yeni bir doğrulama bağlantısı gönderildi.
                </motion.div>
            )}

            <form onSubmit={submit}>
                <div className="mt-4 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <motion.button
                        whileHover={{ scale: 1.02 }}
                        whileTap={{ scale: 0.98 }}
                        disabled={processing}
                        type="submit"
                        className="w-full text-white bg-indigo-600 hover:bg-indigo-500 shadow-lg shadow-indigo-500/30 font-medium rounded-xl text-sm px-5 py-3 flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed transition-all"
                    >
                        {processing ? (
                            <span className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                        ) : (
                            <>
                                <RefreshCcw size={16} /> Tekrar Gönder
                            </>
                        )}
                    </motion.button>

                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="w-full sm:w-auto text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700 font-medium rounded-xl text-sm px-5 py-3 flex items-center justify-center gap-2 transition-colors border border-slate-700"
                    >
                        <LogOut size={16} /> Çıkış Yap
                    </Link>
                </div>
            </form>
        </GuestLayout>
    );
}
