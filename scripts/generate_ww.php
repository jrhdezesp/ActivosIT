<?php

// Compatibilidad con PHP 7.4 para las funciones agregadas en PHP 8.0.
if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool
    {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool
    {
        return $needle === '' || substr($haystack, -strlen($needle)) === $needle;
    }
}

require __DIR__ . '/../vendor/autoload.php';

use Utilities\Context;
use Utilities\DotEnv;

if (php_sapi_name() !== 'cli') {
    echo "Este script se ejecuta por CLI\n";
    exit(1);
}

$tableName = $argv[1] ?? '';
$moduleName = $argv[2] ?? '';

if ($tableName === '') {
    echo "Uso: php scripts/generate_ww.php <tabla> [ModuloPlural]\n";
    exit(1);
}

if (!preg_match('/^[a-zA-Z0-9_]+$/', $tableName)) {
    echo "Nombre de tabla invalido\n";
    exit(1);
}

$dotenv = new DotEnv(__DIR__ . '/../parameters.env');
$config = $dotenv->load();
Context::setArrayToContext($config);

$dsn = sprintf(
    '%s:host=%s;dbname=%s;port=%s;charset=utf8',
    Context::getContextByKey('DB_PROVIDER'),
    Context::getContextByKey('DB_SERVER'),
    Context::getContextByKey('DB_DATABASE'),
    Context::getContextByKey('DB_PORT')
);

$pdo = new PDO(
    $dsn,
    Context::getContextByKey('DB_USER'),
    Context::getContextByKey('DB_PSWD'),
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

$descStmt = $pdo->query('DESC `'.$tableName.'`');
$columns = $descStmt->fetchAll();

if (count($columns) === 0) {
    echo "La tabla no tiene campos o no existe\n";
    exit(1);
}

function studly(string $text): string
{
    $parts = preg_split('/[_\-\s]+/', strtolower($text));
    $parts = array_filter($parts, fn ($v) => $v !== '');
    return implode('', array_map('ucfirst', $parts));
}

function camel(string $text): string
{
    $s = studly($text);
    return lcfirst($s);
}

function pluralize(string $word): string
{
    if (str_ends_with($word, 's')) {
        return $word;
    }
    if (str_ends_with($word, 'z')) {
        return $word.'es';
    }
    return $word.'s';
}

function singularize(string $word): string
{
    if (str_ends_with($word, 'es')) {
        return substr($word, 0, -2);
    }
    if (str_ends_with($word, 's')) {
        return substr($word, 0, -1);
    }
    return $word;
}

function detectPhpType(string $mysqlType): string
{
    $type = strtolower($mysqlType);
    if (str_contains($type, 'int')) {
        return 'int';
    }
    if (str_contains($type, 'decimal') || str_contains($type, 'float') || str_contains($type, 'double')) {
        return 'float';
    }
    return 'string';
}

function isTextType(string $mysqlType): bool
{
    $type = strtolower($mysqlType);
    return str_contains($type, 'char') || str_contains($type, 'text');
}

function toTemplateAccessor(string $column): string
{
    return '{{'.$column.'}}';
}

$modulePlural = $moduleName !== '' ? studly($moduleName) : studly(pluralize($tableName));
$moduleSingular = singularize($modulePlural);

$nsControllers = 'Controllers\\'.$modulePlural;
$nsDao = 'Dao\\'.$modulePlural;

$classPlural = $modulePlural;
$classSingular = $moduleSingular;

$viewFolder = strtolower($modulePlural);
$viewListName = strtolower($modulePlural);
$viewFormName = strtolower($moduleSingular);

$dataVarPlural = strtolower($modulePlural);
$dataVarSingular = strtolower($moduleSingular);

$primaryColumn = null;
foreach ($columns as $col) {
    if (($col['Key'] ?? '') === 'PRI') {
        $primaryColumn = $col;
        break;
    }
}
if ($primaryColumn === null) {
    $primaryColumn = $columns[0];
}

$pkName = $primaryColumn['Field'];
$pkPhpType = detectPhpType($primaryColumn['Type']);
$pkAuto = str_contains(strtolower($primaryColumn['Extra'] ?? ''), 'auto_increment');

$descriptionColumn = $columns[0]['Field'];
foreach ($columns as $col) {
    $fname = strtolower($col['Field']);
    if ($col['Field'] !== $pkName && isTextType($col['Type'])) {
        if (str_contains($fname, 'name') || str_contains($fname, 'nombre') || str_contains($fname, 'dsc') || str_contains($fname, 'desc') || str_contains($fname, 'title') || str_contains($fname, 'titulo')) {
            $descriptionColumn = $col['Field'];
            break;
        }
    }
}

$statusColumn = '';
foreach ($columns as $col) {
    $fname = strtolower($col['Field']);
    if (str_contains($fname, 'status') || str_contains($fname, 'est')) {
        $statusColumn = $col['Field'];
        break;
    }
}

$sortableColumns = [$pkName, $descriptionColumn];
foreach ($columns as $col) {
    if (!in_array($col['Field'], $sortableColumns)) {
        $sortableColumns[] = $col['Field'];
    }
    if (count($sortableColumns) >= 3) {
        break;
    }
}

$insertColumns = [];
$updateColumns = [];
foreach ($columns as $col) {
    $name = $col['Field'];
    if ($name === $pkName && $pkAuto) {
        continue;
    }
    $insertColumns[] = $name;
    if ($name !== $pkName) {
        $updateColumns[] = $name;
    }
}

$signatureInsert = [];
foreach ($insertColumns as $colName) {
    $colMeta = array_values(array_filter($columns, fn ($c) => $c['Field'] === $colName))[0];
    $signatureInsert[] = detectPhpType($colMeta['Type']).' $'.$colName;
}

$signatureUpdate = [];
$signatureUpdate[] = $pkPhpType.' $'.$pkName;
foreach ($updateColumns as $colName) {
    $colMeta = array_values(array_filter($columns, fn ($c) => $c['Field'] === $colName))[0];
    $signatureUpdate[] = detectPhpType($colMeta['Type']).' $'.$colName;
}

$insertColsSql = implode(', ', $insertColumns);
$insertValsSql = implode(', ', array_map(fn ($c) => ':'.$c, $insertColumns));
$updateSetSql = implode(', ', array_map(fn ($c) => $c.' = :'.$c, $updateColumns));

$orderList = array_map(fn ($c) => "'".$c."'", $sortableColumns);
$orderList[] = "''";
$orderValidation = implode(', ', $orderList);

$listSelectColumns = implode(', ', array_map(fn ($c) => 't.'.$c, $columns ? array_column($columns, 'Field') : []));
$statusDscSql = '';
if ($statusColumn !== '') {
    $statusDscSql = ", case when t.".$statusColumn." = 'ACT' then 'Activo' when t.".$statusColumn." = 'INA' then 'Inactivo' else 'Sin Asignar' end as ".$statusColumn."Dsc";
}

$daoContent = "<?php\n\nnamespace ".$nsDao.";\n\nuse Dao\\Table;\n\nclass ".$classPlural." extends Table\n{\n    public static function get".$classPlural."(\n        string \$partialName = '',\n        string \$status = '',\n        string \$orderBy = '',\n        bool \$orderDescending = false,\n        int \$page = 0,\n        int \$itemsPerPage = 10\n    ) {\n        \$sqlstr = \"SELECT ".$listSelectColumns.$statusDscSql."\n    FROM ".$tableName." t\";\n        \$sqlstrCount = 'SELECT COUNT(*) as count FROM ".$tableName." t';\n        \$conditions = [];\n        \$params = [];\n        if (\$partialName != '') {\n            \$conditions[] = 't.".$descriptionColumn." LIKE :partialName';\n            \$params['partialName'] = '%'.\$partialName.'%';\n        }\n";

if ($statusColumn !== '') {
    $daoContent .= "        if (!in_array(\$status, ['ACT', 'INA', ''])) {\n            throw new \\Exception('Error Processing Request Status has invalid value');\n        }\n        if (\$status != '') {\n            \$conditions[] = 't.".$statusColumn." = :status';\n            \$params['status'] = \$status;\n        }\n";
}

$daoContent .= "        if (count(\$conditions) > 0) {\n            \$sqlstr .= ' WHERE '.implode(' AND ', \$conditions);\n            \$sqlstrCount .= ' WHERE '.implode(' AND ', \$conditions);\n        }\n        if (!in_array(\$orderBy, [".$orderValidation."])) {\n            throw new \\Exception('Error Processing Request OrderBy has invalid value');\n        }\n        if (\$orderBy != '') {\n            \$sqlstr .= ' ORDER BY '.\$orderBy;\n            if (\$orderDescending) {\n                \$sqlstr .= ' DESC';\n            }\n        }\n        \$itemsPerPage = \$itemsPerPage > 0 ? \$itemsPerPage : 10;\n        \$page = \$page >= 0 ? \$page : 0;\n        \$numeroDeRegistros = self::obtenerUnRegistro(\$sqlstrCount, \$params)['count'];\n        \$pagesCount = \$numeroDeRegistros > 0 ? ceil(\$numeroDeRegistros / \$itemsPerPage) : 1;\n        if (\$page > \$pagesCount - 1) {\n            \$page = \$pagesCount - 1;\n        }\n        \$offset = \$page * \$itemsPerPage;\n        \$sqlstr .= ' LIMIT '.\$offset.', '.\$itemsPerPage;\n\n        \$registros = self::obtenerRegistros(\$sqlstr, \$params);\n\n        return ['".$dataVarPlural."' => \$registros, 'total' => \$numeroDeRegistros, 'page' => \$page, 'itemsPerPage' => \$itemsPerPage];\n    }\n\n    public static function get".$classSingular."ById(".$pkPhpType." \$".$pkName.")\n    {\n        \$sqlstr = 'SELECT ".$listSelectColumns." FROM ".$tableName." t WHERE t.".$pkName." = :".$pkName."';\n        \$params = ['".$pkName."' => \$".$pkName."];\n\n        return self::obtenerUnRegistro(\$sqlstr, \$params);\n    }\n\n    public static function insert".$classSingular."(\n        ".implode(",\n        ", $signatureInsert)."\n    ) {\n        \$sqlstr = 'INSERT INTO ".$tableName." (".$insertColsSql.") VALUES (".$insertValsSql.")';\n        \$params = [\n";

foreach ($insertColumns as $colName) {
    $daoContent .= "            '".$colName."' => \$".$colName.",\n";
}

$daoContent .= "        ];\n\n        return self::executeNonQuery(\$sqlstr, \$params);\n    }\n\n    public static function update".$classSingular."(\n        ".implode(",\n        ", $signatureUpdate)."\n    ) {\n        \$sqlstr = 'UPDATE ".$tableName." SET ".$updateSetSql." WHERE ".$pkName." = :".$pkName."';\n        \$params = [\n";

$allUpdateParams = array_merge([$pkName], $updateColumns);
foreach ($allUpdateParams as $colName) {
    $daoContent .= "            '".$colName."' => \$".$colName.",\n";
}

$daoContent .= "        ];\n\n        return self::executeNonQuery(\$sqlstr, \$params);\n    }\n\n    public static function delete".$classSingular."(".$pkPhpType." \$".$pkName.")\n    {\n        \$sqlstr = 'DELETE FROM ".$tableName." WHERE ".$pkName." = :".$pkName."';\n        \$params = ['".$pkName."' => \$".$pkName."];\n\n        return self::executeNonQuery(\$sqlstr, \$params);\n    }\n}\n";

$listControllerContent = "<?php\n\nnamespace ".$nsControllers.";\n\nuse Controllers\\PublicController;\nuse ".$nsDao."\\".$classPlural." as Dao".$classPlural.";\nuse Utilities\\Context;\nuse Utilities\\Paging;\nuse Views\\Renderer;\n\nclass ".$classPlural." extends PublicController\n{\n    private \$partialName = '';\n    private \$status = '';\n    private \$orderBy = '';\n    private \$orderDescending = false;\n    private \$pageNumber = 1;\n    private \$itemsPerPage = 10;\n    private \$viewData = [];\n    private \$".$dataVarPlural." = [];\n    private \$".$dataVarPlural."Count = 0;\n    private \$pages = 0;\n\n    public function run(): void\n    {\n        \$this->getParamsFromContext();\n        \$this->getParams();\n        \$tmpData = Dao".$classPlural."::get".$classPlural."(\n            \$this->partialName,\n            \$this->status,\n            \$this->orderBy,\n            \$this->orderDescending,\n            \$this->pageNumber - 1,\n            \$this->itemsPerPage\n        );\n        \$this->".$dataVarPlural." = \$tmpData['".$dataVarPlural."'];\n        \$this->".$dataVarPlural."Count = \$tmpData['total'];\n        \$this->pages = \$this->".$dataVarPlural."Count > 0 ? ceil(\$this->".$dataVarPlural."Count / \$this->itemsPerPage) : 1;\n        if (\$this->pageNumber > \$this->pages) {\n            \$this->pageNumber = \$this->pages;\n        }\n        \$this->setParamsToContext();\n        \$this->setParamsToDataView();\n        Renderer::render('".$viewFolder."/".$viewListName."', \$this->viewData);\n    }\n\n    private function getParams(): void\n    {\n        \$this->partialName = isset(\$_GET['partialName']) ? \$_GET['partialName'] : \$this->partialName;\n";

if ($statusColumn !== '') {
    $listControllerContent .= "        \$this->status = isset(\$_GET['status']) && in_array(\$_GET['status'], ['ACT', 'INA', 'EMP']) ? \$_GET['status'] : \$this->status;\n        if (\$this->status === 'EMP') {\n            \$this->status = '';\n        }\n";
}

$orderByValues = array_map(fn ($c) => "'".$c."'", $sortableColumns);
$orderByValues[] = "'clear'";
$listControllerContent .= "        \$this->orderBy = isset(\$_GET['orderBy']) && in_array(\$_GET['orderBy'], [".implode(', ', $orderByValues)."]) ? \$_GET['orderBy'] : \$this->orderBy;\n        if (\$this->orderBy === 'clear') {\n            \$this->orderBy = '';\n        }\n        \$this->orderDescending = isset(\$_GET['orderDescending']) ? boolval(\$_GET['orderDescending']) : \$this->orderDescending;\n        \$this->pageNumber = isset(\$_GET['pageNum']) ? intval(\$_GET['pageNum']) : \$this->pageNumber;\n        \$this->itemsPerPage = isset(\$_GET['itemsPerPage']) ? intval(\$_GET['itemsPerPage']) : \$this->itemsPerPage;\n    }\n\n    private function getParamsFromContext(): void\n    {\n        \$this->partialName = Context::getContextByKey('".$viewFolder."_partialName');\n        \$this->status = Context::getContextByKey('".$viewFolder."_status');\n        \$this->orderBy = Context::getContextByKey('".$viewFolder."_orderBy');\n        \$this->orderDescending = boolval(Context::getContextByKey('".$viewFolder."_orderDescending'));\n        \$this->pageNumber = intval(Context::getContextByKey('".$viewFolder."_page'));\n        \$this->itemsPerPage = intval(Context::getContextByKey('".$viewFolder."_itemsPerPage'));\n        if (\$this->pageNumber < 1) {\n            \$this->pageNumber = 1;\n        }\n        if (\$this->itemsPerPage < 1) {\n            \$this->itemsPerPage = 10;\n        }\n    }\n\n    private function setParamsToContext(): void\n    {\n        Context::setContext('".$viewFolder."_partialName', \$this->partialName, true);\n        Context::setContext('".$viewFolder."_status', \$this->status, true);\n        Context::setContext('".$viewFolder."_orderBy', \$this->orderBy, true);\n        Context::setContext('".$viewFolder."_orderDescending', \$this->orderDescending, true);\n        Context::setContext('".$viewFolder."_page', \$this->pageNumber, true);\n        Context::setContext('".$viewFolder."_itemsPerPage', \$this->itemsPerPage, true);\n    }\n\n    private function setParamsToDataView(): void\n    {\n        \$this->viewData['partialName'] = \$this->partialName;\n        \$this->viewData['status'] = \$this->status;\n        \$this->viewData['orderBy'] = \$this->orderBy;\n        \$this->viewData['orderDescending'] = \$this->orderDescending;\n        \$this->viewData['pageNum'] = \$this->pageNumber;\n        \$this->viewData['itemsPerPage'] = \$this->itemsPerPage;\n        \$this->viewData['".$dataVarPlural."Count'] = \$this->".$dataVarPlural."Count;\n        \$this->viewData['pages'] = \$this->pages;\n        \$this->viewData['".$dataVarPlural."'] = \$this->".$dataVarPlural.";\n        if (\$this->orderBy !== '') {\n            \$orderByKey = 'Order'.ucfirst(\$this->orderBy);\n            \$orderByKeyNoOrder = 'OrderBy'.ucfirst(\$this->orderBy);\n            \$this->viewData[\$orderByKeyNoOrder] = true;\n            if (\$this->orderDescending) {\n                \$orderByKey .= 'Desc';\n            }\n            \$this->viewData[\$orderByKey] = true;\n        }\n";

if ($statusColumn !== '') {
    $listControllerContent .= "        \$statusKey = 'status_'.(\$this->status === '' ? 'EMP' : \$this->status);\n        \$this->viewData[\$statusKey] = 'selected';\n";
}

$listControllerContent .= "        \$pagination = Paging::getPagination(\n            \$this->".$dataVarPlural."Count,\n            \$this->itemsPerPage,\n            \$this->pageNumber,\n            'index.php?page=".$classPlural."_".$classPlural."',\n            '".$classPlural."_".$classPlural."'\n        );\n        \$this->viewData['pagination'] = \$pagination;\n    }\n}\n";

$formArrayDefaults = [];
foreach ($columns as $col) {
    $default = "''";
    $phpType = detectPhpType($col['Type']);
    if ($phpType === 'int' || $phpType === 'float') {
        $default = '0';
    }
    if ($col['Field'] === $statusColumn) {
        $default = "'ACT'";
    }
    $formArrayDefaults[] = "        '".$col['Field']."' => ".$default.",";
}

$formPostReads = [];
foreach ($columns as $col) {
    $type = detectPhpType($col['Type']);
    if ($type === 'int') {
        $formPostReads[] = "        \$this->".$dataVarSingular."['".$col['Field']."'] = intval(\$_POST['".$col['Field']."'] ?? '');";
    } elseif ($type === 'float') {
        $formPostReads[] = "        \$this->".$dataVarSingular."['".$col['Field']."'] = floatval(\$_POST['".$col['Field']."'] ?? '');";
    } else {
        $formPostReads[] = "        \$this->".$dataVarSingular."['".$col['Field']."'] = strval(\$_POST['".$col['Field']."'] ?? '');";
    }
}

$validationLines = [];
foreach ($columns as $col) {
    $field = $col['Field'];
    if ($field === $pkName && $pkAuto) {
        continue;
    }
    $validationLines[] = "        if (Validators::IsEmpty(\$this->".$dataVarSingular."['".$field."'])) {";
    $validationLines[] = "            \$errors['".$field."_error'] = 'El campo ".$field." es requerido';";
    $validationLines[] = "        }";
    $validationLines[] = "";
}

$insertArgsCall = [];
foreach ($insertColumns as $colName) {
    $insertArgsCall[] = "            \$this->".$dataVarSingular."['".$colName."']";
}

$updateArgsCall = ["            \$this->".$dataVarSingular."['".$pkName."']"];
foreach ($updateColumns as $colName) {
    $updateArgsCall[] = "            \$this->".$dataVarSingular."['".$colName."']";
}

$formControllerContent = "<?php\n\nnamespace ".$nsControllers.";\n\nuse Controllers\\PublicController;\nuse ".$nsDao."\\".$classPlural." as ".$classPlural."Dao;\nuse Utilities\\Site;\nuse Utilities\\Validators;\nuse Views\\Renderer;\n\nclass ".$classSingular." extends PublicController\n{\n    private \$viewData = [];\n    private \$mode = 'DSP';\n    private \$modeDescriptions = [\n        'DSP' => 'Detalle de %s %s',\n        'INS' => 'Nuevo ".$classSingular."',\n        'UPD' => 'Editar %s %s',\n        'DEL' => 'Eliminar %s %s',\n    ];\n    private \$readonly = '';\n    private \$showCommitBtn = true;\n    private \$".$dataVarSingular." = [\n".implode("\n", $formArrayDefaults)."\n    ];\n    private \$".$dataVarSingular."_xss_token = '';\n\n    public function run(): void\n    {\n        try {\n            \$this->getData();\n            if (\$this->isPostBack()) {\n                if (\$this->validateData()) {\n                    \$this->handlePostAction();\n                }\n            }\n            \$this->setViewData();\n            Renderer::render('".$viewFolder."/".$viewFormName."', \$this->viewData);\n        } catch (\\Exception \$ex) {\n            Site::redirectToWithMsg(\n                'index.php?page=".$classPlural."_".$classPlural."',\n                \$ex->getMessage()\n            );\n        }\n    }\n\n    private function getData()\n    {\n        \$this->mode = \$_GET['mode'] ?? 'NOF';\n        if (isset(\$this->modeDescriptions[\$this->mode])) {\n            \$this->readonly = \$this->mode === 'DEL' ? 'readonly' : '';\n            \$this->showCommitBtn = \$this->mode !== 'DSP';\n            if (\$this->mode !== 'INS') {\n                \$this->".$dataVarSingular." = ".$classPlural."Dao::get".$classSingular."ById(".$pkPhpType."val(\$_GET['".$pkName."'] ?? ".($pkPhpType === 'string' ? "''" : '0')."));\n                if (!\$this->".$dataVarSingular.") {\n                    throw new \\Exception('No se encontró el registro', 1);\n                }\n            }\n        } else {\n            throw new \\Exception('Formulario cargado en modalidad invalida', 1);\n        }\n    }\n\n    private function validateData()\n    {\n        \$errors = [];\n        \$this->".$dataVarSingular."_xss_token = \$_POST['".$dataVarSingular."_xss_token'] ?? '';\n".implode("\n", $formPostReads)."\n\n".implode("\n", $validationLines)."\n        if (count(\$errors) > 0) {\n            foreach (\$errors as \$key => \$value) {\n                \$this->".$dataVarSingular."[\$key] = \$value;\n            }\n\n            return false;\n        }\n\n        return true;\n    }\n\n    private function handlePostAction()\n    {\n        switch (\$this->mode) {\n            case 'INS':\n                \$this->handleInsert();\n                break;\n            case 'UPD':\n                \$this->handleUpdate();\n                break;\n            case 'DEL':\n                \$this->handleDelete();\n                break;\n            default:\n                throw new \\Exception('Modo invalido', 1);\n                break;\n        }\n    }\n\n    private function handleInsert()\n    {\n        \$result = ".$classPlural."Dao::insert".$classSingular."(\n".implode(",\n", $insertArgsCall)."\n        );\n        if (\$result > 0) {\n            Site::redirectToWithMsg(\n                'index.php?page=".$classPlural."_".$classPlural."',\n                'Registro creado exitosamente'\n            );\n        }\n    }\n\n    private function handleUpdate()\n    {\n        \$result = ".$classPlural."Dao::update".$classSingular."(\n".implode(",\n", $updateArgsCall)."\n        );\n        if (\$result > 0) {\n            Site::redirectToWithMsg(\n                'index.php?page=".$classPlural."_".$classPlural."',\n                'Registro actualizado exitosamente'\n            );\n        }\n    }\n\n    private function handleDelete()\n    {\n        \$result = ".$classPlural."Dao::delete".$classSingular."(\$this->".$dataVarSingular."['".$pkName."']);\n        if (\$result > 0) {\n            Site::redirectToWithMsg(\n                'index.php?page=".$classPlural."_".$classPlural."',\n                'Registro eliminado exitosamente'\n            );\n        }\n    }\n\n    private function setViewData(): void\n    {\n        \$this->viewData['mode'] = \$this->mode;\n        \$this->viewData['".$dataVarSingular."_xss_token'] = \$this->".$dataVarSingular."_xss_token;\n        \$this->viewData['FormTitle'] = sprintf(\n            \$this->modeDescriptions[\$this->mode],\n            \$this->".$dataVarSingular."['".$pkName."'],\n            \$this->".$dataVarSingular."['".$descriptionColumn."']\n        );\n        \$this->viewData['showCommitBtn'] = \$this->showCommitBtn;\n        \$this->viewData['readonly'] = \$this->readonly;\n\n        \$this->viewData['".$dataVarSingular."'] = \$this->".$dataVarSingular.";\n    }\n}\n";

$statusFilterTpl = '';
if ($statusColumn !== '') {
    $statusFilterTpl = "          <label class=\"col-3\" for=\"status\">Estado</label>\n          <select class=\"col-9\" name=\"status\" id=\"status\">\n              <option value=\"EMP\" {{status_EMP}}>Todos</option>\n              <option value=\"ACT\" {{status_ACT}}>Activo</option>\n              <option value=\"INA\" {{status_INA}}>Inactivo</option>\n          </select>\n";
}

$thOrder = '';
$orderTplMap = [];
foreach ($sortableColumns as $col) {
    $cap = ucfirst($col);
    $label = $col;
    $thOrder .= "        <th>\n          {{ifnot OrderBy".$cap."}}\n          <a href=\"index.php?page=".$classPlural."_".$classPlural."&orderBy=".$col."&orderDescending=0\">".$label." <i class=\"fas fa-sort\"></i></a>\n          {{endifnot OrderBy".$cap."}}\n          {{if Order".$cap."Desc}}\n          <a href=\"index.php?page=".$classPlural."_".$classPlural."&orderBy=clear&orderDescending=0\">".$label." <i class=\"fas fa-sort-down\"></i></a>\n          {{endif Order".$cap."Desc}}\n          {{if Order".$cap."}}\n          <a href=\"index.php?page=".$classPlural."_".$classPlural."&orderBy=".$col."&orderDescending=1\">".$label." <i class=\"fas fa-sort-up\"></i></a>\n          {{endif Order".$cap."}}\n        </th>\n";
    $orderTplMap[] = $col;
}

$listDataTds = '';
foreach ($columns as $col) {
    if ($col['Field'] === $descriptionColumn) {
        $listDataTds .= "        <td><a class=\"link\" href=\"index.php?page=".$classPlural."_".$classSingular."&mode=DSP&".$pkName."={{".$pkName."}}\">{{".$descriptionColumn."}}</a></td>\n";
    } else {
        $listDataTds .= "        <td>{{".$col['Field']."}}</td>\n";
    }
}
if ($statusColumn !== '') {
    $listDataTds .= "        <td>{{".$statusColumn."Dsc}}</td>\n";
}

$listTemplate = "<h1>Trabajar con ".$classPlural."</h1>\n<section class=\"grid\">\n  <div class=\"row\">\n    <form class=\"col-12 col-m-8\" action=\"index.php\" method=\"get\">\n      <div class=\"flex align-center\">\n        <div class=\"col-8 row\">\n          <input type=\"hidden\" name=\"page\" value=\"".$classPlural."_".$classPlural."\">\n          <label class=\"col-3\" for=\"partialName\">Buscar</label>\n          <input class=\"col-9\" type=\"text\" name=\"partialName\" id=\"partialName\" value=\"{{partialName}}\" />\n".$statusFilterTpl."        </div>\n        <div class=\"col-4 align-end\">\n          <button type=\"submit\">Filtrar</button>\n        </div>\n      </div>\n    </form>\n  </div>\n</section>\n<section class=\"WWList\">\n  <table>\n    <thead>\n      <tr>\n".$thOrder;
if ($statusColumn !== '') {
    $listTemplate .= "        <th>Estado</th>\n";
}
$listTemplate .= "        <th><a href=\"index.php?page=".$classPlural."_".$classSingular."&mode=INS\">Nuevo</a></th>\n      </tr>\n    </thead>\n    <tbody>\n      {{foreach ".$dataVarPlural."}}\n      <tr>\n".$listDataTds."        <td>\n          <a href=\"index.php?page=".$classPlural."_".$classSingular."&mode=UPD&".$pkName."={{".$pkName."}}\">Editar</a>\n          &nbsp;\n          <a href=\"index.php?page=".$classPlural."_".$classSingular."&mode=DEL&".$pkName."={{".$pkName."}}\">Eliminar</a>\n        </td>\n      </tr>\n      {{endfor ".$dataVarPlural."}}\n    </tbody>\n  </table>\n  {{pagination}}\n</section>\n";

$formInputs = [];
$formInputs[] = "    <div class=\"row my-2 align-center\">\n      <label class=\"col-12 col-m-3\" for=\"".$pkName."D\">Código</label>\n      <input class=\"col-12 col-m-9\" readonly disabled type=\"text\" name=\"".$pkName."D\" id=\"".$pkName."D\" value=\"{{".$pkName."}}\" />\n      <input type=\"hidden\" name=\"mode\" value=\"{{~mode}}\" />\n      <input type=\"hidden\" name=\"".$pkName."\" value=\"{{".$pkName."}}\" />\n      <input type=\"hidden\" name=\"token\" value=\"{{~".$dataVarSingular."_xss_token}}\" />\n    </div>\n";

foreach ($columns as $col) {
    $field = $col['Field'];
    if ($field === $pkName) {
        continue;
    }
    $type = strtolower($col['Type']);
    if (str_contains($type, 'text')) {
        $formInputs[] = "    <div class=\"row my-2 align-center\">\n      <label class=\"col-12 col-m-3\" for=\"".$field."\">".$field."</label>\n      <textarea class=\"col-12 col-m-9\" {{~readonly}} name=\"".$field."\" id=\"".$field."\">{{".$field."}}</textarea>\n      {{if ".$field."_error}}\n      <div class=\"col-12 col-m-9 offset-m-3 error\">\n        {{".$field."_error}}\n      </div>\n      {{endif ".$field."_error}}\n    </div>\n";
    } elseif ($field === $statusColumn) {
        $formInputs[] = "    <div class=\"row my-2 align-center\">\n      <label class=\"col-12 col-m-3\" for=\"".$field."\">".$field."</label>\n      <select name=\"".$field."\" id=\"".$field."\" class=\"col-12 col-m-9\" {{if ~readonly}} readonly disabled {{endif ~readonly}}>\n        <option value=\"ACT\" {{".$field."_act}}>Activo</option>\n        <option value=\"INA\" {{".$field."_ina}}>Inactivo</option>\n      </select>\n      {{if ".$field."_error}}\n      <div class=\"col-12 col-m-9 offset-m-3 error\">\n        {{".$field."_error}}\n      </div>\n      {{endif ".$field."_error}}\n    </div>\n";
    } else {
        $inputType = 'text';
        if (str_contains($type, 'int') || str_contains($type, 'decimal') || str_contains($type, 'float') || str_contains($type, 'double')) {
            $inputType = 'number';
        }
        $formInputs[] = "    <div class=\"row my-2 align-center\">\n      <label class=\"col-12 col-m-3\" for=\"".$field."\">".$field."</label>\n      <input class=\"col-12 col-m-9\" {{~readonly}} type=\"".$inputType."\" name=\"".$field."\" id=\"".$field."\" value=\"{{".$field."}}\" />\n      {{if ".$field."_error}}\n      <div class=\"col-12 col-m-9 offset-m-3 error\">\n        {{".$field."_error}}\n      </div>\n      {{endif ".$field."_error}}\n    </div>\n";
    }
}

$formTemplate = "<section class=\"container-m row px-4 py-4\">\n  <h1>{{FormTitle}}</h1>\n</section>\n<section class=\"container-m row px-4 py-4\">\n  {{with ".$dataVarSingular."}}\n  <form action=\"index.php?page=".$classPlural."_".$classSingular."&mode={{~mode}}&".$pkName."={{".$pkName."}}\" method=\"POST\" class=\"col-12 col-m-8 offset-m-2\">\n".implode("", $formInputs)."    {{endwith ".$dataVarSingular."}}\n    <div class=\"row my-4 align-center flex-end\">\n      {{if showCommitBtn}}\n      <button class=\"primary col-12 col-m-2\" type=\"submit\" name=\"btnConfirmar\">Confirmar</button>\n      &nbsp;\n      {{endif showCommitBtn}}\n      <button class=\"col-12 col-m-2\" type=\"button\" id=\"btnCancelar\">\n        {{if showCommitBtn}}\n        Cancelar\n        {{endif showCommitBtn}}\n        {{ifnot showCommitBtn}}\n        Regresar\n        {{endifnot showCommitBtn}}\n      </button>\n    </div>\n  </form>\n</section>\n\n<script>\n  document.addEventListener(\"DOMContentLoaded\", ()=>{\n    const btnCancelar = document.getElementById(\"btnCancelar\");\n    btnCancelar.addEventListener(\"click\", (e)=>{\n      e.preventDefault();\n      e.stopPropagation();\n      window.location.assign(\"index.php?page=".$classPlural."_".$classPlural."\");\n    });\n  });\n</script>\n";

$targets = [
    __DIR__ . '/../src/Dao/'.$modulePlural.'/'.$classPlural.'.php' => $daoContent,
    __DIR__ . '/../src/Controllers/'.$modulePlural.'/'.$classPlural.'.php' => $listControllerContent,
    __DIR__ . '/../src/Controllers/'.$modulePlural.'/'.$classSingular.'.php' => $formControllerContent,
    __DIR__ . '/../src/Views/templates/'.$viewFolder.'/'.$viewListName.'.view.tpl' => $listTemplate,
    __DIR__ . '/../src/Views/templates/'.$viewFolder.'/'.$viewFormName.'.view.tpl' => $formTemplate,
];

foreach ($targets as $path => $content) {
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents($path, $content);
    echo 'Generado: '.str_replace(__DIR__ . '/../', '', $path)."\n";
}

echo "Completado\n";
