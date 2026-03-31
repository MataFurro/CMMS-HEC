# Instrucciones para Desplegar (Subir) la Base de Datos de BioCMMS

¡He adelantado el trabajo por ti! En la carpeta de tu proyecto he generado un archivo consolidado llamado **`biocmms_full_export.sql`**. Este archivo contiene toda la estructura de la base de datos, todas las migraciones aplicadas correctamente en el orden adecuado, y los usuarios/técnicos de prueba listos.

Sigue estos 3 pasos simples para subir tu base de datos al servidor de producción (como Hostinger, cPanel u otro VPS):

## Paso 1: Crear la Base de Datos en tu Hosting

1. Ingresa al panel de control de tu proveedor de hosting (ej. cPanel, Hostinger Panel) y busca la sección **Bases de datos MySQL**.
2. **Crea una nueva base de datos** (por ejemplo, `biocmms_db`). Toma nota del nombre completo que te genere el sistema (muchas veces le agrega un prefijo, como `usuario_biocmms_db`).
3. **Crea un usuario** para esta base de datos, agrégale una contraseña segura y asegúrate de **asociar el usuario a la base de datos** dándole *Todos los privilegios* (All Privileges).

## Paso 2: Importar la Estructura (el SQL)

1. En tu panel de hosting, abre **phpMyAdmin** (o cualquier gestor de bases de datos web que provean).
2. Selecciona la base de datos recién creada (e.g. `usuario_biocmms_db`) en la barra lateral izquierda. No debe tener ninguna tabla adentro.
3. Ve a la pestaña superior que dice **Importar**.
4. Haz clic en **Seleccionar archivo** y escoge el archivo **`biocmms_full_export.sql`** que te dejé generado en la carpeta principal de tu proyecto.
5. Baja al final de la página y dale al botón **Continuar** o **Importar**. 
   > *Tras unos segundos, verás un mensaje verde de éxito y todas tus tablas aparecerán a la izquierda.*

## Paso 3: Conectar tu BioCMMS PHP

En el servidor donde subirás todo tu código PHP, no olvides editar tu archivo de configuración para apuntar a la base de datos real en lugar de tu XAMPP local.

Abre tu archivo **`.env`** (si lo tienes configurado como principal) o edita los parámetros en `config.php` según corresponda:

```php
DB_HOST = localhost           // Casi siempre es localhost. Si es externo, coloca la IP del servidor.
DB_NAME = usuario_biocmms_db  // El nombre EXACTO que creaste en el Paso 1
DB_USER = tu_usuario          // El usuario MySQL que creaste
DB_PASS = tu_contraseña       // La contraseña fuerte del usuario
```

¡Eso es todo! Luego, solo sube todos los archivos de tu proyecto `cmms php` (a través de FTP o tu gestor de archivos) y el sistema ya funcionará consumiendo la base de datos definitiva en la nube.
