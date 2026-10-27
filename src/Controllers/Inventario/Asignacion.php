<?php

namespace Controllers\Inventario;

use Controllers\PrivateController;
use Dao\Inventario\Asignaciones as DaoAsignaciones;
use Utilities\Bitacora;
use Utilities\CSRF;
use Utilities\DateHelper;
use Utilities\Security;
use Utilities\Site;
use Views\Renderer;

/**
 * Atiende la entrega y devolucion de equipos asignados a personas o areas.
 */
class Asignacion extends PrivateController
{
    private $mode = 'INS';
    private $id = 0;
    // Datos de la asignacion y del equipo durante una entrega o devolucion.
    private $record = [];
    private $item = [];
    private $returnItem = [];
    // Mensajes separados para los datos de asignacion y de devolucion.
    private $errors = [];
    private $returnErrors = [];
    private $equipos = [];

    /**
     * Carga la asignacion, procesa una entrega o devolucion y muestra el formulario.
     */
    public function run(): void
    {
        try {
            $this->getParams();
            $this->loadOptions();

            if ($this->isPostBack()) {
                CSRF::requireValid('asignacion', $_POST['token'] ?? '');

                if ($this->mode === 'DEV') {
                    if ($this->validateReturn()) {
                        $this->handleReturn();
                    }
                } elseif ($this->canEdit() && $this->validateData()) {
                    $this->handleSave();
                }
            }

            $this->renderForm();
        } catch (\Exception $ex) {
            Site::redirectToWithMsg(
                'index.php?page=Inventario_Asignaciones&msg=error',
                $ex->getMessage()
            );
        }
    }

    private function getParams(): void
    {
        $this->mode = strtoupper(strval($_GET['mode'] ?? 'INS'));
        if (!in_array($this->mode, ['INS', 'UPD', 'DEV'], true)) {
            throw new \InvalidArgumentException('La modalidad del formulario no es válida.');
        }

        $this->id = intval($_GET['id'] ?? 0);
        $this->initializeItems();

        if ($this->mode !== 'INS') {
            if ($this->id <= 0) {
                throw new \InvalidArgumentException('La asignación solicitada no es válida.');
            }

            $row = DaoAsignaciones::getAsignacionById($this->id);
            if (!$row) {
                throw new \RuntimeException('No se encontró la asignación solicitada.');
            }

            $this->record = $row;
            $this->item = $this->pickItem($row);
            $this->returnItem = $this->pickReturnItem($row);

            if (
                $this->mode === 'DEV'
                && strval($this->record['estado_asignacion'] ?? '') !== 'ACTIVA'
            ) {
                throw new \RuntimeException('Solo se pueden devolver asignaciones activas.');
            }
        }

        if ($this->isPostBack()) {
            if ($this->mode === 'DEV') {
                $this->pickReturnPost();
            } elseif (
                $this->mode === 'INS'
                || strval($this->record['estado_asignacion'] ?? '') === 'ACTIVA'
            ) {
                $this->pickAssignmentPost();
            }
        }
    }

    private function initializeItems(): void
    {
        $this->item = [
            'id_equipo' => '0',
            'persona_responsable' => '',
            'departamento_destino' => '',
            'fecha_asignacion' => date('Y-m-d\TH:i'),
            'condicion_entrega' => '',
            'accesorios_entregados' => '',
            'observaciones' => '',
        ];

        $this->returnItem = [
            'fecha_devolucion' => date('Y-m-d\TH:i'),
            'condicion_devolucion' => '',
        ];
    }

    private function pickItem(array $row): array
    {
        return [
            'id_equipo' => strval(intval($row['id_equipo'] ?? 0)),
            'persona_responsable' => strval($row['persona_responsable'] ?? ''),
            'departamento_destino' => strval($row['departamento_destino'] ?? ''),
            'fecha_asignacion' => $this->toInputDateTime(strval($row['fecha_asignacion'] ?? '')),
            'condicion_entrega' => strval($row['condicion_entrega'] ?? ''),
            'accesorios_entregados' => strval($row['accesorios_entregados'] ?? ''),
            'observaciones' => strval($row['observaciones'] ?? ''),
        ];
    }

    private function pickReturnItem(array $row): array
    {
        $fecha = strval($row['fecha_devolucion'] ?? '');

        return [
            'fecha_devolucion' => $fecha !== ''
                ? $this->toInputDateTime($fecha)
                : date('Y-m-d\TH:i'),
            'condicion_devolucion' => strval($row['condicion_devolucion'] ?? ''),
        ];
    }

    private function pickAssignmentPost(): void
    {
        $fields = [
            'id_equipo',
            'persona_responsable',
            'departamento_destino',
            'fecha_asignacion',
            'condicion_entrega',
            'accesorios_entregados',
            'observaciones',
        ];

        foreach ($fields as $field) {
            if (array_key_exists($field, $_POST) && is_string($_POST[$field])) {
                $this->item[$field] = trim($_POST[$field]);
            }
        }
    }

    private function pickReturnPost(): void
    {
        $fields = ['fecha_devolucion', 'condicion_devolucion'];

        foreach ($fields as $field) {
            if (array_key_exists($field, $_POST) && is_string($_POST[$field])) {
                $this->returnItem[$field] = trim($_POST[$field]);
            }
        }
    }

    private function loadOptions(): void
    {
        $includeEquipo = max(
            intval($this->record['id_equipo'] ?? 0),
            intval($this->item['id_equipo'] ?? 0)
        );
        $excludeAsignacion = $this->mode === 'INS' ? 0 : $this->id;

        $this->equipos = DaoAsignaciones::getEquiposOptions(
            true,
            $includeEquipo,
            $excludeAsignacion
        );
    }

    private function canEdit(): bool
    {
        if ($this->mode === 'INS') {
            return true;
        }

        return $this->mode === 'UPD'
            && strval($this->record['estado_asignacion'] ?? '') === 'ACTIVA';
    }

    /**
     * Revisa que la asignacion tenga los datos necesarios para guardarse.
     */
    private function validateData(): bool
    {
        $this->errors = [];

        $equipoId = intval($this->item['id_equipo'] ?? 0);
        $this->item['id_equipo'] = strval($equipoId);
        $equipo = DaoAsignaciones::getEquipoById($equipoId);
        $equipoActualId = intval($this->record['id_equipo'] ?? 0);
        $isSameEquipo = $this->mode === 'UPD' && $equipoId === $equipoActualId;

        if ($equipoId <= 0 || !$equipo) {
            $this->errors['id_equipo'] = 'Selecciona un equipo válido.';
        } elseif (intval($equipo['estado_registro'] ?? 0) !== 1) {
            $this->errors['id_equipo'] = 'El equipo seleccionado está inactivo.';
        } elseif (strval($equipo['estado_operativo'] ?? '') === 'DE_BAJA') {
            $this->errors['id_equipo'] = 'El equipo está de baja y no se puede asignar.';
        } else {
            if (
                !$isSameEquipo
                && strval($equipo['estado_operativo'] ?? '') !== 'DISPONIBLE'
            ) {
                $this->errors['id_equipo'] = 'El equipo no está disponible para asignar.';
            }

            if (DaoAsignaciones::tieneAsignacionActiva($equipoId, $this->id)) {
                $this->errors['id_equipo'] = 'El equipo ya tiene una asignación activa.';
            }

            if (DaoAsignaciones::tieneMantenimientoActivo($equipoId)) {
                $this->errors['id_equipo'] = 'El equipo está en mantenimiento y no se puede asignar.';
            }
        }

        $persona = trim(strval($this->item['persona_responsable'] ?? ''));
        $this->item['persona_responsable'] = $persona;
        if ($persona === '') {
            $this->errors['persona_responsable'] = 'Ingresa el nombre de la persona responsable.';
        } elseif ($this->textLength($persona) > 150) {
            $this->errors['persona_responsable'] = 'El nombre no puede superar los 150 caracteres.';
        }

        $departamento = trim(strval($this->item['departamento_destino'] ?? ''));
        $this->item['departamento_destino'] = $departamento;
        if ($departamento === '') {
            $this->errors['departamento_destino'] = 'Ingresa el departamento de destino.';
        } elseif ($this->textLength($departamento) > 150) {
            $this->errors['departamento_destino'] = 'El departamento no puede superar los 150 caracteres.';
        }

        $fechaAsignacion = DateHelper::normalizeDateTime(strval($this->item['fecha_asignacion'] ?? ''));
        if ($fechaAsignacion === null) {
            $this->errors['fecha_asignacion'] = 'Ingresa una fecha y hora válidas.';
        } elseif (strtotime($fechaAsignacion) > time()) {
            $this->errors['fecha_asignacion'] = 'La fecha de asignación no puede ser futura.';
        }

        $condicion = trim(strval($this->item['condicion_entrega'] ?? ''));
        $this->item['condicion_entrega'] = $condicion;
        if ($this->textLength($condicion) > 65535) {
            $this->errors['condicion_entrega'] = 'La condición de entrega supera el máximo permitido.';
        }

        $observaciones = trim(strval($this->item['observaciones'] ?? ''));
        $this->item['observaciones'] = $observaciones;
        if ($this->textLength($observaciones) > 65535) {
            $this->errors['observaciones'] = 'Las observaciones superan el máximo permitido.';
        }

        $accesorios = trim(strval($this->item['accesorios_entregados'] ?? ''));
        $this->item['accesorios_entregados'] = $accesorios;
        if ($this->textLength($accesorios) > 65535) {
            $this->errors['accesorios_entregados'] = 'Los accesorios superan el máximo permitido.';
        }

        return count($this->errors) === 0;
    }

    private function validateReturn(): bool
    {
        $this->returnErrors = [];

        $fechaDevolucion = DateHelper::normalizeDateTime(
            strval($this->returnItem['fecha_devolucion'] ?? '')
        );

        if ($fechaDevolucion === null) {
            $this->returnErrors['fecha_devolucion'] = 'Ingresa una fecha y hora válidas.';
        } elseif (strtotime($fechaDevolucion) > time()) {
            $this->returnErrors['fecha_devolucion'] = 'La fecha de devolución no puede ser futura.';
        } else {
            $fechaAsignacion = DateHelper::normalizeDateTime(
                strval($this->record['fecha_asignacion'] ?? '')
            );
            if (
                $fechaAsignacion !== null
                && strtotime($fechaDevolucion) < strtotime($fechaAsignacion)
            ) {
                $this->returnErrors['fecha_devolucion'] = 'La devolución no puede ser anterior a la asignación.';
            }
        }

        $condicion = trim(strval($this->returnItem['condicion_devolucion'] ?? ''));
        $this->returnItem['condicion_devolucion'] = $condicion;
        if ($this->textLength($condicion) > 65535) {
            $this->returnErrors['condicion_devolucion'] = 'La condición de devolución supera el máximo permitido.';
        }

        return count($this->returnErrors) === 0;
    }

    /**
     * Registra una nueva asignacion del equipo seleccionado.
     */
    private function handleSave(): void
    {
        $userId = intval(Security::getUserId());
        $fechaAsignacion = DateHelper::normalizeDateTime(
            strval($this->item['fecha_asignacion'])
        );

        if ($fechaAsignacion === null) {
            throw new \RuntimeException('La fecha de asignación no es válida.');
        }

        $data = [
            'id_equipo' => intval($this->item['id_equipo']),
            'persona_responsable' => strval($this->item['persona_responsable']),
            'departamento_destino' => strval($this->item['departamento_destino']),
            'id_usuario_entrega' => $userId,
            'fecha_asignacion' => $fechaAsignacion,
            'condicion_entrega' => $this->nullIfEmpty(strval($this->item['condicion_entrega'])),
            'accesorios_entregados' => $this->nullIfEmpty(strval($this->item['accesorios_entregados'])),
            'observaciones' => $this->nullIfEmpty(strval($this->item['observaciones'])),
        ];

        $oldEquipoId = 0;
        $before = null;

        DaoAsignaciones::beginTransaction();

        try {
            if ($this->mode === 'INS') {
                $oldEquipoId = 0;
            } else {
                $current = DaoAsignaciones::getAsignacionForUpdate($this->id);
                if (!$current || strval($current['estado_asignacion'] ?? '') !== 'ACTIVA') {
                    throw new \RuntimeException('Solo se pueden editar asignaciones activas.');
                }

                $before = $current;
                $oldEquipoId = intval($current['id_equipo'] ?? 0);
            }

            $equipoId = intval($data['id_equipo']);
            $equipoLock = DaoAsignaciones::getEquipoForUpdate($equipoId);
            if (!$equipoLock) {
                throw new \RuntimeException('El equipo seleccionado no existe.');
            }

            $cambiaEquipo = $this->mode === 'INS' || $equipoId !== $oldEquipoId;
            if ($cambiaEquipo && (
                intval($equipoLock['estado_registro'] ?? 0) !== 1
                || strval($equipoLock['estado_operativo'] ?? '') !== 'DISPONIBLE'
                || DaoAsignaciones::tieneAsignacionActiva($equipoId, $this->id)
                || DaoAsignaciones::tieneMantenimientoActivo($equipoId)
            )) {
                throw new \RuntimeException(
                    'El equipo ya no está disponible para asignar. Actualiza el formulario e intenta de nuevo.'
                );
            }

            if ($this->mode === 'INS') {
                $id = DaoAsignaciones::insertAsignacion($data);
            } else {
                if (!DaoAsignaciones::updateAsignacion($this->id, $data)) {
                    throw new \RuntimeException('No se pudo actualizar la asignación.');
                }
                $id = $this->id;
            }

            DaoAsignaciones::syncEquipoEstado($equipoId);
            if ($oldEquipoId > 0 && $oldEquipoId !== $equipoId) {
                DaoAsignaciones::syncEquipoEstado($oldEquipoId);
            }

            DaoAsignaciones::commit();
        } catch (\Throwable $ex) {
            DaoAsignaciones::rollback();
            throw $ex;
        }

        Bitacora::registrar(
            $this->mode === 'INS' ? 'CREAR_ASIGNACION' : 'ACTUALIZAR_ASIGNACION',
            'Asignaciones',
            Bitacora::RESULTADO_EXITO,
            intval($id),
            $before,
            $data
        );

        $msg = $this->mode === 'INS' ? 'created' : 'updated';
        Site::redirectTo('index.php?page=Inventario_Asignaciones&msg=' . $msg);
    }

    /**
     * Registra la devolucion del equipo y su condicion de retorno.
     */
    private function handleReturn(): void
    {
        if ($this->id <= 0) {
            throw new \InvalidArgumentException('La asignación solicitada no es válida.');
        }

        $fechaDevolucion = DateHelper::normalizeDateTime(
            strval($this->returnItem['fecha_devolucion'])
        );

        if ($fechaDevolucion === null) {
            throw new \RuntimeException('La fecha de devolución no es válida.');
        }

        $userId = intval(Security::getUserId());

        DaoAsignaciones::beginTransaction();

        try {
            $current = DaoAsignaciones::getAsignacionForUpdate($this->id);
            if (!$current || strval($current['estado_asignacion'] ?? '') !== 'ACTIVA') {
                throw new \RuntimeException('Solo se pueden devolver asignaciones activas.');
            }

            if (!DaoAsignaciones::registrarDevolucion(
                $this->id,
                $fechaDevolucion,
                $userId,
                $this->nullIfEmpty(strval($this->returnItem['condicion_devolucion']))
            )) {
                throw new \RuntimeException('No se pudo registrar la devolución.');
            }

            DaoAsignaciones::syncEquipoEstado(intval($current['id_equipo'] ?? 0));
            DaoAsignaciones::commit();
        } catch (\Throwable $ex) {
            DaoAsignaciones::rollback();
            throw $ex;
        }

        Bitacora::registrar(
            'DEVOLVER_EQUIPO',
            'Asignaciones',
            Bitacora::RESULTADO_EXITO,
            $this->id,
            $current,
            [
                'fecha_devolucion' => $fechaDevolucion,
                'condicion_devolucion' => $this->nullIfEmpty(
                    strval($this->returnItem['condicion_devolucion'])
                ),
                'id_usuario_devolucion' => $userId,
            ]
        );

        Site::redirectTo('index.php?page=Inventario_Asignaciones&msg=returned');
    }

    private function renderForm(): void
    {
        $isReturn = $this->mode === 'DEV';
        $canEdit = $this->canEdit();
        $estado = strval($this->record['estado_asignacion'] ?? 'ACTIVA');
        $isReturned = $estado === 'DEVUELTO';

        $formTitle = 'Nueva asignación';
        $formDescription = 'Registra la entrega del equipo, la persona responsable y las condiciones en que lo recibe.';
        $formInfo = '';
        $formWarning = '';

        if ($isReturn) {
            $formTitle = 'Registrar devolución';
            $formDescription = 'Confirma la devolución del equipo y deja constancia de su condición al recibirlo.';
            $formWarning = 'Al registrar la devolución, la asignación quedará cerrada. '
                . 'El equipo se liberará para una nueva entrega si no tiene un mantenimiento en proceso.';
        } elseif ($this->mode === 'UPD') {
            if ($estado === 'ACTIVA') {
                $formTitle = 'Editar asignación';
                $formDescription = 'Actualiza el responsable, el departamento o las condiciones mientras la asignación siga activa.';
            } else {
                $formTitle = 'Asignación devuelta';
                $formDescription = 'Esta asignación ya fue cerrada y se conserva únicamente como historial.';
                $formInfo = 'El equipo fue recibido nuevamente. No se puede editar una asignación devuelta.';
            }
        }

        $formParams = [
            'page' => 'Inventario_Asignacion',
            'mode' => $this->mode,
        ];
        if ($this->mode !== 'INS') {
            $formParams['id'] = $this->id;
        }

        $formMeta = '';
        if ($this->mode !== 'INS') {
            $entrega = trim(strval($this->record['entrega_nombre'] ?? ''));
            $fecha = $this->formatDateTime(strval($this->record['fecha_asignacion'] ?? ''));
            $formMeta = 'Registrada' . ($entrega !== '' ? ' por ' . $entrega : '')
                . ($fecha !== '' ? ' el ' . $fecha : '');
        }

        $login = Security::getUser();
        $userName = is_array($login) ? strval($login['userName'] ?? '') : '';
        $recibeNombre = $isReturn
            ? $userName
            : trim(strval($this->record['recibe_nombre'] ?? ''));

        $selectedEquipo = strval(intval($this->item['id_equipo'] ?? 0));

        $returnErrors = count($this->returnErrors) > 0;
        $allErrors = count($this->errors) + count($this->returnErrors);

        Renderer::render('inventario/asignacion', [
            'FormEyebrow' => 'Inventario / Asignaciones',
            'FormTitle' => $formTitle,
            'FormDescription' => $formDescription,
            'FormAction' => 'index.php?' . http_build_query($formParams),
            'FormCancelUrl' => 'index.php?page=Inventario_Asignaciones',
            'FormBackLabel' => 'Volver a asignaciones',
            'FormMeta' => $formMeta,
            'FormInfo' => $formInfo,
            'FormWarning' => $formWarning,
            'FormHasErrors' => $allErrors > 0,
            'FormErrorSummary' => $allErrors > 0
                ? 'Revisa los campos marcados en rojo e intenta de nuevo.'
                : '',
            'FormToken' => CSRF::token('asignacion'),
            'FormMode' => $this->mode,
            'FormId' => $this->id,
            'CanEdit' => $canEdit,
            'IsReturn' => $isReturn,
            'ShowReturn' => !$isReturn && $this->id > 0 && $estado === 'ACTIVA',
            'ShowReturnSection' => $isReturn || $isReturned,
            'ReturnUrl' => 'index.php?page=Inventario_Asignacion&mode=DEV&id=' . $this->id,
            'FormEstadoLabel' => $this->mode === 'INS' ? '' : $this->estadoLabel($estado),
            'FormEstadoClass' => $this->estadoClass($estado),
            'FormRequired' => $canEdit ? 'required' : '',
            'FormReadonly' => $canEdit ? '' : 'readonly',
            'FormDisabled' => $canEdit ? '' : 'disabled',
            'ReturnRequired' => $isReturn ? 'required' : '',
            'ReturnReadonly' => $isReturn ? '' : 'readonly',
            'FormSaveLabel' => $this->mode === 'INS' ? 'Guardar asignación' : 'Guardar cambios',
            'FormEquipo' => $selectedEquipo,
            'FormPersona' => strval($this->item['persona_responsable'] ?? ''),
            'FormDepartamento' => strval($this->item['departamento_destino'] ?? ''),
            'FormFechaAsignacion' => strval($this->item['fecha_asignacion'] ?? ''),
            'FormCondicionEntrega' => strval($this->item['condicion_entrega'] ?? ''),
            'FormAccesorios' => strval($this->item['accesorios_entregados'] ?? ''),
            'FormObservaciones' => strval($this->item['observaciones'] ?? ''),
            'FormFechaDevolucion' => strval($this->returnItem['fecha_devolucion'] ?? ''),
            'FormCondicionDevolucion' => strval($this->returnItem['condicion_devolucion'] ?? ''),
            'FormRecepcionNombre' => $recibeNombre,
            'FormEquipoError' => strval($this->errors['id_equipo'] ?? ''),
            'FormPersonaError' => strval($this->errors['persona_responsable'] ?? ''),
            'FormDepartamentoError' => strval($this->errors['departamento_destino'] ?? ''),
            'FormFechaAsignacionError' => strval($this->errors['fecha_asignacion'] ?? ''),
            'FormCondicionEntregaError' => strval($this->errors['condicion_entrega'] ?? ''),
            'FormAccesoriosError' => strval($this->errors['accesorios_entregados'] ?? ''),
            'FormObservacionesError' => strval($this->errors['observaciones'] ?? ''),
            'FormFechaDevolucionError' => strval($this->returnErrors['fecha_devolucion'] ?? ''),
            'FormCondicionDevolucionError' => strval($this->returnErrors['condicion_devolucion'] ?? ''),
            'HasReturnErrors' => $returnErrors,
            'EquiposOptionsHtml' => $this->buildEquipoOptionsHtml($selectedEquipo),
            'HasEquiposOptions' => count($this->equipos) > 0,
            'EquipoPlaceholderSelected' => $selectedEquipo === '0' ? 'selected' : '',
        ]);
    }

    private function buildEquipoOptionsHtml(string $selectedId): string
    {
        $html = '';

        foreach ($this->equipos as $row) {
            $value = strval($row['value'] ?? '');
            $label = $this->equipmentLabel($row);
            if (intval($row['status'] ?? 1) !== 1) {
                $label .= ' (inactivo)';
            }

            $html .= '<option value="' . $this->escape($value) . '"'
                . ($value === $selectedId ? ' selected' : '')
                . '>' . $this->escape($label) . '</option>';
        }

        return $html;
    }

    private function equipmentLabel(array $row): string
    {
        $serie = trim(strval($row['numero_serie_equipo'] ?? ''));
        $patrimonial = trim(strval($row['codigo_patrimonial_equipo'] ?? ''));
        $articulo = trim(strval($row['nombre_articulo'] ?? ''));
        $codigo = trim(strval($row['codigo_interno_articulo'] ?? ''));

        $label = $serie !== '' ? $serie : 'Equipo #' . intval($row['value'] ?? 0);
        if ($patrimonial !== '') {
            $label .= ' · ' . $patrimonial;
        }
        if ($articulo !== '') {
            $label .= ' — ' . $articulo;
        }
        if ($codigo !== '') {
            $label .= ' (' . $codigo . ')';
        }

        return $label;
    }

    private function estadoLabel(string $estado): string
    {
        $labels = [
            'ACTIVA' => 'Activa',
            'DEVUELTO' => 'Devuelto',
        ];

        return $labels[$estado] ?? '';
    }

    private function estadoClass(string $estado): string
    {
        $classes = [
            'ACTIVA' => 'badge--success',
            'DEVUELTO' => 'badge--outline',
        ];

        return $classes[$estado] ?? 'badge--outline';
    }

    private function toInputDateTime(string $value): string
    {
        $timestamp = strtotime($value);

        return $timestamp !== false ? date('Y-m-d\TH:i', $timestamp) : '';
    }

    private function formatDateTime(string $value): string
    {
        $timestamp = strtotime($value);

        return $timestamp !== false ? date('d/m/Y H:i', $timestamp) : '';
    }

    private function nullIfEmpty(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function textLength(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }
}
