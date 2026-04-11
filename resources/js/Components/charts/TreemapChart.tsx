import React from 'react';
import { Position } from '@/types/models';

/**
 * TreemapChart — Portföy Dağılımı Treemap Grafiği
 *
 * Pozisyonları alan oranlarıyla gösterir. Her hücrenin büyüklüğü
 * pozisyonun toplam portföy değerine oranıyla belirlenir.
 *
 * Pure SVG — harici kütüphane gerektirmez.
 */

interface TreemapChartProps {
    positions: Position[];
}

interface TreemapNode {
    symbol: string;
    name: string;
    value: number;
    pnlPercent: number;
    assetClass: string;
}

const ASSET_COLORS: Record<string, { bg: string; text: string }> = {
    CRYPTO: { bg: '#f59e0b', text: '#ffffff' },
    STOCK:  { bg: '#3b82f6', text: '#ffffff' },
    FX:     { bg: '#10b981', text: '#ffffff' },
};

const TreemapChart: React.FC<TreemapChartProps> = ({ positions }) => {
    if (positions.length === 0) {
        return (
            <div className="flex items-center justify-center h-64 text-gray-400">
                <div className="text-center">
                    <span className="text-3xl block mb-2">🗺️</span>
                    <span className="text-sm">Treemap verisi yok</span>
                </div>
            </div>
        );
    }

    // Node'ları hazırla
    const nodes: TreemapNode[] = positions
        .filter(p => p.net_quantity > 0 && p.current_price > 0)
        .map(p => ({
            symbol: p.symbol,
            name: p.name,
            value: p.current_price * p.net_quantity,
            pnlPercent: p.unrealized_pnl_percent,
            assetClass: p.asset_class,
        }))
        .sort((a, b) => b.value - a.value);

    const totalValue = nodes.reduce((sum, n) => sum + n.value, 0);

    if (totalValue === 0) return null;

    // Basit squarified treemap layout
    const containerWidth = 600;
    const containerHeight = 300;
    const rects = calculateTreemapLayout(nodes, totalValue, containerWidth, containerHeight);

    return (
        <div className="w-full overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
            <svg
                viewBox={`0 0 ${containerWidth} ${containerHeight}`}
                className="w-full h-auto"
                style={{ minHeight: '250px' }}
            >
                {rects.map((rect, i) => {
                    const node = nodes[i];
                    const colors = ASSET_COLORS[node.assetClass] || ASSET_COLORS.CRYPTO;
                    const percentage = ((node.value / totalValue) * 100).toFixed(1);
                    const isLargeEnough = rect.w > 60 && rect.h > 40;

                    // PnL'ye göre opaklık
                    const opacity = 0.7 + Math.min(Math.abs(node.pnlPercent) / 20, 0.3);

                    return (
                        <g key={node.symbol}>
                            {/* Ana hücre */}
                            <rect
                                x={rect.x + 1}
                                y={rect.y + 1}
                                width={Math.max(rect.w - 2, 0)}
                                height={Math.max(rect.h - 2, 0)}
                                fill={colors.bg}
                                opacity={opacity}
                                rx={4}
                                className="transition-all duration-300 hover:opacity-100 cursor-pointer"
                            />
                            {/* Sembol */}
                            {isLargeEnough && (
                                <>
                                    <text
                                        x={rect.x + rect.w / 2}
                                        y={rect.y + rect.h / 2 - 8}
                                        textAnchor="middle"
                                        fill={colors.text}
                                        fontSize="13"
                                        fontWeight="bold"
                                    >
                                        {node.symbol}
                                    </text>
                                    <text
                                        x={rect.x + rect.w / 2}
                                        y={rect.y + rect.h / 2 + 8}
                                        textAnchor="middle"
                                        fill={colors.text}
                                        fontSize="10"
                                        opacity={0.85}
                                    >
                                        %{percentage}
                                    </text>
                                    <text
                                        x={rect.x + rect.w / 2}
                                        y={rect.y + rect.h / 2 + 22}
                                        textAnchor="middle"
                                        fill={node.pnlPercent >= 0 ? '#bbf7d0' : '#fecaca'}
                                        fontSize="9"
                                    >
                                        {node.pnlPercent >= 0 ? '▲' : '▼'} {Math.abs(node.pnlPercent).toFixed(1)}%
                                    </text>
                                </>
                            )}
                            {/* Küçük hücreler için sadece sembol */}
                            {!isLargeEnough && rect.w > 30 && rect.h > 20 && (
                                <text
                                    x={rect.x + rect.w / 2}
                                    y={rect.y + rect.h / 2 + 4}
                                    textAnchor="middle"
                                    fill={colors.text}
                                    fontSize="9"
                                    fontWeight="bold"
                                >
                                    {node.symbol}
                                </text>
                            )}
                        </g>
                    );
                })}
            </svg>
        </div>
    );
};

/**
 * Basit slice-and-dice treemap layout.
 */
function calculateTreemapLayout(
    nodes: TreemapNode[],
    totalValue: number,
    width: number,
    height: number
): Array<{ x: number; y: number; w: number; h: number }> {
    const rects: Array<{ x: number; y: number; w: number; h: number }> = [];
    let currentX = 0;
    let currentY = 0;
    let remainingWidth = width;
    let remainingHeight = height;
    let horizontal = width >= height;

    nodes.forEach((node, i) => {
        const ratio = node.value / totalValue;

        if (horizontal) {
            const w = remainingWidth * ratio * (nodes.length / (nodes.length - i));
            const clampedW = Math.min(w, remainingWidth);
            rects.push({ x: currentX, y: currentY, w: clampedW, h: remainingHeight });
            currentX += clampedW;
            remainingWidth -= clampedW;
        } else {
            const h = remainingHeight * ratio * (nodes.length / (nodes.length - i));
            const clampedH = Math.min(h, remainingHeight);
            rects.push({ x: currentX, y: currentY, w: remainingWidth, h: clampedH });
            currentY += clampedH;
            remainingHeight -= clampedH;
        }

        // Her 3 node'da yön değiştir
        if ((i + 1) % 3 === 0) {
            horizontal = !horizontal;
        }
    });

    return rects;
}

export default TreemapChart;
