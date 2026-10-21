<?php

namespace Controllers\Navegacion;

use Controllers\PrivateController;
use Utilities\Nav;
use Utilities\NavRenderer;
use Views\Renderer;

class Navegacion extends PrivateController
{
    public function run(): void
    {
        $entries = NavRenderer::forEditor(Nav::getPrivateNavigation());
        $groups = array_values(array_filter($entries, function ($entry) {
            return isset($entry["type"]) && $entry["type"] === "group";
        }));
        $links = array_values(array_filter($entries, function ($entry) {
            return isset($entry["type"]) && $entry["type"] === "link";
        }));
        Renderer::render("navegacion/navegacion", [
            "EditorEntries" => $entries,
            "EditorGroups" => $groups,
            "EditorGroupCount" => count($groups),
            "EditorLinkCount" => count($links),
            "EditorItemCount" => $this->countItems($entries)
        ]);
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
