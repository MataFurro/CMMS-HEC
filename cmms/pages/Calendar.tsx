
import React, { useState } from 'react';
import { Link } from 'react-router-dom';

const Calendar: React.FC = () => {
  const [view, setView] = useState<'year' | 'month' | 'week' | 'day'>('month');
  const weekDays = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
  const hours = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00'];

  const monthDays = Array.from({ length: 35 }, (_, i) => i - 3); 

  const annualWorkload = [
    { month: 'Enero', ots: 12 }, { month: 'Febrero', ots: 8 }, { month: 'Marzo', ots: 15 },
    { month: 'Abril', ots: 10 }, { month: 'Mayo', ots: 22 }, { month: 'Junio', ots: 14 },
    { month: 'Julio', ots: 9 }, { month: 'Agosto', ots: 11 }, { month: 'Septiembre', ots: 18 },
    { month: 'Octubre', ots: 13 }, { month: 'Noviembre', ots: 7 }, { month: 'Diciembre', ots: 5 },
  ];

  const renderMonthGrid = () => (
    <div className="bg-panel-dark rounded-[2rem] border border-border-dark shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-300">
      <div className="grid grid-cols-7 border-b border-border-dark bg-white/5">
        {weekDays.map(day => (
          <div key={day} className="py-5 text-center text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">
            {day.substring(0, 2)}
          </div>
        ))}
      </div>
      <div className="grid grid-cols-7 auto-rows-[140px]">
        {monthDays.map((day, idx) => {
          const isCurrentMonth = day > 0 && day <= 31;
          const isToday = day === 14; 
          return (
            <div key={idx} className={`p-3 border-r border-b border-border-dark transition-all hover:bg-white/[0.02] flex flex-col relative group ${!isCurrentMonth ? 'opacity-10 bg-slate-900/50' : ''} ${isToday ? 'bg-medical-blue/5' : ''}`}>
              <div className="flex justify-between items-start mb-2">
                <span className={`text-sm font-black font-mono ${isToday ? 'text-medical-blue' : 'text-slate-500'}`}>{day <= 0 ? 30 + day : day > 31 ? day - 31 : day}</span>
                {isToday && <span className="size-2 rounded-full bg-medical-blue shadow-[0_0_12px_rgba(14,165,233,0.5)]"></span>}
              </div>
              <div className="space-y-1.5 overflow-hidden">
                {day === 5 && <div className="px-2 py-1 bg-danger/10 border-l-2 border-danger rounded text-[9px] font-black text-danger truncate uppercase">OT #2024-0890</div>}
                {(day === 12 || day === 22) && <div className="px-2 py-1 bg-medical-blue/10 border-l-2 border-medical-blue rounded text-[9px] font-black text-medical-blue truncate uppercase">OT #2024-0885</div>}
                {day === 15 && <div className="px-2 py-1 bg-amber-500/10 border-l-2 border-amber-500 rounded text-[9px] font-black text-amber-500 truncate uppercase">OT #2024-0891</div>}
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );

  const renderYearGrid = () => (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 animate-in fade-in zoom-in-95 duration-300">
      {annualWorkload.map((item, idx) => (
        <div key={idx} className="bg-panel-dark rounded-3xl border border-border-dark p-6 hover:border-medical-blue/40 transition-all group relative overflow-hidden">
          <div className="flex justify-between items-start mb-4">
            <h4 className="text-sm font-black text-white uppercase tracking-widest">{item.month}</h4>
            <span className={`text-[10px] font-black px-2 py-0.5 rounded ${item.ots > 15 ? 'bg-danger/10 text-danger' : 'bg-medical-blue/10 text-medical-blue'}`}>{item.ots} OTs</span>
          </div>
          <div className="grid grid-cols-7 gap-1 opacity-40 group-hover:opacity-100 transition-opacity">
            {Array.from({ length: 28 }).map((_, d) => (
              <div key={d} className={`aspect-square rounded-[2px] ${(d + idx) % 7 === 0 ? 'bg-medical-blue/40' : 'bg-slate-800'}`}></div>
            ))}
          </div>
          <button className="absolute inset-0 bg-medical-blue/5 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center pointer-events-none group-hover:pointer-events-auto">
             <span className="text-[10px] font-black text-medical-blue uppercase tracking-widest bg-medical-dark px-4 py-2 rounded-xl border border-medical-blue/30 shadow-2xl">Ver Detalle</span>
          </button>
        </div>
      ))}
    </div>
  );

  const renderWeekGrid = () => (
    <div className="bg-panel-dark rounded-[2rem] border border-border-dark shadow-2xl overflow-hidden animate-in fade-in slide-in-from-right-4 duration-300">
      <div className="grid grid-cols-[80px_1fr_1fr_1fr_1fr_1fr_1fr_1fr] border-b border-border-dark bg-white/5">
        <div className="py-4 border-r border-border-dark"></div>
        {weekDays.map(day => (
          <div key={day} className="py-4 text-center text-[10px] font-black text-slate-500 uppercase tracking-widest border-r border-border-dark/30">
            {day} <span className="block text-white opacity-40 mt-1">1{weekDays.indexOf(day) + 3} May</span>
          </div>
        ))}
      </div>
      <div className="h-[600px] overflow-y-auto custom-scrollbar">
        {hours.map(hour => (
          <div key={hour} className="grid grid-cols-[80px_1fr_1fr_1fr_1fr_1fr_1fr_1fr] border-b border-border-dark/30">
            <div className="py-6 px-3 text-[10px] font-mono text-slate-600 border-r border-border-dark text-right font-bold uppercase">{hour}</div>
            {weekDays.map((_, idx) => (
              <div key={idx} className="border-r border-border-dark/30 relative min-h-[80px] hover:bg-white/[0.01] transition-colors">
                {idx === 0 && hour === '10:00' && (
                  <div className="absolute inset-x-1 top-2 p-3 bg-medical-blue/20 border-l-4 border-medical-blue rounded-xl z-10 shadow-lg shadow-medical-blue/10">
                    <p className="text-[9px] font-black text-white uppercase leading-none">OT #2024-0892</p>
                    <p className="text-[8px] text-medical-blue font-bold mt-1 uppercase truncate">V. Mecánico PB840</p>
                  </div>
                )}
                {idx === 2 && hour === '14:00' && (
                  <div className="absolute inset-x-1 top-2 p-3 bg-danger/20 border-l-4 border-danger rounded-xl z-10">
                    <p className="text-[9px] font-black text-white uppercase leading-none">URGENCIA CRÍTICA</p>
                    <p className="text-[8px] text-danger font-bold mt-1 uppercase truncate">Rayos X Portátil</p>
                  </div>
                )}
              </div>
            ))}
          </div>
        ))}
      </div>
    </div>
  );

  const renderDayGrid = () => (
    <div className="bg-panel-dark rounded-[2rem] border border-border-dark shadow-2xl overflow-hidden animate-in fade-in slide-in-from-bottom-4 duration-300">
      <div className="p-8 border-b border-border-dark bg-white/5 flex items-center justify-between">
         <div>
            <h3 className="text-xl font-bold text-white uppercase tracking-tight">Jueves, 14 de Mayo, 2024</h3>
            <p className="text-xs text-slate-500 font-bold uppercase tracking-widest mt-1">4 Intervenciones Programadas para hoy</p>
         </div>
         <div className="flex items-center gap-4 bg-emerald-500/5 px-4 py-2 rounded-2xl border border-emerald-500/20">
            <span className="size-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span className="text-[10px] font-black text-emerald-500 uppercase tracking-widest text-center">Técnicos de Turno: 4/5 Disponibles</span>
         </div>
      </div>
      <div className="p-8 space-y-8 h-[500px] overflow-y-auto custom-scrollbar">
        {[
          { time: '09:30', title: 'Mantenimiento Preventivo', equipment: 'Monitor Multiparámetro', tech: 'Mario Lagos', status: 'PENDING', color: 'medical-blue' },
          { time: '11:45', title: 'Calibración de Sensores', equipment: 'Incubadora Neonatal', tech: 'Ana Muñoz', status: 'IN_PROGRESS', color: 'amber-500' },
          { time: '15:20', title: 'Correctivo Urgente', equipment: 'Desfibrilador Zoll', tech: 'Carlos Ruiz', status: 'PENDING', color: 'danger' },
          { time: '17:00', title: 'Ronda de Inspección', equipment: 'Servicio de Urgencias', tech: 'Equipo Biomédico', status: 'PENDING', color: 'slate-500' },
        ].map((event, idx) => (
          <div key={idx} className="flex gap-8 group">
            <div className="w-20 pt-1 shrink-0">
               <span className="text-lg font-mono font-black text-white">{event.time}</span>
               <div className="mt-2 h-full w-px bg-slate-800 mx-auto opacity-50 group-last:hidden"></div>
            </div>
            <div className={`flex-1 card-glass p-6 border-l-4 border-l-${event.color} hover:bg-white/[0.03] transition-all`}>
               <div className="flex justify-between items-start">
                  <div>
                    <h4 className="font-bold text-white text-base">{event.title}</h4>
                    <p className="text-sm text-slate-400 font-medium mt-1">{event.equipment}</p>
                  </div>
                  <span className={`text-[9px] font-black px-3 py-1 rounded-lg uppercase tracking-widest bg-${event.color}/10 text-${event.color} border border-${event.color}/20`}>
                    {event.status === 'IN_PROGRESS' ? 'En Curso' : 'Programada'}
                  </span>
               </div>
               <div className="mt-4 flex items-center gap-6">
                  <div className="flex items-center gap-2">
                    <span className="material-symbols-outlined text-slate-500 text-sm">person</span>
                    <span className="text-[10px] font-bold text-slate-500 uppercase">{event.tech}</span>
                  </div>
                  <div className="flex items-center gap-2">
                    <span className="material-symbols-outlined text-slate-500 text-sm">location_on</span>
                    <span className="text-[10px] font-bold text-slate-500 uppercase">Sector A - Piso 2</span>
                  </div>
               </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );

  return (
    <div className="space-y-8 animate-in fade-in duration-500">
      <div className="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
          <h1 className="text-4xl font-bold tracking-tight text-white flex items-center gap-3">
            Agenda Técnica 2024
            <span className="text-medical-blue material-symbols-outlined text-3xl font-variation-fill">event_upcoming</span>
          </h1>
          <p className="text-slate-400 mt-2 text-lg">Cronograma maestro sincronizado con el módulo de Órdenes de Trabajo.</p>
        </div>
        <div className="flex items-center gap-4">
          <div className="flex bg-white/5 border border-border-dark p-1.5 rounded-2xl">
            {[
              { id: 'year', label: 'Año' },
              { id: 'month', label: 'Mes' },
              { id: 'week', label: 'Semana' },
              { id: 'day', label: 'Día' }
            ].map(v => (
              <button 
                key={v.id}
                onClick={() => setView(v.id as any)}
                className={`px-5 py-2 text-xs font-bold rounded-xl transition-all ${view === v.id ? 'bg-medical-blue text-white shadow-lg shadow-medical-blue/20' : 'text-slate-500 hover:text-white'}`}
              >
                {v.label}
              </button>
            ))}
          </div>
          <Link to="/work-orders" className="flex items-center gap-3 px-6 py-3 border border-slate-700 text-slate-300 rounded-2xl hover:bg-white/5 transition-all font-bold text-sm">
            <span className="material-symbols-outlined text-xl">settings_applications</span>
            <span>Manejo de OTs</span>
          </Link>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <div className="lg:col-span-3 space-y-6">
          <div className="bg-panel-dark p-6 rounded-3xl border border-border-dark shadow-xl">
            <h3 className="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-6 px-1 text-center">Leyenda de Estados</h3>
            <div className="space-y-4">
              <div className="flex items-center gap-3 p-3 bg-white/5 rounded-xl border border-transparent">
                <div className="size-3 rounded-full bg-danger"></div>
                <span className="text-[10px] font-black text-slate-400 uppercase tracking-widest">Correctivos Críticos</span>
              </div>
              <div className="flex items-center gap-3 p-3 bg-white/5 rounded-xl border border-transparent">
                <div className="size-3 rounded-full bg-medical-blue"></div>
                <span className="text-[10px] font-black text-slate-400 uppercase tracking-widest">Preventivos Programados</span>
              </div>
              <div className="flex items-center gap-3 p-3 bg-white/5 rounded-xl border border-transparent">
                <div className="size-3 rounded-full bg-amber-500"></div>
                <span className="text-[10px] font-black text-slate-400 uppercase tracking-widest">Calibraciones Pendientes</span>
              </div>
            </div>
            <div className="mt-8 pt-8 border-t border-border-dark space-y-4">
                <p className="text-[10px] font-black text-slate-500 uppercase tracking-widest text-center">Filtro por Servicio</p>
                <div className="space-y-3">
                  {['UCI Adultos', 'Pabellón Central', 'Urgencias'].map(loc => (
                    <label key={loc} className="flex items-center gap-3 cursor-pointer group">
                      <input type="checkbox" defaultChecked className="size-5 rounded-lg border-border-dark bg-background-dark text-medical-blue focus:ring-0" />
                      <span className="text-xs text-slate-400 group-hover:text-white font-bold uppercase tracking-wider transition-colors">{loc}</span>
                    </label>
                  ))}
                </div>
            </div>
          </div>
          <div className="bg-medical-blue/5 border border-medical-blue/20 p-6 rounded-3xl flex flex-col items-center text-center">
            <span className="material-symbols-outlined text-medical-blue text-3xl mb-3">sync</span>
            <h4 className="text-[10px] font-black text-medical-blue uppercase tracking-widest mb-2">Sincronización Activa</h4>
            <p className="text-xs font-bold text-white">Actualizado en tiempo real</p>
          </div>
        </div>

        <div className="lg:col-span-9">
          {view === 'month' && renderMonthGrid()}
          {view === 'year' && renderYearGrid()}
          {view === 'week' && renderWeekGrid()}
          {view === 'day' && renderDayGrid()}
          
          <div className="mt-6 flex justify-between items-center px-4 opacity-50">
             <p className="text-[10px] font-black text-slate-500 uppercase tracking-widest">© BioCMMS Agenda - Sincronizado con v4.2 Pro</p>
             <div className="flex gap-4">
                <span className="flex items-center gap-2 text-[10px] font-black text-slate-500 uppercase"><span className="size-2 rounded-full bg-slate-700"></span> Feriado</span>
                <span className="flex items-center gap-2 text-[10px] font-black text-slate-500 uppercase"><span className="size-2 rounded-full bg-medical-blue"></span> Programado</span>
             </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Calendar;
