# Plan de Flujo: Messenger Standalone (Arquitectura Satélite)

Este sistema funciona como un **anexo independiente** del CMMS principal, diseñado para el personal de servicio.

## Concepto: Arquitectura de "Base de Datos Separada"
Para mantener la integridad y simplicidad:
- **Independencia**: El Mensajero tiene su propia base de datos (o sistema de logs/SQLite) independiente del CMMS.
- **Vínculo Único**: El único punto de unión es el **ID del Equipo** o **Número de Serie**.
- **Nomenclatura**: Se prioriza el ID/Serie sobre el nombre, ya que un mismo equipo puede ser llamado de distintas formas por diferentes usuarios, pero su ID es invariable.

## Fase 1: Captura de Información (Ida)
1. **Acceso**: El usuario del servicio abre la interfaz del Mensajero.
2. **Entrada de Datos**:
   - **Correo**: Para contacto.
   - **ID / N° de Serie**: **Campo Maestro**. Único dato que identifica al activo sin errores de nombre.
   - **Servicio**: Ubicación clínica.
   - **Equipo**: Nombre descriptivo (solo como referencia).
   - **Problema & Imagen**: Detalles de la falla.
3. **Envío**: Se registra en la **Base de Datos Satélite**.

## Fase 2: Procesamiento Independiente
1. **Validación Satélite**: El sistema valida el reporte sin consultar la DB del CMMS (evita latencia y riesgos de seguridad).
2. **Almacenamiento Local**: Imágenes y logs se guardan en el ecosistema del Mensajero (`API Mail/`).
3. **Confirmación**: Se genera un Tracking ID independiente.

## Fase 3: Integración Manual/Diferida (Vuelta)
1. **Consulta Técnica**: El biomédico usa el ID del Equipo para buscar la ficha técnica oficial en el CMMS.
2. **Acción**: Si el reporte es válido, se procede a la creación de la OT en el CMMS usando el ID del equipo.
3. **Respuesta**: El Mensajero envía el feedback técnico al correo del solicitante.
