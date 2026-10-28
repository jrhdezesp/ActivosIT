<?php

namespace Controllers;

use Utilities\Security;

/**
 * Base para paginas que requieren una cuenta activa y permiso de acceso.
 */
abstract class PrivateController extends PublicController
{
    private function _isAuthorized()
    {
        $isAuthorized = Security::isAuthorized(
            Security::getUserId(),
            $this->name,
            'CTR'
        );
        if (!$isAuthorized){
            throw new PrivateNoAuthException();
        }
    }
    private function _isAuthenticated()
    {
        if (!Security::isLogged()){
            throw new PrivateNoLoggedException();
        }
    }
    /** Indica si la cuenta puede usar una funcion adicional de la pagina. */
    protected function isFeatureAutorized($feature) :bool
    {
        return Security::isAuthorized(
            Security::getUserId(),
            $feature
        );
    }

    /**
     * Exige un permiso de accion para crear, editar, confirmar o anular.
     *
     * @param string $action Nombre del permiso de accion.
     * @return void
     */
    protected function requireAction(string $action): void
    {
        if (!Security::isAuthorized(Security::getUserId(), $action, 'ACC')) {
            throw new PrivateNoAuthException();
        }
    }
    /** Confirma el acceso antes de permitir que se muestre la pagina privada. */
    public function __construct()
    {
        parent::__construct();
        $this->_isAuthenticated();
        // El control de acceso se aplica siempre. En desarrollo solo se
        // registran automáticamente los permisos que aún no existen, pero un
        // usuario sin el permiso asignado a su rol no puede entrar.
        $this->_isAuthorized();
    }
}
