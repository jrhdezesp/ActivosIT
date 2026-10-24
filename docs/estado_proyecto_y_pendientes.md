# Estado del proyecto y pendientes

## 1. Resumen ejecutivo

El sistema ActivosIT cuenta con un núcleo funcional avanzado para la gestión de inventario, equipos, movimientos, asignaciones, mantenimientos, reportes, exportaciones, permisos y auditoría.

La estimación actual es:

- **Sistema funcional:** aproximadamente **87%**.
- **Cumplimiento integral del proyecto**, incluyendo pruebas formales, documentación y preparación para producción: aproximadamente **78-82%**.

Estos porcentajes son una estimación técnica basada en la revisión del código, scripts SQL, documentación existente y validaciones realizadas. No representan una métrica automática ni sustituyen una prueba de aceptación formal.

El proyecto no presenta un bloqueo principal en la vista de reportes operativos. La mayor parte del trabajo pendiente corresponde a evidencias de pruebas, documentación de entrega, seguridad de producción, normalización de catálogos y definición del manejo de repuestos.

## 2. Estado por área

| Área | Avance estimado | Estado |
|---|---:|---|
| Funcionalidad principal del inventario | 90% | Funcional, requiere pruebas formales |
| Artículos y equipos | 90% | Funcional, con oportunidades de reforzar integridad |
| Entradas, salidas y Kardex | 90% | Funcional, requiere matriz de casos límite |
| Asignaciones y devoluciones | 85% | Funcional, pendiente ampliar evidencias y catálogos |
| Mantenimientos | 85% | Funcional, pendiente definir repuestos trazables |
| Reportes y exportaciones | 90% | Implementado, pendiente aceptación formal |
| Seguridad y permisos | 80% | Implementado parcialmente para producción |
| Auditoría | 75% | Integrada en operaciones principales, falta matriz completa |
| Respaldos y restauración | 80% | Procedimiento documentado y probado, falta formalizar operación |
| Arquitectura MVC | 90% | Integridad revisada y paginación optimizada |
| Pruebas formales | 55% | Falta matriz de pruebas y evidencias completas |
| Documentación y entregables | 65% | README y respaldo disponibles, faltan documentos de entrega |

## 3. Funcionalidad ya cubierta

El proyecto ya cuenta con los siguientes elementos:

- Gestión de artículos.
- Gestión de equipos serializados.
- Entradas y salidas de inventario.
- Actualización de existencias.
- Kardex y movimientos.
- Asignaciones y devoluciones.
- Mantenimientos.
- Usuarios, roles y permisos.
- Auditoría de operaciones principales.
- Centro de reportes.
- Reportes operativos.
- Filtros y paginación.
- Exportación CSV, XLSX y PDF.
- Gráficas con Chart.js local.
- Protección CSRF.
- Contraseñas con `password_hash`.
- Consultas PDO parametrizadas.
- Control de acceso en servidor.
- Procedimiento documentado de respaldo y restauración.
- Corrección de la paginación de reportes directamente en SQL.
- Separación entre datos del controlador y presentación de la vista.

La vista de reportes operativos se encuentra en [`src/Views/templates/reportes/operativos.view.tpl`](../src/Views/templates/reportes/operativos.view.tpl).

## 4. Pendientes por área

### 4.1 Pruebas formales y evidencias

Este es el pendiente más importante para poder afirmar que el proyecto está terminado.

Se debe preparar evidencia de:

- Creación, edición, confirmación y anulación.
- Operaciones ejecutadas por cada rol:
  - Administrador.
  - Técnico de soporte.
  - Auditor.
- Restricciones de permisos.
- Cantidades insuficientes.
- Equipos serializados.
- Asignaciones y devoluciones.
- Mantenimientos completados y cancelados.
- Filtros, paginación y exportaciones.
- Restauración de respaldos.
- Concurrencia o doble operación sobre el mismo inventario.
- Validación de datos obligatorios y límites de longitud.
- Rechazo de fechas o estados inválidos.
- Reversión transaccional ante errores.

La matriz de pruebas debería incluir:

| Campo | Descripción |
|---|---|
| ID | Identificador único del caso |
| Requisito | Requisito que se verifica |
| Caso de prueba | Acción que se ejecutará |
| Datos utilizados | Usuario, registros y parámetros |
| Resultado esperado | Comportamiento requerido |
| Resultado obtenido | Comportamiento observado |
| Evidencia | Captura, consulta, archivo o registro |
| Estado | Aprobado, rechazado o bloqueado |
| Fecha y responsable | Trazabilidad de la ejecución |

### 4.2 Documentación de entrega

La documentación existente cubre instalación general, migraciones, reportes y respaldos. Para completar la entrega se recomienda agregar:

- Diagrama entidad-relación.
- Diagrama de arquitectura MVC.
- Manual de instalación.
- Manual de usuario.
- Manual de administrador.
- Matriz completa de roles y permisos.
- Catálogo de rutas y módulos.
- Descripción de los 14 reportes operativos.
- Guía de despliegue en producción.
- Procedimiento de actualización y ejecución de migraciones.
- Registro de cambios o changelog.
- Documento de solución de problemas frecuentes.
- Matriz de requisitos contra funcionalidades y pruebas.

Documentación disponible actualmente:

- [`README.md`](../README.md)
- [`docs/respaldo_restauracion.md`](./respaldo_restauracion.md)
- [`docs/scripts/11_reportes_permisos.sql`](./scripts/11_reportes_permisos.sql)

### 4.3 Catálogos organizacionales

Algunos datos pueden manejarse todavía como texto libre. Debe evaluarse la creación de catálogos relacionados para:

- Departamentos.
- Responsables o custodios.
- Ubicaciones.
- Proveedores.
- Tipos de mantenimiento.
- Estados operativos, cuando no estén suficientemente normalizados.

El objetivo es evitar variantes como:

```text
Soporte
Soporte TI
Departamento de Soporte
```

La solución recomendada es utilizar catálogos con identificadores y claves foráneas. Esto mejora:

- Integridad referencial.
- Consistencia de los reportes.
- Filtros.
- Mantenimiento de datos.
- Auditoría.

### 4.4 Repuestos de mantenimiento

Debe definirse si los repuestos utilizados en mantenimientos forman parte del inventario trazable.

Si la respuesta es afirmativa, todavía se requiere:

- Catálogo de repuestos.
- Entradas y salidas de repuestos.
- Cantidades utilizadas.
- Relación entre mantenimiento y repuestos.
- Descuento de existencias.
- Reporte de consumo.
- Auditoría de movimientos.
- Validación de existencias suficientes.

Si los repuestos solo serán información descriptiva, debe documentarse explícitamente que no forman parte del inventario controlado.

### 4.5 Auditoría

La auditoría ya está integrada en varias operaciones mediante [`Bitacora`](../src/Dao/Bitacora.php), pero falta cerrar una matriz definitiva de cobertura.

Debe confirmarse el registro de:

- Creaciones.
- Modificaciones.
- Confirmaciones.
- Anulaciones.
- Motivos de anulación.
- Cambios de permisos.
- Intentos de acceso no autorizado.
- Inicio y cierre de sesión.
- Usuario.
- Fecha y hora.
- Dirección IP, cuando corresponda.
- Valores anteriores y nuevos.
- Exportaciones, si el requisito las considera auditables.
- Consultas, si el requisito las considera auditables.

La auditoría nunca debe registrar contraseñas, tokens ni secretos.

### 4.6 Seguridad para producción

La aplicación ya cuenta con controles importantes:

- Contraseñas protegidas con `password_hash`.
- Protección CSRF.
- Consultas parametrizadas.
- Validación de permisos en servidor.
- Sesiones endurecidas.
- Auditoría de operaciones.

Antes del despliegue productivo se debe verificar:

- HTTPS obligatorio.
- Límite de intentos de inicio de sesión.
- Bloqueo temporal o mecanismo equivalente.
- Usuario de base de datos con privilegios mínimos.
- Errores detallados desactivados en producción.
- Protección de `parameters.env`.
- Rotación de credenciales.
- Permisos adecuados en directorios de almacenamiento.
- Política de retención de respaldos.
- Separación entre respaldos y código fuente.
- Revisión de archivos temporales y logs.
- Configuración de cookies `Secure`, `HttpOnly` y `SameSite`.

### 4.7 Integridad reforzada en base de datos

Varias reglas dependen actualmente de validaciones en PHP. Se recomienda reforzarlas en MariaDB cuando sea compatible:

- Restricciones de cantidades positivas.
- Restricciones de estados válidos.
- Índices para filtros frecuentes.
- Claves foráneas completas.
- Validación de fechas.
- Prevención de estados imposibles.
- Reglas para evitar devoluciones superiores a asignaciones.
- Reglas para impedir existencias negativas.
- Triggers o procedimientos solamente cuando simplifiquen una regla crítica y estén documentados.

La validación en base de datos debe complementar, no reemplazar, la validación de la aplicación.

### 4.8 Cierre operativo y Git

Antes de declarar el proyecto terminado se debe:

1. Revisar el diff final.
2. Ejecutar lint de PHP y JSON.
3. Ejecutar `git diff --check`.
4. Ejecutar la matriz de pruebas.
5. Confirmar migraciones y respaldo.
6. Crear el commit final.
7. Publicar el commit en `main`.
8. Confirmar que `main` y `origin/main` estén sincronizadas.
9. Registrar la versión entregada.

Los cambios recientes deben revisarse antes de crear el commit final. No se deben descartar cambios locales ni reescribir historial sin autorización.

## 5. Reportes operativos

El módulo de reportes operativos ya cuenta con:

- Selector de tipo de reporte.
- Filtros comunes.
- Paginación.
- Selector de filas por página.
- Exportación CSV.
- Exportación XLSX.
- Exportación PDF.
- Estado vacío.
- Resumen de resultados.
- Distribución visual consistente con las demás vistas.
- Consulta paginada directamente en SQL.
- Consulta completa separada para exportación.

La arquitectura fue ajustada para que:

- El DAO ejecute `COUNT(*)` para calcular el total.
- La página utilice `LIMIT` y `OFFSET`.
- El controlador entregue datos estructurados.
- La plantilla genere las celdas mediante iteración.
- El controlador no construya fragmentos HTML de la tabla.

La vista correspondiente es [`operativos.view.tpl`](../src/Views/templates/reportes/operativos.view.tpl).

Pendientes específicos de aceptación:

- Confirmar los 14 reportes con datos representativos.
- Verificar filtros combinados.
- Verificar página inicial y páginas posteriores.
- Verificar el cambio de filas por página.
- Confirmar que exportaciones no queden limitadas a la página visible.
- Confirmar que los encabezados y valores con acentos se exporten correctamente.
- Confirmar permisos por rol.
- Confirmar que reportes sin resultados presenten un estado claro.

## 6. Respaldos y restauración

Existe un procedimiento documentado en [`docs/respaldo_restauracion.md`](./respaldo_restauracion.md).

El procedimiento contempla:

- `mysqldump`.
- Windows/XAMPP.
- Linux y macOS.
- `--single-transaction`.
- Rutinas, eventos y triggers.
- Codificación `utf8mb4`.
- Hash SHA-256.
- Restauración aislada.
- Verificación de tablas y conteos.
- Restauración operativa.
- Separación entre respaldo, código y configuración.

Para completar este punto se recomienda definir:

- Frecuencia de respaldo.
- Responsable.
- Retención.
- Ubicación protegida.
- Cifrado.
- Prueba periódica de restauración.
- Registro de cada ejecución.
- Procedimiento de recuperación ante desastre.
- Tiempo objetivo de recuperación.
- Punto objetivo de recuperación.

## 7. Prioridad recomendada

### Prioridad alta

1. Crear la matriz formal de pruebas y evidencias.
2. Probar permisos con los tres roles.
3. Revisar integralmente la cobertura de auditoría.
4. Confirmar respaldo y restauración en una base aislada.
5. Ejecutar la aceptación de los 14 reportes.
6. Revisar seguridad de producción.

### Prioridad media

7. Completar manuales y diagramas.
8. Normalizar departamentos, custodios y ubicaciones.
9. Reforzar validaciones e integridad en MariaDB.
10. Definir el tratamiento de repuestos.
11. Documentar despliegue, migraciones y recuperación.

### Prioridad de cierre

12. Ejecutar lint y pruebas finales.
13. Revisar cambios locales.
14. Crear el commit final.
15. Publicar en `main`.
16. Confirmar sincronización con `origin/main`.
17. Registrar la versión entregada.
18. Realizar una prueba de aceptación con usuarios reales.

## 8. Criterio para considerar el proyecto terminado

El proyecto puede considerarse listo para entrega cuando se cumplan todos los puntos siguientes:

- [ ] Los flujos principales funcionan con datos reales.
- [ ] Los roles tienen exactamente los permisos esperados.
- [ ] Las operaciones inválidas son rechazadas.
- [ ] Las transacciones revierten correctamente ante errores.
- [ ] Las existencias no pueden quedar negativas.
- [ ] Las asignaciones y devoluciones conservan trazabilidad.
- [ ] Los mantenimientos registran su resultado o motivo de cancelación.
- [ ] Los 14 reportes fueron probados.
- [ ] Las exportaciones fueron verificadas.
- [ ] La auditoría cubre todos los eventos obligatorios.
- [ ] El respaldo fue restaurado exitosamente en una base aislada.
- [ ] La documentación de usuario y administración está completa.
- [ ] El diagrama de base de datos está disponible.
- [ ] El diagrama MVC está disponible.
- [ ] La configuración de producción fue revisada.
- [ ] La matriz de pruebas está aprobada.
- [ ] El commit final está publicado.
- [ ] `main` y `origin/main` están sincronizadas.

## 9. Conclusión

ActivosIT ya es un sistema funcional y técnicamente avanzado. La mayor parte de las funciones centrales está implementada y las observaciones recientes de arquitectura en reportes operativos fueron corregidas.

El proyecto todavía no debe considerarse al 100% porque faltan principalmente:

- Evidencias formales de pruebas.
- Documentación completa de entrega.
- Cierre de auditoría.
- Revisión de seguridad para producción.
- Definición de catálogos organizacionales.
- Definición del control de repuestos.
- Cierre y publicación de los cambios finales.

Con la ejecución de las actividades de prioridad alta y la finalización de los entregables, el proyecto podría acercarse razonablemente al 100% de cumplimiento verificable.
