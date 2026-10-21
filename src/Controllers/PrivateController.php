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
        // En desarrollo se permite cualquier usuario autenticado
        // para poder asignar permisos desde la aplicacion.
        if (\Utilities\Context::getContextByKey("DEVELOPMENT") !== "1") {
            $this->_isAuthorized();
        }
    }
}
