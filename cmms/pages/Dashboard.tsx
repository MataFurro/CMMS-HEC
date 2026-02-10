
import React from 'react';
import {
  AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer,
  PieChart, Pie, Cell, BarChart, Bar, Legend
} from 'recharts';
import { technicians, trendData, statusData, kpiCards, recentEvents } from '../mockData';
import { COLORS, KPI_TARGETS, UI_LABELS } from '../constants';

const Dashboard: React.FC = () => {

  // Datos para comparativa de técnicos (Derived from imported data)
  const techComparisonData = technicians.map(t => ({
    name: t.name.split(' ')[0],
    cerradas: Math.round(t.total * (t.stats.done / 100)),
    pendientes: Math.round(t.total * ((t.stats.progress + t.stats.pending) / 100))
  }));

  return (
    <div className="space-y-8 animate-in fade-in duration-500">
      {/* Header Interno del Dashboard */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
          <h1 className="text-3xl font-bold text-white tracking-tight">{UI_LABELS.DASHBOARD_TITLE}</h1>
          <p className="text-xs text-slate-500 mt-1 uppercase tracking-wider font-bold">{UI_LABELS.DASHBOARD_SUBTITLE}</p>
        </div>
        <div className="flex gap-3">
          <button className="h-11 px-6 border border-slate-700 text-slate-300 rounded-xl text-sm font-bold hover:bg-slate-800 flex items-center gap-2 transition-all active:scale-95">
            <span className="material-symbols-outlined text-xl">file_download</span>
            {UI_LABELS.EXPORT_BTN}
          </button>
        </div>
      </div>

      {/* Grid de KPIs Superiores */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        {kpiCards.map((kpi, idx) => (
          <div key={idx} className={`card-glass p-5 border-l-4 ${kpi.color}`}>
            <div className="flex justify-between items-start mb-2">
              <span className="material-symbols-outlined text-slate-400 text-lg font-variation-fill">{kpi.icon}</span>
              <span className={`text-[9px] font-black px-2 py-0.5 rounded ${idx === 3 ? 'bg-red-500/10 text-red-500' : 'bg-emerald-500/10 text-emerald-500'}`}>{kpi.trend}</span>
            </div>
            <p className="stat-label">{kpi.label}</p>
            <h3 className="stat-value">{kpi.value}</h3>
            <p className="text-[10px] text-slate-500 mt-1 italic tracking-tight font-medium">{kpi.sub}</p>
          </div>
        ))}
      </div>

      {/* Contenido Principal con Gráficos */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
        {/* Gráfico de Tendencias MTBF/MTTR */}
        <div className="lg:col-span-8 card-glass p-8">
          <div className="flex justify-between items-center mb-8">
            <div>
              <h3 className="text-sm font-bold text-white uppercase tracking-wider">{UI_LABELS.RELIABILITY_HISTORY}</h3>
              <p className="text-xs text-slate-500 font-medium">Relación entre MTBF (hrs) y MTTR (hrs) - Últimos 5 meses</p>
            </div>
          </div>
          <div className="h-[300px] w-full">
            <ResponsiveContainer width="100%" height="100%">
              <AreaChart data={trendData}>
                <defs>
                  <linearGradient id="colorMtbf" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor={COLORS.MEDICAL_BLUE} stopOpacity={0.3} />
                    <stop offset="95%" stopColor={COLORS.MEDICAL_BLUE} stopOpacity={0} />
                  </linearGradient>
                </defs>
                <CartesianGrid strokeDasharray="3 3" stroke={COLORS.SLATE_700} vertical={false} />
                <XAxis dataKey="name" stroke="#64748b" fontSize={10} tickLine={false} axisLine={false} />
                <YAxis stroke="#64748b" fontSize={10} tickLine={false} axisLine={false} />
                <Tooltip
                  contentStyle={{ backgroundColor: COLORS.BG_DARK, border: `1px solid ${COLORS.SLATE_700}`, borderRadius: '12px' }}
                  itemStyle={{ fontSize: '12px', fontWeight: 'bold' }}
                />
                <Area type="monotone" dataKey="mtbf" stroke={COLORS.MEDICAL_BLUE} fillOpacity={1} fill="url(#colorMtbf)" strokeWidth={3} />
              </AreaChart>
            </ResponsiveContainer>
          </div>
        </div>

        {/* Distribución por Estado (Donut) */}
        <div className="lg:col-span-4 card-glass p-8">
          <h3 className="text-sm font-bold text-white uppercase tracking-wider mb-6">{UI_LABELS.ASSET_STATUS}</h3>
          <div className="h-[240px] w-full relative">
            <ResponsiveContainer width="100%" height="100%">
              <PieChart>
                <Pie
                  data={statusData}
                  cx="50%"
                  cy="50%"
                  innerRadius={60}
                  outerRadius={80}
                  paddingAngle={8}
                  dataKey="value"
                >
                  {statusData.map((entry, index) => (
                    <Cell key={`cell-${index}`} fill={entry.color} />
                  ))}
                </Pie>
                <Tooltip />
              </PieChart>
            </ResponsiveContainer>
            <div className="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
              <span className="text-2xl font-black text-white">82</span>
              <span className="text-[9px] font-black text-slate-500 uppercase tracking-widest">Activos</span>
            </div>
          </div>
          <div className="space-y-3 mt-4">
            {statusData.map(s => (
              <div key={s.name} className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <div className="size-2 rounded-full" style={{ backgroundColor: s.color }}></div>
                  <span className="text-[10px] font-bold text-slate-400 uppercase">{s.name}</span>
                </div>
                <span className="text-[10px] font-black text-white">{s.value}%</span>
              </div>
            ))}
          </div>
        </div>

        {/* Panel de Carga Laboral Detallada */}
        <div className="lg:col-span-7 card-glass p-8">
          <div className="flex justify-between items-center mb-10">
            <div>
              <h3 className="text-sm font-bold text-white uppercase tracking-wider">{UI_LABELS.WORKLOAD_DISTRIBUTION}</h3>
              <p className="text-xs text-slate-500 font-medium">Distribución de OTs y uso de capacidad</p>
            </div>
          </div>
          <div className="space-y-10">
            {technicians.map((tech) => (
              <div key={tech.name} className="grid grid-cols-12 items-center gap-6 group">
                <div className="col-span-3 flex items-center gap-3">
                  <div className="w-10 h-10 rounded-lg bg-medical-blue/20 flex items-center justify-center text-medical-blue font-black text-xs border border-medical-blue/30 group-hover:scale-105 transition-transform">
                    {tech.initial}
                  </div>
                  <div className="hidden sm:block">
                    <p className="text-xs font-bold text-white leading-none">{tech.name}</p>
                    <p className="text-[9px] text-slate-500 font-bold uppercase mt-1.5 tracking-wider">{tech.role}</p>
                  </div>
                </div>
                <div className="col-span-6">
                  <div className="h-2 w-full bg-slate-900 rounded-full overflow-hidden flex">
                    <div className="bg-medical-blue h-full" style={{ width: `${tech.stats.done}%` }}></div>
                    <div className="bg-amber-500 h-full" style={{ width: `${tech.stats.progress}%` }}></div>
                    <div className="bg-slate-700 h-full" style={{ width: `${tech.stats.pending}%` }}></div>
                  </div>
                </div>
                <div className="col-span-3 text-right">
                  <span className={`text-lg font-black ${tech.capacity > KPI_TARGETS.TECH_CAPACITY_WARNING ? 'text-red-500' : 'text-emerald-400'}`}>
                    {tech.capacity}%
                  </span>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Comparativa de Productividad (BarChart) */}
        <div className="lg:col-span-5 card-glass p-8">
          <h3 className="text-sm font-bold text-white uppercase tracking-wider mb-8">{UI_LABELS.TECH_EFFECTIVENESS}</h3>
          <div className="h-[300px] w-full">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={techComparisonData}>
                <CartesianGrid strokeDasharray="3 3" stroke={COLORS.SLATE_700} vertical={false} />
                <XAxis dataKey="name" stroke="#64748b" fontSize={10} tickLine={false} axisLine={false} />
                <YAxis stroke="#64748b" fontSize={10} tickLine={false} axisLine={false} />
                <Tooltip
                  contentStyle={{ backgroundColor: COLORS.BG_DARK, border: `1px solid ${COLORS.SLATE_700}`, borderRadius: '12px' }}
                  cursor={{ fill: 'rgba(255,255,255,0.05)' }}
                />
                <Legend iconType="circle" wrapperStyle={{ paddingTop: '20px', fontSize: '10px', textTransform: 'uppercase', fontWeight: 'bold' }} />
                <Bar dataKey="cerradas" name="Cerradas" fill={COLORS.EMERALD} radius={[4, 4, 0, 0]} barSize={20} />
                <Bar dataKey="pendientes" name="Pendientes" fill={COLORS.SLATE_700} radius={[4, 4, 0, 0]} barSize={20} />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </div>
      </div>

      {/* Bitácora de Eventos Recientes (Actualizada) */}
      <div className="card-glass p-8">
        <h3 className="text-sm font-bold text-white uppercase tracking-wider mb-8">{UI_LABELS.RECENT_EVENTS}</h3>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-10">
          {recentEvents.map((event) => (
            <div key={event.id} className="relative pl-10 before:content-[''] before:absolute before:left-3 before:top-2 before:bottom-2 before:w-px before:bg-slate-700/50">
              <div className={`absolute left-1 top-1 w-4 h-4 rounded-full bg-${event.colorClass} ring-4 ring-medical-dark shadow-xl shadow-${event.colorClass}/20`}></div>
              <p className="text-sm font-bold text-white">{event.title}</p>
              <p className="text-xs text-slate-400 mt-1">{event.subtitle}</p>
              <p className="text-[10px] text-slate-600 font-black uppercase tracking-tighter mt-2">{event.time}</p>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};

export default Dashboard;
