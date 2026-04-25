import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';
import { Plus, Check } from 'lucide-react';
import Modal from '@/Components/Modal';

interface Category {
    id: string;
    name: string;
    icon: string | null;
    color: string | null;
    cat_type: 'SYSTEM' | 'CUSTOM';
}

interface CategoryPickerProps {
    direction: 'INCOME' | 'EXPENSE';
    selectedId?: string | null;
    onChange: (id: string) => void;
}

export default function CategoryPicker({ direction, selectedId, onChange }: CategoryPickerProps) {
    const queryClient = useQueryClient();
    const [isModalOpen, setIsModalOpen] = useState(false);
    
    // New category state
    const [newName, setNewName] = useState('');
    const [newIcon, setNewIcon] = useState('📌');
    const [newColor, setNewColor] = useState('#AEB6BF');
    const [error, setError] = useState('');

    const { data: categories = [], isLoading } = useQuery<Category[]>({
        queryKey: ['categories', direction],
        queryFn: async () => {
            const { data } = await axios.get(`/api/categories?direction=${direction}`);
            return data;
        },
    });

    const createMutation = useMutation({
        mutationFn: async (newCategory: { name: string; icon: string; color: string; direction: string }) => {
            const { data } = await axios.post('/api/categories', newCategory);
            return data;
        },
        onSuccess: (data) => {
            queryClient.invalidateQueries({ queryKey: ['categories', direction] });
            onChange(data.id);
            setIsModalOpen(false);
            setNewName('');
            setNewIcon('📌');
            setError('');
        },
        onError: (err: any) => {
            setError(err.response?.data?.message || 'Bir hata oluştu.');
        }
    });

    const handleCreate = (e: React.FormEvent) => {
        e.preventDefault();
        createMutation.mutate({
            name: newName,
            icon: newIcon,
            color: newColor,
            direction,
        });
    };

    if (isLoading) {
        return <div className="animate-pulse h-24 bg-slate-800/50 rounded-xl w-full"></div>;
    }

    return (
        <div className="w-full">
            <label className="block text-sm font-medium text-slate-300 mb-2">Kategori</label>
            
            <div className="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3">
                {categories.map((cat) => (
                    <button
                        key={cat.id}
                        type="button"
                        onClick={() => onChange(cat.id)}
                        className={`select-none relative flex flex-col items-center justify-center p-3 rounded-xl border transition-all ${
                            selectedId === cat.id 
                            ? 'bg-indigo-500/20 border-indigo-500 ring-1 ring-indigo-500' // Selected
                            : 'bg-slate-900/50 border-slate-700 hover:bg-slate-800 hover:border-slate-600' // Default
                        }`}
                        style={{ boxShadow: selectedId === cat.id && cat.color ? `0 0 10px ${cat.color}40` : 'none' }}
                    >
                        <span className="text-2xl mb-1">{cat.icon}</span>
                        <span className="text-xs font-medium text-slate-300 text-center truncate w-full" title={cat.name}>
                            {cat.name}
                        </span>
                        
                        {selectedId === cat.id && (
                            <div className="absolute top-1 right-1 bg-indigo-500 rounded-full p-0.5">
                                <Check size={10} className="text-white" />
                            </div>
                        )}
                    </button>
                ))}

                {/* Add New Button */}
                <button
                    type="button"
                    onClick={() => setIsModalOpen(true)}
                    className="select-none flex flex-col items-center justify-center p-3 rounded-xl border border-dashed border-slate-700 hover:border-slate-500 hover:bg-slate-800/50 text-slate-400 hover:text-slate-200 transition-colors"
                >
                    <Plus size={24} className="mb-1" />
                    <span className="text-xs font-medium text-center">Ekle</span>
                </button>
            </div>

            {/* New Category Modal */}
            <Modal show={isModalOpen} onClose={() => setIsModalOpen(false)}>
                <div className="p-6 bg-slate-900 border border-slate-800 rounded-lg">
                    <h2 className="text-lg font-medium text-white mb-4">
                        Yeni {direction === 'INCOME' ? 'Gelir' : 'Gider'} Kategorisi Oluştur
                    </h2>
                    
                    {error && (
                        <div className="mb-4 text-sm text-rose-400 bg-rose-400/10 p-3 rounded-lg border border-rose-400/20">
                            {error}
                        </div>
                    )}

                    <form onSubmit={handleCreate} className="flex flex-col gap-4">
                        <div>
                            <label className="block text-sm font-medium text-slate-300 mb-1">Emoji / İkon</label>
                            <input 
                                type="text"
                                maxLength={2}
                                value={newIcon}
                                onChange={(e) => setNewIcon(e.target.value)}
                                className="bg-slate-950 border border-slate-700 text-slate-200 text-xl rounded-xl block w-full p-3 h-14"
                                required
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-slate-300 mb-1">Kategori Adı</label>
                            <input 
                                type="text"
                                value={newName}
                                onChange={(e) => setNewName(e.target.value)}
                                className="bg-slate-950 border border-slate-700 text-slate-200 text-sm rounded-xl block w-full p-3 h-12"
                                placeholder="Örn: Market"
                                required
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-slate-300 mb-1">Renk</label>
                            <div className="flex gap-2 items-center">
                                <input 
                                    type="color"
                                    value={newColor}
                                    onChange={(e) => setNewColor(e.target.value)}
                                    className="h-10 w-10 p-0 border-0 rounded cursor-pointer bg-transparent"
                                />
                                <span className="text-xs text-slate-500 uppercase">{newColor}</span>
                            </div>
                        </div>

                        <div className="flex justify-end gap-3 mt-4">
                            <button
                                type="button"
                                onClick={() => setIsModalOpen(false)}
                                className="px-4 py-2 text-sm font-medium text-slate-400 hover:text-white transition-colors"
                            >
                                İptal
                            </button>
                            <button
                                type="submit"
                                disabled={createMutation.isPending}
                                className="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-sm font-medium disabled:opacity-70 transition-colors"
                            >
                                {createMutation.isPending ? 'Oluşturuluyor...' : 'Oluştur'}
                            </button>
                        </div>
                    </form>
                </div>
            </Modal>
        </div>
    );
}
