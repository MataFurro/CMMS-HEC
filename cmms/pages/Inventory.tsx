
import React, { useState, useMemo } from 'react';
import { Link } from 'react-router-dom';
import { Asset, AssetCriticality } from '../types';
import { mockAssets } from '../mockData';
import { UI_LABELS, ASSET_STATUS, ASSET_CRITICALITY, COLORS } from '../constants';
import { useAuth, Permission } from '../context/AuthContext';
import * as XLSX from 'xlsx';

const Inventory: React.FC = () => {
  const { hasPermission } = useAuth();
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState('ALL');
  const [assets, setAssets] = useState<Asset[]>(mockAssets);
  const fileInputRef = React.useRef<HTMLInputElement>(null);

  const filtered = useMemo(() => {
    return assets.filter(a => {
      const matchSearch = a.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        a.brand.toLowerCase().includes(searchTerm.toLowerCase()) ||
        a.id.toLowerCase().includes(searchTerm.toLowerCase());
      const matchStatus = statusFilter === 'ALL' || a.status === statusFilter;
      return matchSearch && matchStatus;
    });
  }, [searchTerm, statusFilter, assets]);

  const handleFileUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (evt) => {
      const bstr = evt.target?.result;
      const wb = XLSX.read(bstr, { type: 'binary' });
      const wsname = wb.SheetNames[0];
      const ws = wb.Sheets[wsname];
      const data = XLSX.utils.sheet_to_json(ws);

      const newAssets: Asset[] = data.map((row: any) => ({
        id: row['ID INVENTARIO'] || `NEW-${Math.random().toString(36).substr(2, 9)}`,
        serialNumber: row['SERIE'] || 'S/N',
        name: row['NOMBRE EQUIPO'] || row['EQUIPO'] || 'Nuevo Equipo',
        brand: row['MARCA'] || 'S/M',
        model: row['MODELO'] || 'S/M',
        location: row['UBICACIÓN'] || 'Sin Ubicación',
        subLocation: row['SUB UBICACIÓN'] || '',
        vendor: row['PROVEEDOR'] || '',
        serviceProvider: row['SERVICIO TÉCNICO'] || '',
        ownership: row['PROPIEDAD'] || 'Propio',
        criticality: (row['CRITICIDAD'] === 'Crítico' ? AssetCriticality.CRITICAL : AssetCriticality.RELEVANT),
        status: (row['ESTADO'] === 'Baja' ? 'OUT_OF_SERVICE' : row['ESTADO'] === 'Mantención' ? 'MAINTENANCE' : 'OPERATIVE'),
        usefulLife: parseInt(row['VIDA ÚTIL (%)']) || 100,
        yearsRemaining: parseInt(row['AÑOS RESTANTES']) || 10,
        warrantyUntil: row['GARANTÍA'] || '-',
        imageUrl: 'https://picsum.photos/seed/' + Math.random() + '/200/200'
      }));

      setAssets(prev => [...newAssets, ...prev]);
    };
    reader.readAsBinaryString(file);
  };

  return (
    <div className="space-y-10">
      <input
        type="file"
        ref={fileInputRef}
        onChange={handleFileUpload}
        accept=".xlsx, .xls"
        className="hidden"
      />
      <div className="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
          <h1 className="text-4xl font-bold tracking-tight text-white flex items-center gap-4">
            {UI_LABELS.INVENTORY_TITLE}
            <span className="text-medical-blue font-light text-2xl tracking-normal opacity-60">{UI_LABELS.INVENTORY_SUBTITLE}</span>
          </h1>
          <p className="text-slate-400 mt-2 text-lg">Gestión centralizada de equipamiento clínico y soporte de vida.</p>
        </div>
        <div className="flex items-center gap-4">
          {hasPermission(Permission.MANAGE_INVENTORY) && (
            <>
              <button
                onClick={() => fileInputRef.current?.click()}
                className="h-11 flex items-center gap-3 px-6 bg-excel-green text-white rounded-2xl hover:bg-excel-green/90 transition-all font-bold shadow-xl shadow-excel-green/20 active:scale-95"
              >
                <span className="material-symbols-outlined text-xl">upload_file</span>
                <span>{UI_LABELS.BTN_UPLOAD_EXCEL}</span>
              </button>
              <button className="h-11 flex items-center gap-3 px-6 bg-slate-800 text-white rounded-2xl hover:bg-slate-700 transition-all font-bold border border-slate-700 active:scale-95">
                <span className="material-symbols-outlined text-xl">table_view</span>
                <span>{UI_LABELS.BTN_DOWNLOAD_EXCEL}</span>
              </button>
              <Link
                to="/inventory/new"
                className="h-11 flex items-center gap-3 px-8 bg-primary text-white rounded-2xl hover:bg-primary/90 transition-all font-bold shadow-xl shadow-primary/20 active:scale-95 hover:shadow-primary/40"
              >
                <span className="material-symbols-outlined text-xl">add_box</span>
                <span>{UI_LABELS.BTN_NEW_ASSET}</span>
              </Link>
            </>
          )}
        </div>
      </div>

      <div className="card-glass p-6 shadow-2xl">
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
          <div className="lg:col-span-5 relative group">
            <span className="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-medical-blue transition-colors">search</span>
            <input
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="w-full bg-white/5 border border-slate-700/50 rounded-2xl pl-12 pr-6 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none transition-all placeholder:text-slate-600 text-white"
              placeholder={UI_LABELS.SEARCH_PLACEHOLDER}
            />
          </div>
          <div className="lg:col-span-3">
            <select
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
              className="w-full bg-white/5 border border-slate-700/50 rounded-2xl px-6 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none appearance-none cursor-pointer text-white"
            >
              <option value="ALL">{UI_LABELS.FILTER_ALL_STATUS}</option>
              <option value={ASSET_STATUS.OPERATIVE}>Operativo</option>
              <option value={ASSET_STATUS.MAINTENANCE}>En Mantención</option>
              <option value={ASSET_STATUS.OUT_OF_SERVICE}>Fuera de Servicio</option>
            </select>
          </div>
          <div className="lg:col-span-2">
            <select className="w-full bg-white/5 border border-slate-700/50 rounded-2xl px-6 py-3 text-sm focus:ring-2 focus:ring-medical-blue/20 focus:border-medical-blue outline-none appearance-none cursor-pointer text-white">
              <option value="">{UI_LABELS.FILTER_CRITICALITY}</option>
              <option>{ASSET_CRITICALITY.CRITICAL}</option>
              <option>{ASSET_CRITICALITY.RELEVANT}</option>
            </select>
          </div>
          <div className="lg:col-span-2">
            <button
              onClick={() => { setSearchTerm(''); setStatusFilter('ALL'); }}
              className="w-full flex items-center justify-center gap-2 text-slate-500 hover:text-white transition-colors font-bold text-sm py-3"
            >
              <span className="material-symbols-outlined text-xl">filter_list_off</span>
              {UI_LABELS.BTN_CLEAR_FILTERS}
            </button>
          </div>
        </div>
      </div>

      <div className="card-glass overflow-hidden shadow-2xl">
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="bg-white/5 border-b border-slate-700/50">
                <th className="px-8 py-5 text-[11px] font-black uppercase tracking-[0.2em] text-slate-500">Activo / Modelo</th>
                <th className="px-6 py-5 text-[11px] font-black uppercase tracking-[0.2em] text-slate-500 text-center">Criticidad</th>
                <th className="px-6 py-5 text-[11px] font-black uppercase tracking-[0.2em] text-slate-500">Ubicación / Sub</th>
                <th className="px-6 py-5 text-[11px] font-black uppercase tracking-[0.2em] text-slate-500">Proveedor / Garantía</th>
                <th className="px-6 py-5 text-[11px] font-black uppercase tracking-[0.2em] text-slate-500 text-center">Plan Mant.</th>
                <th className="px-6 py-5 text-[11px] font-black uppercase tracking-[0.2em] text-slate-500">Pertenencia</th>
                <th className="px-6 py-5 text-[11px] font-black uppercase tracking-[0.2em] text-slate-500 text-center">Estado</th>
                <th className="px-6 py-5 text-[11px] font-black uppercase tracking-[0.2em] text-slate-500 text-center">Vida Útil</th>
                <th className="px-8 py-5 text-[11px] font-black uppercase tracking-[0.2em] text-slate-500 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-700/50">
              {filtered.map((asset) => (
                <tr key={asset.id} className="hover:bg-white/5 transition-colors group">
                  <td className="px-8 py-6">
                    <div className="flex items-center gap-5">
                      <div className="w-14 h-14 rounded-xl border border-slate-700/50 overflow-hidden bg-medical-dark p-1 shrink-0 group-hover:scale-110 transition-transform">
                        <img src={asset.imageUrl} className="w-full h-full object-cover rounded-lg" alt={asset.name} />
                      </div>
                      <div>
                        <Link to={`/asset/${asset.id}`} className="font-bold text-white hover:text-medical-blue transition-colors block text-base">
                          {asset.name}
                        </Link>
                        <div className="text-xs text-slate-500 mt-1 uppercase font-semibold">
                          {asset.brand} {asset.model} · <span className="font-mono text-medical-blue">{asset.id}</span>
                        </div>
                      </div>
                    </div>
                  </td>
                  <td className="px-6 py-6 text-center">
                    <span className={`inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-[10px] font-black uppercase border ${asset.criticality === AssetCriticality.CRITICAL
                      ? 'bg-danger/10 text-danger border-danger/30'
                      : 'bg-medical-blue/10 text-medical-blue border-medical-blue/30'
                      }`}>
                      <span className="material-symbols-outlined text-[14px] fill-1">{asset.criticality === AssetCriticality.CRITICAL ? 'bolt' : 'clinical_notes'}</span>
                      {asset.criticality}
                    </span>
                  </td>
                  <td className="px-6 py-6">
                    <div className="font-bold text-slate-200 text-sm">{asset.location}</div>
                    <div className="text-[10px] text-slate-500 uppercase mt-0.5 font-bold">{asset.subLocation || '-'}</div>
                  </td>
                  <td className="px-6 py-6">
                    <div className="font-bold text-slate-200 text-xs">{asset.vendor || '-'}</div>
                    {asset.warrantyExpiration && (
                      <div className={`text-[9px] uppercase mt-0.5 font-bold ${new Date() > new Date(asset.warrantyExpiration) ? 'text-danger' : 'text-emerald-500'}`}>
                        Vence: {asset.warrantyExpiration}
                      </div>
                    )}
                  </td>
                  <td className="px-6 py-6 text-center">
                    <span className={`px-2.5 py-1 rounded-lg text-[10px] font-black uppercase border ${asset.underMaintenancePlan ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/30' : 'bg-slate-700/10 text-slate-500 border-slate-700/30'}`}>
                      {asset.underMaintenancePlan ? 'Sí' : 'No'}
                    </span>
                  </td>
                  <td className="px-6 py-6">
                    <div className="font-bold text-slate-200 text-xs">{asset.ownership || '-'}</div>
                  </td>
                  <td className="px-6 py-6 text-center">
                    <span className={`px-4 py-1.5 rounded-xl text-xs font-black inline-flex items-center gap-2 uppercase tracking-wide border ${asset.status === ASSET_STATUS.OPERATIVE ? 'bg-success/10 text-success border-success/30' :
                      asset.status === ASSET_STATUS.MAINTENANCE ? 'bg-amber-500/10 text-amber-500 border-amber-500/30' :
                        'bg-slate-700/10 text-slate-500 border-slate-700/30'
                      }`}>
                      <span className={`size-2 rounded-full ${asset.status === ASSET_STATUS.OPERATIVE ? 'bg-success shadow-[0_0_8px_rgba(16,185,129,0.5)]' : 'bg-amber-500'}`}></span>
                      {asset.status === ASSET_STATUS.OPERATIVE ? 'Operativo' : asset.status === ASSET_STATUS.MAINTENANCE ? 'Mantención' : 'Baja'}
                    </span>
                  </td>
                  <td className="px-6 py-6">
                    <div className="w-28 mx-auto">
                      <div className="flex items-center justify-between mb-2">
                        <span className="text-[10px] font-black text-slate-500">{asset.usefulLife}%</span>
                        <span className="text-[9px] text-slate-600 font-bold uppercase">{asset.yearsRemaining} años rest.</span>
                      </div>
                      <div className="h-1.5 w-full bg-white/5 rounded-full overflow-hidden border border-white/5">
                        <div className={`h-full ${asset.usefulLife > 50 ? 'bg-success' : 'bg-amber-500'}`} style={{ width: `${asset.usefulLife}%` }}></div>
                      </div>
                    </div>
                  </td>
                  <td className="px-8 py-6 text-right">
                    <div className="flex justify-end gap-2">
                      <Link
                        to={`/asset/${asset.id}`}
                        className="px-4 py-2 bg-medical-blue/10 text-medical-blue rounded-xl hover:bg-medical-blue hover:text-white transition-all border border-medical-blue/20 flex items-center gap-2 text-[10px] font-black uppercase tracking-wider shadow-lg shadow-medical-blue/5"
                        title="Ver Historial y OTs"
                      >
                        <span className="material-symbols-outlined text-sm">history</span>
                        Historial
                      </Link>
                      {hasPermission(Permission.MANAGE_INVENTORY) && (
                        <button className="p-2 text-slate-500 hover:text-white hover:bg-white/5 rounded-xl border border-transparent hover:border-slate-700/50 transition-all">
                          <span className="material-symbols-outlined text-xl">more_vert</span>
                        </button>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <div className="px-8 py-6 bg-white/5 border-t border-slate-700/50 flex items-center justify-between">
          <p className="text-[10px] text-slate-500 font-black uppercase tracking-widest">
            Mostrando {filtered.length} de {assets.length} Activos Biomédicos Registrados
          </p>
          <div className="flex gap-3">
            <button className="px-4 py-2 text-xs font-bold border border-slate-700/50 rounded-xl text-slate-500 hover:bg-white/5 transition-all disabled:opacity-30" disabled>Anterior</button>
            <button className="size-9 text-xs bg-medical-blue text-white rounded-xl font-bold">1</button>
            <button className="px-4 py-2 text-xs font-bold border border-slate-700/50 rounded-xl text-slate-500 hover:bg-white/5 transition-all">Siguiente</button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Inventory;
