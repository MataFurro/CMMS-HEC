
import React from 'react';
import { Routes, Route, Link, useLocation, Navigate } from 'react-router-dom';
import Login from './pages/Login';
import Dashboard from './pages/Dashboard';
import Inventory from './pages/Inventory';
import WorkOrders from './pages/WorkOrders';
import WorkOrderOpening from './pages/WorkOrderOpening';
import WorkOrderExecution from './pages/WorkOrderExecution';
import AssetHistory from './pages/AssetHistory';
import Calendar from './pages/Calendar';
import NewAsset from './pages/NewAsset';
import { useAuth } from './context/AuthContext';
import { useState } from 'react';
import { recentEvents } from './mockData';

const Sidebar = () => {
  const location = useLocation();
  const { logout, currentUser } = useAuth();
  const menuItems = [
    { name: 'Dashboard', path: '/dashboard', icon: 'dashboard' },
    { name: 'Agenda Técnica', path: '/calendar', icon: 'calendar_month' },
    { name: 'Órdenes', path: '/work-orders', icon: 'assignment' },
    { name: 'Inventario', path: '/inventory', icon: 'precision_manufacturing' },
  ];

  return (
    <aside className="fixed lg:sticky top-0 left-0 flex flex-col w-20 lg:w-72 bg-medical-surface border-r border-slate-700/50 h-screen shrink-0 z-50 transition-all duration-300">
      <div className="p-4 lg:p-8 flex flex-col items-center lg:items-start">
        <div className="flex items-center gap-3 mb-10 lg:mb-12">
          <div className="bg-medical-blue rounded-lg w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center shadow-lg shadow-medical-blue/20">
            <span className="material-symbols-outlined text-white text-2xl lg:text-3xl font-variation-fill">engineering</span>
          </div>
          <div className="hidden lg:block">
            <h2 className="text-sm font-black tracking-tight text-white leading-none uppercase">BioCMMS</h2>
            <p className="text-[10px] text-medical-blue font-black tracking-widest uppercase mt-1">Professional Edition</p>
          </div>
        </div>

        <nav className="flex flex-col gap-1.5 w-full">
          {menuItems.map((item) => {
            const isActive = location.pathname.startsWith(item.path);
            return (
              <Link
                key={item.path}
                to={item.path}
                title={item.name}
                className={`flex items-center justify-center lg:justify-start gap-4 p-3 lg:px-5 lg:py-3.5 rounded-xl transition-all duration-200 group ${isActive
                  ? 'text-medical-blue bg-medical-blue/10 font-bold border-r-4 border-medical-blue rounded-r-none'
                  : 'text-slate-400 hover:text-white hover:bg-slate-800'
                  }`}
              >
                <span className={`material-symbols-outlined text-2xl ${isActive ? 'fill-1' : ''}`}>
                  {item.icon}
                </span>
                <span className="hidden lg:block text-xs font-semibold uppercase tracking-wider">{item.name}</span>
              </Link>
            );
          })}
        </nav>
      </div>

      <div className="mt-auto p-4 lg:p-8 border-t border-slate-700/50 w-full">
        <div className="flex items-center gap-4 mb-4">
          <div className="size-10 lg:size-10 rounded-full bg-slate-700 flex items-center justify-center shrink-0 overflow-hidden border border-slate-600">
            <img src={currentUser?.avatar || "https://picsum.photos/seed/doc1/100/100"} className="w-full h-full object-cover" alt="User" />
          </div>
          <div className="hidden lg:block overflow-hidden">
            <p className="text-xs font-bold text-white leading-none truncate">{currentUser?.name || 'Usuario'}</p>
            <p className="text-[10px] text-slate-500 uppercase font-bold tracking-widest mt-1.5 truncate">{currentUser?.role || 'Invitado'}</p>
          </div>
        </div>
      </div>
    </aside>
  );
};

const Header = () => {
  const { logout, currentUser } = useAuth();
  const [showNotifications, setShowNotifications] = useState(false);
  const [showSettings, setShowSettings] = useState(false);

  return (
    <header className="h-16 lg:h-20 border-b border-slate-700/50 bg-medical-dark/80 backdrop-blur-md sticky top-0 z-40 px-6 lg:px-10 flex items-center justify-between ml-20 lg:ml-0">
      <div className="flex lg:hidden items-center gap-3">
        <h2 className="text-sm font-black text-white tracking-tighter uppercase">BioCMMS</h2>
      </div>
      <div className="hidden md:flex items-center bg-slate-900 border border-slate-700/50 rounded-lg px-4 py-1.5 group focus-within:border-medical-blue/50 transition-all">
        <span className="material-symbols-outlined text-slate-500 text-lg group-focus-within:text-medical-blue transition-colors">search</span>
        <input
          className="bg-transparent border-none focus:ring-0 text-xs w-48 lg:w-72 placeholder:text-slate-600 text-white"
          placeholder="Buscar activos, OTs, personal..."
          type="text"
        />
      </div>
      <div className="flex items-center gap-3 lg:gap-5">
        <div className="hidden sm:flex items-center gap-2">
          <div className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
          <span className="text-[9px] font-black text-emerald-500 uppercase tracking-widest">En Línea</span>
        </div>

        {/* Notifications Dropdown */}
        <div className="relative">
          <button
            onClick={() => { setShowNotifications(!showNotifications); setShowSettings(false); }}
            className="p-2 rounded-lg bg-slate-800 border border-slate-700 text-slate-400 relative hover:text-white transition-all active:scale-95"
          >
            <span className="material-symbols-outlined text-xl">notifications</span>
            <span className="absolute top-2 right-2 size-1.5 bg-red-500 rounded-full border border-medical-dark"></span>
          </button>

          {showNotifications && (
            <div className="absolute right-0 top-full mt-2 w-80 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl z-50 overflow-hidden animate-in fade-in zoom-in-95 duration-200">
              <div className="p-3 border-b border-slate-800 flex justify-between items-center">
                <span className="text-xs font-bold text-white uppercase tracking-wider">Notificaciones</span>
                <span className="text-[10px] text-medical-blue font-bold cursor-pointer hover:underline">Marcar leídas</span>
              </div>
              <div className="max-h-64 overflow-y-auto custom-scrollbar">
                {recentEvents.map((event, idx) => (
                  <div key={idx} className="p-3 hover:bg-slate-800/50 transition-colors border-b border-slate-800/50 last:border-0 cursor-pointer">
                    <div className="flex justify-between items-start mb-1">
                      <span className={`text-[10px] font-bold px-1.5 py-0.5 rounded ${event.type === 'warning' ? 'bg-amber-500/10 text-amber-500' : 'bg-emerald-500/10 text-emerald-500'}`}>{event.type === 'warning' ? 'Alerta' : 'Info'}</span>
                      <span className="text-[10px] text-slate-500">{event.time}</span>
                    </div>
                    <p className="text-xs font-bold text-slate-200 leading-tight mb-0.5">{event.title}</p>
                    <p className="text-[10px] text-slate-500 line-clamp-2">{event.subtitle}</p>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>

        <div className="h-6 w-px bg-slate-700 hidden lg:block"></div>

        {/* Settings Dropdown */}
        <div className="relative hidden lg:block">
          <button
            onClick={() => { setShowSettings(!showSettings); setShowNotifications(false); }}
            className="p-2 rounded-lg bg-slate-800 border border-slate-700 text-slate-400 hover:text-white transition-all active:scale-95"
          >
            <span className="material-symbols-outlined text-xl">settings</span>
          </button>

          {showSettings && (
            <div className="absolute right-0 top-full mt-2 w-48 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl z-50 overflow-hidden animate-in fade-in zoom-in-95 duration-200">
              <div className="p-3 border-b border-slate-800">
                <p className="text-xs font-bold text-white">{currentUser?.name}</p>
                <p className="text-[10px] text-slate-500 uppercase font-bold">{currentUser?.role}</p>
              </div>
              <div className="p-1">
                <button className="w-full text-left px-3 py-2 text-xs font-medium text-slate-300 hover:text-white hover:bg-slate-800 rounded-lg flex items-center gap-2 transition-colors">
                  <span className="material-symbols-outlined text-base">settings</span>
                  Configuración
                </button>
                <button className="w-full text-left px-3 py-2 text-xs font-medium text-slate-300 hover:text-white hover:bg-slate-800 rounded-lg flex items-center gap-2 transition-colors">
                  <span className="material-symbols-outlined text-base">person</span>
                  Perfil
                </button>
                <button
                  onClick={logout}
                  className="w-full text-left px-3 py-2 text-xs font-medium text-red-400 hover:text-red-300 hover:bg-red-500/10 rounded-lg flex items-center gap-2 transition-colors"
                >
                  <span className="material-symbols-outlined text-base">logout</span>
                  Cerrar Sesión
                </button>
              </div>
            </div>
          )}
        </div>
      </div>
    </header>
  );
};

const MainLayout = ({ children }: { children: React.ReactNode }) => (
  <div className="flex min-h-screen bg-medical-dark text-slate-200">
    <Sidebar />
    <div className="flex-1 flex flex-col min-w-0">
      <Header />
      <main className="p-6 lg:p-8 max-w-[1600px] w-full mx-auto custom-scrollbar ml-20 lg:ml-0 overflow-x-hidden">
        {children}
      </main>
    </div>
  </div>
);


const App: React.FC = () => {
  const { currentUser, login } = useAuth();

  if (!currentUser) return <Login onLogin={(userId) => login(userId)} />;

  return (
    <Routes>
      <Route path="/" element={<Navigate to="/dashboard" />} />
      <Route path="/dashboard" element={<MainLayout><Dashboard /></MainLayout>} />
      <Route path="/calendar" element={<MainLayout><Calendar /></MainLayout>} />
      <Route path="/inventory" element={<MainLayout><Inventory /></MainLayout>} />
      <Route path="/inventory/new" element={<MainLayout><NewAsset /></MainLayout>} />
      <Route path="/asset/:id" element={<MainLayout><AssetHistory /></MainLayout>} />
      <Route path="/work-orders" element={<MainLayout><WorkOrders /></MainLayout>} />
      <Route path="/work-orders/new" element={<MainLayout><WorkOrderOpening /></MainLayout>} />
      <Route path="/work-order/:id/execute" element={<MainLayout><WorkOrderExecution /></MainLayout>} />
      <Route path="*" element={<Navigate to="/dashboard" />} />
    </Routes>
  );
};

export default App;
