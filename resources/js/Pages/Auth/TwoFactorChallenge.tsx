import React, { FormEvent } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';

export default function TwoFactorChallenge() {
    const { data, setData, post, errors, processing } = useForm({
        code: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(route('two-factor.challenge.verify'));
    };

    return (
        <>
            <Head title="İki Adımlı Doğrulama" />

            <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 px-4">
                <div className="w-full max-w-md">

                    {/* Card */}
                    <div className="bg-slate-900/80 backdrop-blur-lg border border-slate-800 rounded-3xl p-8 shadow-2xl">

                        {/* Icon */}
                        <div className="flex justify-center mb-6">
                            <div className="p-4 bg-indigo-500/10 rounded-2xl border border-indigo-500/20">
                                <ShieldCheck size={40} className="text-indigo-400" />
                            </div>
                        </div>

                        <h1 className="text-2xl font-bold text-white text-center mb-2">
                            İki Adımlı Doğrulama
                        </h1>
                        <p className="text-center text-slate-400 text-sm mb-8">
                            Google Authenticator uygulamanızdaki 6 haneli kodu girin veya kurtarma kodunuzu kullanın.
                        </p>

                        <form onSubmit={submit} className="space-y-6">
                            <div>
                                <TextInput
                                    id="code"
                                    type="text"
                                    inputMode="numeric"
                                    autoComplete="one-time-code"
                                    autoFocus
                                    placeholder="000000"
                                    className="block w-full text-center text-2xl tracking-[0.5em] bg-slate-950 border-slate-700 text-white focus:border-indigo-500 focus:ring-indigo-500 rounded-xl py-4"
                                    value={data.code}
                                    onChange={(e) => setData('code', e.target.value.replace(/\D/g, '').slice(0, 10))}
                                />
                                <InputError message={errors.code} className="mt-3 text-center" />
                            </div>

                            <PrimaryButton
                                disabled={processing || data.code.length < 6}
                                className="w-full justify-center bg-indigo-600 hover:bg-indigo-500 py-3 text-base rounded-xl"
                            >
                                {processing ? 'Doğrulanıyor...' : 'Doğrula'}
                            </PrimaryButton>
                        </form>

                        <div className="mt-6 text-center">
                            <p className="text-xs text-slate-500">
                                Kurtarma kodunuz varsa (10 haneli) aynı alana girip doğrulayabilirsiniz.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
