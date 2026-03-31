# BioCMMS v4.5 BUILD 2026 - Gestión de Mantenimiento Biomédico

BioCMMS es un sistema integral de gestión de mantenimiento (CMMS) diseñado específicamente para ingeniería clínica y mantenimiento biomédico, enfocado en el cumplimiento de normativas y trazabilidad de activos.

## 🚀 Instalación Rápida (Windows + XAMPP/MySQL)

Si acabas de descargar el proyecto, sigue estos pasos para ponerlo en marcha:

### 1. Preparar el Entorno
1. Asegúrate de tener **XAMPP** instalado (PHP 8.x recomendado).
2. Clona o pega esta carpeta dentro de tu directorio de servidor (ej: `C:\xampp\htdocs\cmms-php`).
3. Abre el Panel de Control de XAMPP e inicia **Apache** y **MySQL**.

### 2. Configurar Base de Datos (Un solo clic)
He incluido un automatizador para que no tengas que importar archivos manualmente:
1. Localiza el archivo **`Instalar_BaseDeDatos.bat`** en la carpeta principal.
2. Haz **doble clic** en él.
3. El script creará la base de datos `biocmms`, las tablas y cargará los datos iniciales automáticamente.

---

## ⚙️ Configuración Personalizada (.env)

Si usas un servidor MySQL independiente (fuera de XAMPP) o tienes una contraseña de root:
1. Abre el archivo **`.env`** en la raíz del proyecto.
2. Edita las siguientes líneas según tu configuración:
   ```bash
   DB_HOST=127.0.0.1
   DB_USER=root
   DB_PASS=tu_contraseña_aqui
   DB_NAME=biocmms
   ```

---

## 🔑 Acceso al Sistema

El sistema cuenta con una cuenta maestra de administrador para pruebas iniciales:
*   **URL:** `http://localhost/cmms-php/`
*   **Email:** `admin@biocmms.com` (o simplemente `admin`)
*   **Contraseña:** `password` (o cualquier texto, el acceso maestro está habilitado).

---

## 🛠️ Solución de Problemas Comunes

### El diseño se ve mal en Chrome
Si la interfaz se ve desordenada o sin colores:
1. Asegúrate de haber copiado la carpeta `assets` completa.
2. **Forzar Recarga:** Presiona `Ctrl + F5` para limpiar la caché de estilos del navegador.
3. El sistema ya está configurado para funcionar **offline** (usa Tailwind local), por lo que no depende de Internet para el diseño base.

### Error de conexión a Base de Datos
Si el instalador `.bat` te da un error en rojo:
1. Verifica que el servicio MySQL esté encendido en XAMPP.
2. Si tienes MySQL instalado por separado, verifica que la contraseña en el `.env` coincida con la de tu servidor.
3. Asegúrate de que el puerto de MySQL sea el estándar (3306).

---

## 📄 Requisitos del Sistema
*   **PHP:** 8.0 o superior.
*   **Base de Datos:** MySQL 5.7+ o MariaDB 10.4+.
*   **Extensiones PHP:** `pdo_mysql`, `mbstring`, `json`.

---
© 2026 BioCMMS Project - Gestión Médica Avanzada.
