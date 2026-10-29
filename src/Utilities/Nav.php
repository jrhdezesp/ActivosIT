<?php

namespace Utilities;

// Obtiene, filtra y prepara las opciones de navegacion del sistema.
class Nav
{
    // La entrada del editor y su grupo no pueden ocultarse: un administrador
    // perderia el acceso al editor desde el menu si se ocultaran.
    private const PROTECTED_IDS = ["Menu_Navegacion", "Menu_Seguridad"];

    // Preferencias de orden y visibilidad leidas una vez por peticion.
    private static $preferences = null;

    /** Indica si una entrada del menu debe permanecer siempre visible. */
    public static function isProtectedNavId(string $navId): bool
    {
        return in_array($navId, self::PROTECTED_IDS, true);
    }

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
            // La visibilidad elegida en el editor solo oculta la entrada del
            // menu; el permiso MNU sigue siendo el que autoriza el acceso.
            if (($navEntry["visible"] ?? true) === false) {
                continue;
            }
            if (isset($navEntry["items"]) && is_array($navEntry["items"])) {
                $visibleItems = [];
                foreach ($navEntry["items"] as $item) {
                    if (($item["visible"] ?? true) === false) {
                        continue;
                    }
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
        $entries = isset($navigationData["private"]) && is_array($navigationData["private"])
            ? $navigationData["private"]
            : [];

        return self::applyPreferences($entries);
    }

    /**
     * Combina la definicion del menu con el orden y la visibilidad guardados.
     * Los elementos sin preferencia conservan el orden de nav.config.json.
     */
    private static function applyPreferences(array $entries): array
    {
        $preferences = self::getPreferences();
        foreach ($entries as $index => $entry) {
            $entries[$index] = self::withPreference($entry, $preferences);
            if (isset($entries[$index]["items"]) && is_array($entries[$index]["items"])) {
                foreach ($entries[$index]["items"] as $itemIndex => $item) {
                    $entries[$index]["items"][$itemIndex] = self::withPreference($item, $preferences);
                }
                $entries[$index]["items"] = self::sortEntries($entries[$index]["items"]);
            }
        }

        return self::sortEntries($entries);
    }

    private static function withPreference(array $entry, array $preferences): array
    {
        $navId = strval($entry["id"] ?? "");
        $preference = $preferences[$navId] ?? null;
        $entry["orden"] = $preference !== null ? $preference["orden"] : PHP_INT_MAX;
        $entry["visible"] = self::isProtectedNavId($navId)
            ? true
            : ($preference !== null ? $preference["visible"] : true);

        return $entry;
    }

    private static function sortEntries(array $entries): array
    {
        $sortable = [];
        foreach ($entries as $position => $entry) {
            $sortable[] = [
                "orden" => intval($entry["orden"] ?? PHP_INT_MAX),
                "posicion" => $position,
                "entrada" => $entry,
            ];
        }
        usort($sortable, function ($a, $b) {
            if ($a["orden"] === $b["orden"]) {
                return $a["posicion"] <=> $b["posicion"];
            }
            return $a["orden"] <=> $b["orden"];
        });

        return array_map(function ($item) {
            return $item["entrada"];
        }, $sortable);
    }

    private static function getPreferences(): array
    {
        if (self::$preferences === null) {
            self::$preferences = \Dao\Security\Navegacion::getPreferencias();
        }

        return self::$preferences;
    }

    // Limpia los datos de navegacion guardados para la solicitud y la sesion.
    public static function invalidateNavData()
    {
        self::$preferences = null;
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
