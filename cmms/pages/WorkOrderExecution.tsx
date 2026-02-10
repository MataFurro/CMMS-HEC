
import React, { useState } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { WOStatus } from '../types';

const WorkOrderExecution: React.FC = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [loading, setLoading] = useState(false);
  
  // Simulación de detección si es una OT ya finalizada (ejemplo: OT-2024-0742)
  const isCompleted = id === 'OT-2024-0742';

  const handleFinish = () => {
    setLoading(true);
    setTimeout(() => {
      setLoading(false);
      navigate('/work-orders');
    }, 1500);
  };

  const attachments = [
    { name: 'Protocolo_Seguridad_Electrica_IEC62353.pdf', type: 'pdf', size: '1.2 MB', date: '10/05/2024' },
    { name: 'Certificado_Calibracion_Fluke.pdf', type: 'pdf', size: '840 KB', date: '10/05/2024' },
    { name: 'Captura_Falla_Sensor.jpg', type: 'image', size: '2.1 MB', date: '10/05/2024' }
  ];

  const referenceDocs = [
    { name: 'Manual_Servicio_PB840.pdf', category: 'Referencia Técnica' },
    { name: 'Protocolo_Preventivo_Standard.pdf', category: 'Checklist Guía' }
  ];

  return (
    <div className="max-w-[1200px] mx-auto space-y-10 animate-in fade-in duration-500">
      <div className="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
          <nav className="flex items-center gap-2 text-[10px] text-slate-500 uppercase tracking-[0.2em] font-black mb-3">
            <span>Gestión Técnica</span>
            <span className="material-symbols-outlined text-sm">chevron_right</span>
            <Link to="/work-orders" className="hover:text-medical-blue transition-colors">Órdenes</Link>
            <span className="material-symbols-outlined text-sm text-slate-700">chevron_right</span>
            <span className="text-medical-blue">{isCompleted ? 'Reporte Final' : 'Ejecución y Cierre'}</span>
          </nav>
          <div className="flex items-center gap-4">
            <h1 className="text-4xl font-bold tracking-tight text-white">Orden Técnica #{id}</h1>
            <span className={`px-4 py-1.5 border rounded-2xl text-[10px] font-black uppercase tracking-wider flex items-center gap-2 ${
              isCompleted 
              ? 'bg-success/10 text-success border-success/30' 
              : 'bg-amber-500/10 text-amber-500 border-amber-500/30 shadow-[0_0_12px_rgba(245,158,11,0.1)]'
            }`}>
               <span className={`size-2 rounded-full ${isCompleted ? 'bg-success shadow-[0_0_8px_rgba(16,185,129,0.5)]' : 'bg-amber-500 animate-pulse'}`}></span> 
               {isCompleted ? 'FINALIZADA' : 'EN EJECUCIÓN'}
            </span>
          </div>
          <p className="text-slate-400 mt-2 text-lg">
            {isCompleted ? 'Mantenimiento Preventivo Anual' : 'Mantenimiento Correctivo'} · Ventilador Mecánico Puritan Bennett 840
          </p>
        </div>
        <div className="flex gap-4">
          <Link 
            to="/asset/PB-840-00122"
            className="px-6 py-3 bg-white/5 border border-slate-700/50 text-slate-300 rounded-2xl font-bold text-sm flex items-center gap-3 hover:bg-white/10 transition-all"
          >
            <span className="material-symbols-outlined text-xl">history</span>
            Ficha del Activo
          </Link>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-12 gap-10">
        <div className="lg:col-span-8 space-y-8">
          {/* Protocolos de Seguridad */}
          <div className="bg-panel-dark p-8 rounded-3xl border border-slate-700/50 shadow-2xl relative overflow-hidden">
            <div className="absolute top-0 right-0 p-8 opacity-10 pointer-events-none">
               <span className="material-symbols-outlined text-8xl">verified_user</span>
            </div>
            <div className="flex items-center justify-between mb-8">
              <div className="flex items-center gap-4">
                <div className="p-2.5 bg-medical-blue/10 text-medical-blue rounded-xl border border-medical-blue/20">
                  <span className="material-symbols-outlined font-variation-fill">fact_check</span>
                </div>
                <div>
                   <h3 className="text-xl font-bold text-white">Pruebas Normativas</h3>
                   <p className="text-xs text-slate-500 uppercase font-bold tracking-widest mt-0.5">Validación de seguridad eléctrica y funcional</p>
                </div>
              </div>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              {['Seguridad Eléctrica (Fugas)', 'Inspección Visual Chasis', 'Prueba de Batería', 'Calibración Sensores O2'].map((check) => (
                <div key={check} className={`flex items-center gap-4 p-5 border rounded-2xl transition-all ${isCompleted ? 'bg-success/5 border-success/20' : 'bg-white/5 border-slate-700/50 hover:border-medical-blue/30'}`}>
                  <span className={`material-symbols-outlined font-black ${isCompleted ? 'text-success' : 'text-slate-700'}`}>
                    {isCompleted ? 'check_circle' : 'radio_button_unchecked'}
                  </span>
                  <span className={`text-sm font-bold ${isCompleted ? 'text-slate-200' : 'text-slate-400'}`}>{check}</span>
                </div>
              ))}
            </div>
          </div>

          {/* Documentación y Archivos Adjuntos */}
          <div className="bg-panel-dark p-8 rounded-3xl border border-slate-700/50 shadow-2xl">
            <div className="flex items-center justify-between mb-8">
              <div className="flex items-center gap-4">
                <div className="p-2.5 bg-medical-blue/10 text-medical-blue rounded-xl border border-medical-blue/20">
                  <span className="material-symbols-outlined font-variation-fill">attachment</span>
                </div>
                <div>
                   <h3 className="text-xl font-bold text-white">Evidencia y Adjuntos</h3>
                   <p className="text-xs text-slate-500 uppercase font-bold tracking-widest mt-0.5">Protocolos firmados y capturas de pantalla</p>
                </div>
              </div>
              {!isCompleted && (
                <button className="flex items-center gap-2 text-[10px] font-black text-medical-blue uppercase tracking-widest hover:bg-medical-blue/10 px-4 py-2 rounded-xl border border-medical-blue/30 transition-all">
                  <span className="material-symbols-outlined text-sm">cloud_upload</span>
                  Cargar Evidencia
                </button>
              )}
            </div>
            
            <div className="space-y-3">
              {(isCompleted ? attachments : attachments.slice(0, 1)).map((file) => (
                <div key={file.name} className="flex items-center justify-between p-4 bg-white/5 border border-slate-700/50 rounded-2xl group hover:border-medical-blue/30 transition-all">
                  <div className="flex items-center gap-4">
                    <div className={`p-2 rounded-lg ${file.type === 'pdf' ? 'bg-red-500/10 text-red-500 border border-red-500/20' : 'bg-medical-blue/10 text-medical-blue border border-medical-blue/20'}`}>
                      <span className="material-symbols-outlined">{file.type === 'pdf' ? 'picture_as_pdf' : 'image'}</span>
                    </div>
                    <div>
                      <p className="text-sm font-bold text-slate-200 group-hover:text-white transition-colors">{file.name}</p>
                      <p className="text-[10px] text-slate-500 font-bold uppercase tracking-widest">{file.size} · {file.date}</p>
                    </div>
                  </div>
                  <button className="p-2 text-slate-500 hover:text-medical-blue transition-colors">
                    <span className="material-symbols-outlined">download</span>
                  </button>
                </div>
              ))}
              {!isCompleted && (
                <div className="p-8 border-2 border-dashed border-slate-700/50 rounded-2xl flex flex-col items-center justify-center text-slate-600 gap-2 hover:border-medical-blue/50 hover:text-medical-blue transition-all cursor-pointer">
                   <span className="material-symbols-outlined text-3xl">upload_file</span>
                   <p className="text-[10px] font-black uppercase tracking-widest">Arrastra evidencia técnica aquí</p>
                </div>
              )}
            </div>
          </div>

          {/* Informe de Intervención */}
          <div className="bg-panel-dark p-8 rounded-3xl border border-slate-700/50 shadow-2xl relative overflow-hidden">
             <div className="flex items-center gap-4 mb-8">
              <div className="p-2.5 bg-medical-blue/10 text-medical-blue rounded-xl border border-medical-blue/20">
                <span className="material-symbols-outlined font-variation-fill">edit_note</span>
              </div>
              <h3 className="text-xl font-bold text-white">Informe Final de Intervención</h3>
            </div>
            {isCompleted ? (
              <div className="p-6 bg-slate-900/50 border border-slate-700/50 rounded-2xl text-sm text-slate-300 leading-relaxed italic relative">
                <span className="material-symbols-outlined absolute -top-3 -left-3 text-medical-blue text-4xl opacity-20">format_quote</span>
                "Se realiza mantenimiento preventivo anual según cronograma institucional. Se verifican parámetros de seguridad eléctrica bajo norma IEC 62353 con resultados satisfactorios. Se procede a la recalibración de sensores de flujo y reemplazo de kits de mantenimiento semestral. El equipo se entrega en condiciones óptimas de operación a cargo de la jefatura de UCI."
              </div>
            ) : (
              <textarea 
                className="w-full bg-slate-900 border border-slate-700/50 rounded-2xl p-6 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all min-h-[180px] text-slate-300 placeholder:text-slate-700 font-medium"
                placeholder="Describa el trabajo realizado, hallazgos técnicos, repuestos reemplazados y observaciones finales..."
              ></textarea>
            )}
          </div>
        </div>

        <aside className="lg:col-span-4 space-y-8">
          {/* Documentación de Referencia Técnica */}
          <div className="bg-panel-dark p-6 rounded-3xl border border-slate-700/50 shadow-xl">
            <h4 className="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-6">Referencia Técnica del Equipo</h4>
            <div className="space-y-3">
              {referenceDocs.map((doc) => (
                <button key={doc.name} className="w-full flex items-center gap-3 p-3 bg-white/5 border border-transparent hover:border-medical-blue/30 rounded-xl text-left transition-all group">
                   <span className="material-symbols-outlined text-medical-blue opacity-50 group-hover:opacity-100">description</span>
                   <div>
                     <p className="text-[11px] font-bold text-slate-300 group-hover:text-white transition-colors">{doc.name}</p>
                     <p className="text-[9px] text-slate-600 font-bold uppercase">{doc.category}</p>
                   </div>
                </button>
              ))}
            </div>
          </div>

          <div className="bg-panel-dark p-8 rounded-3xl border border-slate-700/50 shadow-2xl sticky top-28">
            <h3 className="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-8">Información de Cierre</h3>
            
            <div className="space-y-6">
              <div className="p-5 bg-white/5 rounded-2xl border border-slate-700/50">
                <p className="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">Técnico Responsable</p>
                <div className="flex items-center gap-4">
                   <div className="size-10 rounded-full bg-medical-blue/20 border border-medical-blue/30 flex items-center justify-center font-black text-medical-blue">AM</div>
                   <div>
                     <p className="text-sm font-bold text-white">Ana Muñoz</p>
                     <p className="text-[9px] text-slate-500 font-bold uppercase tracking-wider">Ing. Electromedicina</p>
                   </div>
                </div>
              </div>

              {isCompleted ? (
                <div className="space-y-4">
                  <div className="p-5 bg-success/5 border border-success/20 rounded-2xl text-center shadow-[inset_0_0_12px_rgba(16,185,129,0.05)]">
                    <span className="text-[10px] font-black text-success uppercase tracking-widest">Condición Final</span>
                    <p className="text-xl font-bold text-white mt-1">Conforme / Operativo</p>
                  </div>
                  <button className="w-full py-4 border border-slate-700/50 text-slate-300 font-black uppercase tracking-widest rounded-2xl hover:bg-white/5 transition-all flex items-center justify-center gap-3 text-xs">
                    <span className="material-symbols-outlined text-xl">print</span>
                    <span>Imprimir Reporte OT</span>
                  </button>
                </div>
              ) : (
                <div className="space-y-4">
                  <button 
                    onClick={handleFinish}
                    disabled={loading}
                    className="w-full py-4 bg-success text-white font-black uppercase tracking-widest rounded-2xl shadow-xl shadow-success/20 hover:bg-success/90 transition-all flex items-center justify-center gap-3 disabled:opacity-50 text-xs"
                  >
                    {loading ? <span className="animate-spin material-symbols-outlined text-xl">sync</span> : <span className="material-symbols-outlined text-xl">verified</span>}
                    <span>Finalizar e Informar</span>
                  </button>
                  <button className="w-full py-4 bg-white/5 border border-slate-700/50 text-slate-300 font-black uppercase tracking-widest rounded-2xl hover:bg-white/10 transition-all flex items-center justify-center gap-3 text-xs">
                    <span className="material-symbols-outlined text-xl">save</span>
                    <span>Guardar Borrador</span>
                  </button>
                </div>
              )}
            </div>
          </div>
        </aside>
      </div>
    </div>
  );
};

export default WorkOrderExecution;
