
import React, { useState } from 'react';

interface LoginProps {
  onLogin: (userId: string) => void;
}

const Login: React.FC<LoginProps> = ({ onLogin }) => {
  const [selectedUser, setSelectedUser] = useState('u2'); // Default Engineer
  return (
    <div className="min-h-screen flex items-center justify-center relative overflow-hidden bg-background-light dark:bg-background-dark font-display">
      <div className="absolute inset-0 z-0 opacity-20" style={{
        backgroundImage: 'radial-gradient(circle at 2px 2px, #137fec 1px, transparent 0)',
        backgroundSize: '40px 40px'
      }}></div>

      <main className="relative z-10 w-full max-w-[1100px] px-6 py-12 flex flex-col-reverse md:flex-row items-center justify-center gap-12 lg:gap-24">
        {/* Formulario a la Izquierda */}
        <div className="w-full max-w-[460px]">
          <div className="glass-panel p-8 md:p-10 rounded-2xl shadow-2xl shadow-primary/10 border border-border-dark">
            <div className="flex flex-col gap-2 mb-8">
              <div className="md:hidden flex justify-center mb-4">
                <span className="material-symbols-outlined text-5xl text-primary font-variation-fill">biotech</span>
              </div>
              <h3 className="text-2xl font-bold text-white">Acceso al Sistema</h3>
              <p className="text-[#9dabb9] text-sm font-medium">Ingrese sus credenciales técnicas</p>
            </div>

            <form className="flex flex-col gap-5" onSubmit={(e) => { e.preventDefault(); onLogin(selectedUser); }}>
              <div className="flex flex-col gap-2">
                <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">Seleccionar Rol (Demo)</label>
                <div className="relative group">
                  <span className="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-[#9dabb9] group-focus-within:text-primary transition-colors">badge</span>
                  <select
                    className="w-full bg-background-dark border border-border-dark text-white rounded-xl pl-12 pr-4 py-3.5 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all placeholder:text-slate-700 appearance-none"
                    value={selectedUser}
                    onChange={(e) => setSelectedUser(e.target.value)}
                  >
                    <option value="u1">Técnico (Mario)</option>
                    <option value="u2">Ingeniero (Laura)</option>
                    <option value="u3">Jefe Ingeniería (Roberto)</option>
                    <option value="u4">Auditor (Ana)</option>
                  </select>
                </div>
              </div>

              <div className="flex flex-col gap-2">
                <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">Usuario de Red</label>
                <div className="relative group">
                  <span className="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-[#9dabb9] group-focus-within:text-primary transition-colors">person</span>
                  <input className="w-full bg-background-dark border border-border-dark text-white rounded-xl pl-12 pr-4 py-3.5 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all placeholder:text-slate-700" placeholder="ej. j.ruiz" type="text" disabled />
                </div>
              </div>
              <div className="flex flex-col gap-2">
                <div className="flex justify-between items-center">
                  <label className="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">Contraseña</label>
                  <a className="text-xs font-bold text-primary hover:underline uppercase tracking-tighter p-2 -mr-2" href="#">¿Recuperar clave?</a>
                </div>
                <div className="relative group">
                  <span className="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-[#9dabb9] group-focus-within:text-primary transition-colors">lock</span>
                  <input className="w-full bg-background-dark border border-border-dark text-white rounded-xl pl-12 pr-12 py-3.5 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all placeholder:text-slate-700" placeholder="••••••••" type="password" disabled />
                </div>
              </div>
              <button className="mt-4 w-full bg-primary hover:bg-primary/90 text-white font-bold py-4 rounded-xl shadow-lg shadow-primary/25 active:scale-[0.98] transition-all flex items-center justify-center gap-3 uppercase tracking-widest text-xs">
                <span>Entrar como {selectedUser === 'u1' ? 'Técnico' : selectedUser === 'u2' ? 'Ingeniero' : selectedUser === 'u3' ? 'Jefe' : 'Auditor'}</span>
                <span className="material-symbols-outlined text-xl">login</span>
              </button>
            </form>
          </div>
        </div>

        {/* Info a la Derecha */}
        <div className="flex flex-col gap-6 max-w-md text-left">
          <div className="flex items-center gap-3 text-primary">
            <span className="material-symbols-outlined text-5xl font-variation-fill">biotech</span>
            <h1 className="text-3xl font-bold tracking-tight text-white leading-none">BioCMMS <span className="text-primary/80 font-light text-xl">v4.2</span></h1>
          </div>
          <div className="space-y-4">
            <h2 className="text-4xl font-bold leading-tight text-white tracking-tight">Gestión Biomédica de Alto Rendimiento</h2>
            <p className="text-[#9dabb9] text-lg leading-relaxed font-medium">
              Ecosistema centralizado para el cumplimiento normativo de su equipamiento médico.
            </p>
          </div>
          <div className="flex items-center gap-4 pt-4">
            <div className="flex -space-x-3">
              {[1, 2, 3].map(i => (
                <div key={i} className="h-10 w-10 rounded-full border-2 border-background-dark overflow-hidden shadow-xl">
                  <img src={`https://picsum.photos/seed/doctor${i}/100/100`} alt="User" />
                </div>
              ))}
            </div>
            <p className="text-[11px] text-slate-500 font-bold uppercase tracking-wider">+150 Instituciones de Salud</p>
          </div>
        </div>
      </main>
    </div>
  );
};

export default Login;
