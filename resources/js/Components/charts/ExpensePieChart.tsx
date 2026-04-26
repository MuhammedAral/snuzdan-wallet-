import React from 'react';
import { ResponsivePie } from '@nivo/pie';

/**
 * ExpensePieChart — Harcama Dağılımı Pasta Grafiği
 *
 * @nivo/pie kullanarak harcamaların kategorilere göre dağılımını gösterir.
 */

interface ExpenseCategory {
    id: string; // Kategori adı
    label: string;
    value: number; // Toplam harcama tutarı
    color?: string;
}

interface ExpensePieChartProps {
    data: ExpenseCategory[];
}

const ExpensePieChart: React.FC<ExpensePieChartProps> = ({ data }) => {
    if (data.length === 0) {
        return (
            <div className="flex items-center justify-center h-full text-gray-400">
                <div className="text-center">
                    <span className="text-3xl block mb-2">🍽️</span>
                    <span className="text-sm">Harcama verisi yok</span>
                </div>
            </div>
        );
    }

    return (
        <div className="w-full h-full min-h-[250px]">
            <ResponsivePie
                data={data}
                margin={{ top: 20, right: 20, bottom: 20, left: 20 }}
                innerRadius={0.6}
                padAngle={2}
                cornerRadius={5}
                activeOuterRadiusOffset={8}
                colors={{ scheme: 'category10' }}
                borderWidth={1}
                borderColor={{
                    from: 'color',
                    modifiers: [ [ 'darker', 0.2 ] ]
                }}
                enableArcLinkLabels={false}
                arcLabel={(e) => `${e.id} (%${((e.value / data.reduce((acc, d) => acc + d.value, 0)) * 100).toFixed(0)})`}
                arcLabelsSkipAngle={15}
                arcLabelsTextColor="#ffffff"
                theme={{
                    labels: {
                        text: {
                            fontSize: 11,
                            fontWeight: 'bold',
                            textShadow: '0px 1px 2px rgba(0,0,0,0.5)'
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

export default ExpensePieChart;
