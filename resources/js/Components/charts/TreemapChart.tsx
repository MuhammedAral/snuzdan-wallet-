import React from 'react';
import { Position } from '@/types/models';
import { ResponsiveTreeMapHtml } from '@nivo/treemap';

/**
 * TreemapChart — Portföy Dağılımı Treemap Grafiği
 *
 * @nivo/treemap kullanarak portföy dağılımını gösterir.
 */

interface TreemapChartProps {
    positions: Position[];
}

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

    // Nivo için veriyi formatla
    const rootNode = {
        name: 'Portfolio',
        color: 'hsl(0, 0%, 90%)',
        children: positions
            .filter(p => p.net_quantity > 0 && p.current_price > 0)
            .map(p => {
                const isProfitable = p.unrealized_pnl >= 0;
                // Asset class'a göre renk seçimi
                let hue = 0;
                if (p.asset_class === 'CRYPTO') hue = 35; // Orange
                else if (p.asset_class === 'STOCK') hue = 217; // Blue
                else if (p.asset_class === 'FX') hue = 160; // Emerald

                // PnL'ye göre lightness
                const lightness = isProfitable ? 40 : 60;

                return {
                    id: p.symbol,
                    name: p.name,
                    value: p.current_price * p.net_quantity,
                    pnlPercent: p.unrealized_pnl_percent,
                    assetClass: p.asset_class,
                    color: `hsl(${hue}, 80%, ${lightness}%)`,
                };
            })
    };

    return (
        <div className="w-full h-[300px] rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <ResponsiveTreeMapHtml
                data={rootNode}
                identity="id"
                value="value"
                valueFormat=" >-$0,.2f"
                margin={{ top: 10, right: 10, bottom: 10, left: 10 }}
                labelSkipSize={12}
                labelTextColor={{ from: 'color', modifiers: [ [ 'darker', 3 ] ] }}
                colors={{ datum: 'data.color' }}
                nodeOpacity={0.8}
                borderWidth={1}
                borderColor={{ from: 'color', modifiers: [ [ 'darker', 0.3 ] ] }}
                label={(e) => `${e.id} (${((e.value / rootNode.children.reduce((acc, c) => acc + c.value, 0)) * 100).toFixed(1)}%)`}
                theme={{
                    labels: {
                        text: {
                            fontSize: 12,
                            fontWeight: 'bold',
                            fill: '#ffffff',
                            textShadow: '0px 1px 2px rgba(0,0,0,0.8)'
                        }
                    },
                    tooltip: {
                        container: {
                            background: '#1f2937',
                            color: '#f3f4f6',
                            fontSize: 12,
                            borderRadius: '8px',
                        }
                    }
                }}
            />
        </div>
    );
};

export default TreemapChart;
