# Bosquejo - Mensajero de Reportes (CMMS Messenger)

Este componente actúa como un **mensajero bidireccional** que traslada las peticiones de servicio desde las áreas clínicas hasta el CMMS, y devuelve información técnica al solicitante.

## Flujo de Trabajo Detallado

### 1. Captura de Reporte (Ida: MS -> CMMS)
El personal clínico utiliza una interfaz (basada en Stitch) para enviar:
- **Correo del Solicitante**: Para recibir la respuesta técnica.
- **Equipo y Número de Serie**: Identificación precisa del activo.
- **Descripción (Texto)**: Detalle del problema detectado.
- **Evidencia (Imagen)**: Captura visual del error o estado físico.

**Acción**: El Messenger recibe estos datos, procesa la imagen y los deposita en la cola de entrada del CMMS.

### 2. Respuesta Técnica (Vuelta: CMMS -> Solicitante)
Una vez que el equipo de mantenimiento procesa la solicitud:
- El Messenger genera un **Reporte Técnico**.
- Envía un correo de vuelta al solicitante con el **Detalle del Informe** y el estado de la intervención.

---

## Funciones Principales (Logic Level)

### 1. `enviarReporteAlCMMS(array $payload, FILE $imagen)`
- **Procesamiento**: Valida los campos, guarda la imagen en un directorio seguro y notifica al dashboard de mantenimiento.

### 2. `enviarDetalleTecnico(string $correoDestino, array $informeTecnico)`
- **Procesamiento**: Construye el mensaje de respuesta con el diagnóstico y las acciones realizadas, y lo envía vía SMTP.

---

## Estructura de Datos (Bandeja de Entrada)
| Campo | Tipo | Descripción |
| --- | --- | --- |
| `email_solicitante` | String | Correo para feedback. |
| `serie_equipo` | String | S/N del activo. |
| `nombre_equipo` | String | Nombre descriptivo. |
| `descripcion`| Text | Relato de la falla. |
| `path_imagen` | String | Ruta al archivo guardado. |

---

## Estructura del Componente (`API Mail/`)
- `messenger.php`: Recibe las peticiones `POST` con archivos (multipart/form-data).
- `return_path.php`: Gestiona el envío de correos de vuelta con el reporte técnico.
- `uploads/`: Directorio para almacenar imágenes de reportes.

---

## Próximos pasos técnicos
1. Crear `API Mail/messenger.php` para recepción de datos y archivos.
2. Configurar una librería básica de envío de correos (ej: PHPMailer o mail() simple).
