<?php

namespace Controllers\Navegacion;

use Controllers\PrivateController;
use Dao\Security\Navegacion as DaoNavegacion;
use Utilities\Bitacora;
use Utilities\CSRF;
use Utilities\Nav;
use Utilities\NavRenderer;
use Utilities\Security;
use Utilities\Site;
use Views\Renderer;

/**
 * Permite ordenar y ocultar los grupos y accesos del menu privado.
 *
 * No crea rutas nuevas: solo guarda preferencias para identificadores que ya
 * existen en nav.config.json.
 */
class Navegacion extends PrivateController
{
    /** Prepara los grupos y accesos que aparecen en la pantalla de navegacion. */
    public function run(): void
    {
        $this->requireAction('Acciones.Seguridad.Navegacion.Administrar');
        if ($this->isPostBack()) {
            $this->handleSave();
        }

        $entries = NavRenderer::forEditor(Nav::getPrivateNavigation());
        $groups = array_values(array_filter($entries, function ($entry) {
            return isset($entry["type"]) && $entry["type"] === "group";
        }));
        $links = array_values(array_filter($entries, function ($entry) {
            return isset($entry["type"]) && $entry["type"] === "link";
        }));
        $flash = $this->getFlash(strval($_GET["msg"] ?? ""));

        Renderer::render("navegacion/navegacion", [
            "EditorEntries" => $entries,
            "EditorGroups" => $groups,
            "EditorGroupCount" => count($groups),
            "EditorLinkCount" => count($links),
            "EditorItemCount" => $this->countItems($entries),
            "CSRFToken" => CSRF::token("navegacion"),
            "FlashHas" => $flash !== null,
            "FlashType" => $flash["type"] ?? "",
            "FlashIcon" => $flash["icon"] ?? "",
            "FlashTitle" => $flash["title"] ?? "",
            "FlashMessage" => $flash["message"] ?? ""
        ]);
    }

    /** Valida y guarda el orden y la visibilidad enviados por el editor. */
    private function handleSave(): void
    {
        try {
            CSRF::requireValid("navegacion", $_POST["csrf_token"] ?? "");
            $payload = json_decode(strval($_POST["distribucion"] ?? ""), true);
            if (!is_array($payload) || !isset($payload["groups"]) || !is_array($payload["groups"])) {
                throw new \InvalidArgumentException("La distribución enviada no es válida.");
            }
            $preferences = $this->buildPreferences($payload["groups"]);
            if (count($preferences) === 0) {
                throw new \InvalidArgumentException("No se recibieron elementos válidos para guardar.");
            }
            DaoNavegacion::guardarPreferencias($preferences, intval(Security::getUserId()));
            Bitacora::registrar(
                "ACTUALIZAR_NAVEGACION",
                "Seguridad",
                Bitacora::RESULTADO_EXITO,
                null,
                null,
                ["elementos" => count($preferences)]
            );
            Site::redirectTo("index.php?page=Navegacion_Navegacion&msg=saved");
        } catch (\Throwable $ex) {
            Site::redirectToWithMsg(
                "index.php?page=Navegacion_Navegacion&msg=error",
                Site::safeMessage($ex)
            );
        }
    }

    /**
     * Convierte el arreglo del editor en filas para la tabla.
     * Los identificadores desconocidos se descartan.
     */
    private function buildPreferences(array $groups): array
    {
        $known = [];
        foreach (Nav::getPrivateNavigation() as $entry) {
            $known[strval($entry["id"] ?? "")] = true;
            foreach ($entry["items"] ?? [] as $item) {
                $known[strval($item["id"] ?? "")] = true;
            }
        }

        $preferences = [];
        $order = 0;
        foreach ($groups as $group) {
            $groupId = strval($group["id"] ?? "");
            if ($groupId !== "" && isset($known[$groupId])) {
                $preferences[] = [
                    "nav_id" => $groupId,
                    "orden" => $order++,
                    "visible" => Nav::isProtectedNavId($groupId) || $this->readVisibility($group)
                ];
            }
            foreach ((array) ($group["items"] ?? []) as $item) {
                $itemId = strval($item["id"] ?? "");
                if ($itemId === "" || !isset($known[$itemId])) {
                    continue;
                }
                $preferences[] = [
                    "nav_id" => $itemId,
                    "orden" => $order++,
                    "visible" => Nav::isProtectedNavId($itemId) || $this->readVisibility($item)
                ];
            }
        }

        return $preferences;
    }

    /** Interpreta el indicador de visibilidad; si falta, la entrada se muestra. */
    private function readVisibility(array $entry): bool
    {
        if (!array_key_exists("visible", $entry)) {
            return true;
        }

        return filter_var($entry["visible"], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    }

    private function getFlash(string $code): ?array
    {
        $flashes = [
            "saved" => [
                "type" => "success",
                "icon" => "fa-circle-check",
                "title" => "Navegación actualizada",
                "message" => "El orden y la visibilidad se guardaron. El menú ya refleja los cambios."
            ],
            "error" => [
                "type" => "danger",
                "icon" => "fa-circle-exclamation",
                "title" => "No se pudo guardar la navegación",
                "message" => "Revisa la distribución e intenta de nuevo."
            ]
        ];

        return $flashes[$code] ?? null;
    }

    private function countItems(array $entries): int
    {
        $count = 0;
        foreach ($entries as $entry) {
            $count += isset($entry["items"]) && is_array($entry["items"])
                ? count($entry["items"])
                : 0;
        }
        return $count;
    }
}
