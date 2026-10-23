<?php

namespace Controllers;

abstract class PrivateController extends PublicController
{
    private function _isAuthorized()
    {
        $isAuthorized = \Utilities\Security::isAuthorized(
            \Utilities\Security::getUserId(),
            $this->name,
            'CTR'
        );
        if (!$isAuthorized){
            throw new PrivateNoAuthException();
        }
    }
    private function _isAuthenticated()
    {
        if (!\Utilities\Security::isLogged()){
            throw new PrivateNoLoggedException();
        }
    }
    protected function isFeatureAutorized($feature) :bool
    {
        return \Utilities\Security::isAuthorized(
            \Utilities\Security::getUserId(),
            $feature
        );
    }
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
