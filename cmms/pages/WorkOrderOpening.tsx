
import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';

const WorkOrderOpening: React.FC = () => {
  const navigate = useNavigate();
  const [criticality, setCriticality] = useState('CRITICAL');

  return (
    <div className="max-w-[1200px] mx-auto space-y-10 animate-in fade-in duration-500">
      {/* Header y Breadcrumbs */}
      <div className="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div className="space-y-3">
          <nav className="flex items-center gap-2 text-[10px] text-slate-500 uppercase tracking-[0.2em] font-black">
            <span>Órdenes de Trabajo</span>
            <span className="material-symbols-outlined text-sm">chevron_right</span>
            <span className="text-medical-blue">Apertura y Asignación</span>
          </nav>
          <h1 className="text-4xl font-bold tracking-tight text-white">Apertura de Orden de Trabajo</h1>
          <p className="text-slate-400 font-medium">Gestión de inicio y asignación técnica - Estándar Biomédico</p>
        </div>
        <div className="bg-medical-blue/10 border border-medical-blue/20 px-4 py-2 rounded-xl flex items-center gap-3">
          <div className="flex flex-col">
            <span className="text-[9px] font-black text-slate-500 uppercase tracking-widest">Fase Actual</span>
            <span className="text-xs font-bold text-white flex items-center gap-2">
              <span className="material-symbols-outlined text-sm text-medical-blue">assignment_ind</span>
              Ingreso y Asignación
            </span>
          </div>
        </div>
      </div>

      <div className="space-y-6">
        {/* Sección 1: Datos del Equipo y Recepción */}
        <div className="bg-panel-dark p-8 rounded-[2rem] border border-slate-700/30 shadow-2xl relative overflow-hidden group">
          <div className="flex items-center gap-4 mb-10">
            <div className="p-3 bg-medical-blue/10 text-medical-blue rounded-2xl border border-medical-blue/20">
              <span className="material-symbols-outlined text-2xl font-variation-fill">inventory_2</span>
            </div>
            <h3 className="text-lg font-bold text-white">1. Datos del Equipo y Recepción</h3>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-8">
            {/* Identificación del Activo */}
            <div className="space-y-3">
              <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Identificación del Activo</label>
              <div className="relative flex items-center">
                <span className="material-symbols-outlined absolute left-4 text-slate-500">qr_code_scanner</span>
                <input 
                  type="text" 
                  defaultValue="V-042" 
                  className="w-full bg-slate-900 border border-slate-700/50 rounded-xl pl-12 pr-24 py-3.5 text-sm font-bold text-white focus:border-medical-blue outline-none transition-all"
                />
                <button className="absolute right-2 px-4 py-1.5 bg-medical-blue/10 text-medical-blue text-[10px] font-black uppercase rounded-lg hover:bg-medical-blue/20 transition-all border border-medical-blue/20">
                  Buscar
                </button>
              </div>
            </div>

            {/* Nivel de Criticidad - EDITABLE */}
            <div className="space-y-3">
              <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Nivel de Criticidad</label>
              <div className="relative">
                <select 
                  value={criticality}
                  onChange={(e) => setCriticality(e.target.value)}
                  className={`w-full appearance-none px-5 py-3.5 rounded-xl border font-black uppercase tracking-wider text-xs outline-none transition-all cursor-pointer ${
                    criticality === 'CRITICAL' 
                      ? 'bg-red-500/10 border-red-500/30 text-red-500' 
                      : criticality === 'RELEVANT'
                      ? 'bg-amber-500/10 border-amber-500/30 text-amber-500'
                      : 'bg-slate-900 border-slate-700/50 text-slate-300'
                  }`}
                >
                  <option value="CRITICAL" className="bg-medical-dark text-red-500">! CRÍTICO - Apoyo de Vida</option>
                  <option value="RELEVANT" className="bg-medical-dark text-amber-500">RELEVANTE - Diagnóstico</option>
                  <option value="LOW" className="bg-medical-dark text-slate-400">BAJO - Administrativo/Apoyo</option>
                </select>
                <span className="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none opacity-50">expand_more</span>
              </div>
            </div>

            {/* Nombre del Equipo - EDITABLE */}
            <div className="space-y-3">
              <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Nombre del Equipo</label>
              <input 
                type="text" 
                defaultValue="Ventilador Mecánico Adulto PB840" 
                className="w-full bg-slate-900 border border-slate-700/50 rounded-xl px-5 py-3.5 text-sm font-bold text-white focus:border-medical-blue outline-none transition-all"
              />
            </div>

            {/* Quién Recibe */}
            <div className="space-y-3">
              <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Quién Recibe (Solicitante en Unidad)</label>
              <div className="relative flex items-center">
                <span className="material-symbols-outlined absolute left-4 text-slate-400">person_search</span>
                <input 
                  type="text" 
                  placeholder="Nombre de enfermero/a o médico..." 
                  className="w-full bg-slate-900 border border-slate-700/50 rounded-xl pl-12 pr-5 py-3.5 text-sm text-white focus:border-medical-blue outline-none transition-all placeholder:text-slate-700"
                />
              </div>
            </div>

            {/* Ubicación / Servicio - EDITABLE */}
            <div className="space-y-3">
              <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Ubicación / Servicio</label>
              <input 
                type="text" 
                defaultValue="UCI Adultos - Piso 4, Ala Norte" 
                className="w-full bg-slate-900 border border-slate-700/50 rounded-xl px-5 py-3.5 text-sm font-bold text-white focus:border-medical-blue outline-none transition-all"
              />
            </div>

            {/* Motivo Inicial */}
            <div className="space-y-3">
              <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Motivo Inicial de Apertura</label>
              <input 
                type="text" 
                placeholder="Ej: Falla en sensor, Preventivo según plan..." 
                className="w-full bg-slate-900 border border-slate-700/50 rounded-xl px-5 py-3.5 text-sm text-white focus:border-medical-blue outline-none transition-all placeholder:text-slate-700"
              />
            </div>
          </div>
        </div>

        {/* Sección 2: Gestión y Asignación */}
        <div className="bg-panel-dark p-8 rounded-[2rem] border border-slate-700/30 shadow-2xl relative overflow-hidden group">
          <div className="flex items-center gap-4 mb-10">
            <div className="p-3 bg-medical-blue/10 text-medical-blue rounded-2xl border border-medical-blue/20">
              <span className="material-symbols-outlined text-2xl font-variation-fill">engineering</span>
            </div>
            <h3 className="text-lg font-bold text-white">2. Gestión y Asignación de OT</h3>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-8">
            <div className="space-y-3">
              <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Ingeniero Responsable (Supervisor)</label>
              <input 
                type="text" 
                readOnly 
                defaultValue="Ing. Carlos Ruiz" 
                className="w-full bg-slate-900/50 border border-slate-700/30 rounded-xl px-5 py-3.5 text-sm font-bold text-slate-300 outline-none cursor-not-allowed"
              />
            </div>

            <div className="space-y-3 text-white">
              <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Técnico Ejecutor Asignado</label>
              <select className="w-full bg-slate-900 border border-slate-700/50 rounded-xl px-5 py-3.5 text-sm focus:border-medical-blue outline-none transition-all appearance-none cursor-pointer">
                <option value="">Seleccione técnico ejecutor...</option>
                <option value="1">Mario Lagos - Especialista UCI</option>
                <option value="2">Ana Muñoz - Electromedicina</option>
                <option value="3">Carlos Ruiz - Generalista</option>
              </select>
            </div>
          </div>

          {/* Nota de Flujo */}
          <div className="mt-12 p-6 bg-medical-blue/5 border border-medical-blue/20 rounded-2xl flex gap-4">
            <span className="material-symbols-outlined text-medical-blue shrink-0">info</span>
            <div className="space-y-1">
              <p className="text-[11px] font-black text-medical-blue uppercase tracking-widest">Nota de Flujo de Trabajo:</p>
              <p className="text-[11px] text-slate-500 leading-relaxed font-medium">
                Los detalles técnicos del mantenimiento (repuestos, horas hombre, protocolos de medición y observaciones finales) serán registrados por el técnico asignado en la pantalla de <span className="text-white font-bold italic underline">Ejecución y Cierre de OT</span> una vez finalizada la intervención.
              </p>
            </div>
          </div>
        </div>
      </div>

      {/* Botones de Acción */}
      <div className="flex flex-col sm:flex-row justify-end items-center gap-6 pt-10 border-t border-slate-700/30">
        <button 
          onClick={() => navigate('/work-orders')}
          className="text-xs font-black text-slate-500 hover:text-white uppercase tracking-[0.2em] transition-colors"
        >
          Cancelar
        </button>
        <button 
          onClick={() => navigate('/work-orders')}
          className="px-10 py-4 bg-medical-blue text-white rounded-2xl font-black uppercase tracking-widest text-xs flex items-center gap-4 hover:bg-medical-blue/90 transition-all shadow-xl shadow-medical-blue/20 active:scale-95"
        >
          <span className="material-symbols-outlined text-xl">send</span>
          Abrir y Notificar OT
        </button>
      </div>

      {/* Footer minimalista */}
      <footer className="flex flex-col md:flex-row justify-between items-center py-10 opacity-30 gap-6">
        <p className="text-[9px] font-black text-slate-500 uppercase tracking-widest">© 2024 Biomedical Systems. Gestión Clínica Hospitalaria.</p>
        <div className="flex gap-8 text-[9px] font-black text-slate-500 uppercase tracking-widest">
           <a href="#" className="hover:text-white transition-colors">Estado del Sistema</a>
           <a href="#" className="hover:text-white transition-colors">Manual de Usuario</a>
           <a href="#" className="hover:text-white transition-colors">Soporte Técnico</a>
        </div>
      </footer>
    </div>
  );
};

export default WorkOrderOpening;
