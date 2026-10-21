<?php

namespace Utilities;

class NavRenderer
{
    public static function forSidebar(array $entries): array
    {
        return self::decorate($entries, [self::class, "sidebarItem"]);
    }

    public static function forDashboard(array $entries): array
    {
        return self::decorate($entries, [self::class, "dashboardItem"]);
    }

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
        return '<li class="nav-editor-item" data-item data-item-id="' . $id . '">'
            . '<span class="nav-editor-item__handle" aria-hidden="true"><i class="fa-solid fa-grip-vertical"></i></span>'
            . '<span class="nav-editor-item__icon"><i class="fa-solid ' . $icon . '" aria-hidden="true"></i></span>'
            . '<div class="nav-editor-item__copy"><strong>' . $label . '</strong><span>' . $url . '</span></div>'
            . '<span class="status-badge status-badge--' . $status . '">' . $statusLabel . '</span>'
            . '<div class="nav-editor-item__controls">'
            . '<button type="button" class="editor-icon-button" data-move="up" title="Subir" aria-label="Subir ' . $label . '"><i class="fa-solid fa-arrow-up"></i></button>'
            . '<button type="button" class="editor-icon-button" data-move="down" title="Bajar" aria-label="Bajar ' . $label . '"><i class="fa-solid fa-arrow-down"></i></button>'
            . '</div></li>';
    }

    private static function previewItem(array $item): string
    {
        $status = self::escape($item["status"] ?? "active");
        $label = self::escape($item["nav_label"] ?? "");
        return '<li data-status="' . $status . '">' . $label . '</li>';
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
        return isset($params["page"]) && (string) $params["page"] === $currentPage;
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
