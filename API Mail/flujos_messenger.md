# Flujos de Trabajo - Messenger HEC (Standalone)

Este documento describe los flujos de comunicación bidireccional implementados en la carpeta `Diseño/API Mail/`.

## 1. Flujo de Reporte (Solicitud Clínica)
Este es el camino que recorren los datos desde que el personal clínico detecta una falla hasta que el sistema la registra.

```mermaid
sequenceDiagram
    participant C as Personal Clínico (index.html)
    participant M as Messenger (messenger.php)
    participant B as Bridge (bridge.php)
    participant L as Logs / Uploads
    
    C->>M: POST (Email, S/N, Equipo, Texto, Imagen)
    M->>M: Valida campos requeridos
    M->>L: Guarda imagen en /uploads/
    M->>B: procesarReporteInterno(data)
    B->>L: Registra entrada en messenger.log
    B-->>M: Retorna Tracking ID (MS-HEC-XXXX)
    M-->>C: Respuesta JSON (Success + ID)
    Note over C: Muestra confirmación al usuario
```

## 2. Flujo de Respuesta (Feedback Técnico)
Este flujo representa la vuelta de información hacia el solicitante una vez que mantenimiento procesa el caso.

```mermaid
sequenceDiagram
    participant T as Ingeniero Mantenimiento
    participant B as Bridge (bridge.php)
    participant R as Return Path (lib/Mailer)
    participant S as Solicitante (Email)
    
    T->>B: Ejecuta Cierre Técnico (Datos del Informe)
    B->>R: enviarDetalleTecnico(correo, reporte)
    R->>R: Genera cuerpo del correo (Plantilla HTML)
    R->>S: Envía Email con Detalle Técnico
    Note over S: Recibe reporte en su bandeja de entrada
```

## Detalles de Integración Standalone
- **Entrada**: Formulario modernizado con vista previa de imagen.
- **Almacenamiento**: Las imágenes se guardan de forma física para evitar pérdida de evidencia.
- **Rastreo**: Cada reporte genera un `tracking_id` único para seguimiento manual por ahora.
- **Logs**: El archivo `messenger.log` actúa como nuestra "base de datos" temporal.
