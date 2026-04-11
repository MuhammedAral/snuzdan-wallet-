import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link } from '@inertiajs/react';
import { PropsWithChildren } from 'react';
import { motion } from 'framer-motion';

export default function Guest({ children }: PropsWithChildren) {
    return (
        <div className="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-slate-950 text-slate-100 selection:bg-indigo-500/30">
            {/* Ambient Background Glows */}
            <div className="absolute top-[-10%] left-[-10%] h-[500px] w-[500px] rounded-full bg-indigo-600/20 blur-[120px]" />
            <div className="absolute right-[-10%] bottom-[-10%] h-[600px] w-[600px] rounded-full bg-fuchsia-600/20 blur-[120px]" />
            
            <motion.div 
                initial={{ opacity: 0, y: -20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.5 }}
                className="z-10 mb-8"
            >
                <Link href="/">
                    <ApplicationLogo className="h-20 w-20 fill-current text-indigo-400 drop-shadow-[0_0_15px_rgba(99,102,241,0.5)]" />
                </Link>
            </motion.div>

            <motion.div 
                initial={{ opacity: 0, scale: 0.95 }}
                animate={{ opacity: 1, scale: 1 }}
                transition={{ duration: 0.5, delay: 0.1 }}
                className="z-10 w-full px-6 sm:max-w-md sm:px-0"
            >
                <div className="backdrop-blur-xl bg-slate-900/60 border border-slate-700/50 shadow-[0_8px_32px_0_rgba(0,0,0,0.37)] rounded-2xl overflow-hidden relative">
                    {/* Inner subtle glow to the card */}
                    <div className="absolute inset-0 bg-gradient-to-tr from-white/5 to-transparent opacity-20 pointer-events-none" />
                    
                    <div className="relative z-10 px-6 py-8 sm:px-10">
                        {children}
                    </div>
                </div>
            </motion.div>
            
            {/* Decorative Grid Background */}
            <div className="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCI+CjxwYXRoIGQ9Ik0wIDBoNDB2NDBIMHoiIGZpbGw9Im5vbmUiLz4KPHBhdGggZD0iTTAgMGg0MHYxSDB6bTAgNDBWMGgxdjQweiIgZmlsbD0icmdiYSgyNTUsMjU1LDI1NSwwLjAyKSIvPgo8L3N2Zz4=')] opacity-50 mix-blend-overlay pointer-events-none" />
        </div>
    );
}
