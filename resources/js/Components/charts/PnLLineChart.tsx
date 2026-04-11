import React from 'react';

/**
 * PnLLineChart — Kar/Zarar Çizgi Grafiği
 *
 * X ekseni: tarih, Y ekseni: tutar
 * İki çizgi: Realized PnL (yeşil) ve Unrealized PnL (mavi)
 * Sıfır çizgisi referans olarak gösterilir.
 *
 * Pure SVG — recharts bağımlılığı olmadan çalışır.
 */

interface PnLDataPoint {
    date: string;
    realized: number;
    unrealized: number;
}

interface PnLLineChartProps {
    data: PnLDataPoint[];
    height?: number;
}

const PnLLineChart: React.FC<PnLLineChartProps> = ({ data, height = 250 }) => {
    if (data.length < 2) {
        return (
            <div className="flex items-center justify-center text-gray-400" style={{ height }}>
                <div className="text-center">
                    <span className="text-3xl block mb-2">📉</span>
                    <span className="text-sm">Yeterli veri yok (en az 2 nokta gerekli)</span>
                </div>
            </div>
        );
    }

    const width = 600;
    const padding = { top: 20, right: 20, bottom: 40, left: 60 };
    const chartW = width - padding.left - padding.right;
    const chartH = height - padding.top - padding.bottom;

    // Min/max değerleri bul
    const allValues = data.flatMap(d => [d.realized, d.unrealized]);
    const minVal = Math.min(...allValues, 0);
    const maxVal = Math.max(...allValues, 0);
    const range = maxVal - minVal || 1;

    // Koordinat dönüşüm fonksiyonları
    const xScale = (i: number) => padding.left + (i / (data.length - 1)) * chartW;
    const yScale = (val: number) => padding.top + chartH - ((val - minVal) / range) * chartH;

    // SVG path oluştur
    const makePath = (key: 'realized' | 'unrealized'): string => {
        return data.map((d, i) =>
            `${i === 0 ? 'M' : 'L'} ${xScale(i).toFixed(1)} ${yScale(d[key]).toFixed(1)}`
        ).join(' ');
    };

    // Sıfır çizgisi Y koordinatı
    const zeroY = yScale(0);

    // Y ekseni etiketleri
    const yTicks = 5;
    const yLabels = Array.from({ length: yTicks + 1 }, (_, i) => {
        const val = minVal + (range / yTicks) * i;
        return { val, y: yScale(val) };
    });

    return (
        <div className="w-full overflow-hidden">
            <svg viewBox={`0 0 ${width} ${height}`} className="w-full h-auto">
                {/* Grid çizgileri */}
                {yLabels.map(({ val, y }, i) => (
                    <g key={i}>
                        <line
                            x1={padding.left} y1={y}
                            x2={width - padding.right} y2={y}
                            stroke="currentColor"
                            className="text-gray-200 dark:text-gray-700"
                            strokeWidth={0.5}
                        />
                        <text
                            x={padding.left - 8}
                            y={y + 4}
                            textAnchor="end"
                            className="fill-gray-400"
                            fontSize="10"
                        >
                            {val >= 1000 || val <= -1000
                                ? `${(val / 1000).toFixed(1)}K`
                                : val.toFixed(0)
                            }
                        </text>
                    </g>
                ))}

                {/* Sıfır çizgisi (referans) */}
                <line
                    x1={padding.left} y1={zeroY}
                    x2={width - padding.right} y2={zeroY}
                    stroke="currentColor"
                    className="text-gray-400 dark:text-gray-500"
                    strokeWidth={1}
                    strokeDasharray="4 2"
                />

                {/* X ekseni tarihleri */}
                {data.map((d, i) => {
                    // Her 5. tarihi veya ilk/son'u göster
                    if (i !== 0 && i !== data.length - 1 && i % Math.ceil(data.length / 5) !== 0) return null;
                    return (
                        <text
                            key={i}
                            x={xScale(i)}
                            y={height - 8}
                            textAnchor="middle"
                            className="fill-gray-400"
                            fontSize="9"
                        >
                            {new Date(d.date).toLocaleDateString('tr-TR', { day: '2-digit', month: 'short' })}
                        </text>
                    );
                })}

                {/* Unrealized PnL çizgisi (mavi) */}
                <path
                    d={makePath('unrealized')}
                    fill="none"
                    stroke="#3b82f6"
                    strokeWidth={2}
                    strokeLinejoin="round"
                    className="transition-all duration-500"
                />

                {/* Realized PnL çizgisi (yeşil) */}
                <path
                    d={makePath('realized')}
                    fill="none"
                    stroke="#10b981"
                    strokeWidth={2}
                    strokeLinejoin="round"
                    className="transition-all duration-500"
                />

                {/* Noktalar — Unrealized */}
                {data.map((d, i) => (
                    <circle
                        key={`u-${i}`}
                        cx={xScale(i)}
                        cy={yScale(d.unrealized)}
                        r={3}
                        fill="#3b82f6"
                        className="opacity-0 hover:opacity-100 transition-opacity cursor-pointer"
                    />
                ))}

                {/* Noktalar — Realized */}
                {data.map((d, i) => (
                    <circle
                        key={`r-${i}`}
                        cx={xScale(i)}
                        cy={yScale(d.realized)}
                        r={3}
                        fill="#10b981"
                        className="opacity-0 hover:opacity-100 transition-opacity cursor-pointer"
                    />
                ))}

                {/* Legend */}
                <g transform={`translate(${padding.left + 10}, ${padding.top})`}>
                    <rect x={0} y={0} width={8} height={8} rx={2} fill="#10b981" />
                    <text x={12} y={8} className="fill-gray-500" fontSize="10">Realized PnL</text>
                    <rect x={100} y={0} width={8} height={8} rx={2} fill="#3b82f6" />
                    <text x={112} y={8} className="fill-gray-500" fontSize="10">Unrealized PnL</text>
                </g>
            </svg>
        </div>
    );
};

export default PnLLineChart;
