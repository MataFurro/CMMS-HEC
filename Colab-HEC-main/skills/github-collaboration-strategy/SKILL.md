---
name: github-collaboration-strategy
description: "Estrategia para gestionar proyectos colaborativos en GitHub de manera segura, protegiendo la funcionalidad y separando tareas entre equipos mediante flujos de trabajo automatizados."
version: 1.0.0
author: Antigravity / Pablof
created: 2026-02-12
platforms: [github]
category: devops
tags: [collaboration, git, github-actions, teamwork]
risk: safe
---

# github-collaboration-strategy

## Propósito

Esta skill permite implementar y mantener una estrategia de trabajo colaborativo robusta en cualquier repositorio de GitHub gestionado por Antigravity. Asegura que múltiples equipos puedan trabajar simultáneamente sin interferir entre sí y manteniendo la integridad del código.

## Cuándo usar esta Skill

- Al iniciar un nuevo proyecto colaborativo.
- Cuando un equipo reporta conflictos frecuentes de código.
- Para automatizar la protección de la rama principal (`main`).
- Para estandarizar el proceso de revisión mediante Pull Requests.

## Flujo de Trabajo Maestro

### 1. Preparación del Repositorio

Cuando se activa esta skill, el primer paso es asegurar que el entorno Git sea correcto:

- Crear rama `main` si no existe.
- Crear el archivo `.github/PULL_REQUEST_TEMPLATE.md`.
- Configurar el workflow básico de CI en `.github/workflows/ci.yml`.

### 2. Ciclo de Desarrollo de Características (Feature)

Para cada nueva tarea, el asistente (tú) debe seguir este protocolo:

- Crear una rama con el prefijo `feature/` o `fix/`.
- Realizar cambios atómicos y descriptivos.
- Al completar, abrir un Pull Request (PR) utilizando la plantilla estandarizada.

### 3. Protocolo de Revisión y Merge

- **No Self-Merge**: Nunca fusionar tus propios cambios sin aprobación.
- **Revisiones Cruzadas**: Solicitar revisión de al menos un miembro de otro equipo.
- **Validación Automática**: El merge es imposible si los checks de CI (linters/tests) fallan.

## Recursos de la Skill

- `/home/pablof/Diseños App/Repositorio GIT/.agent/skills/github-collaboration-strategy/templates/PR_TEMPLATE.md`: Plantilla para Pull Requests.
- `/home/pablof/Diseños App/Repositorio GIT/.agent/skills/github-collaboration-strategy/scripts/setup-repo.sh`: Script de configuración inicial.

## Instrucciones para el Asistente

1. **Detección**: Si el usuario menciona "colaboración", "varios equipos" o "estrategia github", consulta esta skill.
2. **Ejecución**: Usa el script `setup-repo.sh` para inicializar el repositorio si aún no está configurado.
3. **Mantenimiento**: Asegúrate de que cada PR que abras siga el formato de la plantilla.
