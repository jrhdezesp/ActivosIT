<?php

namespace Utilities;

// Adapta las opciones de navegacion al formato que usan distintas vistas.
class NavRenderer
{
    private const CATALOG_PAGE_BY_TYPE = [
        "clasificaciones" => "Catalogos_Catalogos",
        "ubicaciones" => "Catalogos_Catalogos",
        "unidades_medida" => "Catalogos_Catalogos",
        "tipos_documentos" => "Catalogos_CatalogosOperativos",
        "tipos_mantenimientos" => "Catalogos_CatalogosOperativos",
        "motivos_ajustes_anulaciones" => "Catalogos_CatalogosOperativos",
    ];

    private const FORM_PARENT_PAGE = [
        "Articulos_Articulo" => "Articulos_Articulos",
        "Inventario_Entrada" => "Inventario_Entradas",
        "Inventario_Salida" => "Inventario_Salidas",
        "Inventario_Mantenimiento" => "Inventario_Mantenimientos",
        "Inventario_Asignacion" => "Inventario_Asignaciones",
    ];

    // Prepara elementos con formato para el menu lateral.
    public static function forSidebar(array $entries): array
    {
        return self::decorate($entries, [self::class, "sidebarItem"]);
    }

    // Prepara elementos con formato para los accesos del dashboard.
    public static function forDashboard(array $entries): array
    {
        return self::decorate($entries, [self::class, "dashboardItem"]);
    }

    // Prepara elementos para la pantalla de edicion y su vista previa.
    public static function forEditor(array $entries): array
    {
        return self::decorate(
            $entries,
            [self::class, "editorItem"],
            [self::class, "previewItem"]
        );
    }

    private static function decorate(
        array $entries,
        callable $itemRenderer,
        ?callable $previewRenderer = null
    ): array {
        $result = [];
        $currentPage = isset($_GET["page"]) ? (string) $_GET["page"] : "";
        foreach ($entries as $entry) {
            $items = isset($entry["items"]) && is_array($entry["items"])
                ? $entry["items"]
                : [];
            $itemsHtml = "";
            $previewHtml = "";
            $hasActiveItem = false;
            foreach ($items as $item) {
                $item["active_class"] = self::isActiveUrl($item["nav_url"] ?? "", $currentPage)
                    ? " is-active"
                    : "";
                $hasActiveItem = $hasActiveItem || $item["active_class"] !== "";
                $itemsHtml .= $itemRenderer($item);
                if ($previewRenderer !== null) {
                    $previewHtml .= $previewRenderer($item);
                }
            }
            $type = $entry["type"] ?? (count($items) > 0 ? "group" : "link");
            $status = $entry["status"] ?? ($type === "group" ? "group" : "active");
            $entry["type"] = $type;
            $entry["status"] = $status;
            $entry["description"] = $entry["description"] ?? "";
            $entry["status_label"] = self::statusLabel($status);
            $isVisible = ($entry["visible"] ?? true) !== false;
            $entry["hidden_class"] = $isVisible ? "" : " is-hidden";
            $entry["visible_attr"] = $isVisible ? "1" : "0";
            $entry["visibility_icon"] = $isVisible ? "fa-eye" : "fa-eye-slash";
            $entry["visibility_title"] = $isVisible ? "Ocultar del menú" : "Mostrar en el menú";
            $entry["visibility_pressed"] = $isVisible ? "true" : "false";
            $entry["hidden_badge_html"] = $isVisible
                ? ""
                : '<span class="status-badge status-badge--hidden">Oculto</span>';
            $entry["visibility_control_html"] = Nav::isProtectedNavId(strval($entry["id"] ?? ""))
                ? self::lockedVisibilityButton()
                : '<button type="button" class="editor-icon-button" data-toggle-visibility aria-pressed="'
                    . $entry["visibility_pressed"] . '" title="' . $entry["visibility_title"]
                    . '" aria-label="' . $entry["visibility_title"] . ' ' . self::escape($entry["nav_label"] ?? "")
                    . '"><i class="fa-solid ' . $entry["visibility_icon"] . '"></i></button>';
            $entry["active_class"] = $hasActiveItem
                || self::isActiveUrl($entry["nav_url"] ?? "", $currentPage)
                ? " is-active"
                : "";
            $entry["open_attr"] = $hasActiveItem ? " open" : "";
            $entry["items_html"] = $itemsHtml;
            $entry["preview_items_html"] = $previewHtml;
            $entry["meta"] = $type === "group"
                ? "Grupo · " . count($items) . " elementos"
                : "Enlace · " . ($entry["nav_url"] ?? "");
            $result[] = $entry;
        }
        return $result;
    }

    private static function sidebarItem(array $item): string
    {
        $url = self::escape($item["nav_url"] ?? "#");
        $status = self::escape($item["status"] ?? "active");
        $icon = self::escape($item["icon"] ?? "fa-circle");
        $label = self::escape($item["nav_label"] ?? "");
        $activeClass = $item["active_class"] ?? "";
        return '<li><a class="sidebar-link' . $activeClass . '" href="' . $url . '" data-status="' . $status . '">'
            . '<i class="fa-solid ' . $icon . '" aria-hidden="true"></i>'
            . '<span>' . $label . '</span>'
            . '</a></li>';
    }

    private static function dashboardItem(array $item): string
    {
        $url = self::escape($item["nav_url"] ?? "#");
        $status = self::escape($item["status"] ?? "active");
        $icon = self::escape($item["icon"] ?? "fa-circle");
        $label = self::escape($item["nav_label"] ?? "");
        return '<li><a class="module-card__item" href="' . $url . '" data-status="' . $status . '">'
            . '<span class="module-card__item-icon"><i class="fa-solid ' . $icon . '" aria-hidden="true"></i></span>'
            . '<span>' . $label . '</span>'
            . '</a></li>';
    }

    private static function editorItem(array $item): string
    {
        $id = self::escape($item["id"] ?? "");
        $url = self::escape($item["nav_url"] ?? "");
        $status = self::escape($item["status"] ?? "active");
        $statusLabel = self::escape(self::statusLabel($item["status"] ?? "active"));
        $icon = self::escape($item["icon"] ?? "fa-circle");
        $label = self::escape($item["nav_label"] ?? "");
        $isVisible = ($item["visible"] ?? true) !== false;
        $isProtected = Nav::isProtectedNavId($id);
        $hiddenClass = $isVisible ? "" : " is-hidden";
        $visibilityIcon = $isVisible ? "fa-eye" : "fa-eye-slash";
        $visibilityTitle = $isVisible ? "Ocultar del menú" : "Mostrar en el menú";
        $visibilityBadge = $isVisible
            ? ""
            : '<span class="status-badge status-badge--hidden">Oculto</span>';
        $visibilityControl = $isProtected
            ? self::lockedVisibilityButton()
            : '<button type="button" class="editor-icon-button" data-toggle-visibility aria-pressed="'
                . ($isVisible ? "true" : "false") . '" title="' . $visibilityTitle . '" aria-label="' . $visibilityTitle
                . ' ' . $label . '"><i class="fa-solid ' . $visibilityIcon . '"></i></button>';
        return '<li class="nav-editor-item' . $hiddenClass . '" data-item data-item-id="' . $id
            . '" data-visible="' . ($isVisible ? "1" : "0") . '">'
            . '<span class="nav-editor-item__handle" aria-hidden="true"><i class="fa-solid fa-grip-vertical"></i></span>'
            . '<span class="nav-editor-item__icon"><i class="fa-solid ' . $icon . '" aria-hidden="true"></i></span>'
            . '<div class="nav-editor-item__copy"><strong>' . $label . '</strong><span>' . $url . '</span></div>'
            . '<span class="status-badge status-badge--' . $status . '">' . $statusLabel . '</span>'
            . $visibilityBadge
            . '<div class="nav-editor-item__controls">'
            . $visibilityControl
            . '<button type="button" class="editor-icon-button" data-move="up" title="Subir" aria-label="Subir ' . $label . '"><i class="fa-solid fa-arrow-up"></i></button>'
            . '<button type="button" class="editor-icon-button" data-move="down" title="Bajar" aria-label="Bajar ' . $label . '"><i class="fa-solid fa-arrow-down"></i></button>'
            . '</div></li>';
    }

    /** Boton fijo que recuerda que la entrada no puede ocultarse. */
    private static function lockedVisibilityButton(): string
    {
        return '<button type="button" class="editor-icon-button" disabled title="Siempre visible" aria-label="Siempre visible"><i class="fa-solid fa-lock"></i></button>';
    }

    private static function previewItem(array $item): string
    {
        $status = self::escape($item["status"] ?? "active");
        $label = self::escape($item["nav_label"] ?? "");
        $hiddenClass = ($item["visible"] ?? true) !== false ? "" : ' class="is-hidden"';
        return '<li' . $hiddenClass . ' data-status="' . $status . '">' . $label . '</li>';
    }

    private static function isActiveUrl(string $url, string $currentPage): bool
    {
        if ($currentPage === "") {
            return false;
        }
        $query = parse_url($url, PHP_URL_QUERY);
        if (!is_string($query)) {
            return false;
        }
        parse_str($query, $params);
        $targetPage = isset($params["page"]) ? (string) $params["page"] : "";
        if ($targetPage === "") {
            return false;
        }
        if ($targetPage === $currentPage) {
            return true;
        }
        if (isset(self::FORM_PARENT_PAGE[$currentPage])) {
            return self::FORM_PARENT_PAGE[$currentPage] === $targetPage;
        }
        if ($currentPage === "Catalogos_Catalogo") {
            $type = isset($_GET["tipo"]) ? (string) $_GET["tipo"] : "";
            return isset(self::CATALOG_PAGE_BY_TYPE[$type])
                && self::CATALOG_PAGE_BY_TYPE[$type] === $targetPage;
        }
        return false;
    }

    private static function statusLabel(string $status): string
    {
        if ($status === "pending") {
            return "Pendiente";
        }
        if ($status === "group") {
            return "Grupo";
        }
        return "Activo";
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
    }

    private function __construct()
    {
    }

    private function __clone()
    {
    }
}
