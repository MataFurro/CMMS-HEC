
import React, { useState, useMemo } from 'react';
import { Link } from 'react-router-dom';
import { WOStatus, WorkOrder } from '../types';

const mockWOs: WorkOrder[] = [
  {
    id: 'OT-2024-0892',
    assetId: 'PB-840-00122',
    assetName: 'Ventilador Mecánico Adulto',
    type: 'Correctivo',
    status: WOStatus.IN_PROGRESS,
    priority: 'Alta',
    technician: 'Mario Lagos',
    requester: 'Dr. Andrés Soto',
    date: '14/05/2024',
    description: 'Falla intermitente en sensor O2 - Requiere calibración.',
    location: 'UCI Adultos - Box 04'
  },
  {
    id: 'OT-2024-0742',
    assetId: 'ZL-X-44211',
    assetName: 'Desfibrilador Zoll X',
    type: 'Preventivo',
    status: WOStatus.COMPLETED,
    priority: 'Media',
    technician: 'Ana Muñoz',
    requester: 'Enf. Central',
    date: '10/05/2024',
    description: 'Mantenimiento preventivo anual y cambio de parches.',
    location: 'Urgencias',
    attachments: [
      { name: 'Protocolo_Seguridad_Zoll.pdf', type: 'pdf', size: '1.2 MB', url: '#' },
      { name: 'Foto_Post_Mantenimiento.jpg', type: 'image', size: '2.4 MB', url: '#' }
    ]
  },
  {
    id: 'OT-2024-0891',
    assetId: 'XM-500-221',
    assetName: 'Monitor Multiparámetro',
    type: 'Preventivo',
    status: WOStatus.PENDING,
    priority: 'Media',
    technician: 'Carlos Ruiz',
    requester: 'Enf. Lucía Rivas',
    date: '14/05/2024',
    description: 'Mantenimiento Preventivo Semestral según Plan 2024.',
    location: 'Urgencias'
  },
  {
    id: 'OT-2024-0888',
    assetId: 'AL-8015-994',
    assetName: 'Bomba de Infusión Alaris',
    type: 'Correctivo',
    status: WOStatus.IN_PROGRESS,
    priority: 'Alta',
    technician: 'Ana Muñoz',
    requester: 'Enf. Pedro Juan',
    date: '12/05/2024',
    description: 'Error de oclusión constante.',
    location: 'Pediatría'
  }
];

const WorkOrders: React.FC = () => {
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState<string>('ALL');
  const [techFilter, setTechFilter] = useState<string>('ALL');
  const [dateFilter, setDateFilter] = useState('');

  const filteredWOs = useMemo(() => {
    return mockWOs.filter(wo => {
      const matchesSearch = wo.id.toLowerCase().includes(searchTerm.toLowerCase()) || 
                            wo.assetName.toLowerCase().includes(searchTerm.toLowerCase());
      const matchesStatus = statusFilter === 'ALL' || wo.status === statusFilter;
      const matchesTech = techFilter === 'ALL' || wo.technician === techFilter;
      const matchesDate = !dateFilter || wo.date === dateFilter; // Simplificado para coincidencia exacta en este mock

      return matchesSearch && matchesStatus && matchesTech && matchesDate;
    });
  }, [searchTerm, statusFilter, techFilter, dateFilter]);

  const resetFilters = () => {
    setSearchTerm('');
    setStatusFilter('ALL');
    setTechFilter('ALL');
    setDateFilter('');
  };

  return (
    <div className="space-y-10 animate-in fade-in duration-500">
      <div className="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
          <h1 className="text-4xl font-bold tracking-tight text-white">Órdenes de Trabajo</h1>
          <p className="text-slate-400 mt-2 text-lg">Seguimiento y gestión técnica de intervenciones clínicas.</p>
        </div>
        <div className="flex items-center gap-4">
          <Link to="/work-orders/new" className="flex items-center gap-3 px-8 py-3 bg-medical-blue text-white rounded-2xl hover:bg-medical-blue/90 transition-all font-bold shadow-xl shadow-medical-blue/20">
            <span className="material-symbols-outlined text-xl">add</span>
            <span>Apertura de OT</span>
          </Link>
        </div>
      </div>

      {/* Panel de Estadísticas Rápidas */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div className="card-glass p-6 flex flex-col items-center text-center border-l-4 border-l-slate-500">
           <span className="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Total Abiertas</span>
           <span className="text-3xl font-bold text-white">24</span>
        </div>
        <div className="card-glass p-6 flex flex-col items-center text-center border-l-4 border-l-amber-500">
           <span className="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">En Ejecución</span>
           <span className="text-3xl font-bold text-amber-500">8</span>
        </div>
        <div className="card-glass p-6 flex flex-col items-center text-center border-l-4 border-l-success">
           <span className="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Finalizadas Hoy</span>
           <span className="text-3xl font-bold text-success">5</span>
        </div>
        <div className="card-glass p-6 flex flex-col items-center text-center border-l-4 border-l-medical-blue">
           <span className="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">SLA Cumplido</span>
           <span className="text-3xl font-bold text-medical-blue">98%</span>
        </div>
      </div>

      {/* Barra de Filtros Avanzados */}
      <div className="card-glass p-6 shadow-2xl">
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-end">
          <div className="lg:col-span-3 space-y-2">
            <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Buscador</label>
            <div className="relative group">
              <span className="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-medical-blue transition-colors">search</span>
              <input 
                type="text"
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                placeholder="Folio o Activo..." 
                className="w-full bg-white/5 border border-slate-700/50 rounded-xl pl-12 pr-4 py-3 text-sm text-white focus:border-medical-blue outline-none transition-all"
              />
            </div>
          </div>

          <div className="lg:col-span-2 space-y-2">
            <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Estado</label>
            <select 
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
              className="w-full bg-white/5 border border-slate-700/50 rounded-xl px-4 py-3 text-sm text-white focus:border-medical-blue outline-none transition-all cursor-pointer appearance-none"
            >
              <option value="ALL">Todos</option>
              <option value={WOStatus.PENDING}>Pendiente</option>
              <option value={WOStatus.IN_PROGRESS}>En Ejecución</option>
              <option value={WOStatus.COMPLETED}>Finalizada</option>
            </select>
          </div>

          <div className="lg:col-span-3 space-y-2">
            <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Encargado (Técnico)</label>
            <select 
              value={techFilter}
              onChange={(e) => setTechFilter(e.target.value)}
              className="w-full bg-white/5 border border-slate-700/50 rounded-xl px-4 py-3 text-sm text-white focus:border-medical-blue outline-none transition-all cursor-pointer appearance-none"
            >
              <option value="ALL">Todos los Técnicos</option>
              <option value="Mario Lagos">Mario Lagos</option>
              <option value="Ana Muñoz">Ana Muñoz</option>
              <option value="Carlos Ruiz">Carlos Ruiz</option>
            </select>
          </div>

          <div className="lg:col-span-2 space-y-2">
            <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Fecha</label>
            <input 
              type="text" 
              placeholder="dd/mm/aaaa"
              value={dateFilter}
              onChange={(e) => setDateFilter(e.target.value)}
              className="w-full bg-white/5 border border-slate-700/50 rounded-xl px-4 py-3 text-sm text-white focus:border-medical-blue outline-none transition-all"
            />
          </div>

          <div className="lg:col-span-2">
            <button 
              onClick={resetFilters}
              className="w-full flex items-center justify-center gap-2 py-3 text-slate-500 hover:text-white transition-colors font-bold text-[11px] uppercase tracking-widest border border-transparent hover:border-slate-700/50 rounded-xl"
            >
              <span className="material-symbols-outlined text-lg">filter_alt_off</span>
              Limpiar Filtros
            </button>
          </div>
        </div>
      </div>

      {/* Tabla de Resultados */}
      <div className="card-glass overflow-hidden shadow-2xl">
        <div className="overflow-x-auto">
          {filteredWOs.length > 0 ? (
            <table className="w-full text-left border-collapse">
              <thead>
                <tr className="bg-white/5 border-b border-slate-700/50">
                  <th className="px-8 py-5 text-[11px] font-black uppercase tracking-[0.2em] text-slate-500">Folio / Fecha</th>
                  <th className="px-6 py-5 text-[11px] font-black uppercase tracking-[0.2em] text-slate-500">Activo Biomédico</th>
                  <th className="px-6 py-5 text-[11px] font-black uppercase tracking-[0.2em] text-slate-500">Tipo / Prioridad</th>
                  <th className="px-6 py-5 text-[11px] font-black uppercase tracking-[0.2em] text-slate-500">Encargado</th>
                  <th className="px-6 py-5 text-[11px] font-black uppercase tracking-[0.2em] text-slate-500">Estado</th>
                  <th className="px-8 py-5 text-[11px] font-black uppercase tracking-[0.2em] text-slate-500 text-right">Gestión</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-700/50">
                {filteredWOs.map(wo => (
                  <tr key={wo.id} className="hover:bg-white/5 transition-colors group">
                    <td className="px-8 py-6">
                      <div className="font-black text-medical-blue font-mono text-sm uppercase">{wo.id}</div>
                      <div className="text-[10px] text-slate-500 font-bold mt-1 uppercase">{wo.date}</div>
                    </td>
                    <td className="px-6 py-6">
                      <div className="font-bold text-white text-base">{wo.assetName}</div>
                      <div className="text-[10px] text-slate-500 mt-1 uppercase font-bold flex items-center gap-1">
                         <span className="material-symbols-outlined text-sm">location_on</span> {wo.location}
                      </div>
                    </td>
                    <td className="px-6 py-6">
                      <div className="flex flex-col gap-1.5">
                        <span className={`inline-flex px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider w-fit border ${
                          wo.type === 'Correctivo' ? 'bg-danger/10 text-danger border-danger/20' : 'bg-medical-blue/10 text-medical-blue border-medical-blue/20'
                        }`}>
                          {wo.type}
                        </span>
                        <span className={`text-[10px] font-bold ${wo.priority === 'Alta' ? 'text-danger' : 'text-slate-500'} flex items-center gap-1`}>
                           <span className="material-symbols-outlined text-sm fill-1">bolt</span> {wo.priority} Prioridad
                        </span>
                      </div>
                    </td>
                    <td className="px-6 py-6">
                      <div className="flex items-center gap-3">
                        <div className="size-8 rounded-full bg-slate-800 flex items-center justify-center text-[10px] font-black text-medical-blue border border-slate-700">
                          {wo.technician.split(' ').map(n => n[0]).join('')}
                        </div>
                        <span className="text-sm font-bold text-slate-300">{wo.technician}</span>
                      </div>
                    </td>
                    <td className="px-6 py-6">
                      <span className={`px-3 py-1.5 rounded-xl text-[10px] font-black uppercase border flex items-center gap-2 w-fit ${
                        wo.status === WOStatus.IN_PROGRESS ? 'bg-amber-500/10 text-amber-500 border-amber-500/30 shadow-[0_0_12px_rgba(245,158,11,0.1)]' :
                        wo.status === WOStatus.PENDING ? 'bg-slate-700/10 text-slate-500 border-slate-700/30' :
                        'bg-success/10 text-success border-success/30'
                      }`}>
                        <span className={`size-1.5 rounded-full ${wo.status === WOStatus.IN_PROGRESS ? 'bg-amber-500 animate-pulse' : wo.status === WOStatus.PENDING ? 'bg-slate-500' : 'bg-success'}`}></span>
                        {wo.status === WOStatus.IN_PROGRESS ? 'En Ejecución' : 
                         wo.status === WOStatus.PENDING ? 'Pendiente' : 'Finalizada'}
                      </span>
                    </td>
                    <td className="px-8 py-6 text-right">
                      <div className="flex justify-end gap-2">
                        <Link 
                          to={`/work-order/${wo.id}/execute`}
                          className="p-2.5 bg-white/5 text-slate-400 rounded-xl hover:text-white transition-all border border-slate-700/50"
                          title="Ver Detalles"
                        >
                          <span className="material-symbols-outlined text-xl">visibility</span>
                        </Link>
                        {wo.status !== WOStatus.COMPLETED && (
                          <Link 
                            to={`/work-order/${wo.id}/execute`}
                            className="px-4 py-2.5 bg-medical-blue text-white rounded-xl text-[10px] font-black uppercase hover:bg-medical-blue/90 transition-all shadow-lg shadow-medical-blue/20 flex items-center gap-2"
                          >
                            Ejecutar
                            <span className="material-symbols-outlined text-sm">play_arrow</span>
                          </Link>
                        )}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          ) : (
            <div className="py-20 flex flex-col items-center justify-center text-center opacity-40">
               <span className="material-symbols-outlined text-6xl mb-4">search_off</span>
               <h3 className="text-xl font-bold text-white">No se encontraron resultados</h3>
               <p className="text-sm text-slate-400 mt-2">Prueba ajustando los filtros de búsqueda o limpia la selección.</p>
               <button onClick={resetFilters} className="mt-6 text-medical-blue font-black uppercase text-[10px] tracking-widest hover:underline">
                 Ver todas las Órdenes
               </button>
            </div>
          )}
        </div>
      </div>
      
      {/* Resumen de Resultados */}
      <div className="flex justify-between items-center px-4">
        <p className="text-[10px] text-slate-500 font-black uppercase tracking-widest">
          Mostrando {filteredWOs.length} de {mockWOs.length} registros totales
        </p>
      </div>
    </div>
  );
};

export default WorkOrders;
