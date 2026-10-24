# Respaldo y restauración de ActivosIT

Este procedimiento permite generar un respaldo lógico completo de la base de
datos y comprobar su restauración sin modificar la base operativa.

## Alcance y responsabilidades

- El respaldo debe ejecutarse con un usuario de base de datos autorizado para
  leer todas las tablas y objetos requeridos.
- Los archivos de respaldo deben almacenarse fuera del repositorio y con acceso
  restringido.
- `parameters.env` contiene credenciales y no debe incluirse en el respaldo de
  archivos del proyecto ni publicarse.
- La restauración debe probarse primero en una base separada. La base
  `activosit` solo debe reemplazarse después de validar el resultado y contar
  con una copia previa.

## 1. Preparación

Verifique:

1. Que MariaDB/MySQL esté activo.
2. Que `mysqldump` y `mysql` pertenezcan a la misma instalación o a versiones
   compatibles.
3. Que exista espacio suficiente para el archivo de salida.
4. Que nadie esté ejecutando confirmaciones o anulaciones durante el respaldo.
5. Que el directorio de respaldo esté fuera del repositorio, por ejemplo
   `C:\ActivosIT\backups`.

En XAMPP, las rutas habituales son:

```powershell
$mysqlBin = 'C:\xampp\mysql\bin'
$backupDir = 'C:\ActivosIT\backups'
New-Item -ItemType Directory -Force $backupDir | Out-Null
```

## 2. Generar el respaldo

Sustituya los valores de conexión por los del entorno. No escriba la contraseña
en el comando ni en un archivo versionado; `mysqldump` la solicitará de forma
interactiva cuando se use `-p`.

### Windows / PowerShell

```powershell
$timestamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$backupFile = Join-Path $backupDir "activosit-$timestamp.sql"

& "$mysqlBin\mysqldump.exe" `
  --host=127.0.0.1 `
  --port=3306 `
  --user=root `
  -p `
  --single-transaction `
  --routines `
  --events `
  --triggers `
  --hex-blob `
  --default-character-set=utf8mb4 `
  activosit `
  --result-file=$backupFile

if ($LASTEXITCODE -ne 0) {
    throw "mysqldump no pudo generar el respaldo."
}

if (!(Test-Path $backupFile) -or (Get-Item $backupFile).Length -eq 0) {
    throw "El archivo de respaldo no existe o está vacío."
}

Get-FileHash -Algorithm SHA256 $backupFile
Write-Host "Respaldo creado: $backupFile"
```

### Linux / macOS

```bash
timestamp="$(date +%Y%m%d-%H%M%S)"
backup_file="/var/backups/activosit-${timestamp}.sql"

mysqldump \
  --host=127.0.0.1 \
  --port=3306 \
  --user=root \
  -p \
  --single-transaction \
  --routines \
  --events \
  --triggers \
  --hex-blob \
  --default-character-set=utf8mb4 \
  activosit \
  --result-file="$backup_file"

test -s "$backup_file"
sha256sum "$backup_file"
```

`--single-transaction` obtiene una instantánea consistente para tablas InnoDB
sin bloquear las operaciones normales. No reemplaza la coordinación operativa:
no deben ejecutarse cambios críticos durante la ventana de respaldo.

## 3. Validar el archivo

Antes de transferir o conservar el respaldo:

```powershell
Get-Item 'C:\ActivosIT\backups\activosit-*.sql' |
    Sort-Object LastWriteTime -Descending |
    Select-Object -First 1 Name, Length, LastWriteTime
```

Confirme que el archivo contenga, como mínimo, tablas de seguridad, artículos,
movimientos y bitácora:

```powershell
Select-String -Path 'C:\ActivosIT\backups\activosit-*.sql' `
    -Pattern 'CREATE TABLE.*articulos|CREATE TABLE.*movimientos_inventario|CREATE TABLE.*bitacora'
```

Conserve el hash SHA-256 junto con el respaldo. Si el archivo se copia a otro
servidor, valide nuevamente el hash antes de restaurarlo.

## 4. Restaurar en una base de prueba

La prueba debe usar un nombre de base diferente, por ejemplo
`activosit_restore_test_20261024`. No ejecute la restauración directamente sobre
`activosit` como primera comprobación.

### Windows / PowerShell

```powershell
$testDatabase = 'activosit_restore_test_20261024'
$backupFile = 'C:\ActivosIT\backups\activosit-AAAAMMDD-HHMMSS.sql'

& "$mysqlBin\mysql.exe" `
  --host=127.0.0.1 `
  --port=3306 `
  --user=root `
  -p `
  -e "CREATE DATABASE IF NOT EXISTS ``$testDatabase`` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

if ($LASTEXITCODE -ne 0) {
    throw "No se pudo crear la base de prueba."
}

& $env:ComSpec /c "`"$mysqlBin\mysql.exe`" --host=127.0.0.1 --port=3306 --user=root -p $testDatabase < `"$backupFile`""
if ($LASTEXITCODE -ne 0) {
    throw "La restauración en la base de prueba falló."
}
```

### Linux / macOS

```bash
test_database="activosit_restore_test_20261024"
backup_file="/var/backups/activosit-AAAAMMDD-HHMMSS.sql"

mysql -h 127.0.0.1 -P 3306 -u root -p \
  -e "CREATE DATABASE IF NOT EXISTS \`${test_database}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

mysql -h 127.0.0.1 -P 3306 -u root -p "$test_database" < "$backup_file"
```

## 5. Verificar la restauración

Ejecute estas comprobaciones en la base restaurada:

```sql
SELECT COUNT(*) AS tablas
FROM information_schema.tables
WHERE table_schema = 'activosit_restore_test_20261024'
  AND table_type = 'BASE TABLE';

SELECT COUNT(*) AS articulos
FROM activosit_restore_test_20261024.articulos;

SELECT COUNT(*) AS movimientos
FROM activosit_restore_test_20261024.movimientos_inventario;

SELECT COUNT(*) AS bitacora
FROM activosit_restore_test_20261024.bitacora;

SELECT COUNT(*) AS usuarios
FROM activosit_restore_test_20261024.usuarios;
```

Compare los conteos con la base de origen antes de la restauración. También
debe comprobar:

- Que existan las columnas agregadas por `13_modelo_operativo.sql`.
- Que las claves foráneas y los índices estén presentes.
- Que pueda iniciar sesión con una cuenta de prueba.
- Que pueda consultar artículos, movimientos, asignaciones y mantenimientos.
- Que un reporte pueda consultar datos sin modificar la base restaurada.

No pruebe confirmaciones, anulaciones o cambios de inventario con usuarios
reales durante esta validación. Si se requiere una prueba transaccional, use
datos explícitamente creados para la base de prueba y elimine esa base al
terminar.

## 6. Restauración operativa de `activosit`

Solo después de validar el respaldo:

1. Anuncie una ventana de mantenimiento.
2. Detenga la aplicación o impida nuevos accesos.
3. Genere un respaldo adicional de la base actual.
4. Confirme dos veces el nombre de la base destino.
5. Restaure el archivo validado.
6. Ejecute las consultas de verificación anteriores sobre `activosit`.
7. Pruebe inicio de sesión, artículos, Kardex, asignaciones, mantenimientos y
   reportes.
8. Habilite nuevamente la aplicación y registre la fecha, operador, archivo,
   hash y resultado.

No deben ejecutarse comandos destructivos como `DROP DATABASE activosit` como
parte de una restauración rutinaria. Si el procedimiento de recuperación exige
reemplazar la base, debe existir una copia de seguridad reciente y autorización
explícita del responsable del sistema.

## 7. Respaldo de archivos de la aplicación

La base de datos no contiene el código ni la configuración del servidor.
Respalde por separado:

- Código fuente de la versión desplegada.
- `composer.json` y `composer.lock`.
- `vendor/` o un procedimiento para reconstruirlo con Composer.
- Recursos de `public/`.
- `parameters.env`, únicamente en almacenamiento cifrado y restringido.
- Configuración de Apache/PHP y certificados, si corresponden.

No incluya `parameters.env` en Git, tickets, capturas ni respaldos sin control
de acceso. La restauración debe usar una copia segura de la configuración del
entorno y regenerar credenciales si se sospecha exposición.

## Registro de la prueba

Para cada prueba de restauración conserve:

| Campo | Valor |
|---|---|
| Fecha y hora | |
| Operador | |
| Servidor de origen | |
| Archivo de respaldo | |
| SHA-256 | |
| Base de prueba | |
| Tablas restauradas | |
| Conteos comparados | |
| Pruebas funcionales | |
| Resultado | |
| Observaciones | |

La prueba se considera aprobada únicamente si el archivo no está vacío, el
hash coincide, la restauración termina sin errores y las verificaciones
funcionales son satisfactorias.
