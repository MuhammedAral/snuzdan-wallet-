import React from 'react';
import { PortfolioSummary } from '@/types/models';

/**
 * AllocationPieChart — Portföy Dağılımı Pasta Grafiği
 *
 * Asset class'a göre portföy dağılımını görselleştirir.
 * Pure CSS/SVG ile çizilir (recharts bağımlılığı olmadan çalışır).
 */

interface AllocationPieChartProps {
    allocation: Record<string, number>;
    totalValue: number;
}

const COLORS: Record<string, { fill: string; label: string }> = {
    CRYPTO: { fill: '#f59e0b', label: 'Kripto' },
    STOCK: { fill: '#3b82f6', label: 'Hisse' },
    FX: { fill: '#10b981', label: 'Döviz' },
};

const AllocationPieChart: React.FC<AllocationPieChartProps> = ({ allocation, totalValue }) => {
    const entries = Object.entries(allocation).filter(([_, pct]) => pct > 0);

    if (entries.length === 0) {
        return (
            <div className="flex flex-col items-center justify-center py-8 text-gray-400">
                <span className="text-3xl mb-2">🥧</span>
                <span className="text-sm">Dağılım verisi yok</span>
            </div>
        );
    }

    // SVG Donut chart hesaplama
    const radius = 60;
    const circumference = 2 * Math.PI * radius;
    let cumulativeOffset = 0;

    const segments = entries.map(([key, pct]) => {
        const dashLength = (pct / 100) * circumference;
        const dashOffset = cumulativeOffset;
        cumulativeOffset += dashLength;

        return {
            key,
            pct,
            dashLength,
            dashOffset,
            color: COLORS[key]?.fill || '#6b7280',
            label: COLORS[key]?.label || key,
        };
    });

    return (
        <div className="flex items-center gap-6">
            {/* SVG Donut */}
            <div className="relative">
                <svg width="160" height="160" viewBox="0 0 160 160">
                    {/* Background circle */}
                    <circle
                        cx="80" cy="80" r={radius}
                        fill="none"
                        stroke="currentColor"
                        className="text-gray-200 dark:text-gray-700"
                        strokeWidth="16"
                    />
                    {/* Segments */}
                    {segments.map((seg) => (
                        <circle
                            key={seg.key}
                            cx="80" cy="80" r={radius}
                            fill="none"
                            stroke={seg.color}
                            strokeWidth="16"
                            strokeDasharray={`${seg.dashLength} ${circumference - seg.dashLength}`}
                            strokeDashoffset={-seg.dashOffset}
                            className="transition-all duration-500"
                            style={{ transform: 'rotate(-90deg)', transformOrigin: '80px 80px' }}
                        />
                    ))}
                </svg>
                {/* Center text */}
                <div className="absolute inset-0 flex flex-col items-center justify-center">
                    <span className="text-xs text-gray-400">Toplam</span>
                    <span className="text-sm font-bold text-gray-900 dark:text-white">
                        ${totalValue.toLocaleString('en-US', { maximumFractionDigits: 0 })}
                    </span>
                </div>
            </div>

            {/* Legend */}
            <div className="flex flex-col gap-2">
                {segments.map((seg) => (
                    <div key={seg.key} className="flex items-center gap-2">
                        <div className="w-3 h-3 rounded-full" style={{ backgroundColor: seg.color }} />
                        <span className="text-sm text-gray-600 dark:text-gray-300">{seg.label}</span>
                        <span className="text-sm font-semibold text-gray-900 dark:text-white ml-auto">
                            %{seg.pct.toFixed(1)}
                        </span>
                    </div>
                ))}
            </div>
        </div>
    );
};

export default AllocationPieChart;
