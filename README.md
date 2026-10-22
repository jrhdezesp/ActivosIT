# Sistema de Gestión e Inventario de Activos IT - ActivosIT

![Logo](public/imgs/brand/logo-icon.png)

![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)
![XAMPP](https://img.shields.io/badge/XAMPP-8.x-FB7A24?logo=xampp&logoColor=white)
![Composer](https://img.shields.io/badge/Composer-2.x-885630?logo=composer&logoColor=white)
![HTML5](https://img.shields.io/badge/HTML5-5-E34F26?logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-3-1572B6?logo=css3&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-ES6%2B-F7DF1E?logo=javascript&logoColor=black)
![Git](https://img.shields.io/badge/Git_&_GitHub-F05032?logo=git&logoColor=white)

## Datos del Proyecto
- **Autor:** Jose Ramon Hernandez Espinal
- **Supervisor:** Ing. Luis Suazo
- **Institución / Empresa:** Pinturas Americanas
- **Estado Actual:** Etapa de Análisis y Alcance (Fase Teórica y Prototipado)

## Tecnologías y Arquitectura
- **Backend:** PHP 8.1+ + Composer (Arquitectura MVC)
- **Base de Datos:** MySQL 8.0 (21 tablas, transacciones ACID, borrado lógico)
- **Frontend:** HTML5, CSS3, JavaScript puro, Bootstrap 5
- **Entorno de Desarrollo:** XAMPP (Apache) en red local
- **Control de Versiones:** Git + GitHub / GitKraken

## Problema Planteado
La administración de la infraestructura tecnológica requiere un control estricto. Actualmente, la falta de un registro centralizado para gestionar el hardware, los componentes, las asignaciones a empleados y los mantenimientos genera inconsistencias en las existencias, falta de trazabilidad y dificultades para la auditoría técnica y financiera del departamento de IT.

## Solución Tecnológica
Desarrollo de un sistema web integral bajo el patrón de diseño **MVC** que automatiza y digitaliza los flujos operativos del departamento de IT.
**Destaca por:** Control estricto de Kardex, gestión dual (artículos por cantidad y equipos físicos por número de serie), trazabilidad completa mediante bitácora de auditoría y un despliegue local ágil y seguro.

---

## Alcance Funcional (En diseño)

### Seguridad y Auditoría
- Autenticación segura y gestión de perfiles.
- Control de acceso basado en Roles y Permisos (RBAC): Administrador, Auditor, Usuario General.
- Bitácora de operaciones (registro inmutable de acciones críticas, IP, fechas y usuarios).

### Módulo de Artículos y Equipos
- **Catálogo General:** Gestión de artículos consumibles controlados por stock y cantidad mínima.
- **Equipos Individuales:** Trazabilidad estricta de activos tecnológicos mediante número de serie, código patrimonial, estado físico y garantía.

### Movimientos de Inventario
- **Entradas:** Recepción de equipos, compras y ajustes debidamente documentados.
- **Salidas:** Extracción de material con validación de existencias en tiempo real para evitar saldos negativos.
- **Kardex:** Historial cronológico de movimientos por artículo.

### Asignaciones y Mantenimientos
- **Cadena de Custodia:** Asignación y devolución documentada de equipos a custodios/departamentos.
- **Mantenimiento:** Registro de intervenciones preventivas/correctivas, diagnósticos, repuestos utilizados y costos.

### Catálogos Auxiliares y Reportes
- Administración dinámica de clasificaciones, ubicaciones, estados y unidades de medida.
- Generación de reportes exportables para auditorías de stock, historial de custodios y mantenimientos.

---

## 📁 Estructura del Proyecto (MVC Propuesto)
```text
InventarioIT/
├── public/                 # DocumentRoot (css, js, img, index.php)
├── src/
│   ├── Controllers/        # Controladores MVC (Rutas y validaciones)
│   ├── Models/             # Acceso a datos (PDO, sentencias preparadas)
│   └── Views/              # Plantillas PHP (Interfaces y formularios)
├── docs/                   # Diagramas UML, base de datos (.sql), diccionarios
├── config/                 # Configuraciones de entorno y base de datos
├── composer.json           # Dependencias backend
└── README.md               # Documentación del proyecto
```
