<?php

namespace Controllers\Inventario;

use Controllers\PrivateController;
use Dao\Mantenimientos\Mantenimientos as DaoMantenimientos;
use Utilities\Bitacora;
use Utilities\CSRF;
use Utilities\Security;
use Utilities\Site;
use Views\Renderer;

class Mantenimiento extends PrivateController
{
    private $mode = 'INS';
    private $id = 0;
    private $record = [];
    private $item = [];
    private $errors = [];
    private $tipos = [];
    private $equipos = [];

    public function run(): void
    {
        try {
            $this->getParams();
            $this->loadOptions();

            if ($this->isPostBack()) {
                CSRF::requireValid('mantenimiento', $_POST['token'] ?? '');

                if ($this->mode === 'DEL') {
                    $this->handleCancelar();
                } elseif ($this->canEdit() && $this->validateData()) {
                    $this->handleSave();
                }
            }

            $this->renderForm();
        } catch (\Exception $ex) {
            Site::redirectToWithMsg(
                'index.php?page=Inventario_Mantenimientos&msg=error',
                $ex->getMessage()
            );
        }
    }

    private function getParams(): void
    {
        $this->mode = strtoupper(strval($_GET['mode'] ?? 'INS'));
        if (!in_array($this->mode, ['INS', 'UPD', 'DEL'], true)) {
            throw new \InvalidArgumentException('La modalidad del formulario no es válida.');
        }

        $this->id = intval($_GET['id'] ?? 0);
        $this->initializeItem();

        if ($this->mode !== 'INS') {
            if ($this->id <= 0) {
                throw new \InvalidArgumentException('El mantenimiento solicitado no es válido.');
            }

            $row = DaoMantenimientos::getMantenimientoById($this->id);
            if (!$row) {
                throw new \RuntimeException('No se encontró el mantenimiento solicitado.');
            }

            $this->record = $row;
            $this->item = $this->pickItem($row);

            if (
                $this->mode === 'DEL'
                && strval($this->record['estado_mantenimiento'] ?? '') !== 'EN_PROCESO'
            ) {
                throw new \RuntimeException('Solo se pueden cancelar mantenimientos en proceso.');
            }
        }

        if (
            $this->isPostBack()
            && $this->mode !== 'DEL'
            && (
                $this->mode === 'INS'
                || strval($this->record['estado_mantenimiento'] ?? '') === 'EN_PROCESO'
            )
        ) {
            $fields = [
                'id_equipo',
                'id_tipo_mantenimiento',
                'fecha_recepcion',
                'tecnico_responsable',
                'diagnostico_inicial',
                'trabajo_realizado',
                'resultado_mantenimiento',
                'recomendaciones',
                'repuestos_utilizados',
                'costo_mantenimiento',
            ];

            foreach ($fields as $field) {
                if (array_key_exists($field, $_POST) && is_string($_POST[$field])) {
                    $this->item[$field] = trim($_POST[$field]);
                }
            }
        }
    }

    private function initializeItem(): void
    {
        $this->item = [
            'id_equipo' => '0',
            'id_tipo_mantenimiento' => '0',
            'fecha_recepcion' => date('Y-m-d\TH:i'),
            'tecnico_responsable' => '',
            'diagnostico_inicial' => '',
            'trabajo_realizado' => '',
            'resultado_mantenimiento' => '',
            'recomendaciones' => '',
            'repuestos_utilizados' => '',
            'costo_mantenimiento' => '0.00',
            'estado_mantenimiento' => 'EN_PROCESO',
            'fecha_finalizacion' => '',
        ];
    }

    private function pickItem(array $row): array
    {
        return [
            'id_equipo' => strval(intval($row['id_equipo'] ?? 0)),
            'id_tipo_mantenimiento' => strval(intval($row['id_tipo_mantenimiento'] ?? 0)),
            'fecha_recepcion' => $this->toInputDateTime(strval($row['fecha_recepcion'] ?? '')),
            'tecnico_responsable' => strval($row['tecnico_responsable'] ?? ''),
            'diagnostico_inicial' => strval($row['diagnostico_inicial'] ?? ''),
            'trabajo_realizado' => strval($row['trabajo_realizado'] ?? ''),
            'resultado_mantenimiento' => strval($row['resultado_mantenimiento'] ?? ''),
            'recomendaciones' => strval($row['recomendaciones'] ?? ''),
            'motivo_cancelacion' => strval($row['motivo_cancelacion'] ?? ''),
            'repuestos_utilizados' => strval($row['repuestos_utilizados'] ?? ''),
            'costo_mantenimiento' => number_format(
                floatval($row['costo_mantenimiento'] ?? 0),
                2,
                '.',
                ''
            ),
            'estado_mantenimiento' => strval($row['estado_mantenimiento'] ?? 'EN_PROCESO'),
            'fecha_finalizacion' => strval($row['fecha_finalizacion'] ?? ''),
        ];
    }

    private function loadOptions(): void
    {
        $includeTipo = max(
            intval($this->record['id_tipo_mantenimiento'] ?? 0),
            intval($this->item['id_tipo_mantenimiento'] ?? 0)
        );
        $includeEquipo = max(
            intval($this->record['id_equipo'] ?? 0),
            intval($this->item['id_equipo'] ?? 0)
        );
        $excludeMantenimiento = $this->mode === 'INS' ? 0 : $this->id;

        $this->tipos = DaoMantenimientos::getTiposMantenimiento(true, $includeTipo);
        $this->equipos = DaoMantenimientos::getEquiposOptions(
            true,
            $includeEquipo,
            $excludeMantenimiento
        );

        if ($this->mode === 'INS' && !$this->isPostBack()) {
            $tipoActual = intval($this->item['id_tipo_mantenimiento'] ?? 0);
            $tipoValido = false;
            foreach ($this->tipos as $tipo) {
                if (intval($tipo['value'] ?? 0) === $tipoActual) {
                    $tipoValido = true;
                    break;
                }
            }
            if (!$tipoValido) {
                $this->item['id_tipo_mantenimiento'] = strval($this->tipos[0]['value'] ?? '0');
            }
        }
    }

    private function canEdit(): bool
    {
        if ($this->mode === 'DEL') {
            return false;
        }

        return $this->mode === 'INS'
            || strval($this->record['estado_mantenimiento'] ?? '') === 'EN_PROCESO';
    }

    private function validateData(): bool
    {
        $this->errors = [];

        $accion = strval($_POST['accion'] ?? 'guardar');
        $completar = $accion === 'completar';
        $estadoObjetivo = $completar ? 'COMPLETADO' : 'EN_PROCESO';

        $equipoId = intval($this->item['id_equipo'] ?? 0);
        $this->item['id_equipo'] = strval($equipoId);
        $equipo = DaoMantenimientos::getEquipoById($equipoId);

        if ($equipoId <= 0 || !$equipo) {
            $this->errors['id_equipo'] = 'Selecciona un equipo válido.';
        } elseif (
            intval($equipo['estado_registro'] ?? 0) !== 1
            && $equipoId !== intval($this->record['id_equipo'] ?? 0)
        ) {
            $this->errors['id_equipo'] = 'El equipo seleccionado está inactivo.';
        } elseif (DaoMantenimientos::tieneMantenimientoActivo($equipoId, $this->id)) {
            $this->errors['id_equipo'] = 'El equipo ya tiene otro mantenimiento en proceso.';
        }

        $tipoId = intval($this->item['id_tipo_mantenimiento'] ?? 0);
        $this->item['id_tipo_mantenimiento'] = strval($tipoId);
        $tipo = DaoMantenimientos::getTipoMantenimientoById($tipoId);

        if ($tipoId <= 0 || !$tipo) {
            $this->errors['id_tipo_mantenimiento'] = 'Selecciona un tipo de mantenimiento válido.';
        } elseif (
            intval($tipo['estado_mant'] ?? 0) !== 1
            && $tipoId !== intval($this->record['id_tipo_mantenimiento'] ?? 0)
        ) {
            $this->errors['id_tipo_mantenimiento'] = 'El tipo de mantenimiento está inactivo.';
        }

        $fechaRecepcion = $this->normalizeDateTime(strval($this->item['fecha_recepcion'] ?? ''));
        if ($fechaRecepcion === null) {
            $this->errors['fecha_recepcion'] = 'Ingresa una fecha y hora válidas.';
        } elseif (strtotime($fechaRecepcion) > time() + 300) {
            $this->errors['fecha_recepcion'] = 'La fecha de recepción no puede ser futura.';
        }

        $tecnico = trim(strval($this->item['tecnico_responsable'] ?? ''));
        $this->item['tecnico_responsable'] = $tecnico;
        if ($tecnico === '') {
            $this->errors['tecnico_responsable'] = 'Ingresa el nombre del técnico responsable.';
        } elseif ($this->textLength($tecnico) > 150) {
            $this->errors['tecnico_responsable'] = 'El nombre del técnico no puede superar los 150 caracteres.';
        }

        $diagnostico = trim(strval($this->item['diagnostico_inicial'] ?? ''));
        $this->item['diagnostico_inicial'] = $diagnostico;
        if ($this->textLength($diagnostico) > 65535) {
            $this->errors['diagnostico_inicial'] = 'El diagnóstico supera el máximo permitido.';
        }

        $trabajo = trim(strval($this->item['trabajo_realizado'] ?? ''));
        $this->item['trabajo_realizado'] = $trabajo;
        if ($this->textLength($trabajo) > 65535) {
            $this->errors['trabajo_realizado'] = 'El trabajo realizado supera el máximo permitido.';
        } elseif ($completar && $trabajo === '') {
            $this->errors['trabajo_realizado'] = 'Describe el trabajo realizado antes de completar el mantenimiento.';
        }

        foreach (['resultado_mantenimiento', 'recomendaciones'] as $field) {
            $this->item[$field] = trim(strval($this->item[$field] ?? ''));
            if ($this->textLength($this->item[$field]) > 65535) {
                $this->errors[$field] = 'El contenido supera el máximo permitido.';
            }
        }
        if ($completar && $this->item['resultado_mantenimiento'] === '') {
            $this->errors['resultado_mantenimiento'] = 'Describe el resultado final antes de completar el mantenimiento.';
        }

        $repuestos = trim(strval($this->item['repuestos_utilizados'] ?? ''));
        $this->item['repuestos_utilizados'] = $repuestos;
        if ($this->textLength($repuestos) > 65535) {
            $this->errors['repuestos_utilizados'] = 'Los repuestos superan el máximo permitido.';
        }

        $costoRaw = trim(strval($this->item['costo_mantenimiento'] ?? ''));
        if ($costoRaw === '') {
            $costoRaw = '0.00';
        }
        $costoRaw = str_replace(',', '.', $costoRaw);
        if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $costoRaw)) {
            $this->errors['costo_mantenimiento'] = 'Ingresa un costo válido con máximo dos decimales.';
        } else {
            $this->item['costo_mantenimiento'] = number_format(floatval($costoRaw), 2, '.', '');
        }

        $this->item['estado_mantenimiento'] = $estadoObjetivo;
        $this->item['fecha_finalizacion'] = $completar ? date('Y-m-d H:i:s') : '';

        return count($this->errors) === 0;
    }

    private function handleSave(): void
    {
        $userId = intval(Security::getUserId());
        $completar = strval($this->item['estado_mantenimiento'] ?? '') === 'COMPLETADO';

        $data = [
            'id_equipo' => intval($this->item['id_equipo']),
            'id_tipo_mantenimiento' => intval($this->item['id_tipo_mantenimiento']),
            'fecha_recepcion' => $this->normalizeDateTime(strval($this->item['fecha_recepcion'])),
            'diagnostico_inicial' => $this->nullIfEmpty(strval($this->item['diagnostico_inicial'])),
            'trabajo_realizado' => $this->nullIfEmpty(strval($this->item['trabajo_realizado'])),
            'resultado_mantenimiento' => $this->nullIfEmpty(strval($this->item['resultado_mantenimiento'])),
            'recomendaciones' => $this->nullIfEmpty(strval($this->item['recomendaciones'])),
            'tecnico_responsable' => strval($this->item['tecnico_responsable']),
            'repuestos_utilizados' => $this->nullIfEmpty(strval($this->item['repuestos_utilizados'])),
            'costo_mantenimiento' => strval($this->item['costo_mantenimiento']),
            'fecha_finalizacion' => $completar
                ? $this->normalizeDateTime(strval($this->item['fecha_finalizacion']))
                : null,
            'estado_mantenimiento' => $completar ? 'COMPLETADO' : 'EN_PROCESO',
            'id_usuario_registro' => $userId,
        ];

        if ($data['fecha_recepcion'] === null) {
            throw new \RuntimeException('La fecha de recepción no es válida.');
        }

        $oldEquipoId = 0;
        $before = null;

        DaoMantenimientos::beginTransaction();

        try {
            if ($this->mode === 'INS') {
                $id = DaoMantenimientos::insertMantenimiento($data);
            } else {
                $current = DaoMantenimientos::getMantenimientoById($this->id);
                if (!$current || strval($current['estado_mantenimiento'] ?? '') !== 'EN_PROCESO') {
                    throw new \RuntimeException('Solo se pueden editar mantenimientos en proceso.');
                }

                $before = $current;
                $oldEquipoId = intval($current['id_equipo'] ?? 0);
                if (!DaoMantenimientos::updateMantenimiento($this->id, $data)) {
                    throw new \RuntimeException('No se pudo actualizar el mantenimiento.');
                }
                $id = $this->id;
            }

            DaoMantenimientos::syncEquipoEstado(intval($data['id_equipo']));
            if ($oldEquipoId > 0 && $oldEquipoId !== intval($data['id_equipo'])) {
                DaoMantenimientos::syncEquipoEstado($oldEquipoId);
            }

            DaoMantenimientos::commit();
        } catch (\Throwable $ex) {
            DaoMantenimientos::rollback();
            throw $ex;
        }

        if ($completar) {
            $tipoOperacion = 'COMPLETAR_MANTENIMIENTO';
            $msg = 'completed';
        } elseif ($this->mode === 'INS') {
            $tipoOperacion = 'CREAR_MANTENIMIENTO';
            $msg = 'created';
        } else {
            $tipoOperacion = 'ACTUALIZAR_MANTENIMIENTO';
            $msg = 'updated';
        }

        Bitacora::registrar(
            $tipoOperacion,
            'Mantenimientos',
            Bitacora::RESULTADO_EXITO,
            intval($id),
            $before,
            $data
        );

        Site::redirectTo('index.php?page=Inventario_Mantenimientos&msg=' . $msg);
    }

    private function handleCancelar(): void
    {
        if ($this->id <= 0) {
            throw new \InvalidArgumentException('El mantenimiento solicitado no es válido.');
        }

        $current = DaoMantenimientos::getMantenimientoById($this->id);
        if (!$current) {
            throw new \RuntimeException('No se encontró el mantenimiento solicitado.');
        }
        if (strval($current['estado_mantenimiento'] ?? '') !== 'EN_PROCESO') {
            throw new \RuntimeException('Solo se pueden cancelar mantenimientos en proceso.');
        }

        DaoMantenimientos::beginTransaction();

        try {
            $motivo = trim(strval($_POST['motivo_cancelacion'] ?? ''));
            if ($motivo === '') {
                throw new \InvalidArgumentException('Indica el motivo de cancelación.');
            }
            if (!DaoMantenimientos::cancelarMantenimiento($this->id, date('Y-m-d H:i:s'), $motivo)) {
                throw new \RuntimeException('No se pudo cancelar el mantenimiento.');
            }
            DaoMantenimientos::syncEquipoEstado(intval($current['id_equipo'] ?? 0));
            DaoMantenimientos::commit();
        } catch (\Throwable $ex) {
            DaoMantenimientos::rollback();
            throw $ex;
        }

        Bitacora::registrar(
            'CANCELAR_MANTENIMIENTO',
            'Mantenimientos',
            Bitacora::RESULTADO_EXITO,
            $this->id,
            $current,
            ['estado_mantenimiento' => 'CANCELADO', 'motivo_cancelacion' => $motivo]
        );

        Site::redirectTo('index.php?page=Inventario_Mantenimientos&msg=cancelled');
    }

    private function renderForm(): void
    {
        $isDelete = $this->mode === 'DEL';
        $canEdit = $this->canEdit();
        $estado = strval($this->record['estado_mantenimiento'] ?? 'EN_PROCESO');

        $formTitle = 'Nuevo mantenimiento';
        $formDescription = 'Registra la recepción del equipo, el diagnóstico inicial y el técnico responsable.';
        $formInfo = '';
        $formWarning = '';

        if ($isDelete) {
            $formTitle = 'Cancelar mantenimiento';
            $formDescription = 'Revisa el registro antes de cancelarlo. La acción conserva el historial.';
            $formWarning = 'Al cancelar, el mantenimiento quedará marcado como CANCELADO. '
                . 'El equipo se liberará del taller si no tiene otra asignación activa.';
        } elseif ($this->mode === 'UPD') {
            if ($estado === 'EN_PROCESO') {
                $formTitle = 'Editar mantenimiento';
                $formDescription = 'Actualiza el diagnóstico, los repuestos o el costo mientras el equipo siga en el taller.';
            } elseif ($estado === 'COMPLETADO') {
                $formTitle = 'Mantenimiento completado';
                $formDescription = 'Este mantenimiento ya fue cerrado y se conserva únicamente como historial.';
                $formInfo = 'El trabajo realizado y el costo quedaron registrados. No se puede editar un mantenimiento completado.';
            } else {
                $formTitle = 'Mantenimiento cancelado';
                $formDescription = 'Este mantenimiento fue cancelado y se conserva únicamente como historial.';
                $formInfo = 'El registro quedó cerrado sin continuar con el servicio técnico.';
            }
        }

        $formParams = [
            'page' => 'Inventario_Mantenimiento',
            'mode' => $this->mode,
        ];
        if ($this->mode !== 'INS') {
            $formParams['id'] = $this->id;
        }

        $formMeta = '';
        if ($this->mode !== 'INS') {
            $usuario = trim(strval($this->record['usuario_nombre'] ?? ''));
            $creado = $this->formatDateTime(strval($this->record['fecha_recepcion'] ?? ''));
            $formMeta = 'Registrado' . ($usuario !== '' ? ' por ' . $usuario : '')
                . ($creado !== '' ? ' el ' . $creado : '');
        }

        $fechaFinalDisplay = 'Se registra al completar';
        if (strval($this->record['fecha_finalizacion'] ?? '') !== '') {
            $fechaFinalDisplay = $this->formatDateTime(strval($this->record['fecha_finalizacion']));
        }

        $selectedEquipo = strval(intval($this->item['id_equipo'] ?? 0));
        $selectedTipo = strval(intval($this->item['id_tipo_mantenimiento'] ?? 0));

        Renderer::render('mantenimientos/mantenimiento', [
            'FormEyebrow' => 'Inventario / Mantenimientos',
            'FormTitle' => $formTitle,
            'FormDescription' => $formDescription,
            'FormAction' => 'index.php?' . http_build_query($formParams),
            'FormCancelUrl' => 'index.php?page=Inventario_Mantenimientos',
            'FormBackLabel' => 'Volver a mantenimientos',
            'FormDanger' => $isDelete,
            'FormMeta' => $formMeta,
            'FormInfo' => $formInfo,
            'FormWarning' => $formWarning,
            'FormHasErrors' => count($this->errors) > 0,
            'FormErrorSummary' => count($this->errors) > 0
                ? 'Revisa los campos marcados en rojo e intenta de nuevo.'
                : '',
            'FormToken' => CSRF::token('mantenimiento'),
            'FormMode' => $this->mode,
            'FormId' => $this->id,
            'CanEdit' => $canEdit,
            'ShowCancel' => !$isDelete && $this->id > 0 && $estado === 'EN_PROCESO',
            'CancelUrl' => 'index.php?page=Inventario_Mantenimiento&mode=DEL&id=' . $this->id,
            'FormEstadoLabel' => $this->mode === 'INS' ? '' : $this->estadoLabel($estado),
            'FormEstadoClass' => $this->estadoClass($estado),
            'FormRequired' => $canEdit ? 'required' : '',
            'FormReadonly' => $canEdit ? '' : 'readonly',
            'FormDisabled' => $canEdit ? '' : 'disabled',
            'FormSaveLabel' => $this->mode === 'INS' ? 'Guardar mantenimiento' : 'Guardar cambios',
            'FormCompleteLabel' => $this->mode === 'INS'
                ? 'Guardar y completar'
                : 'Completar mantenimiento',
            'FormEquipo' => $selectedEquipo,
            'FormTipo' => $selectedTipo,
            'FormFechaRecepcion' => strval($this->item['fecha_recepcion'] ?? ''),
            'FormTecnico' => strval($this->item['tecnico_responsable'] ?? ''),
            'FormDiagnostico' => strval($this->item['diagnostico_inicial'] ?? ''),
            'FormTrabajo' => strval($this->item['trabajo_realizado'] ?? ''),
            'FormResultado' => strval($this->item['resultado_mantenimiento'] ?? ''),
            'FormRecomendaciones' => strval($this->item['recomendaciones'] ?? ''),
            'FormMotivoCancelacion' => strval($this->item['motivo_cancelacion'] ?? ''),
            'FormRepuestos' => strval($this->item['repuestos_utilizados'] ?? ''),
            'FormCosto' => strval($this->item['costo_mantenimiento'] ?? '0.00'),
            'FormFechaFinalizacionDisplay' => $fechaFinalDisplay,
            'FormEquipoError' => strval($this->errors['id_equipo'] ?? ''),
            'FormTipoError' => strval($this->errors['id_tipo_mantenimiento'] ?? ''),
            'FormFechaError' => strval($this->errors['fecha_recepcion'] ?? ''),
            'FormTecnicoError' => strval($this->errors['tecnico_responsable'] ?? ''),
            'FormDiagnosticoError' => strval($this->errors['diagnostico_inicial'] ?? ''),
            'FormTrabajoError' => strval($this->errors['trabajo_realizado'] ?? ''),
            'FormResultadoError' => strval($this->errors['resultado_mantenimiento'] ?? ''),
            'FormRecomendacionesError' => strval($this->errors['recomendaciones'] ?? ''),
            'FormRepuestosError' => strval($this->errors['repuestos_utilizados'] ?? ''),
            'FormCostoError' => strval($this->errors['costo_mantenimiento'] ?? ''),
            'EquiposOptionsHtml' => $this->buildEquipoOptionsHtml($selectedEquipo),
            'HasEquiposOptions' => count($this->equipos) > 0,
            'TiposForm' => $this->decorateOptions($this->tipos, $selectedTipo),
            'EquipoPlaceholderSelected' => $selectedEquipo === '0' ? 'selected' : '',
            'TipoPlaceholderSelected' => $selectedTipo === '0' ? 'selected' : '',
        ]);
    }

    private function decorateOptions(array $rows, string $selected): array
    {
        $options = [];

        foreach ($rows as $row) {
            $value = strval($row['value'] ?? '');
            $label = strval($row['label'] ?? '');
            if (intval($row['status'] ?? 1) !== 1) {
                $label .= ' (inactivo)';
            }

            $options[] = [
                'value' => $value,
                'label' => $label,
                'selected' => $value === $selected ? 'selected' : '',
            ];
        }

        return $options;
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
            'EN_PROCESO' => 'En proceso',
            'COMPLETADO' => 'Completado',
            'CANCELADO' => 'Cancelado',
        ];

        return $labels[$estado] ?? '';
    }

    private function estadoClass(string $estado): string
    {
        $classes = [
            'EN_PROCESO' => 'badge--warning',
            'COMPLETADO' => 'badge--success',
            'CANCELADO' => 'badge--danger',
        ];

        return $classes[$estado] ?? 'badge--outline';
    }

    private function normalizeDateTime(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $value = str_replace('T', ' ', $value);
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value)) {
            $value .= ':00';
        }

        $date = \DateTime::createFromFormat('Y-m-d H:i:s', $value);
        if ($date === false || $date->format('Y-m-d H:i:s') !== $value) {
            return null;
        }

        return $value;
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
