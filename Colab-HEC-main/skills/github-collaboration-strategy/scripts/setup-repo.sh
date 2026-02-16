#!/bin/bash

# Script para configurar un repositorio con la estrategia de colaboración
# Uso: ./setup-repo.sh [nombre-del-repo]

set -e

REPO_NAME=${1:-"."}

echo "🔧 Configurando repositorio: $REPO_NAME"

# Crear estructura de carpetas de GitHub
mkdir -p "$REPO_NAME/.github/workflows"

# Copiar plantilla de PR
cp "templates/PR_TEMPLATE.md" "$REPO_NAME/.github/PULL_REQUEST_TEMPLATE.md" 2>/dev/null || echo "⚠️  No se encontró la plantilla de PR. Creando una básica."

# Crear un workflow de CI básico si no existe
if [ ! -f "$REPO_NAME/.github/workflows/ci.yml" ]; then
cat <<EOF > "$REPO_NAME/.github/workflows/ci.yml"
name: CI Baseline

on:
  pull_request:
    branches: [ main ]

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Basic Check
        run: echo "CI Basline OK. Configura tus propios tests aquí."
EOF
fi

echo "✅ Configuración inicial completada."
echo "👉 Siguientes pasos: Activar 'Branch Protection Rules' en GitHub para la rama 'main'."
