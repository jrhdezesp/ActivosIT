<?php

namespace Controllers\Inventario;

use Controllers\PrivateController;
use Dao\Inventario\Salidas as DaoSalidas;
use Utilities\Bitacora;
use Utilities\CSRF;
use Utilities\Fechas;
use Utilities\DetallesInventario;
use Utilities\Security;
use Utilities\Site;
use Views\Renderer;

/**
 * Atiende el formulario para registrar o corregir una salida de inventario.
 */
class Salida extends PrivateController
{
    private $mode = 'INS';
    private $id = 0;
    // Datos generales del documento que se presenta en el formulario.
    private $doc = [];
    private $item = [];
    // Lineas actuales y originales, usadas para revisar los cambios del documento.
    private $detalles = [];
    private $originalDetalles = [];
    // Mensajes de revision del documento y de cada linea.
    private $errors = [];
    private $lineErrors = [];
    private $tipos = [];
    private $motivos = [];
    private $articulos = [];
    private $equipos = [];

    /**
     * Carga el documento, procesa el formulario y muestra su estado actual.
     */
    public function run(): void
    {
        try {
            $this->getParams();
            if ($this->mode === 'DEL') {
                $this->requireAction('Acciones.Inventario.Salidas.Anular');
            } elseif ($this->mode === 'UPD') {
                $this->requireAction('Acciones.Inventario.Salidas.Editar');
            } else {
                $this->requireAction('Acciones.Inventario.Salidas.Crear');
            }
            if ($this->isPostBack() && strval($_POST['accion'] ?? '') === 'confirmar') {
                $this->requireAction('Acciones.Inventario.Salidas.Confirmar');
            }
            $this->loadOptions();

            if ($this->isPostBack()) {
                CSRF::requireValid('salida', $_POST['token'] ?? '');

                if ($this->mode === 'DEL') {
                    $this->handleAnular();
                } elseif ($this->canEdit() && $this->validateData()) {
                    $this->handleSave();
                }
            }

            $this->renderForm();
        } catch (\Exception $ex) {
            Site::redirectToWithMsg(
                'index.php?page=Inventario_Salidas&msg=error',
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
                throw new \InvalidArgumentException('La salida solicitada no es válida.');
            }

            $row = DaoSalidas::getSalidaById($this->id);
            if (!$row) {
                throw new \RuntimeException('No se encontró la salida solicitada.');
            }

            $this->doc = $row;
            $this->item = $this->pickItem($row);
            $this->detalles = $this->pickDetalles(DaoSalidas::getDetalles($this->id));
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
                'destino_salida',
                'autorizacion',
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
            'id_tipo_doc' => '3',
            'fecha_movimiento' => date('Y-m-d\TH:i'),
            'id_motivo' => '0',
            'destino_salida' => '',
            'autorizacion' => '',
            'observaciones' => '',
        ];
    }

    private function pickItem(array $row): array
    {
        return [
            'id_tipo_doc' => strval($row['id_tipo_doc'] ?? '0'),
            'fecha_movimiento' => Fechas::paraCampoFechaHora(strval($row['fecha_movimiento'] ?? '')),
            'id_motivo' => $row['id_motivo'] === null ? '0' : strval($row['id_motivo']),
            'destino_salida' => strval($row['destino_salida'] ?? ''),
            'autorizacion' => strval($row['autorizacion'] ?? ''),
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
                'nombre_articulo' => strval($row['nombre_articulo'] ?? ''),
                'codigo_interno_articulo' => strval($row['codigo_interno_articulo'] ?? ''),
                'tipo_control' => strval($row['tipo_control'] ?? ''),
                'stock' => strval(intval($row['existencia_actual'] ?? 0)),
                'simbolo_medida' => strval($row['simbolo_medida'] ?? ''),
                'nombre_unidad' => strval($row['nombre_unidad'] ?? ''),
                'numero_serie_equipo' => strval($row['numero_serie_equipo'] ?? ''),
                'codigo_patrimonial_equipo' => strval($row['codigo_patrimonial_equipo'] ?? ''),
                'estado_operativo' => strval($row['estado_operativo'] ?? ''),
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
                'nombre_articulo' => '',
                'codigo_interno_articulo' => '',
                'tipo_control' => '',
                'stock' => '0',
                'simbolo_medida' => '',
                'nombre_unidad' => '',
                'numero_serie_equipo' => '',
                'codigo_patrimonial_equipo' => '',
                'estado_operativo' => '',
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
            'nombre_articulo' => '',
            'codigo_interno_articulo' => '',
            'tipo_control' => '',
            'stock' => '0',
            'simbolo_medida' => '',
            'nombre_unidad' => '',
            'numero_serie_equipo' => '',
            'codigo_patrimonial_equipo' => '',
            'estado_operativo' => '',
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

        $this->tipos = DaoSalidas::getTiposDocumento(true, $includeTipo);
        $this->motivos = DaoSalidas::getMotivosSalida(true, $includeMotivo);
        $this->articulos = DaoSalidas::getArticulosOptions($includeArticulos);
        $this->equipos = DaoSalidas::getEquiposDisponibles();

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

    /**
     * Revisa los datos y las lineas antes de registrar la salida.
     */
    private function validateData(): bool
    {
        $this->errors = [];
        $this->lineErrors = [];

        $tipoId = intval($this->item['id_tipo_doc'] ?? 0);
        $this->item['id_tipo_doc'] = strval($tipoId);
        $tipo = DaoSalidas::getTipoDocumentoById($tipoId);

        if ($tipoId <= 0 || !$tipo) {
            $this->errors['id_tipo_doc'] = 'Selecciona un tipo de documento válido.';
        } elseif (strval($tipo['naturaleza_doc'] ?? '') !== 'SALIDA') {
            $this->errors['id_tipo_doc'] = 'El tipo de documento debe corresponder a una salida.';
        } elseif (
            intval($tipo['estado_doc'] ?? 0) !== 1
            && $tipoId !== intval($this->doc['id_tipo_doc'] ?? 0)
        ) {
            $this->errors['id_tipo_doc'] = 'El tipo de documento seleccionado está inactivo.';
        }

        $fecha = Fechas::normalizarFechaHora(strval($this->item['fecha_movimiento'] ?? ''));
        if ($fecha === null) {
            $this->errors['fecha_movimiento'] = 'Ingresa una fecha y hora válidas.';
        }

        $motivoId = intval($this->item['id_motivo'] ?? 0);
        $this->item['id_motivo'] = strval($motivoId);
        if ($motivoId > 0) {
            $motivo = DaoSalidas::getMotivoById($motivoId);
            if (!$motivo) {
                $this->errors['id_motivo'] = 'Selecciona un motivo válido.';
            } elseif (
                !in_array(strval($motivo['motivo_aplica_a'] ?? ''), ['AJUSTE_SALIDA', 'BAJA'], true)
            ) {
                $this->errors['id_motivo'] = 'El motivo seleccionado no aplica a salidas.';
            } elseif (
                intval($motivo['estado_motivo'] ?? 0) !== 1
                && $motivoId !== intval($this->doc['id_motivo'] ?? 0)
            ) {
                $this->errors['id_motivo'] = 'El motivo seleccionado está inactivo.';
            }
        }

        $destino = trim(strval($this->item['destino_salida'] ?? ''));
        $this->item['destino_salida'] = $destino;
        if ($destino === '') {
            $this->errors['destino_salida'] = 'Ingresa el destino de la salida.';
        } elseif ($this->textLength($destino) > 255) {
            $this->errors['destino_salida'] = 'El destino no puede superar los 255 caracteres.';
        }

        $autorizacion = trim(strval($this->item['autorizacion'] ?? ''));
        $this->item['autorizacion'] = $autorizacion;
        if ($this->textLength($autorizacion) > 255) {
            $this->errors['autorizacion'] = 'La autorización no puede superar los 255 caracteres.';
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
            $articulo = DaoSalidas::getArticuloById($articuloId);
            $isSerie = false;

            if ($articuloId <= 0 || !$articulo) {
                $lineErrors['articulo'] = 'Selecciona un artículo válido.';
            } elseif (
                intval($articulo['estado_registro'] ?? 0) !== 1
                && !DetallesInventario::contieneArticulo($this->originalDetalles, $articuloId)
            ) {
                $lineErrors['articulo'] = 'El artículo seleccionado está inactivo.';
            } else {
                $isSerie = strval($articulo['tipo_control'] ?? '') === 'SERIE';
                $row['nombre_articulo'] = strval($articulo['nombre_articulo'] ?? '');
                $row['codigo_interno_articulo'] = strval($articulo['codigo_interno_articulo'] ?? '');
                $row['tipo_control'] = strval($articulo['tipo_control'] ?? '');
                $row['stock'] = strval(intval($articulo['existencia_actual'] ?? 0));
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
                    $lineErrors['equipo'] = 'Selecciona el equipo que sale del inventario.';
                } else {
                    $equipo = DaoSalidas::getEquipoById($equipoId);
                    $row['numero_serie_equipo'] = strval($equipo['numero_serie_equipo'] ?? '');
                    $row['codigo_patrimonial_equipo'] = strval($equipo['codigo_patrimonial_equipo'] ?? '');
                    $row['estado_operativo'] = strval($equipo['estado_operativo'] ?? '');

                    if (!$equipo) {
                        $lineErrors['equipo'] = 'El equipo seleccionado no existe.';
                    } elseif (intval($equipo['estado_registro'] ?? 0) !== 1) {
                        $lineErrors['equipo'] = 'El equipo seleccionado está inactivo.';
                    } elseif (intval($equipo['id_articulo'] ?? 0) !== $articuloId) {
                        $lineErrors['equipo'] = 'El equipo no corresponde al artículo seleccionado.';
                    } elseif (strval($equipo['estado_operativo'] ?? '') !== 'DISPONIBLE') {
                        $lineErrors['equipo'] = 'El equipo ya no está disponible para salir.';
                    } elseif (isset($equiposUsados[$equipoId])) {
                        $lineErrors['equipo'] = 'El equipo está repetido en este documento.';
                    } elseif (DaoSalidas::equipoUsadoEnSalidaConfirmada($equipoId, $this->id)) {
                        $lineErrors['equipo'] = 'El equipo ya fue registrado en otra salida confirmada.';
                    } else {
                        $equiposUsados[$equipoId] = true;
                    }
                }
            } elseif ($equipoId > 0) {
                $lineErrors['equipo'] = 'Solo los artículos controlados por serie llevan equipo.';
            }

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

    /**
     * Guarda la salida y actualiza el detalle de articulos o equipos.
     */
    private function handleSave(): void
    {
        $confirm = strval($_POST['accion'] ?? '') === 'confirmar';
        $userId = intval(Security::getUserId());
        $fecha = Fechas::normalizarFechaHora(strval($this->item['fecha_movimiento'] ?? ''));

        if ($fecha === null) {
            throw new \RuntimeException('La fecha del movimiento no es válida.');
        }

        $motivoId = intval($this->item['id_motivo'] ?? 0);
        $destino = trim(strval($this->item['destino_salida'] ?? ''));
        $autorizacion = trim(strval($this->item['autorizacion'] ?? ''));
        $observaciones = trim(strval($this->item['observaciones'] ?? ''));

        $data = [
            'id_tipo_doc' => intval($this->item['id_tipo_doc']),
            'fecha_movimiento' => $fecha,
            'id_usuario' => $userId,
            'id_motivo' => $motivoId > 0 ? $motivoId : null,
            'destino_salida' => $destino,
            'autorizacion' => $autorizacion === '' ? null : $autorizacion,
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
            ];
        }

        // El borrador se guarda en su propia transaccion. Si la confirmacion
        // falla (por ejemplo, por existencias), el documento no se pierde.
        $before = null;

        DaoSalidas::beginTransaction();

        try {
            if ($this->mode === 'INS') {
                $docId = DaoSalidas::insertDocumento($data);
                DaoSalidas::insertDetalles($docId, $detalles);
            } else {
                $current = DaoSalidas::getSalidaById($this->id);
                if (!$current || strval($current['estado_doc'] ?? '') !== 'BORRADOR') {
                    throw new \RuntimeException('Solo se pueden editar documentos en borrador.');
                }

                $before = $current;
                if (!DaoSalidas::updateDocumento($this->id, $data)) {
                    throw new \RuntimeException('No se pudo actualizar el documento de salida.');
                }

                DaoSalidas::replaceDetalles($this->id, $detalles);
                $docId = $this->id;
            }

            DaoSalidas::commit();
        } catch (\Throwable $ex) {
            DaoSalidas::rollback();
            throw $ex;
        }

        $auditData = $data;
        $auditData['estado_doc'] = 'BORRADOR';
        $auditData['detalles'] = $detalles;

        Bitacora::registrar(
            $this->mode === 'INS' ? 'CREAR_SALIDA' : 'ACTUALIZAR_SALIDA',
            'Inventario',
            Bitacora::RESULTADO_EXITO,
            intval($docId),
            $before,
            $auditData
        );

        if ($confirm) {
            try {
                DaoSalidas::beginTransaction();
                DaoSalidas::confirmar($docId, $userId);
                DaoSalidas::commit();
            } catch (\Throwable $ex) {
                DaoSalidas::rollback();
                Bitacora::registrar(
                    'CONFIRMAR_SALIDA',
                    'Inventario',
                    Bitacora::RESULTADO_ERROR,
                    intval($docId),
                    ['estado_doc' => 'BORRADOR'],
                    ['error' => $ex->getMessage()]
                );
                Site::redirectToWithMsg(
                    'index.php?page=Inventario_Salida&mode=UPD&id=' . $docId . '&msg=confirm_error',
                    $ex->getMessage()
                );
            }

            Bitacora::registrar(
                'CONFIRMAR_SALIDA',
                'Inventario',
                Bitacora::RESULTADO_EXITO,
                intval($docId),
                ['estado_doc' => 'BORRADOR'],
                ['estado_doc' => 'CONFIRMADO']
            );
        }

        $msg = $confirm ? 'confirmed' : ($this->mode === 'INS' ? 'created' : 'updated');
        Site::redirectTo('index.php?page=Inventario_Salidas&msg=' . $msg);
    }

    /**
     * Anula una salida existente cuando el proceso lo permite.
     */
    private function handleAnular(): void
    {
        if ($this->id <= 0) {
            throw new \InvalidArgumentException('La salida solicitada no es válida.');
        }

        $doc = DaoSalidas::getSalidaById($this->id);
        if (!$doc) {
            throw new \RuntimeException('No se encontró la salida solicitada.');
        }

        if (strval($doc['estado_doc'] ?? '') === 'ANULADO') {
            throw new \RuntimeException('El documento ya está anulado.');
        }

        $userId = intval(Security::getUserId());

        DaoSalidas::beginTransaction();

        try {
            DaoSalidas::anular($this->id, $userId);
            DaoSalidas::commit();
        } catch (\Throwable $ex) {
            DaoSalidas::rollback();
            throw $ex;
        }

        Bitacora::registrar(
            'ANULAR_SALIDA',
            'Inventario',
            Bitacora::RESULTADO_EXITO,
            $this->id,
            $doc,
            ['estado_doc' => 'ANULADO']
        );

        Site::redirectTo('index.php?page=Inventario_Salidas&msg=annulled');
    }

    private function renderForm(): void
    {
        $isDelete = $this->mode === 'DEL';
        $canEdit = $this->canEdit();
        $estado = strval($this->doc['estado_doc'] ?? '');

        $formTitle = 'Nueva salida de inventario';
        $formDescription = 'Registra el despacho, la baja o el ajuste negativo de artículos. Puedes guardar un borrador y confirmarlo después.';

        if ($isDelete) {
            $formTitle = 'Anular salida';
            $formDescription = 'Revisa el documento antes de anularlo. La acción no elimina el historial.';
        } elseif ($this->mode === 'UPD') {
            if ($estado === 'BORRADOR') {
                $formTitle = 'Editar salida';
                $formDescription = 'Actualiza el borrador y confírmalo cuando el detalle esté completo.';
            } elseif ($estado === 'CONFIRMADO') {
                $formTitle = 'Salida confirmada';
                $formDescription = 'Este documento ya descontó existencias y su detalle no puede editarse.';
            } else {
                $formTitle = 'Salida anulada';
                $formDescription = 'Este documento fue anulado. Se conserva únicamente como historial.';
            }
        }

        $formParams = [
            'page' => 'Inventario_Salida',
            'mode' => $this->mode,
        ];
        if ($this->mode !== 'INS') {
            $formParams['id'] = $this->id;
        }

        $detallesView = [];
        $totalUnidades = 0;
        $totalLineas = 0;

        foreach ($this->detalles as $index => $row) {
            $articuloId = intval($row['id_articulo'] ?? 0);
            $isSerie = strval($row['tipo_control'] ?? '') === 'SERIE';
            $cantidad = intval($row['cantidad'] ?? 0);
            $stock = intval($row['stock'] ?? 0);
            $lineErrors = $this->lineErrors[$index] ?? [];

            if ($articuloId > 0 && !$this->isRowEmpty($row)) {
                $totalUnidades += $cantidad;
                $totalLineas += 1;
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
                'cantidadReadonly' => $isSerie ? 'readonly' : '',
                'equipoDisabled' => $isSerie ? '' : 'disabled',
                'articuloError' => strval($lineErrors['articulo'] ?? ''),
                'cantidadError' => strval($lineErrors['cantidad'] ?? ''),
                'equipoError' => strval($lineErrors['equipo'] ?? ''),
                'articuloNombre' => strval($row['nombre_articulo'] ?? '') !== ''
                    ? strval($row['nombre_articulo'])
                    : 'Artículo #' . $articuloId,
                'articuloCodigo' => strval($row['codigo_interno_articulo'] ?? ''),
                'unidadLabel' => strval($row['simbolo_medida'] ?? '') !== ''
                    ? strval($row['simbolo_medida'])
                    : strval($row['nombre_unidad'] ?? ''),
                'equipoLabel' => $equipoLabel,
                'stock' => strval($stock),
                'stockWarning' => $canEdit && $articuloId > 0 && $cantidad > $stock,
            ];
        }

        $tipoId = intval($this->item['id_tipo_doc'] ?? 0);
        $motivoId = intval($this->item['id_motivo'] ?? 0);
        $selectedTipo = strval($tipoId);
        $selectedMotivo = strval($motivoId);

        $formMeta = '';
        if ($this->mode === 'UPD' || $isDelete) {
            $usuario = trim(strval($this->doc['usuario_nombre'] ?? ''));
            $creado = Fechas::paraMostrarFechaHora(strval($this->doc['fecha_creacion'] ?? ''));
            $lineas = intval($this->doc['lineas'] ?? count($this->detalles));
            $formMeta = 'Registrada' . ($usuario !== '' ? ' por ' . $usuario : '')
                . ($creado !== '' ? ' el ' . $creado : '')
                . ' · ' . $lineas . ($lineas === 1 ? ' línea' : ' líneas');
        }

        $formWarning = '';
        if ($isDelete) {
            if ($estado === 'CONFIRMADO') {
                $formWarning = 'Este documento está confirmado. Al anularlo se devolverán '
                    . $totalUnidades . ($totalUnidades === 1 ? ' unidad' : ' unidades')
                    . ' al inventario, se registrará un ajuste positivo en el Kardex y los equipos volverán a estar disponibles.';
            } else {
                $formWarning = 'Este documento está en borrador. Se anulará sin afectar las existencias.';
            }
        }

        $formNotice = '';
        if (strval($_GET['msg'] ?? '') === 'confirm_error') {
            $formNotice = 'No se pudo confirmar la salida. El borrador quedó guardado; revisa las existencias y vuelve a intentarlo.';
        }

        Renderer::render('inventario/salida', [
            'FormEyebrow' => 'Inventario / Salidas',
            'FormTitle' => $formTitle,
            'FormDescription' => $formDescription,
            'FormAction' => 'index.php?' . http_build_query($formParams),
            'FormCancelUrl' => 'index.php?page=Inventario_Salidas',
            'FormBackLabel' => 'Volver a salidas',
            'FormDanger' => $isDelete,
            'FormMeta' => $formMeta,
            'FormWarning' => $formWarning,
            'FormNotice' => $formNotice,
            'FormHasErrors' => count($this->errors) > 0,
            'FormErrorSummary' => count($this->errors) > 0
                ? 'Revisa los campos marcados en rojo e intenta de nuevo.'
                : '',
            'FormDetallesError' => strval($this->errors['detalles'] ?? ''),
            'FormToken' => CSRF::token('salida'),
            'FormMode' => $this->mode,
            'FormId' => $this->id,
            'CanEdit' => $canEdit,
            'ShowAnnul' => !$isDelete
                && $this->id > 0
                && $estado !== 'ANULADO',
            'AnnulUrl' => 'index.php?page=Inventario_Salida&mode=DEL&id=' . $this->id,
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
            'FormDestino' => strval($this->item['destino_salida'] ?? ''),
            'FormAutorizacion' => strval($this->item['autorizacion'] ?? ''),
            'FormObservaciones' => strval($this->item['observaciones'] ?? ''),
            'FormTipoError' => strval($this->errors['id_tipo_doc'] ?? ''),
            'FormFechaError' => strval($this->errors['fecha_movimiento'] ?? ''),
            'FormMotivoError' => strval($this->errors['id_motivo'] ?? ''),
            'FormDestinoError' => strval($this->errors['destino_salida'] ?? ''),
            'FormAutorizacionError' => strval($this->errors['autorizacion'] ?? ''),
            'FormObservacionesError' => strval($this->errors['observaciones'] ?? ''),
            'TiposForm' => $this->decorateOptions($this->tipos, $selectedTipo),
            'MotivosForm' => $this->decorateOptions($this->motivos, $selectedMotivo),
            'TipoPlaceholderSelected' => $tipoId <= 0 ? 'selected' : '',
            'MotivoPlaceholderSelected' => $motivoId <= 0 ? 'selected' : '',
            'Detalles' => $detallesView,
            'DetallesCount' => count($detallesView),
            'TotalUnidades' => $totalUnidades,
            'TotalLineas' => $totalLineas,
            'StockHeader' => $canEdit ? 'Disponible' : 'Existencia actual',
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
                'stock' => intval($row['stock'] ?? 0),
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

        return in_array($cantidad, ['', '0', '1'], true);
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
