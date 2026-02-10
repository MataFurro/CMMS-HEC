
import React, { useState } from 'react';
import { useParams, Link } from 'react-router-dom';

const AssetHistory: React.FC = () => {
  const { id } = useParams();
  const [activeTab, setActiveTab] = useState('ots');

  const assetDocs = [
    { name: 'Manual de Servicio_PB840_ES.pdf', type: 'pdf', size: '14.2 MB', category: 'Manual Técnico' },
    { name: 'Especificaciones_Fabricante.pdf', type: 'pdf', size: '2.1 MB', category: 'Datasheet' },
    { name: 'Guia_Rapida_UCI.pdf', type: 'pdf', size: '1.5 MB', category: 'Manual Usuario' },
    { name: 'Certificado_Importacion_Aduana.pdf', type: 'pdf', size: '890 KB', category: 'Legal' }
  ];

  return (
    <div className="space-y-10 animate-in fade-in slide-in-from-bottom-4 duration-500">
      <div className="bg-panel-dark rounded-[2.5rem] border border-border-dark p-8 lg:p-12 shadow-2xl">
        <div className="flex flex-col lg:flex-row gap-12">
          <div className="w-full lg:w-72 h-72 rounded-3xl overflow-hidden border border-border-dark bg-background-dark p-1 shadow-inner shrink-0 group">
            <img 
              src="https://lh3.googleusercontent.com/aida-public/AB6AXuArjc0RB-oPKqM3OEkdyKO5qx0pqx3tnnDtgQIHmBy0OPhWndzJRDGmHAcSYe5KMj0OjejmEqQHwFvzj3j49_uv32qOSGRi45_B0VwA769XNTkLdWndUI_FM0j2hmcjFtaudmO_Y7PVvrQYFCicy5r0hOsgef2wmHu8tH4m42rvSfGyQ0ijsJnKLkakgcGce8Iu_LCpMrDOwXVMHGj1pEW6dn2BZOSGHAPH7GUrrvLeB-Sphiq9IgFn8INtJB9-UCwIwvp96rzTMKE" 
              className="w-full h-full object-cover rounded-[1.2rem] group-hover:scale-105 transition-transform duration-700" 
              alt="Asset" 
            />
          </div>
          <div className="flex-grow">
            <div className="flex flex-wrap items-center gap-4 mb-6">
              <nav className="flex items-center gap-2 text-[10px] text-slate-500 uppercase tracking-[0.2em] font-black w-full mb-3">
                <span>Inventario Técnico</span>
                <span className="material-symbols-outlined text-sm">chevron_right</span>
                <span className="text-medical-blue">Hoja de Vida y Historial de OTs</span>
              </nav>
              <h1 className="text-5xl font-bold tracking-tight text-white">Ventilador Mecánico PB840</h1>
              <span className="px-5 py-2 rounded-2xl text-[10px] font-black uppercase tracking-wider bg-success/10 text-success border border-success/30 flex items-center gap-2 shadow-[0_0_12px_rgba(16,185,129,0.1)]">
                <span className="size-2 rounded-full bg-success animate-pulse"></span> OPERATIVO
              </span>
            </div>
            <div className="flex items-center gap-4 text-slate-500 mb-10 font-mono text-sm uppercase font-bold tracking-widest">
              <span className="bg-white/5 border border-white/5 px-4 py-1.5 rounded-xl">ID: {id}</span>
              <span className="bg-white/5 border border-white/5 px-4 py-1.5 rounded-xl">S/N: 450912234-PB</span>
            </div>
            <div className="grid grid-cols-2 md:grid-cols-3 gap-10 py-10 border-t border-border-dark">
              <div>
                <p className="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-2">Marca / Modelo</p>
                <p className="text-base font-bold text-white">Puritan Bennett / 840</p>
              </div>
              <div>
                <p className="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-2">Criticidad</p>
                <p className="text-base font-bold text-danger flex items-center gap-1.5">
                   <span className="material-symbols-outlined text-sm fill-1">bolt</span> Crítico (Soporte Vital)
                </p>
              </div>
              <div>
                <p className="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-2">Ubicación Actual</p>
                <p className="text-base font-bold text-white">UCI Adultos - Box 04</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div className="flex flex-col lg:flex-row gap-10">
        <div className="flex-grow space-y-10">
          <div className="border-b border-border-dark overflow-x-auto no-scrollbar">
            <nav className="flex gap-12 min-w-max">
              {[
                { id: 'ots', label: 'Historial de Órdenes (OTs)' },
                { id: 'specs', label: 'Especificaciones' },
                { id: 'docs', label: 'Documentación' }
              ].map((tab) => (
                <button 
                  key={tab.id} 
                  onClick={() => setActiveTab(tab.id)}
                  className={`pb-5 text-[11px] font-black uppercase tracking-[0.15em] transition-all relative ${activeTab === tab.id ? 'text-medical-blue' : 'text-slate-500 hover:text-slate-300'}`}
                >
                  {tab.label}
                  {activeTab === tab.id && <div className="absolute bottom-0 left-0 right-0 h-1 bg-medical-blue rounded-t-full shadow-[0_0_8px_rgba(14,165,233,0.5)]"></div>}
                </button>
              ))}
            </nav>
          </div>

          {activeTab === 'ots' && (
            <div className="relative pl-12 space-y-14 before:content-[''] before:absolute before:left-[11px] before:top-2 before:bottom-0 before:w-1 before:bg-border-dark before:rounded-full">
              <div className="relative">
                <div className="absolute -left-[3.1rem] top-0 size-8 bg-success rounded-full border-4 border-background-dark flex items-center justify-center z-10 shadow-lg shadow-success/20">
                  <span className="material-symbols-outlined text-sm text-white font-black">check</span>
                </div>
                <div className="bg-panel-dark p-8 rounded-3xl border border-border-dark shadow-2xl hover:border-success/40 transition-all group">
                  <div className="flex justify-between items-start mb-6">
                    <div>
                      <h4 className="font-bold text-xl text-white group-hover:text-medical-blue transition-colors">OT #OT-2024-0742 · Preventivo Anual</h4>
                      <p className="text-xs text-slate-500 font-bold uppercase tracking-widest mt-1">Ana Muñoz · <span className="text-success font-black">FINALIZADA</span></p>
                    </div>
                    <span className="text-[11px] font-black font-mono text-slate-500 bg-white/5 px-3 py-1 rounded-lg uppercase tracking-widest">10/05/2024</span>
                  </div>
                  <div className="text-sm text-slate-400 leading-relaxed font-medium">
                    Mantenimiento preventivo anual y cambio de parches. Protocolo de seguridad eléctrica satisfactorio.
                  </div>
                  <div className="mt-6 pt-6 border-t border-white/5 flex gap-4">
                    <Link 
                      to="/work-order/OT-2024-0742/execute"
                      className="text-[10px] font-black text-slate-500 uppercase tracking-widest hover:text-medical-blue transition-colors flex items-center gap-2"
                    >
                      <span className="material-symbols-outlined text-sm">visibility</span> Ver Reporte OT
                    </Link>
                  </div>
                </div>
              </div>

              <div className="relative">
                <div className="absolute -left-[3.1rem] top-0 size-8 bg-amber-500 rounded-full border-4 border-background-dark flex items-center justify-center z-10 shadow-lg shadow-amber-500/20">
                  <span className="material-symbols-outlined text-sm text-white font-black">build</span>
                </div>
                <div className="bg-panel-dark p-8 rounded-3xl border border-border-dark shadow-2xl hover:border-amber-500/40 transition-all group">
                  <div className="flex justify-between items-start mb-6">
                    <div>
                      <h4 className="font-bold text-xl text-white group-hover:text-amber-500 transition-colors">OT #1105 · Correctivo - Sensores</h4>
                      <p className="text-xs text-slate-500 font-bold uppercase tracking-widest mt-1">Mantenimiento Externo · <span className="text-amber-500 font-black">REPARADO</span></p>
                    </div>
                    <span className="text-[11px] font-black font-mono text-slate-500 bg-white/5 px-3 py-1 rounded-lg uppercase tracking-widest">15/02/2024</span>
                  </div>
                  <div className="text-sm text-slate-400 italic bg-white/5 p-5 rounded-2xl border border-white/5">
                    "Se detecta falla en sensor de oxígeno durante ronda diaria de bioingeniería. Se procede al reemplazo de emergencia y recalibración de flujo espiratorio."
                  </div>
                </div>
              </div>
            </div>
          )}

          {activeTab === 'docs' && (
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              {assetDocs.map((doc) => (
                <div key={doc.name} className="bg-panel-dark p-5 rounded-2xl border border-border-dark flex items-center justify-between group hover:border-medical-blue/30 transition-all">
                  <div className="flex items-center gap-4">
                    <div className="size-12 rounded-xl bg-red-500/10 text-red-500 flex items-center justify-center border border-red-500/20">
                       <span className="material-symbols-outlined">picture_as_pdf</span>
                    </div>
                    <div>
                      <p className="text-sm font-bold text-white group-hover:text-medical-blue transition-colors">{doc.name}</p>
                      <div className="flex items-center gap-3 mt-1">
                        <span className="text-[10px] font-black text-slate-500 uppercase tracking-widest">{doc.category}</span>
                        <span className="text-[10px] text-slate-600 font-bold">{doc.size}</span>
                      </div>
                    </div>
                  </div>
                  <button className="p-2 text-slate-500 hover:text-white transition-colors">
                    <span className="material-symbols-outlined">download</span>
                  </button>
                </div>
              ))}
            </div>
          )}

          {activeTab === 'specs' && (
            <div className="bg-panel-dark p-8 rounded-3xl border border-border-dark shadow-2xl space-y-8">
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div>
                  <p className="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Clasificación de Riesgo</p>
                  <p className="text-sm font-bold text-white">Clase IIb (Alto Riesgo)</p>
                </div>
                <div>
                  <p className="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Alimentación</p>
                  <p className="text-sm font-bold text-white">100-240V AC / 50-60Hz</p>
                </div>
                <div>
                  <p className="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Batería Interna</p>
                  <p className="text-sm font-bold text-white">NiMH - Autonomía 60 min</p>
                </div>
              </div>
            </div>
          )}
        </div>

        <aside className="w-full lg:w-96 space-y-8 shrink-0">
          <div className="bg-panel-dark p-8 rounded-[2rem] border border-border-dark shadow-2xl sticky top-28">
            <h4 className="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-8">Acciones de Gestión</h4>
            <div className="space-y-4">
              <Link to="/work-orders/new" className="w-full py-4 bg-medical-blue text-white font-black uppercase tracking-widest rounded-2xl shadow-xl shadow-medical-blue/25 hover:bg-medical-blue/90 transition-all flex items-center justify-center gap-3">
                <span className="material-symbols-outlined text-xl">add_box</span>
                <span>Programar Nueva OT</span>
              </Link>
              <button className="w-full py-3.5 border border-border-dark text-slate-300 font-black uppercase tracking-widest rounded-2xl hover:bg-white/5 transition-all text-[11px] flex items-center justify-center gap-3">
                <span className="material-symbols-outlined text-xl">qr_code_2</span>
                <span>Etiqueta QR</span>
              </button>
              <button className="w-full py-3.5 border border-border-dark text-slate-300 font-black uppercase tracking-widest rounded-2xl hover:bg-white/5 transition-all text-[11px] flex items-center justify-center gap-3">
                <span className="material-symbols-outlined text-xl">picture_as_pdf</span>
                <span>Descargar Hoja de Vida</span>
              </button>
            </div>
          </div>
        </aside>
      </div>
    </div>
  );
};

export default AssetHistory;
