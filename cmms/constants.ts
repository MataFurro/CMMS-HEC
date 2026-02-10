
/**
 * CMMS Interface Constants
 * Derived from regulations and UI standards.
 */

// Colors for Charts and UI elements (matching Tailwind config where possible)
export const COLORS = {
    MEDICAL_BLUE: '#0ea5e9', // Matches bg-medical-blue (approx sky-500)
    EMERALD: '#10b981',      // Matches emerald-500
    AMBER: '#f59e0b',        // Matches amber-500
    RED: '#ef4444',          // Matches red-500
    SLATE_400: '#94a3b8',    // Matches slate-400
    SLATE_700: '#334155',    // Matches slate-700
    BG_DARK: '#1e293b',      // Matches slate-800
    SUCCESS: '#10b981',      // Matches emerald-500 (alias for EMERALD)
    WARNING: '#f59e0b',      // Matches amber-500 (alias for AMBER)
    DANGER: '#ef4444',       // Matches red-500 (alias for RED)
};

// Asset Statuses - Consistent with types.ts
export const ASSET_STATUS = {
    OPERATIVE: 'OPERATIVE',
    MAINTENANCE: 'MAINTENANCE',
    OUT_OF_SERVICE: 'OUT_OF_SERVICE',
};

// Asset Criticalities - Consistent with types.ts
export const ASSET_CRITICALITY = {
    CRITICAL: 'Crítico',
    RELEVANT: 'Relevante',
    LOW: 'Bajo',
    NA: 'No Aplica',
};

// KPI Targets and Thresholds
export const KPI_TARGETS = {
    MTBF_GOAL: 700, // Hours
    MTTR_GOAL: 4.0, // Hours
    PMP_COMPLIANCE_GOAL: 90, // Percentage
    TECH_CAPACITY_WARNING: 90, // Percentage
    TECH_CAPACITY_OPTIMAL: 85, // Percentage
};

// UI Strings
export const UI_LABELS = {
    DASHBOARD_TITLE: 'Panel de Gestión Biomédica',
    DASHBOARD_SUBTITLE: 'Monitoreo en Tiempo Real de Activos y Carga Técnica',
    EXPORT_BTN: 'Exportar KPI',
    CONTROL_PANEL_BTN: 'Panel de Control',
    RELIABILITY_HISTORY: 'Histórico de Confiabilidad',
    ASSET_STATUS: 'Estado del Parque',
    WORKLOAD_DISTRIBUTION: 'Carga Laboral por Especialista',
    TECH_EFFECTIVENESS: 'Efectividad por Técnico',
    RECENT_EVENTS: 'Bitácora de Eventos Recientes',
    INVENTORY_TITLE: 'Inventario Técnico',
    INVENTORY_SUBTITLE: '(Activos Biomédicos)',
    INVENTORY_DESC: 'Gestión centralizada de equipamiento clínico y soporte de vida.',
    BTN_DOWNLOAD_EXCEL: 'Descargar Excel',
    BTN_UPLOAD_EXCEL: 'Cargar Excel',
    BTN_NEW_ASSET: 'Nuevo Activo',
    SEARCH_PLACEHOLDER: 'Nombre de equipo, marca o ID inventario...',
    FILTER_ALL_STATUS: 'Todos los Estados',
    FILTER_CRITICALITY: 'Criticidad',
    BTN_CLEAR_FILTERS: 'Limpiar',
};
