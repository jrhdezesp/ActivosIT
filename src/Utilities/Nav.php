<?php

namespace Utilities;

// Obtiene, filtra y prepara las opciones de navegacion del sistema.
class Nav
{
    // Prepara las opciones visibles para las paginas publicas.
    public static function setPublicNavContext()
    {
        $navigationData = self::getNavFromJson()["public"] ?? [];
        // Se publica siempre en el contexto de la peticion: el renderizador lee
        // Context::getContext(), no la sesion. Si solo se guardara en sesion el
        // menu desaparecia en la segunda peticion.
        Context::setContext("PUBLIC_NAVIGATION", $navigationData);
    }
    // Filtra las opciones privadas segun los permisos de la cuenta.
    public static function setNavContext()
    {
        $isDevelopment = Context::getContextByKey("DEVELOPMENT") === "1";
        $visibleNavigation = [];
        $userID = Security::getUserId();
        $navigationData = self::getPrivateNavigation();
        foreach ($navigationData as $navEntry) {
            if (isset($navEntry["items"]) && is_array($navEntry["items"])) {
                $visibleItems = [];
                foreach ($navEntry["items"] as $item) {
                    // Se evalua siempre: en desarrollo el permiso se registra
                    // automaticamente para poder asignarlo desde Roles.
                    $canSee = Security::isAuthorized($userID, $item["id"], 'MNU');
                    if ($isDevelopment || $canSee) {
                        $visibleItems[] = $item;
                    }
                }
                if (count($visibleItems) > 0) {
                    $navEntry["items"] = $visibleItems;
                    $visibleNavigation[] = $navEntry;
                }
            } else {
                $canSee = Security::isAuthorized($userID, $navEntry["id"], 'MNU');
                if ($isDevelopment || $canSee) {
                    $visibleNavigation[] = $navEntry;
                }
            }
        }
        // Se decora en cada peticion para que el elemento activo y el submenu
        // abierto correspondan a la pagina actual (antes quedaban congelados).
        Context::setContext("NAVIGATION", NavRenderer::forSidebar($visibleNavigation));
    }

    // Devuelve las opciones privadas definidas para el sistema.
    public static function getPrivateNavigation(): array
    {
        $navigationData = self::getNavFromJson();
        return isset($navigationData["private"]) && is_array($navigationData["private"])
            ? $navigationData["private"]
            : [];
    }

    // Limpia los datos de navegacion guardados para la solicitud y la sesion.
    public static function invalidateNavData()
    {
        Context::removeContextByKey("NAVIGATION_DATA");
        Context::removeContextByKey("NAVIGATION");
        Context::removeContextByKey("PUBLIC_NAVIGATION");
    }

    private static function getNavFromJson()
    {
        $filePath = __DIR__ . '/../../nav.config.json';
        if (!file_exists($filePath)) {
            throw new \Exception(sprintf('%s does not exist', $filePath));
        }
        if (!is_readable($filePath)) {
            throw new \Exception(sprintf('%s file is not readable', $filePath));
        }
        // Lectura directa: el menu se recalcula en cada peticion para reflejar
        // cambios de permisos o de nav.config.json sin re-iniciar sesion.
        $jsonContent = file_get_contents($filePath);
        $jsonData = json_decode($jsonContent, true);
        return is_array($jsonData) ? $jsonData : [];
    }

    private function __construct()
    {
    }
    private function __clone()
    {
    }
}
