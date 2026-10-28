<?php

namespace Controllers\Equipos;

use Controllers\PrivateController;
use Dao\Equipos\Traslados as DaoTraslados;
use Utilities\Bitacora;
use Utilities\CSRF;
use Utilities\Security;
use Utilities\Site;
use Views\Renderer;

/**
 * Registra el traslado de un equipo a otra ubicacion.
 *
 * El cambio queda en la bitacora con la ubicacion anterior y la nueva, de
 * modo que exista un registro del movimiento aunque no haya un documento
 * formal de traslado.
 */
class Traslado extends PrivateController
{
    private array $equipos = [];
    private array $ubicaciones = [];
    private int $equipoId = 0;
    private int $ubicacionId = 0;
    private string $observacion = '';
    private array $errors = [];
    private string $flashMessage = '';
    private string $formToken = '';

    public function run(): void
    {
        $this->formToken = CSRF::token('traslado');
        $this->equipos = DaoTraslados::getEquiposOptions();
        $this->ubicaciones = DaoTraslados::getUbicacionesOptions();

        if (trim(strval($_GET['msg'] ?? '')) === 'moved') {
            $this->flashMessage = 'El equipo se trasladó correctamente a la nueva ubicación.';
        }

        if ($this->isPostBack()) {
            $this->procesar();
        }

        $this->decorarOpciones();

        Renderer::render('equipos/traslado', [
            'Equipos' => $this->equipos,
            'Ubicaciones' => $this->ubicaciones,
            'EquipoId' => $this->equipoId,
            'UbicacionId' => $this->ubicacionId,
            'Observacion' => $this->observacion,
            'Errors' => $this->errors,
            'HayErrores' => count($this->errors) > 0,
            'ErrorMessage' => implode(' ', $this->errors),
            'FlashMessage' => $this->flashMessage,
            'HayFlash' => $this->flashMessage !== '',
            'FormToken' => $this->formToken,
        ]);
    }

    private function procesar(): void
    {
        $this->requireAction('Acciones.Equipos.Trasladar');

        if (!CSRF::validate('traslado', $_POST['token'] ?? '')) {
            $this->errors[] = 'El formulario expiró. Recarga la página e intenta de nuevo.';
            return;
        }

        $this->equipoId = intval($_POST['equipo'] ?? 0);
        $this->ubicacionId = intval($_POST['ubicacion'] ?? 0);
        $this->observacion = trim(strval($_POST['observacion'] ?? ''));

        if ($this->buscarOpcion($this->equipos, $this->equipoId) === null) {
            $this->errors[] = 'Selecciona un equipo válido.';
        }
        if ($this->buscarOpcion($this->ubicaciones, $this->ubicacionId) === null) {
            $this->errors[] = 'Selecciona una ubicación de destino válida.';
        }
        if (mb_strlen($this->observacion) > 255) {
            $this->errors[] = 'La observación no puede superar los 255 caracteres.';
        }

        if (count($this->errors) > 0) {
            return;
        }

        try {
            $resultado = DaoTraslados::trasladar($this->equipoId, $this->ubicacionId);

            Bitacora::registrar(
                'TRASLADO_EQUIPO',
                'Equipos',
                Bitacora::RESULTADO_EXITO,
                $this->equipoId,
                ['ubicacion' => $resultado['anterior']],
                [
                    'ubicacion' => $resultado['nueva'],
                    'equipo' => $resultado['equipo'],
                    'observacion' => $this->observacion,
                ]
            );

            Site::redirectTo('index.php?page=Equipos_Traslado&msg=moved');
        } catch (\Throwable $ex) {
            $this->errors[] = Site::safeMessage($ex);
        }
    }

    private function buscarOpcion(array $opciones, int $valor): ?array
    {
        foreach ($opciones as $opcion) {
            if (intval($opcion['value']) === $valor) {
                return $opcion;
            }
        }
        return null;
    }

    private function decorarOpciones(): void
    {
        foreach ($this->equipos as $indice => $equipo) {
            $this->equipos[$indice]['selected'] =
                intval($equipo['value']) === $this->equipoId ? 'selected' : '';
        }
        foreach ($this->ubicaciones as $indice => $ubicacion) {
            $this->ubicaciones[$indice]['selected'] =
                intval($ubicacion['value']) === $this->ubicacionId ? 'selected' : '';
        }
    }
}
