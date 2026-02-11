# Reporte de Estado BioCMMS v4.2 Pro

**Fecha de Generación:** 2026-02-11
**Estado del Sistema:** Operativo / Clinical Engineering 2.0 Ready

Este documento sirve como guía para el reporte visual de la aplicación. Debido a una limitación técnica momentánea en el sistema de captura automatizada, se recomienda tomar capturas manuales de las siguientes secciones clave para completar el dosier:

## 1. Dashboard Operativo (Vista General)
- **URL:** `http://localhost:8000/?page=dashboard`
- **Puntos Relevantes:**
    - Gráfico de Confiabilidad Weibull (Beta/Eta).
    - Métricas de Disponibilidad y Tiempo Medio entre Fallas (MTBF).
    - Cuadro de Carga de Trabajo de los Técnicos (Saturación de personal).

## 2. Inventario Estratégico
- **URL:** `http://localhost:8000/?page=inventory`
- **Puntos Relevantes:**
    - Listado de activos con indicadores de criticidad.
    - Filtros de búsqueda y estado de mantenimiento.

## 3. Ejecución de Órdenes (Compliance)
- **URL:** `http://localhost:8000/?page=work_order_execution&id=OT-2024-0512`
- **Puntos Relevantes:**
    - Panel de firmas digitales (21 CFR Part 11).
    - Checklists de seguridad eléctrica.

## 4. Análisis por Familia
- **URL:** `http://localhost:8000/?page=family_analysis`
- **Puntos Relevantes:**
    - Desglose de costos y fallas por tipo de equipo médico.

---
*BioCMMS - Inteligencia en Gestión Biomédica*
