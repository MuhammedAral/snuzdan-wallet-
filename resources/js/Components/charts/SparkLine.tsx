import React from 'react';

/**
 * SparkLine — Mini Satır İçi Grafik
 *
 * PositionCard içinde kullanılır. Son 24 saatin fiyat değişimini
 * küçük bir çizgi ile gösterir. Pozitifse yeşil, negatifse kırmızı.
 *
 * Props:
 *   - data: fiyat dizisi (son 24 veri noktası)
 *   - color: override renk (opsiyonel, yoksa trende göre otomatik)
 *   - width/height: piksel boyutları
 */

interface SparkLineProps {
    data: number[];
    color?: string;
    width?: number;
    height?: number;
}

const SparkLine: React.FC<SparkLineProps> = ({
    data,
    color,
    width = 80,
    height = 28,
}) => {
    if (data.length < 2) {
        return <div style={{ width, height }} />;
    }

    const padding = 2;
    const chartW = width - padding * 2;
    const chartH = height - padding * 2;

    const min = Math.min(...data);
    const max = Math.max(...data);
    const range = max - min || 1;

    // Trend rengi
    const isPositive = data[data.length - 1] >= data[0];
    const strokeColor = color || (isPositive ? '#10b981' : '#ef4444');

    // Path oluştur
    const points = data.map((val, i) => {
        const x = padding + (i / (data.length - 1)) * chartW;
        const y = padding + chartH - ((val - min) / range) * chartH;
        return `${x.toFixed(1)},${y.toFixed(1)}`;
    });

    const linePath = `M ${points.join(' L ')}`;

    // Gradient dolgu için alan path'i
    const lastX = padding + chartW;
    const bottomY = padding + chartH;
    const areaPath = `${linePath} L ${lastX},${bottomY} L ${padding},${bottomY} Z`;

    const gradientId = `spark-gradient-${Math.random().toString(36).slice(2, 8)}`;

    return (
        <svg width={width} height={height} className="inline-block">
            <defs>
                <linearGradient id={gradientId} x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stopColor={strokeColor} stopOpacity={0.2} />
                    <stop offset="100%" stopColor={strokeColor} stopOpacity={0} />
                </linearGradient>
            </defs>

            {/* Alan dolgusu */}
            <path d={areaPath} fill={`url(#${gradientId})`} />

            {/* Çizgi */}
            <path
                d={linePath}
                fill="none"
                stroke={strokeColor}
                strokeWidth={1.5}
                strokeLinejoin="round"
                strokeLinecap="round"
            />

            {/* Son nokta */}
            <circle
                cx={padding + chartW}
                cy={padding + chartH - ((data[data.length - 1] - min) / range) * chartH}
                r={2}
                fill={strokeColor}
            />
        </svg>
    );
};

export default SparkLine;
