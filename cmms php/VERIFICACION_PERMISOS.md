# Verificación de Permisos - Sistema de Roles

## ⚠️ IMPORTANTE: Reiniciar Servidor

Después de modificar archivos PHP, **DEBES reiniciar el servidor** para ver los cambios:

```bash
# Detener servidor actual (Ctrl+C en la terminal)
# Luego reiniciar:
.\start_server.bat
```

O desde PowerShell:
```powershell
# Detener proceso PHP
Get-Process php | Stop-Process -Force

# Reiniciar servidor
C:\xampp\php\php.exe -S localhost:8000
```

---

## Checklist de Verificación por Rol

### 🔧 Técnico

**En Órdenes de Trabajo (`?page=work_orders`):**
- [ ] ❌ NO ve botón "Nueva Orden"
- [ ] ✅ SÍ ve botón "COMPLETAR" en órdenes no terminadas
- [ ] ✅ SÍ ve botón "Ver" (ojo)

**Dentro de Orden (`?page=work_order_execution`):**
- [ ] ✅ SÍ ve botón "Cargar Evidencia"
- [ ] ✅ SÍ puede escribir en textarea
- [ ] ✅ SÍ ve botón "Guardar Borrador"
- [ ] ❌ NO ve botón "Finalizar e Informar"

**En Inventario (`?page=inventory`):**
- [ ] ✅ SÍ puede ver la tabla de activos
- [ ] ❌ NO ve botón "Exportar Excel"
- [ ] ❌ NO ve botón "Cargar Excel"
- [ ] ❌ NO ve botón "Nuevo Activo"

---

### 📋 Auditor

**En Órdenes de Trabajo:**
- [ ] ❌ NO ve botón "Nueva Orden"
- [ ] ❌ NO ve botón "COMPLETAR"
- [ ] ✅ SÍ ve botón "Ver" (ojo)

**Dentro de Orden:**
- [ ] ❌ NO ve botón "Cargar Evidencia"
- [ ] ❌ Textarea en modo `readonly`
- [ ] ❌ NO ve ningún botón de acción

**En Inventario:**
- [ ] ✅ SÍ puede ver la tabla de activos
- [ ] ❌ NO ve ningún botón (exportar, cargar, nuevo)

---

### 👨‍💼 Ingeniero/Admin

**En Órdenes de Trabajo:**
- [ ] ✅ SÍ ve botón "Nueva Orden"
- [ ] ✅ SÍ ve botón "COMPLETAR"
- [ ] ✅ SÍ ve botón "Ver"

**Dentro de Orden:**
- [ ] ✅ SÍ ve botón "Cargar Evidencia"
- [ ] ✅ SÍ puede escribir en textarea
- [ ] ✅ SÍ ve botón "Guardar Borrador"
- [ ] ✅ SÍ ve botón "Finalizar e Informar"

**En Inventario:**
- [ ] ✅ SÍ ve botón "Exportar Excel"
- [ ] ✅ SÍ ve botón "Cargar Excel"
- [ ] ✅ SÍ ve botón "Nuevo Activo"

---

## Cómo Cambiar de Rol para Probar

### Opción 1: Modificar Sesión Directamente

Edita `pages/login.php` o donde se inicialice la sesión:

```php
// Para probar como Técnico
$_SESSION['user_role'] = 'Técnico';

// Para probar como Auditor
$_SESSION['user_role'] = 'Auditor';

// Para probar como Ingeniero
$_SESSION['user_role'] = 'Ingeniero';
```

### Opción 2: Usar DevTools del Navegador

1. Abrir DevTools (F12)
2. Ir a "Application" → "Cookies"
3. Modificar valor de sesión PHP
4. Recargar página

---

## Archivos Modificados

| Archivo | Función |
|---------|---------|
| `config.php` | Funciones helper de permisos |
| `work_orders.php` | Botones "Nueva Orden" y "COMPLETAR" |
| `work_order_execution.php` | Botones de ejecución y finalización |
| `inventory.php` | Botones de exportar/importar/crear |

---

## Solución de Problemas

### Los cambios no se ven

1. **Reiniciar servidor PHP** (ver comandos arriba)
2. **Limpiar caché del navegador** (Ctrl+Shift+R)
3. **Verificar que `config.php` se carga** (agregar `echo "Config loaded";` temporalmente)

### Técnico ve botones que no debería

1. Verificar que `$_SESSION['user_role']` = `'Técnico'` (exacto, con tilde)
2. Verificar que funciones helper están definidas en `config.php`
3. Agregar debug: `<?php var_dump($_SESSION['user_role']); ?>`

### Errores de PHP

1. Revisar logs: `C:\xampp\apache\logs\error.log`
2. Habilitar errores en `config.php`: `ini_set('display_errors', 1);`
