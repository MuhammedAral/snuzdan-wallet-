import { useState } from 'react';
import axios from 'axios';
import { Sparkles, Loader2, Send } from 'lucide-react';

interface AiParsedResult {
    type: string;
    amount: number;
    currency: string;
    category_name: string;
    date: string;
    note: string;
}

interface AiTransactionInputProps {
    onParsed: (data: AiParsedResult) => void;
}

export default function AiTransactionInput({ onParsed }: AiTransactionInputProps) {
    const [prompt, setPrompt] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState('');

    const handleSend = async (e?: React.FormEvent) => {
        if (e) e.preventDefault();
        
        if (!prompt.trim() || isLoading) return;

        setIsLoading(true);
        setError('');

        try {
            const { data } = await axios.post('/api/ai/parse', { prompt });
            if (data.success && data.parsed) {
                onParsed(data.parsed);
                setPrompt(''); // Başarılı parse durumunda içini temizle
            }
        } catch (err: any) {
             setError(err.response?.data?.message || 'Yapay zeka ile iletişim kurulamadı. Ayarlarınızı (GEMINI_API_KEY) kontrol edin.');
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <div className="w-full rounded-2xl bg-gradient-to-br from-indigo-500/10 to-purple-500/10 border border-indigo-500/20 p-4">
            <div className="flex items-start gap-3 mb-3">
                <div className="bg-indigo-500/20 p-2 rounded-lg text-indigo-400">
                    <Sparkles size={20} />
                </div>
                <div>
                    <h4 className="text-gray-900 dark:text-white font-medium text-sm">Yapay Zeka Asistanı</h4>
                    <p className="text-gray-600 dark:text-slate-400 text-xs mt-0.5">"Dün markette 850 TL harcadım" gibi yazın, o halletsin.</p>
                </div>
            </div>

            <form onSubmit={handleSend} className="relative">
                <input
                    type="text"
                    value={prompt}
                    onChange={(e) => setPrompt(e.target.value)}
                    placeholder="Gelir/gider detayını buraya yaz..."
                    disabled={isLoading}
                    className="w-full bg-gray-50 dark:bg-slate-950/50 border border-gray-200 dark:border-slate-800 text-gray-800 dark:text-slate-200 text-sm rounded-xl py-3 pl-4 pr-12 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none placeholder:text-slate-600 transition-all disabled:opacity-50"
                />
                
                <button
                    type="submit"
                    disabled={isLoading || !prompt.trim()}
                    className="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 text-indigo-400 hover:text-indigo-300 hover:bg-indigo-500/20 rounded-lg transition-colors disabled:opacity-50 disabled:hover:bg-transparent"
                >
                    {isLoading ? <Loader2 size={18} className="animate-spin" /> : <Send size={18} />}
                </button>
            </form>

            {error && (
                <div className="mt-3 text-xs text-rose-400 bg-rose-500/10 px-3 py-2 rounded-lg border border-rose-500/20">
                    {error}
                </div>
            )}
        </div>
    );
}
