<?php

namespace Controllers\Inventario;

use Controllers\PrivateController;
use Dao\Inventario\Entradas as DaoEntradas;
use Utilities\Bitacora;
use Utilities\CSRF;
use Utilities\Security;
use Utilities\Site;
use Views\Renderer;

class Entrada extends PrivateController
{
    private $mode = 'INS';
    private $id = 0;
    private $doc = [];
    private $item = [];
    private $detalles = [];
    private $originalDetalles = [];
    private $errors = [];
    private $lineErrors = [];
    private $tipos = [];
    private $motivos = [];
    private $articulos = [];
    private $equipos = [];

    public function run(): void
    {
        try {
            $this->getParams();
            $this->loadOptions();

            if ($this->isPostBack()) {
                CSRF::requireValid('entrada', $_POST['token'] ?? '');

                if ($this->mode === 'DEL') {
                    $this->handleAnular();
                } elseif ($this->canEdit() && $this->validateData()) {
                    $this->handleSave();
                }
            }

            $this->renderForm();
        } catch (\Exception $ex) {
            Site::redirectToWithMsg(
                'index.php?page=Inventario_Entradas&msg=error',
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
                throw new \InvalidArgumentException('La entrada solicitada no es válida.');
            }

            $row = DaoEntradas::getEntradaById($this->id);
            if (!$row) {
                throw new \RuntimeException('No se encontró la entrada solicitada.');
            }

            $this->doc = $row;
            $this->item = $this->pickItem($row);
            $this->detalles = $this->pickDetalles(DaoEntradas::getDetalles($this->id));
            $this->originalDetalles = $this->detalles;
        } else {
            $this->detalles = [$this->emptyDetalle()];
        }

        if (
            $this->isPostBack()
            && $this->mode !== 'DEL'
            && ($this->mode === 'INS' || strval($this->doc['estado_doc'] ?? '') === 'BORRADOR')
        ) {
            $fields = [
                'id_tipo_doc',
                'fecha_movimiento',
                'id_motivo',
                'proveedor_origen',
                'observaciones',
            ];

            foreach ($fields as $field) {
                if (array_key_exists($field, $_POST) && is_string($_POST[$field])) {
                    $this->item[$field] = trim($_POST[$field]);
                }
            }

            $this->detalles = $this->readDetallesFromPost();
        }
    }

    private function initializeItem(): void
    {
        $this->item = [
            'id_tipo_doc' => '1',
            'fecha_movimiento' => date('Y-m-d\TH:i'),
            'id_motivo' => '0',
            'proveedor_origen' => '',
            'observaciones' => '',
        ];
    }

    private function pickItem(array $row): array
    {
        return [
            'id_tipo_doc' => strval($row['id_tipo_doc'] ?? '0'),
            'fecha_movimiento' => $this->toInputDateTime(strval($row['fecha_movimiento'] ?? '')),
            'id_motivo' => $row['id_motivo'] === null ? '0' : strval($row['id_motivo']),
            'proveedor_origen' => strval($row['proveedor_origen'] ?? ''),
            'observaciones' => strval($row['observaciones'] ?? ''),
        ];
    }

    private function pickDetalles(array $rows): array
    {
        $detalles = [];

        foreach ($rows as $row) {
            $detalles[] = [
                'id_articulo' => strval(intval($row['id_articulo'] ?? 0)),
                'cantidad' => strval(intval($row['cantidad'] ?? 0)),
                'id_equipo' => $row['id_equipo'] === null ? '0' : strval(intval($row['id_equipo'])),
                'costo_unitario' => number_format(floatval($row['costo_unitario'] ?? 0), 2, '.', ''),
                'nombre_articulo' => strval($row['nombre_articulo'] ?? ''),
                'codigo_interno_articulo' => strval($row['codigo_interno_articulo'] ?? ''),
                'tipo_control' => strval($row['tipo_control'] ?? ''),
                'simbolo_medida' => strval($row['simbolo_medida'] ?? ''),
                'nombre_unidad' => strval($row['nombre_unidad'] ?? ''),
                'numero_serie_equipo' => strval($row['numero_serie_equipo'] ?? ''),
                'codigo_patrimonial_equipo' => strval($row['codigo_patrimonial_equipo'] ?? ''),
            ];
        }

        return count($detalles) > 0 ? $detalles : [$this->emptyDetalle()];
    }

    private function readDetallesFromPost(): array
    {
        $raw = $_POST['detalles'] ?? [];
        if (!is_array($raw)) {
            return [$this->emptyDetalle()];
        }

        $detalles = [];
        foreach ($raw as $row) {
            if (!is_array($row)) {
                continue;
            }

            $detalles[] = [
                'id_articulo' => strval(intval($row['id_articulo'] ?? 0)),
                'cantidad' => trim(strval($row['cantidad'] ?? '')),
                'id_equipo' => strval(intval($row['id_equipo'] ?? 0)),
                'costo_unitario' => trim(strval($row['costo_unitario'] ?? '')),
                'nombre_articulo' => '',
                'codigo_interno_articulo' => '',
                'tipo_control' => '',
                'simbolo_medida' => '',
                'nombre_unidad' => '',
                'numero_serie_equipo' => '',
                'codigo_patrimonial_equipo' => '',
            ];
        }

        return count($detalles) > 0 ? $detalles : [$this->emptyDetalle()];
    }

    private function emptyDetalle(): array
    {
        return [
            'id_articulo' => '0',
            'cantidad' => '1',
            'id_equipo' => '0',
            'costo_unitario' => '0.00',
            'nombre_articulo' => '',
            'codigo_interno_articulo' => '',
            'tipo_control' => '',
            'simbolo_medida' => '',
            'nombre_unidad' => '',
            'numero_serie_equipo' => '',
            'codigo_patrimonial_equipo' => '',
        ];
    }

    private function loadOptions(): void
    {
        $includeTipo = $this->mode !== 'INS' ? intval($this->doc['id_tipo_doc'] ?? 0) : 0;
        $includeMotivo = $this->mode !== 'INS' ? intval($this->item['id_motivo'] ?? 0) : 0;
        $includeArticulos = [];

        foreach ($this->detalles as $detalle) {
            $includeArticulos[] = intval($detalle['id_articulo'] ?? 0);
        }

        $this->tipos = DaoEntradas::getTiposDocumento(true, $includeTipo);
        $this->motivos = DaoEntradas::getMotivosEntrada(true, $includeMotivo);
        $this->articulos = DaoEntradas::getArticulosOptions($includeArticulos);
        $this->equipos = DaoEntradas::getEquiposDisponibles();

        if ($this->mode === 'INS' && !$this->isPostBack()) {
            $tipoActual = intval($this->item['id_tipo_doc'] ?? 0);
            $tipoValido = false;
            foreach ($this->tipos as $tipo) {
                if (intval($tipo['value'] ?? 0) === $tipoActual) {
                    $tipoValido = true;
                    break;
                }
            }
            if (!$tipoValido) {
                $this->item['id_tipo_doc'] = strval($this->tipos[0]['value'] ?? '0');
            }
        }
    }

    private function canEdit(): bool
    {
        if ($this->mode === 'DEL') {
            return false;
        }

        return $this->mode === 'INS'
            || strval($this->doc['estado_doc'] ?? '') === 'BORRADOR';
    }

    private function validateData(): bool
    {
        $this->errors = [];
        $this->lineErrors = [];

        $tipoId = intval($this->item['id_tipo_doc'] ?? 0);
        $this->item['id_tipo_doc'] = strval($tipoId);
        $tipo = DaoEntradas::getTipoDocumentoById($tipoId);

        if ($tipoId <= 0 || !$tipo) {
            $this->errors['id_tipo_doc'] = 'Selecciona un tipo de documento válido.';
        } elseif (strval($tipo['naturaleza_doc'] ?? '') !== 'ENTRADA') {
            $this->errors['id_tipo_doc'] = 'El tipo de documento debe corresponder a una entrada.';
        } elseif (
            intval($tipo['estado_doc'] ?? 0) !== 1
            && $tipoId !== intval($this->doc['id_tipo_doc'] ?? 0)
        ) {
            $this->errors['id_tipo_doc'] = 'El tipo de documento seleccionado está inactivo.';
        }

        $fecha = $this->normalizeDateTime(strval($this->item['fecha_movimiento'] ?? ''));
        if ($fecha === null) {
            $this->errors['fecha_movimiento'] = 'Ingresa una fecha y hora válidas.';
        }

        $motivoId = intval($this->item['id_motivo'] ?? 0);
        $this->item['id_motivo'] = strval($motivoId);
        if ($motivoId > 0) {
            $motivo = DaoEntradas::getMotivoById($motivoId);
            if (!$motivo) {
                $this->errors['id_motivo'] = 'Selecciona un motivo válido.';
            } elseif (strval($motivo['motivo_aplica_a'] ?? '') !== 'AJUSTE_ENTRADA') {
                $this->errors['id_motivo'] = 'El motivo seleccionado no aplica a entradas.';
            } elseif (
                intval($motivo['estado_motivo'] ?? 0) !== 1
                && $motivoId !== intval($this->doc['id_motivo'] ?? 0)
            ) {
                $this->errors['id_motivo'] = 'El motivo seleccionado está inactivo.';
            }
        }

        $proveedor = trim(strval($this->item['proveedor_origen'] ?? ''));
        $this->item['proveedor_origen'] = $proveedor;
        if ($this->textLength($proveedor) > 150) {
            $this->errors['proveedor_origen'] = 'El proveedor no puede superar los 150 caracteres.';
        } elseif (
            $proveedor === ''
            && $tipo !== null
            && stripos(strval($tipo['nombre_tipo'] ?? ''), 'compra') !== false
        ) {
            $this->errors['proveedor_origen'] = 'Ingresa el proveedor de origen de la compra.';
        }

        $observaciones = trim(strval($this->item['observaciones'] ?? ''));
        $this->item['observaciones'] = $observaciones;
        if ($this->textLength($observaciones) > 65535) {
            $this->errors['observaciones'] = 'Las observaciones superan el máximo permitido.';
        }

        $detalles = [];
        $equiposUsados = [];
        $hayLinea = false;

        foreach ($this->detalles as $row) {
            if ($this->isRowEmpty($row)) {
                continue;
            }

            $hayLinea = true;
            $lineErrors = [];

            $articuloId = intval($row['id_articulo'] ?? 0);
            $row['id_articulo'] = strval($articuloId);
            $articulo = DaoEntradas::getArticuloById($articuloId);
            $isSerie = false;

            if ($articuloId <= 0 || !$articulo) {
                $lineErrors['articulo'] = 'Selecciona un artículo válido.';
            } elseif (
                intval($articulo['estado_registro'] ?? 0) !== 1
                && !$this->isCurrentArticulo($articuloId)
            ) {
                $lineErrors['articulo'] = 'El artículo seleccionado está inactivo.';
            } else {
                $isSerie = strval($articulo['tipo_control'] ?? '') === 'SERIE';
                $row['nombre_articulo'] = strval($articulo['nombre_articulo'] ?? '');
                $row['codigo_interno_articulo'] = strval($articulo['codigo_interno_articulo'] ?? '');
                $row['tipo_control'] = strval($articulo['tipo_control'] ?? '');
            }

            $cantidadRaw = trim(strval($row['cantidad'] ?? ''));
            if ($cantidadRaw === '' || !preg_match('/^\d+$/', $cantidadRaw) || intval($cantidadRaw) < 1) {
                $lineErrors['cantidad'] = 'Ingresa una cantidad mayor a cero.';
            } elseif ($isSerie && intval($cantidadRaw) !== 1) {
                $lineErrors['cantidad'] = 'Los artículos por serie se registran de uno en uno.';
            }

            $equipoId = intval($row['id_equipo'] ?? 0);
            $row['id_equipo'] = strval($equipoId);

            if ($isSerie) {
                if ($equipoId <= 0) {
                    $lineErrors['equipo'] = 'Selecciona el equipo que se está recibiendo.';
                } else {
                    $equipo = DaoEntradas::getEquipoById($equipoId);
                    $row['numero_serie_equipo'] = strval($equipo['numero_serie_equipo'] ?? '');
                    $row['codigo_patrimonial_equipo'] = strval($equipo['codigo_patrimonial_equipo'] ?? '');

                    if (!$equipo) {
                        $lineErrors['equipo'] = 'El equipo seleccionado no existe.';
                    } elseif (intval($equipo['estado_registro'] ?? 0) !== 1) {
                        $lineErrors['equipo'] = 'El equipo seleccionado está inactivo.';
                    } elseif (intval($equipo['id_articulo'] ?? 0) !== $articuloId) {
                        $lineErrors['equipo'] = 'El equipo no corresponde al artículo seleccionado.';
                    } elseif (isset($equiposUsados[$equipoId])) {
                        $lineErrors['equipo'] = 'El equipo está repetido en este documento.';
                    } elseif (DaoEntradas::equipoUsadoEnConfirmado($equipoId)) {
                        $lineErrors['equipo'] = 'El equipo ya fue registrado en otra entrada confirmada.';
                    } else {
                        $equiposUsados[$equipoId] = true;
                    }
                }
            } elseif ($equipoId > 0) {
                $lineErrors['equipo'] = 'Solo los artículos controlados por serie llevan equipo.';
            }

            $costoRaw = trim(strval($row['costo_unitario'] ?? ''));
            if ($costoRaw === '') {
                $costoRaw = '0.00';
            }
            if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $costoRaw)) {
                $lineErrors['costo'] = 'Ingresa un costo válido con máximo dos decimales.';
            }
            $row['costo_unitario'] = $costoRaw;

            $index = count($detalles);
            $detalles[] = $row;
            if (count($lineErrors) > 0) {
                $this->lineErrors[$index] = $lineErrors;
            }
        }

        if (!$hayLinea) {
            $this->errors['detalles'] = 'Agrega al menos una línea de detalle.';
        } elseif (count($this->lineErrors) > 0) {
            $this->errors['detalles'] = 'Revisa las líneas marcadas en rojo.';
        }

        $this->detalles = count($detalles) > 0 ? $detalles : [$this->emptyDetalle()];

        return count($this->errors) === 0;
    }

    private function handleSave(): void
    {
        $confirm = strval($_POST['accion'] ?? '') === 'confirmar';
        $userId = intval(Security::getUserId());
        $fecha = $this->normalizeDateTime(strval($this->item['fecha_movimiento'] ?? ''));

        if ($fecha === null) {
            throw new \RuntimeException('La fecha del movimiento no es válida.');
        }

        $motivoId = intval($this->item['id_motivo'] ?? 0);
        $proveedor = trim(strval($this->item['proveedor_origen'] ?? ''));
        $observaciones = trim(strval($this->item['observaciones'] ?? ''));

        $data = [
            'id_tipo_doc' => intval($this->item['id_tipo_doc']),
            'fecha_movimiento' => $fecha,
            'id_usuario' => $userId,
            'id_motivo' => $motivoId > 0 ? $motivoId : null,
            'proveedor_origen' => $proveedor === '' ? null : $proveedor,
            'estado_doc' => 'BORRADOR',
            'observaciones' => $observaciones === '' ? null : $observaciones,
        ];

        $detalles = [];
        foreach ($this->detalles as $row) {
            if ($this->isRowEmpty($row)) {
                continue;
            }

            $detalles[] = [
                'id_articulo' => intval($row['id_articulo']),
                'cantidad' => intval($row['cantidad']),
                'id_equipo' => intval($row['id_equipo']) > 0 ? intval($row['id_equipo']) : null,
                'costo_unitario' => number_format(
                    floatval(strval($row['costo_unitario'] ?? '0')),
                    2,
                    '.',
                    ''
                ),
            ];
        }

        $before = null;

        DaoEntradas::beginTransaction();

        try {
            if ($this->mode === 'INS') {
                $docId = DaoEntradas::insertDocumento($data);
                DaoEntradas::insertDetalles($docId, $detalles);
            } else {
                $current = DaoEntradas::getEntradaById($this->id);
                if (!$current || strval($current['estado_doc'] ?? '') !== 'BORRADOR') {
                    throw new \RuntimeException('Solo se pueden editar documentos en borrador.');
                }

                $before = $current;
                if (!DaoEntradas::updateDocumento($this->id, $data)) {
                    throw new \RuntimeException('No se pudo actualizar el documento de entrada.');
                }

                DaoEntradas::replaceDetalles($this->id, $detalles);
                $docId = $this->id;
            }

            if ($confirm) {
                DaoEntradas::confirmar($docId, $userId);
            }

            DaoEntradas::commit();
        } catch (\Throwable $ex) {
            DaoEntradas::rollback();
            throw $ex;
        }

        if ($confirm) {
            $tipoOperacion = 'CONFIRMAR_ENTRADA';
        } elseif ($this->mode === 'INS') {
            $tipoOperacion = 'CREAR_ENTRADA';
        } else {
            $tipoOperacion = 'ACTUALIZAR_ENTRADA';
        }

        $auditData = $data;
        $auditData['estado_doc'] = $confirm ? 'CONFIRMADO' : 'BORRADOR';
        $auditData['detalles'] = $detalles;

        Bitacora::registrar(
            $tipoOperacion,
            'Inventario',
            Bitacora::RESULTADO_EXITO,
            intval($docId),
            $before,
            $auditData
        );

        $msg = $confirm ? 'confirmed' : ($this->mode === 'INS' ? 'created' : 'updated');
        Site::redirectTo('index.php?page=Inventario_Entradas&msg=' . $msg);
    }

    private function handleAnular(): void
    {
        if ($this->id <= 0) {
            throw new \InvalidArgumentException('La entrada solicitada no es válida.');
        }

        $doc = DaoEntradas::getEntradaById($this->id);
        if (!$doc) {
            throw new \RuntimeException('No se encontró la entrada solicitada.');
        }

        if (strval($doc['estado_doc'] ?? '') === 'ANULADO') {
            throw new \RuntimeException('El documento ya está anulado.');
        }

        $userId = intval(Security::getUserId());

        DaoEntradas::beginTransaction();

        try {
            DaoEntradas::anular($this->id, $userId);
            DaoEntradas::commit();
        } catch (\Throwable $ex) {
            DaoEntradas::rollback();
            throw $ex;
        }

        Bitacora::registrar(
            'ANULAR_ENTRADA',
            'Inventario',
            Bitacora::RESULTADO_EXITO,
            $this->id,
            $doc,
            ['estado_doc' => 'ANULADO']
        );

        Site::redirectTo('index.php?page=Inventario_Entradas&msg=annulled');
    }

    private function renderForm(): void
    {
        $isDelete = $this->mode === 'DEL';
        $canEdit = $this->canEdit();
        $estado = strval($this->doc['estado_doc'] ?? '');

        $formTitle = 'Nueva entrada de inventario';
        $formDescription = 'Registra la recepción de artículos. Puedes guardar un borrador y confirmarlo después.';

        if ($isDelete) {
            $formTitle = 'Anular entrada';
            $formDescription = 'Revisa el documento antes de anularlo. La acción no elimina el historial.';
        } elseif ($this->mode === 'UPD') {
            if ($estado === 'BORRADOR') {
                $formTitle = 'Editar entrada';
                $formDescription = 'Actualiza el borrador y confírmalo cuando el detalle esté completo.';
            } elseif ($estado === 'CONFIRMADO') {
                $formTitle = 'Entrada confirmada';
                $formDescription = 'Este documento ya actualizó el inventario y su detalle no puede editarse.';
            } else {
                $formTitle = 'Entrada anulada';
                $formDescription = 'Este documento fue anulado. Se conserva únicamente como historial.';
            }
        }

        $formParams = [
            'page' => 'Inventario_Entrada',
            'mode' => $this->mode,
        ];
        if ($this->mode !== 'INS') {
            $formParams['id'] = $this->id;
        }

        $detallesView = [];
        $totalUnidades = 0;
        $totalCosto = 0.0;

        foreach ($this->detalles as $index => $row) {
            $articuloId = intval($row['id_articulo'] ?? 0);
            $isSerie = strval($row['tipo_control'] ?? '') === 'SERIE';
            $cantidad = intval($row['cantidad'] ?? 0);
            $costo = floatval($row['costo_unitario'] ?? 0);
            $subtotal = $cantidad * $costo;
            $lineErrors = $this->lineErrors[$index] ?? [];

            if (!$this->isRowEmpty($row)) {
                $totalUnidades += $cantidad;
                $totalCosto += $subtotal;
            }

            $equipoLabel = trim(
                strval($row['numero_serie_equipo'] ?? '') . ' · '
                . strval($row['codigo_patrimonial_equipo'] ?? '')
            );
            $equipoLabel = trim($equipoLabel, " ·");
            if ($isSerie && $equipoLabel === '') {
                $equipoLabel = 'Equipo #' . intval($row['id_equipo'] ?? 0);
            }
            if (!$isSerie) {
                $equipoLabel = 'No aplica';
            }

            $detallesView[] = [
                'index' => $index,
                'articuloOptions' => $this->buildArticuloOptionsHtml($articuloId),
                'equipoOptions' => $isSerie
                    ? $this->buildEquipoOptionsHtml($articuloId, intval($row['id_equipo'] ?? 0))
                    : '',
                'cantidad' => strval($row['cantidad'] ?? ''),
                'costo' => strval($row['costo_unitario'] ?? ''),
                'subtotal' => $this->formatMoney($subtotal),
                'cantidadReadonly' => $isSerie ? 'readonly' : '',
                'equipoDisabled' => $isSerie ? '' : 'disabled',
                'articuloError' => strval($lineErrors['articulo'] ?? ''),
                'cantidadError' => strval($lineErrors['cantidad'] ?? ''),
                'equipoError' => strval($lineErrors['equipo'] ?? ''),
                'costoError' => strval($lineErrors['costo'] ?? ''),
                'articuloNombre' => strval($row['nombre_articulo'] ?? '') !== ''
                    ? strval($row['nombre_articulo'])
                    : 'Artículo #' . $articuloId,
                'articuloCodigo' => strval($row['codigo_interno_articulo'] ?? ''),
                'unidadLabel' => strval($row['simbolo_medida'] ?? '') !== ''
                    ? strval($row['simbolo_medida'])
                    : strval($row['nombre_unidad'] ?? ''),
                'equipoLabel' => $equipoLabel,
                'costoFmt' => $this->formatMoney($costo),
            ];
        }

        $tipoId = intval($this->item['id_tipo_doc'] ?? 0);
        $motivoId = intval($this->item['id_motivo'] ?? 0);
        $selectedTipo = strval($tipoId);
        $selectedMotivo = strval($motivoId);

        $formMeta = '';
        if ($this->mode === 'UPD' || $isDelete) {
            $usuario = trim(strval($this->doc['usuario_nombre'] ?? ''));
            $creado = $this->formatDateTime(strval($this->doc['fecha_creacion'] ?? ''));
            $lineas = intval($this->doc['lineas'] ?? count($this->detalles));
            $formMeta = 'Registrada' . ($usuario !== '' ? ' por ' . $usuario : '')
                . ($creado !== '' ? ' el ' . $creado : '')
                . ' · ' . $lineas . ($lineas === 1 ? ' línea' : ' líneas');
        }

        $formWarning = '';
        if ($isDelete) {
            if ($estado === 'CONFIRMADO') {
                $formWarning = 'Este documento está confirmado. Al anularlo se descontarán '
                    . $totalUnidades . ($totalUnidades === 1 ? ' unidad' : ' unidades')
                    . ' del inventario y se registrará un ajuste negativo en el Kardex.';
            } else {
                $formWarning = 'Este documento está en borrador. Se anulará sin afectar las existencias.';
            }
        }

        Renderer::render('inventario/entrada', [
            'FormEyebrow' => 'Inventario / Entradas',
            'FormTitle' => $formTitle,
            'FormDescription' => $formDescription,
            'FormAction' => 'index.php?' . http_build_query($formParams),
            'FormCancelUrl' => 'index.php?page=Inventario_Entradas',
            'FormBackLabel' => 'Volver a entradas',
            'FormDanger' => $isDelete,
            'FormMeta' => $formMeta,
            'FormWarning' => $formWarning,
            'FormHasErrors' => count($this->errors) > 0,
            'FormErrorSummary' => count($this->errors) > 0
                ? 'Revisa los campos marcados en rojo e intenta de nuevo.'
                : '',
            'FormDetallesError' => strval($this->errors['detalles'] ?? ''),
            'FormToken' => CSRF::token('entrada'),
            'FormMode' => $this->mode,
            'FormId' => $this->id,
            'CanEdit' => $canEdit,
            'ShowAnnul' => !$isDelete
                && $this->id > 0
                && $estado !== 'ANULADO',
            'AnnulUrl' => 'index.php?page=Inventario_Entrada&mode=DEL&id=' . $this->id,
            'FormEstadoLabel' => $this->estadoLabel($estado),
            'FormEstadoClass' => $this->estadoClass($estado),
            'FormCorrelativoDisplay' => strval($this->doc['correlativo'] ?? '') !== ''
                ? strval($this->doc['correlativo'])
                : 'Se generará al guardar',
            'FormRequired' => $canEdit ? 'required' : '',
            'FormReadonly' => $canEdit ? '' : 'readonly',
            'FormDisabled' => $canEdit ? '' : 'disabled',
            'FormTipo' => $selectedTipo,
            'FormFecha' => strval($this->item['fecha_movimiento'] ?? ''),
            'FormMotivo' => $selectedMotivo,
            'FormProveedor' => strval($this->item['proveedor_origen'] ?? ''),
            'FormObservaciones' => strval($this->item['observaciones'] ?? ''),
            'FormTipoError' => strval($this->errors['id_tipo_doc'] ?? ''),
            'FormFechaError' => strval($this->errors['fecha_movimiento'] ?? ''),
            'FormMotivoError' => strval($this->errors['id_motivo'] ?? ''),
            'FormProveedorError' => strval($this->errors['proveedor_origen'] ?? ''),
            'FormObservacionesError' => strval($this->errors['observaciones'] ?? ''),
            'TiposForm' => $this->decorateOptions($this->tipos, $selectedTipo),
            'MotivosForm' => $this->decorateOptions($this->motivos, $selectedMotivo),
            'TipoPlaceholderSelected' => $tipoId <= 0 ? 'selected' : '',
            'MotivoPlaceholderSelected' => $motivoId <= 0 ? 'selected' : '',
            'Detalles' => $detallesView,
            'DetallesCount' => count($detallesView),
            'TotalUnidades' => $totalUnidades,
            'TotalCosto' => $this->formatMoney($totalCosto),
            'ArticulosOptionsHtml' => $this->buildArticuloOptionsHtml(0),
            'ArticulosJson' => $this->buildArticulosJson(),
            'EquiposJson' => $this->buildEquiposJson(),
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

    private function buildArticuloOptionsHtml(int $selectedId): string
    {
        $html = '';

        foreach ($this->articulos as $row) {
            $value = strval($row['value'] ?? '');
            $label = strval($row['label'] ?? '');
            $code = trim(strval($row['code'] ?? ''));

            if ($code !== '') {
                $label .= ' (' . $code . ')';
            }
            if (intval($row['status'] ?? 1) !== 1) {
                $label .= ' (inactivo)';
            }

            $html .= '<option value="' . $this->escape($value) . '"'
                . ($value === strval($selectedId) ? ' selected' : '')
                . '>' . $this->escape($label) . '</option>';
        }

        return $html;
    }

    private function buildEquipoOptionsHtml(int $articuloId, int $selectedId): string
    {
        $html = '';

        foreach ($this->equipos as $row) {
            if (intval($row['id_articulo'] ?? 0) !== $articuloId) {
                continue;
            }

            $value = strval($row['value'] ?? '');
            $html .= '<option value="' . $this->escape($value) . '"'
                . (intval($value) === $selectedId ? ' selected' : '')
                . '>' . $this->escape($this->equipmentLabel($row)) . '</option>';
        }

        return $html;
    }

    private function buildArticulosJson(): string
    {
        $data = [];
        foreach ($this->articulos as $row) {
            $data[strval($row['value'] ?? '')] = [
                'serie' => strval($row['tipo_control'] ?? '') === 'SERIE',
            ];
        }

        return json_encode(
            $data,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );
    }

    private function buildEquiposJson(): string
    {
        $data = [];
        foreach ($this->equipos as $row) {
            $data[] = [
                'id' => intval($row['value'] ?? 0),
                'article' => intval($row['id_articulo'] ?? 0),
                'label' => $this->equipmentLabel($row),
            ];
        }

        return json_encode(
            $data,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );
    }

    private function equipmentLabel(array $row): string
    {
        $serie = trim(strval($row['numero_serie_equipo'] ?? ''));
        $patrimonial = trim(strval($row['codigo_patrimonial_equipo'] ?? ''));

        if ($serie === '' && $patrimonial === '') {
            return 'Equipo #' . intval($row['value'] ?? 0);
        }

        $label = $serie !== '' ? $serie : 'Sin serie';
        if ($patrimonial !== '') {
            $label .= ' · ' . $patrimonial;
        }

        return $label;
    }

    private function isRowEmpty(array $row): bool
    {
        if (intval($row['id_articulo'] ?? 0) > 0 || intval($row['id_equipo'] ?? 0) > 0) {
            return false;
        }

        $cantidad = trim(strval($row['cantidad'] ?? ''));
        $costo = trim(strval($row['costo_unitario'] ?? ''));

        $cantidadVacia = in_array($cantidad, ['', '0', '1'], true);
        $costoVacio = in_array(str_replace(',', '.', $costo), ['', '0', '0.0', '0.00'], true);

        return $cantidadVacia && $costoVacio;
    }

    private function isCurrentArticulo(int $articuloId): bool
    {
        foreach ($this->originalDetalles as $detalle) {
            if (intval($detalle['id_articulo'] ?? 0) === $articuloId) {
                return true;
            }
        }

        return false;
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

    private function formatMoney(float $value): string
    {
        return number_format($value, 2, '.', ',');
    }

    private function estadoLabel(string $estado): string
    {
        $labels = [
            'BORRADOR' => 'Borrador',
            'CONFIRMADO' => 'Confirmado',
            'ANULADO' => 'Anulado',
        ];

        return $labels[$estado] ?? '';
    }

    private function estadoClass(string $estado): string
    {
        $classes = [
            'BORRADOR' => 'badge--warning',
            'CONFIRMADO' => 'badge--success',
            'ANULADO' => 'badge--danger',
        ];

        return $classes[$estado] ?? 'badge--outline';
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
