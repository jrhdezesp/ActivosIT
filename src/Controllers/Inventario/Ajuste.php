<?php

namespace Controllers\Inventario;

use Controllers\PrivateController;
use Dao\Inventario\Ajustes as DaoAjustes;
use Utilities\Bitacora;
use Utilities\CSRF;
use Utilities\Security;
use Utilities\Site;
use Views\Renderer;

/**
 * Registra ajustes manuales de inventario (conteo fisico, mermas, correcciones).
 *
 * Cada ajuste actualiza la existencia del articulo y deja el movimiento
 * correspondiente en el Kardex, con saldo anterior, saldo nuevo y motivo.
 */
class Ajuste extends PrivateController
{
    private array $articulos = [];
    private array $motivos = [];
    private array $ultimos = [];
    private int $articuloId = 0;
    private string $tipo = 'POSITIVO';
    private string $cantidad = '';
    private int $motivoId = 0;
    private string $observacion = '';
    private array $errors = [];
    private string $flashMessage = '';
    private string $flashType = '';
    private string $formToken = '';

    public function run(): void
    {
        $this->formToken = CSRF::token('ajuste');
        $this->cargarCatalogos();
        $this->leerMensaje();

        if ($this->isPostBack()) {
            $this->procesar();
        }

        $this->decorarOpciones();

        Renderer::render('inventario/ajuste', [
            'Articulos' => $this->articulos,
            'Motivos' => $this->motivos,
            'UltimosAjustes' => $this->ultimos,
            'HayUltimos' => count($this->ultimos) > 0,
            'ArticuloId' => $this->articuloId,
            'TipoPositivo' => $this->tipo === 'POSITIVO',
            'TipoNegativo' => $this->tipo === 'NEGATIVO',
            'Cantidad' => $this->cantidad,
            'MotivoId' => $this->motivoId,
            'Observacion' => $this->observacion,
            'Errors' => $this->errors,
            'HayErrores' => count($this->errors) > 0,
            'ErrorMessage' => implode(' ', $this->errors),
            'FlashMessage' => $this->flashMessage,
            'FlashType' => $this->flashType,
            'HayFlash' => $this->flashMessage !== '',
            'FormToken' => $this->formToken,
        ]);
    }

    /** Marca la opcion elegida en los selectores tras un envio fallido. */
    private function decorarOpciones(): void
    {
        foreach ($this->articulos as $indice => $articulo) {
            $this->articulos[$indice]['selected'] =
                intval($articulo['value']) === $this->articuloId ? 'selected' : '';
        }
        foreach ($this->motivos as $indice => $motivo) {
            $this->motivos[$indice]['selected'] =
                intval($motivo['value']) === $this->motivoId ? 'selected' : '';
        }
    }

    private function cargarCatalogos(): void
    {
        $this->articulos = DaoAjustes::getArticulosOptions();
        $this->motivos = DaoAjustes::getMotivosOptions();
        $this->ultimos = DaoAjustes::getUltimosAjustes(5);
    }

    private function leerMensaje(): void
    {
        $msg = trim(strval($_GET['msg'] ?? ''));
        if ($msg === 'created') {
            $this->flashType = 'success';
            $this->flashMessage = 'El ajuste se registró y la existencia quedó actualizada.';
        }
    }

    private function procesar(): void
    {
        $this->requireAction('Acciones.Inventario.Ajustes.Crear');

        if (!CSRF::validate('ajuste', $_POST['token'] ?? '')) {
            $this->errors[] = 'El formulario expiró. Recarga la página e intenta de nuevo.';
            return;
        }

        $this->articuloId = intval($_POST['articulo'] ?? 0);
        $this->tipo = strtoupper(trim(strval($_POST['tipo'] ?? 'POSITIVO')));
        $this->cantidad = trim(strval($_POST['cantidad'] ?? ''));
        $this->motivoId = intval($_POST['motivo'] ?? 0);
        $this->observacion = trim(strval($_POST['observacion'] ?? ''));

        if ($this->tipo !== 'POSITIVO' && $this->tipo !== 'NEGATIVO') {
            $this->tipo = 'POSITIVO';
        }

        $existencia = $this->articuloId > 0 ? DaoAjustes::getExistencia($this->articuloId) : null;
        if ($existencia === null) {
            $this->errors[] = 'Selecciona un artículo válido y activo.';
        }

        if (!preg_match('/^\d+$/', $this->cantidad) || intval($this->cantidad) <= 0) {
            $this->errors[] = 'La cantidad debe ser un número entero mayor que cero.';
        } elseif (intval($this->cantidad) > 1000000) {
            $this->errors[] = 'La cantidad supera el máximo permitido.';
        } elseif ($this->tipo === 'NEGATIVO' && $existencia !== null && intval($this->cantidad) > $existencia) {
            $this->errors[] = 'El ajuste negativo supera la existencia disponible (' . $existencia . ').';
        }

        $motivo = $this->buscarMotivo($this->motivoId);
        if ($motivo === null) {
            $this->errors[] = 'Selecciona un motivo de ajuste.';
        } else {
            $esperado = $motivo['aplica'] === 'AJUSTE_ENTRADA' ? 'POSITIVO' : 'NEGATIVO';
            if ($esperado !== $this->tipo) {
                $this->errors[] = 'El motivo seleccionado no corresponde al tipo de ajuste.';
            }
        }

        if (mb_strlen($this->observacion) < 5) {
            $this->errors[] = 'Describe el motivo del ajuste (mínimo 5 caracteres).';
        } elseif (mb_strlen($this->observacion) > 255) {
            $this->errors[] = 'La observación no puede superar los 255 caracteres.';
        }

        if (count($this->errors) > 0) {
            return;
        }

        try {
            $resultado = DaoAjustes::registrarAjuste(
                $this->articuloId,
                $this->tipo,
                intval($this->cantidad),
                $this->motivoId,
                $this->observacion,
                intval(Security::getUserId())
            );

            Bitacora::registrar(
                'AJUSTE_INVENTARIO',
                'Inventario',
                Bitacora::RESULTADO_EXITO,
                $this->articuloId,
                ['existencia' => $resultado['saldoAnterior']],
                [
                    'existencia' => $resultado['saldoNuevo'],
                    'tipo' => $this->tipo,
                    'cantidad' => intval($this->cantidad),
                    'motivo_id' => $this->motivoId,
                    'observacion' => $this->observacion,
                ]
            );

            Site::redirectTo('index.php?page=Inventario_Ajuste&msg=created');
        } catch (\Throwable $ex) {
            $this->errors[] = Site::safeMessage($ex);
            Bitacora::registrar(
                'AJUSTE_INVENTARIO',
                'Inventario',
                Bitacora::RESULTADO_ERROR,
                $this->articuloId > 0 ? $this->articuloId : null,
                null,
                ['motivo' => Site::safeMessage($ex)]
            );
        }
    }

    private function buscarMotivo(int $motivoId): ?array
    {
        foreach ($this->motivos as $motivo) {
            if (intval($motivo['value']) === $motivoId) {
                return $motivo;
            }
        }
        return null;
    }
}
