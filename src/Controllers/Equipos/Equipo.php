<?php

namespace Controllers\Equipos;

use Controllers\PrivateController;
use Dao\Equipos\Equipos as DaoEquipos;
use Dao\Inventario\Asignaciones as DaoAsignaciones;
use Utilities\ArrUtils;
use Utilities\Bitacora;
use Utilities\CSRF;
use Utilities\Fechas;
use Utilities\Site;
use Views\Renderer;

/**
 * Atiende el formulario para registrar, actualizar o desactivar un equipo.
 */
class Equipo extends PrivateController
{
    // Estados que describen la situacion actual de un equipo.
    private const ESTADOS_OPERATIVOS = [
        'DISPONIBLE',
        'ASIGNADO',
        'EN_MANTENIMIENTO',
        'EN_REVISION',
        'DE_BAJA',
    ];

    private const ESTADOS_OPERATIVOS_LABELS = [
        'DISPONIBLE' => 'Disponible',
        'ASIGNADO' => 'Asignado',
        'EN_MANTENIMIENTO' => 'En mantenimiento',
        'EN_REVISION' => 'En revisión',
        'DE_BAJA' => 'De baja',
    ];

    private const MOTIVOS_APLICA_LABELS = [
        'AJUSTE_ENTRADA' => 'Ajuste de entrada',
        'AJUSTE_SALIDA' => 'Ajuste de salida',
        'ANULACION' => 'Anulación',
        'BAJA' => 'Baja',
    ];

    private $mode = 'INS';
    private $id = 0;
    // Datos del formulario y copia previa del equipo seleccionado.
    private $item = [];
    private $record = [];
    private $originalItem = [];
    // Mensajes de revision y opciones que se ofrecen para completar el equipo.
    private $errors = [];
    private $articulos = [];
    private $ubicaciones = [];
    private $motivos = [];

    /**
     * Carga el equipo, procesa los cambios enviados y muestra el formulario.
     */
    public function run(): void
    {
        try {
            $this->getParams();
            if ($this->mode === 'DEL') {
                $this->requireAction('Acciones.Equipos.Desactivar');
            } elseif ($this->mode === 'UPD') {
                $this->requireAction('Acciones.Equipos.Editar');
            } else {
                $this->requireAction('Acciones.Equipos.Crear');
            }
            $this->loadOptions();

            if ($this->isPostBack()) {
                CSRF::requireValid('equipo', $_POST['token'] ?? '');

                if ($this->mode === 'DEL') {
                    $this->handleDeactivate();
                } elseif ($this->validateData()) {
                    $this->handleSave();
                }
            }

            $this->renderForm();
        } catch (\Exception $ex) {
            Site::redirectToWithMsg(
                'index.php?page=Equipos_Equipos&msg=error',
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
                throw new \InvalidArgumentException('El equipo solicitado no es válido.');
            }

            $row = DaoEquipos::getEquipoById($this->id);
            if (!$row) {
                throw new \RuntimeException('No se encontró el equipo solicitado.');
            }

            $this->record = $row;
            $this->item = $this->pickItem($row);
            $this->originalItem = $this->item;
        }

        if ($this->isPostBack() && $this->mode !== 'DEL') {
            $fields = [
                'id_articulo',
                'numero_serie_equipo',
                'codigo_patrimonial_equipo',
                'fecha_adquisicion',
                'garantia_vencimiento_equipo',
                'proveedor_origen',
                'estado_operativo',
                'id_ubicacion',
                'id_motivo',
                'observaciones_equipo',
                'estado_registro',
            ];

            foreach ($fields as $field) {
                if (array_key_exists($field, $_POST)) {
                    $this->item[$field] = trim(strval($_POST[$field]));
                }
            }
        }
    }

    private function initializeItem(): void
    {
        $this->item = [
            'id_articulo' => '0',
            'numero_serie_equipo' => '',
            'codigo_patrimonial_equipo' => '',
            'fecha_adquisicion' => '',
            'garantia_vencimiento_equipo' => '',
            'proveedor_origen' => '',
            'estado_operativo' => 'DISPONIBLE',
            'id_ubicacion' => '0',
            'id_motivo' => '0',
            'observaciones_equipo' => '',
            'estado_registro' => '1',
        ];
    }

    private function pickItem(array $row): array
    {
        return [
            'id_articulo' => strval($row['id_articulo'] ?? '0'),
            'numero_serie_equipo' => strval($row['numero_serie_equipo'] ?? ''),
            'codigo_patrimonial_equipo' => strval($row['codigo_patrimonial_equipo'] ?? ''),
            'fecha_adquisicion' => strval($row['fecha_adquisicion'] ?? ''),
            'garantia_vencimiento_equipo' => strval($row['garantia_vencimiento_equipo'] ?? ''),
            'proveedor_origen' => strval($row['proveedor_origen'] ?? ''),
            'estado_operativo' => strval($row['estado_operativo'] ?? 'DISPONIBLE'),
            'id_ubicacion' => strval($row['id_ubicacion'] ?? '0'),
            'id_motivo' => $row['id_motivo'] === null ? '0' : strval($row['id_motivo']),
            'observaciones_equipo' => strval($row['observaciones_equipo'] ?? ''),
            'estado_registro' => strval(intval($row['estado_registro'] ?? 0)),
        ];
    }

    private function loadOptions(): void
    {
        $includeArticulo = $this->mode !== 'INS'
            ? intval($this->originalItem['id_articulo'] ?? 0)
            : 0;
        $includeUbicacion = $this->mode !== 'INS'
            ? intval($this->originalItem['id_ubicacion'] ?? 0)
            : 0;
        $includeMotivo = $this->mode !== 'INS'
            ? intval($this->originalItem['id_motivo'] ?? 0)
            : 0;

        $this->articulos = DaoEquipos::getArticulosOptions(true, $includeArticulo);
        $this->ubicaciones = DaoEquipos::getUbicacionesOptions(true, $includeUbicacion);
        $this->motivos = DaoEquipos::getMotivosOptions($includeMotivo);
    }

    /**
     * Comprueba los datos del equipo antes de guardarlos.
     */
    private function validateData(): bool
    {
        $this->errors = [];
        $excludeId = $this->mode === 'UPD' ? $this->id : 0;

        if ($this->mode === 'UPD') {
            $newArticuloId = intval($this->item['id_articulo']);
            $originalArticuloId = intval($this->originalItem['id_articulo'] ?? 0);
            if ($newArticuloId !== $originalArticuloId) {
                if (DaoEquipos::tieneHistorialOperativo($this->id)) {
                    $this->errors['id_articulo'] = 'No se puede cambiar el artículo de un equipo con historial de asignaciones o mantenimientos.';
                }
            }
        }
        $articuloId = intval($this->item['id_articulo'] ?? 0);
        $this->item['id_articulo'] = strval($articuloId);
        $articulo = DaoEquipos::getArticuloById($articuloId);

        if ($articuloId <= 0 || !$articulo) {
            $this->errors['id_articulo'] = 'Selecciona un artículo válido.';
        } elseif (strval($articulo['tipo_control'] ?? '') !== 'SERIE') {
            $this->errors['id_articulo'] = 'El artículo debe estar controlado por número de serie.';
        } elseif (
            intval($articulo['estado_registro'] ?? 0) !== 1
            && !$this->esArticuloActual($articuloId)
        ) {
            $this->errors['id_articulo'] = 'El artículo seleccionado está inactivo.';
        }

        $serie = trim(strval($this->item['numero_serie_equipo'] ?? ''));
        $this->item['numero_serie_equipo'] = $serie;
        if ($this->textLength($serie) > 100) {
            $this->errors['numero_serie_equipo'] = 'El número de serie no puede superar los 100 caracteres.';
        }

        $patrimonial = trim(strval($this->item['codigo_patrimonial_equipo'] ?? ''));
        $this->item['codigo_patrimonial_equipo'] = $patrimonial;
        if ($this->textLength($patrimonial) > 100) {
            $this->errors['codigo_patrimonial_equipo'] = 'El código patrimonial no puede superar los 100 caracteres.';
        }

        if ($serie === '' && $patrimonial === '') {
            $this->errors['numero_serie_equipo'] = 'Ingresa el número de serie o el código patrimonial del equipo.';
        }

        if ($serie !== '' && DaoEquipos::serieExists($serie, $excludeId)) {
            $this->errors['numero_serie_equipo'] = 'Ya existe un equipo con ese número de serie.';
        }

        if ($patrimonial !== '' && DaoEquipos::patrimonialExists($patrimonial, $excludeId)) {
            $this->errors['codigo_patrimonial_equipo'] = 'Ya existe un equipo con ese código patrimonial.';
        }

        $ubicacionId = intval($this->item['id_ubicacion'] ?? 0);
        $this->item['id_ubicacion'] = strval($ubicacionId);
        $ubicacion = DaoEquipos::getUbicacionById($ubicacionId);
        if ($ubicacionId <= 0 || !$ubicacion) {
            $this->errors['id_ubicacion'] = 'Selecciona una ubicación válida.';
        } elseif (
            intval($ubicacion['estado_ubi'] ?? 0) !== 1
            && !$this->isCurrentUbicacion($ubicacionId)
        ) {
            $this->errors['id_ubicacion'] = 'La ubicación seleccionada está inactiva.';
        }

        $motivoId = intval($this->item['id_motivo'] ?? 0);
        $this->item['id_motivo'] = strval($motivoId);
        if ($motivoId > 0) {
            $motivo = DaoEquipos::getMotivoById($motivoId);
            if (!$motivo) {
                $this->errors['id_motivo'] = 'Selecciona un motivo válido.';
            } elseif (
                intval($motivo['estado_motivo'] ?? 0) !== 1
                && !$this->isCurrentMotivo($motivoId)
            ) {
                $this->errors['id_motivo'] = 'El motivo seleccionado está inactivo.';
            }
        }

        $operativo = strval($this->item['estado_operativo'] ?? '');
        if (!in_array($operativo, self::ESTADOS_OPERATIVOS, true)) {
            $this->errors['estado_operativo'] = 'Selecciona un estado operativo válido.';
        } elseif ($this->mode === 'INS' && $operativo !== 'DISPONIBLE') {
            $this->errors['estado_operativo'] = 'Un equipo nuevo solo puede registrarse como Disponible.';
        } elseif ($this->mode === 'UPD') {
            // Validar coherencia con asignaciones y mantenimientos activos
            $tieneAsignacion = DaoAsignaciones::tieneAsignacionActiva($this->id);
            $tieneMantenimiento = DaoAsignaciones::tieneMantenimientoActivo($this->id);

            if ($tieneAsignacion && $operativo !== 'ASIGNADO') {
                $this->errors['estado_operativo'] = 'El equipo tiene una asignación activa; su estado debe ser Asignado.';
            } elseif ($tieneMantenimiento && $operativo !== 'EN_MANTENIMIENTO') {
                $this->errors['estado_operativo'] = 'El equipo tiene un mantenimiento en proceso; su estado debe ser En mantenimiento.';
            } elseif (!$tieneAsignacion && !$tieneMantenimiento
                && !in_array($operativo, ['DISPONIBLE', 'EN_REVISION', 'DE_BAJA'], true)) {
                $this->errors['estado_operativo'] = 'Selecciona un estado operativo válido.';
            }
        }

        $fechaAdquisicionEntrada = trim(strval($this->item['fecha_adquisicion'] ?? ''));
        $adquisicion = Fechas::normalizarFecha($fechaAdquisicionEntrada);
        if ($fechaAdquisicionEntrada !== '' && $adquisicion === null) {
            $this->errors['fecha_adquisicion'] = 'La fecha de adquisición no es válida.';
        }
        $this->item['fecha_adquisicion'] = $adquisicion ?? '';

        $fechaGarantiaEntrada = trim(strval($this->item['garantia_vencimiento_equipo'] ?? ''));
        $garantia = Fechas::normalizarFecha($fechaGarantiaEntrada);
        if ($fechaGarantiaEntrada !== '' && $garantia === null) {
            $this->errors['garantia_vencimiento_equipo'] = 'La fecha de vencimiento de la garantía no es válida.';
        }
        $this->item['garantia_vencimiento_equipo'] = $garantia ?? '';

        if ($adquisicion !== null && $garantia !== null && $garantia < $adquisicion) {
            $this->errors['garantia_vencimiento_equipo'] = 'La garantía no puede vencer antes de la fecha de adquisición.';
        }

        $proveedor = trim(strval($this->item['proveedor_origen'] ?? ''));
        $this->item['proveedor_origen'] = $proveedor;
        if ($this->textLength($proveedor) > 150) {
            $this->errors['proveedor_origen'] = 'El proveedor no puede superar los 150 caracteres.';
        }

        $observaciones = trim(strval($this->item['observaciones_equipo'] ?? ''));
        $this->item['observaciones_equipo'] = $observaciones;
        if ($this->textLength($observaciones) > 65535) {
            $this->errors['observaciones_equipo'] = 'Las observaciones superan el máximo permitido.';
        }

        $estado = strval($this->item['estado_registro'] ?? '');
        if (!in_array($estado, ['0', '1'], true)) {
            $this->errors['estado_registro'] = 'Selecciona un estado válido.';
        }

        return count($this->errors) === 0;
    }

    /**
     * Registra un equipo nuevo o guarda los cambios del equipo actual.
     */
    private function handleSave(): void
    {
        $serie = trim(strval($this->item['numero_serie_equipo'] ?? ''));
        $patrimonial = trim(strval($this->item['codigo_patrimonial_equipo'] ?? ''));
        $proveedor = trim(strval($this->item['proveedor_origen'] ?? ''));
        $observaciones = trim(strval($this->item['observaciones_equipo'] ?? ''));
        $motivoId = intval($this->item['id_motivo'] ?? 0);

        $data = [
            'id_articulo' => intval($this->item['id_articulo']),
            'numero_serie_equipo' => $serie === '' ? null : $serie,
            'codigo_patrimonial_equipo' => $patrimonial === '' ? null : $patrimonial,
            'fecha_adquisicion' => $this->nullIfEmpty(strval($this->item['fecha_adquisicion'] ?? '')),
            'id_motivo' => $motivoId > 0 ? $motivoId : null,
            'proveedor_origen' => $proveedor === '' ? null : $proveedor,
            'estado_operativo' => strval($this->item['estado_operativo']),
            'id_ubicacion' => intval($this->item['id_ubicacion']),
            'garantia_vencimiento_equipo' => $this->nullIfEmpty(
                strval($this->item['garantia_vencimiento_equipo'] ?? '')
            ),
            'observaciones_equipo' => $observaciones === '' ? null : $observaciones,
            'estado_registro' => intval($this->item['estado_registro']),
        ];

        try {
            if ($this->mode === 'INS') {
                if (!DaoEquipos::insertEquipo($data)) {
                    throw new \RuntimeException('No se pudo registrar el equipo.');
                }
                Bitacora::registrar(
                    'CREAR_EQUIPO',
                    'Equipos',
                    Bitacora::RESULTADO_EXITO,
                    null,
                    null,
                    $data
                );
                Site::redirectTo('index.php?page=Equipos_Equipos&msg=created');
            }

            if ($this->mode === 'UPD') {
                if (!DaoEquipos::updateEquipo($this->id, $data)) {
                    throw new \RuntimeException('No se pudo actualizar el equipo.');
                }
                Bitacora::registrar(
                    'ACTUALIZAR_EQUIPO',
                    'Equipos',
                    Bitacora::RESULTADO_EXITO,
                    $this->id,
                    $this->originalItem,
                    $data
                );
                Site::redirectTo('index.php?page=Equipos_Equipos&msg=updated');
            }
        } catch (\PDOException $ex) {
            if ($ex->getCode() === '23000') {
                $excludeId = $this->mode === 'UPD' ? $this->id : 0;
                if ($serie !== '' && DaoEquipos::serieExists($serie, $excludeId)) {
                    $this->errors['numero_serie_equipo'] = 'Ya existe un equipo con ese número de serie.';
                } elseif ($patrimonial !== '' && DaoEquipos::patrimonialExists($patrimonial, $excludeId)) {
                    $this->errors['codigo_patrimonial_equipo'] = 'Ya existe un equipo con ese código patrimonial.';
                } else {
                    $this->errors['numero_serie_equipo'] = 'Ya existe un equipo con estos datos.';
                }
                return;
            }
            throw $ex;
        }
    }

    /**
     * Desactiva el equipo seleccionado y conserva su historial.
     */
    private function handleDeactivate(): void
    {
        if ($this->id <= 0) {
            throw new \InvalidArgumentException('El equipo solicitado no es válido.');
        }

        if (!DaoEquipos::getEquipoById($this->id)) {
            throw new \RuntimeException('No se encontró el equipo solicitado.');
        }

        // No se puede desactivar un equipo con asignación o mantenimiento activo
        if (DaoAsignaciones::tieneAsignacionActiva($this->id)) {
            throw new \RuntimeException(
                'No se puede desactivar el equipo porque tiene una asignación activa.'
            );
        }
        if (DaoAsignaciones::tieneMantenimientoActivo($this->id)) {
            throw new \RuntimeException(
                'No se puede desactivar el equipo porque tiene un mantenimiento en proceso.'
            );
        }

        if (!DaoEquipos::deactivateEquipo($this->id)) {
            throw new \RuntimeException('No se pudo desactivar el equipo.');
        }

        Bitacora::registrar(
            'DESACTIVAR_EQUIPO',
            'Equipos',
            Bitacora::RESULTADO_EXITO,
            $this->id,
            $this->record,
            ['estado_registro' => 0]
        );

        Site::redirectTo('index.php?page=Equipos_Equipos&msg=deactivated');
    }

    private function renderForm(): void
    {
        $isDelete = $this->mode === 'DEL';
        $selectedArticulo = strval($this->item['id_articulo'] ?? '0');
        $selectedUbicacion = strval($this->item['id_ubicacion'] ?? '0');
        $selectedMotivo = strval($this->item['id_motivo'] ?? '0');

        $formTitle = 'Nuevo equipo';
        $formDescription = 'Registra un equipo serializado y define su ubicación y estado operativo.';
        $submitLabel = 'Registrar equipo';
        $submitIcon = 'fa-plus';

        if ($this->mode === 'UPD') {
            $formTitle = 'Editar equipo';
            $formDescription = 'Actualiza la identificación, ubicación y estado operativo del equipo.';
            $submitLabel = 'Guardar cambios';
            $submitIcon = 'fa-floppy-disk';
        } elseif ($isDelete) {
            $formTitle = 'Desactivar equipo';
            $formDescription = 'El equipo se conservará en el historial, pero dejará de estar disponible para nuevas operaciones.';
            $submitLabel = 'Desactivar equipo';
            $submitIcon = 'fa-ban';
        }

        $formAction = 'index.php?' . http_build_query([
            'page' => 'Equipos_Equipo',
            'mode' => $this->mode,
        ]);

        if ($this->mode !== 'INS') {
            $formAction .= '&id=' . $this->id;
        }

        $recordName = trim(
            strval($this->item['numero_serie_equipo'] ?? '') . ' · '
            . strval($this->item['codigo_patrimonial_equipo'] ?? '')
        );
        if ($recordName === '·') {
            $recordName = 'Equipo #' . $this->id;
        }

        Renderer::render('equipos/equipo', [
            'FormEyebrow' => 'Activos / Equipos',
            'FormTitle' => $formTitle,
            'FormDescription' => $formDescription,
            'FormAction' => $formAction,
            'FormCancelUrl' => 'index.php?page=Equipos_Equipos',
            'FormBackLabel' => 'Volver a equipos',
            'FormSubmitLabel' => $submitLabel,
            'FormSubmitIcon' => $submitIcon,
            'FormDanger' => $isDelete,
            'FormRecordName' => $recordName,
            'FormMeta' => $this->buildFormMeta(),
            'FormHasErrors' => count($this->errors) > 0,
            'FormErrorSummary' => count($this->errors) > 0
                ? 'Revisa los campos marcados en rojo.'
                : '',
            'FormToken' => CSRF::token('equipo'),
            'FormMode' => $this->mode,
            'FormId' => $this->id,
            'FormRequired' => $isDelete ? '' : 'required',
            'FormReadonly' => $isDelete ? 'readonly' : '',
            'FormDisabled' => $isDelete ? 'disabled' : '',
            'FormArticulo' => $selectedArticulo,
            'FormSerie' => strval($this->item['numero_serie_equipo'] ?? ''),
            'FormPatrimonial' => strval($this->item['codigo_patrimonial_equipo'] ?? ''),
            'FormFechaAdquisicion' => strval($this->item['fecha_adquisicion'] ?? ''),
            'FormGarantia' => strval($this->item['garantia_vencimiento_equipo'] ?? ''),
            'FormProveedor' => strval($this->item['proveedor_origen'] ?? ''),
            'FormEstadoOperativo' => strval($this->item['estado_operativo'] ?? 'DISPONIBLE'),
            'FormUbicacion' => $selectedUbicacion,
            'FormMotivo' => $selectedMotivo,
            'FormObservaciones' => strval($this->item['observaciones_equipo'] ?? ''),
            'FormEstadoRegistro' => strval($this->item['estado_registro'] ?? '1'),
            'FormArticuloError' => strval($this->errors['id_articulo'] ?? ''),
            'FormSerieError' => strval($this->errors['numero_serie_equipo'] ?? ''),
            'FormPatrimonialError' => strval($this->errors['codigo_patrimonial_equipo'] ?? ''),
            'FormFechaAdquisicionError' => strval($this->errors['fecha_adquisicion'] ?? ''),
            'FormGarantiaError' => strval($this->errors['garantia_vencimiento_equipo'] ?? ''),
            'FormProveedorError' => strval($this->errors['proveedor_origen'] ?? ''),
            'FormEstadoOperativoError' => strval($this->errors['estado_operativo'] ?? ''),
            'FormUbicacionError' => strval($this->errors['id_ubicacion'] ?? ''),
            'FormMotivoError' => strval($this->errors['id_motivo'] ?? ''),
            'FormObservacionesError' => strval($this->errors['observaciones_equipo'] ?? ''),
            'FormEstadoRegistroError' => strval($this->errors['estado_registro'] ?? ''),
            'ArticuloPlaceholderSelected' => intval($selectedArticulo) <= 0 ? 'selected' : '',
            'UbicacionPlaceholderSelected' => intval($selectedUbicacion) <= 0 ? 'selected' : '',
            'MotivoPlaceholderSelected' => intval($selectedMotivo) <= 0 ? 'selected' : '',
            'ArticulosForm' => $this->decorateArticuloOptions($this->articulos, $selectedArticulo),
            'UbicacionesForm' => $this->decorateUbicacionOptions($this->ubicaciones, $selectedUbicacion),
            'MotivosForm' => $this->decorateMotivoOptions($this->motivos, $selectedMotivo),
            'EstadoOperativoOptions' => ArrUtils::marcarOpcionesSeleccionadas(
                $this->buildEstadoOperativoOptions(),
                strval($this->item['estado_operativo'] ?? 'DISPONIBLE')
            ),
            'EstadoOptions' => ArrUtils::marcarOpcionesSeleccionadas(
                [
                    ['value' => '1', 'label' => 'Activo'],
                    ['value' => '0', 'label' => 'Inactivo'],
                ],
                strval($this->item['estado_registro'] ?? '1')
            ),
        ]);
    }

    private function buildEstadoOperativoOptions(): array
    {
        $options = [];
        foreach (self::ESTADOS_OPERATIVOS_LABELS as $value => $label) {
            $options[] = ['value' => $value, 'label' => $label];
        }

        return $options;
    }

    private function decorateArticuloOptions(array $rows, string $selected): array
    {
        $options = [];

        foreach ($rows as $row) {
            $label = strval($row['label'] ?? '');
            $code = trim(strval($row['code'] ?? ''));
            if ($code !== '') {
                $label .= ' (' . $code . ')';
            }
            if (intval($row['status'] ?? 1) !== 1) {
                $label .= ' (inactivo)';
            }

            $value = strval($row['value'] ?? '');
            $options[] = [
                'value' => $value,
                'label' => $label,
                'selected' => $value === $selected ? 'selected' : '',
            ];
        }

        return $options;
    }

    private function decorateUbicacionOptions(array $rows, string $selected): array
    {
        $options = [];

        foreach ($rows as $row) {
            $label = strval($row['label'] ?? '');
            if (intval($row['status'] ?? 1) !== 1) {
                $label .= ' (inactiva)';
            }

            $value = strval($row['value'] ?? '');
            $options[] = [
                'value' => $value,
                'label' => $label,
                'selected' => $value === $selected ? 'selected' : '',
            ];
        }

        return $options;
    }

    private function decorateMotivoOptions(array $rows, string $selected): array
    {
        $options = [];

        foreach ($rows as $row) {
            $label = strval($row['label'] ?? '');
            $applies = strval($row['applies'] ?? '');
            if (isset(self::MOTIVOS_APLICA_LABELS[$applies])) {
                $label .= ' · ' . self::MOTIVOS_APLICA_LABELS[$applies];
            }
            if (intval($row['status'] ?? 1) !== 1) {
                $label .= ' (inactivo)';
            }

            $value = strval($row['value'] ?? '');
            $options[] = [
                'value' => $value,
                'label' => $label,
                'selected' => $value === $selected ? 'selected' : '',
            ];
        }

        return $options;
    }

    private function esArticuloActual(int $id): bool
    {
        return $this->mode === 'UPD'
            && intval($this->originalItem['id_articulo'] ?? 0) === $id;
    }

    private function isCurrentUbicacion(int $id): bool
    {
        return $this->mode === 'UPD'
            && intval($this->originalItem['id_ubicacion'] ?? 0) === $id;
    }

    private function isCurrentMotivo(int $id): bool
    {
        return $this->mode === 'UPD'
            && intval($this->originalItem['id_motivo'] ?? 0) === $id;
    }

    private function buildFormMeta(): string
    {
        if ($this->mode === 'INS') {
            return '';
        }

        $parts = [];
        $article = trim(strval($this->record['nombre_articulo'] ?? ''));
        $location = trim(strval($this->record['nombre_ubicacion'] ?? ''));
        $updated = $this->formatDate(strval($this->record['fecha_adquisicion'] ?? ''));

        if ($article !== '') {
            $parts[] = 'Artículo: ' . $article;
        }
        if ($location !== '') {
            $parts[] = 'Ubicación: ' . $location;
        }
        if ($updated !== '') {
            $parts[] = 'Adquirido: ' . $updated;
        }

        return implode(' · ', $parts);
    }

    private function nullIfEmpty(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function formatDate(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $timestamp = strtotime($value);

        return $timestamp !== false ? date('d/m/Y', $timestamp) : $value;
    }

    private function textLength(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }
}
