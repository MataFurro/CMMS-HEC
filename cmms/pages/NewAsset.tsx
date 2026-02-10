
import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { AssetCriticality } from '../types';
import { UI_LABELS } from '../constants';

const NewAsset: React.FC = () => {
    const navigate = useNavigate();
    const [formData, setFormData] = useState({
        id: '',
        name: '',
        brand: '',
        model: '',
        serialNumber: '',
        location: '',
        criticality: AssetCriticality.RELEVANT,
        usefulLife: 100,
        yearsRemaining: 10,
        warranty: '',
        warrantyExpiration: '',
        underMaintenancePlan: true
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        // In a real app, this would save to a database
        console.log('Guardando activo:', formData);
        alert('Activo registrado exitosamente');
        navigate('/inventory');
    };

    return (
        <div className="max-w-4xl mx-auto space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight text-white flex items-center gap-3">
                        <span className="material-symbols-outlined text-medical-blue text-3xl">add_circle</span>
                        Registro de Nuevo Activo
                    </h1>
                    <p className="text-slate-400 mt-1 text-sm uppercase tracking-widest font-semibold opacity-70">
                        Completar datos técnicos según norma CMMS HEC
                    </p>
                </div>
                <button
                    onClick={() => navigate('/inventory')}
                    className="p-2.5 rounded-xl bg-slate-800 border border-slate-700 text-slate-400 hover:text-white transition-all flex items-center gap-2 text-xs font-bold uppercase tracking-wider"
                >
                    <span className="material-symbols-outlined text-sm">arrow_back</span>
                    Volver
                </button>
            </div>

            <form onSubmit={handleSubmit} className="card-glass p-8 space-y-8 shadow-2xl relative overflow-hidden">
                <div className="absolute top-0 right-0 w-32 h-32 bg-medical-blue/5 blur-3xl rounded-full -mr-16 -mt-16"></div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-8 relative z-10">
                    {/* Identification Section */}
                    <div className="space-y-6">
                        <h3 className="text-[10px] font-black uppercase tracking-[0.2em] text-medical-blue border-b border-medical-blue/20 pb-2">Identificación de Equipo</h3>

                        <div className="space-y-2">
                            <label className="text-xs font-bold text-slate-400 uppercase tracking-wider">ID Inventario</label>
                            <input
                                required
                                value={formData.id}
                                onChange={(e) => setFormData({ ...formData, id: e.target.value })}
                                placeholder="Ej: PB-840-00122"
                                className="w-full bg-slate-900 border border-slate-700/50 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-white"
                            />
                        </div>

                        <div className="space-y-2">
                            <label className="text-xs font-bold text-slate-400 uppercase tracking-wider">Nombre del Equipo</label>
                            <input
                                required
                                value={formData.name}
                                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                placeholder="Ej: Ventilador Mecánico"
                                className="w-full bg-slate-900 border border-slate-700/50 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-white"
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <label className="text-xs font-bold text-slate-400 uppercase tracking-wider">Marca</label>
                                <input
                                    required
                                    value={formData.brand}
                                    onChange={(e) => setFormData({ ...formData, brand: e.target.value })}
                                    className="w-full bg-slate-900 border border-slate-700/50 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-white"
                                />
                            </div>
                            <div className="space-y-2">
                                <label className="text-xs font-bold text-slate-400 uppercase tracking-wider">Modelo</label>
                                <input
                                    required
                                    value={formData.model}
                                    onChange={(e) => setFormData({ ...formData, model: e.target.value })}
                                    className="w-full bg-slate-900 border border-slate-700/50 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-white"
                                />
                            </div>
                        </div>

                        <div className="space-y-2">
                            <label className="text-xs font-bold text-slate-400 uppercase tracking-wider">Bajo Plan de Mantenimiento</label>
                            <div className="flex gap-4">
                                <label className="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="radio"
                                        checked={formData.underMaintenancePlan}
                                        onChange={() => setFormData({ ...formData, underMaintenancePlan: true })}
                                        className="size-4 accent-medical-blue"
                                    />
                                    <span className="text-sm text-slate-300">Sí</span>
                                </label>
                                <label className="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="radio"
                                        checked={!formData.underMaintenancePlan}
                                        onChange={() => setFormData({ ...formData, underMaintenancePlan: false })}
                                        className="size-4 accent-medical-blue"
                                    />
                                    <span className="text-sm text-slate-300">No</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    {/* Technical Specs Section */}
                    <div className="space-y-6">
                        <h3 className="text-[10px] font-black uppercase tracking-[0.2em] text-medical-blue border-b border-medical-blue/20 pb-2">Especificaciones Técnicas</h3>

                        <div className="space-y-2">
                            <label className="text-xs font-bold text-slate-400 uppercase tracking-wider">Ubicación</label>
                            <select
                                required
                                value={formData.location}
                                onChange={(e) => setFormData({ ...formData, location: e.target.value })}
                                className="w-full bg-slate-900 border border-slate-700/50 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-white appearance-none"
                            >
                                <option value="">Seleccionar Ubicación</option>
                                <option value="UCI Adultos">UCI Adultos</option>
                                <option value="Urgencias">Urgencias</option>
                                <option value="Pabellón Central">Pabellón Central</option>
                                <option value="Imagenología">Imagenología</option>
                            </select>
                        </div>

                        <div className="space-y-2">
                            <label className="text-xs font-bold text-slate-400 uppercase tracking-wider">Criticidad</label>
                            <select
                                required
                                value={formData.criticality}
                                onChange={(e) => setFormData({ ...formData, criticality: e.target.value as AssetCriticality })}
                                className="w-full bg-slate-900 border border-slate-700/50 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-white appearance-none"
                            >
                                <option value={AssetCriticality.CRITICAL}>{AssetCriticality.CRITICAL}</option>
                                <option value={AssetCriticality.RELEVANT}>{AssetCriticality.RELEVANT}</option>
                                <option value={AssetCriticality.LOW}>{AssetCriticality.LOW}</option>
                                <option value={AssetCriticality.NA}>{AssetCriticality.NA}</option>
                            </select>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <label className="text-xs font-bold text-slate-400 uppercase tracking-wider">Garantía (Proveedor)</label>
                                <input
                                    value={formData.warranty}
                                    onChange={(e) => setFormData({ ...formData, warranty: e.target.value })}
                                    placeholder="Ej: Medtronic Chile"
                                    className="w-full bg-slate-900 border border-slate-700/50 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-white"
                                />
                            </div>
                            <div className="space-y-2">
                                <label className="text-xs font-bold text-slate-400 uppercase tracking-wider">Vencimiento Garantía</label>
                                <input
                                    type="date"
                                    value={formData.warrantyExpiration}
                                    onChange={(e) => setFormData({ ...formData, warrantyExpiration: e.target.value })}
                                    className="w-full bg-slate-900 border border-slate-700/50 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-white"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <label className="text-xs font-bold text-slate-400 uppercase tracking-wider">Vida Útil (%)</label>
                                <input
                                    type="number"
                                    value={formData.usefulLife}
                                    onChange={(e) => setFormData({ ...formData, usefulLife: parseInt(e.target.value) })}
                                    className="w-full bg-slate-900 border border-slate-700/50 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-white"
                                />
                            </div>
                            <div className="space-y-2">
                                <label className="text-xs font-bold text-slate-400 uppercase tracking-wider">Años Restantes</label>
                                <input
                                    type="number"
                                    value={formData.yearsRemaining}
                                    onChange={(e) => setFormData({ ...formData, yearsRemaining: parseInt(e.target.value) })}
                                    className="w-full bg-slate-900 border border-slate-700/50 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all text-white"
                                />
                            </div>
                        </div>
                    </div>
                </div>


                <div className="pt-8 border-t border-slate-700/50 flex justify-end gap-4">
                    <button
                        type="button"
                        onClick={() => navigate('/inventory')}
                        className="px-8 py-3 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800 transition-all text-sm font-bold uppercase tracking-wider"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        className="px-10 py-3 bg-medical-blue text-white rounded-2xl hover:bg-medical-blue/90 transition-all font-bold shadow-xl shadow-medical-blue/20 active:scale-95 flex items-center gap-3"
                    >
                        <span className="material-symbols-outlined text-xl">save</span>
                        Guardar Activo
                    </button>
                </div>
            </form>
        </div>
    );
};

export default NewAsset;
