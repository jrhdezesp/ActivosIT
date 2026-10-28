<?php

namespace Controllers\Catalogos;

use Controllers\PrivateController;
use Dao\Catalogos\Catalogos as DaoCatalogos;
use Views\Renderer;

/**
 * Comparte la presentacion de las listas de catalogos generales y operativos.
 */
abstract class CatalogosList extends PrivateController
{
    /**
     * Reune los catalogos de una pagina y los presenta con sus filtros.
     */
    protected function renderCatalogPage(
        string $pageType,
        string $route,
        string $view,
        string $eyebrow,
        string $title,
        string $description
    ): void {
        $search = trim(strval($_GET['q'] ?? ''));
        if (strlen($search) > 100) {
            $search = substr($search, 0, 100);
        }

        $status = strval($_GET['status'] ?? '');
        if (!in_array($status, ['', '0', '1'], true)) {
            $status = '';
        }

        $hasFilters = $search !== '' || $status !== '';
        $groups = [];
        $total = 0;

        foreach (DaoCatalogos::getTypesByPage($pageType) as $type) {
            $definition = DaoCatalogos::getDefinition($type);
            $rows = DaoCatalogos::getItems($type, $search, $status);
            $items = $this->mapItems($type, $definition, $rows);
            $count = count($items);
            $total += $count;

            $groups[] = [
                'type' => $type,
                'title' => $definition['title'],
                'description' => $definition['description'],
                'icon' => $definition['icon'],
                'tone' => $definition['tone'],
                'countLabel' => $count . ($count === 1 ? ' registro' : ' registros'),
                'items' => $items,
                'hasItems' => $count > 0,
                'createUrl' => $this->formUrl($type, 'INS'),
                'hint' => $hasFilters ? 'Resultados filtrados' : 'Datos en vivo',
                'emptyLabel' => $hasFilters
                    ? 'No hay registros que coincidan con la búsqueda.'
                    : 'Este catálogo aún no tiene registros.',
            ];
        }

        $totalLabel = count($groups) . ' catálogos · ' . $total
            . ($total === 1 ? ' registro' : ' registros')
            . ($hasFilters ? ($total === 1 ? ' encontrado' : ' encontrados') : '');

        $flash = $this->getFlash(strval($_GET['msg'] ?? ''));

        Renderer::render($view, [
            'CatalogEyebrow' => $eyebrow,
            'CatalogTitle' => $title,
            'CatalogDescription' => $description,
            'CatalogTotalLabel' => $totalLabel,
            'CatalogGeneralActive' => $pageType === 'general',
            'CatalogOperativoActive' => $pageType === 'operativo',
            'CatalogGroups' => $groups,
            'CatalogSearch' => $search,
            'CatalogStatus' => $status,
            'CatalogStatusAllSelected' => $status === '' ? 'selected' : '',
            'CatalogStatusActiveSelected' => $status === '1' ? 'selected' : '',
            'CatalogStatusInactiveSelected' => $status === '0' ? 'selected' : '',
            'CatalogHasFilters' => $hasFilters,
            'CatalogSearchAction' => 'index.php',
            'CatalogPageRoute' => $route,
            'CatalogClearUrl' => 'index.php?page=' . $route,
            'CatalogHasFlash' => $flash !== null,
            'CatalogFlashType' => $flash['type'] ?? '',
            'CatalogFlashIcon' => $flash['icon'] ?? '',
            'CatalogFlashTitle' => $flash['title'] ?? '',
            'CatalogFlashMessage' => $flash['message'] ?? '',
        ]);
    }

    private function mapItems(string $type, array $definition, array $rows): array
    {
        $items = [];

        foreach ($rows as $row) {
            $id = intval($row[$definition['pk']] ?? 0);
            $isActive = intval($row[$definition['statusField']] ?? 0) === 1;
            $detail = '';
            $code = '#' . $id;
            $typeLabel = '';
            $typeClass = '';

            switch ($type) {
                case 'clasificaciones':
                    $detail = trim(strval($row['descripcion_clasif'] ?? '')) ?: 'Sin descripción';
                    break;

                case 'ubicaciones':
                    $detail = trim(strval($row['descripcion_ubi'] ?? '')) ?: 'Sin descripción';
                    break;

                case 'departamentos':
                    $detail = trim(strval($row['descripcion_departamento'] ?? '')) ?: 'Sin descripcion';
                    break;

                case 'unidades_medida':
                    $symbol = trim(strval($row['simbolo_medida'] ?? ''));
                    if ($symbol !== '') {
                        $code = $symbol;
                    }
                    $detail = 'Unidad de medida';
                    break;

                case 'tipos_documentos':
                    $nature = strval($row['naturaleza_doc'] ?? '');
                    $typeLabel = $this->natureLabel($nature);
                    $typeClass = $nature === 'ENTRADA' ? 'badge--info' : 'badge--warning';
                    $detail = 'Naturaleza: ' . $typeLabel;
                    break;

                case 'tipos_mantenimientos':
                    $detail = trim(strval($row['descripcion_mant'] ?? '')) ?: 'Sin descripción';
                    break;

                case 'motivos_ajustes_anulaciones':
                    $applies = strval($row['motivo_aplica_a'] ?? '');
                    $typeLabel = $this->appliesLabel($applies);
                    $typeClass = $this->appliesClass($applies);
                    $detail = 'Aplica a: ' . $typeLabel;
                    break;
            }

            $items[] = [
                'id' => $id,
                'name' => strval($row[$definition['nameField']] ?? ''),
                'detail' => $detail,
                'code' => $code,
                'statusLabel' => $isActive ? 'Activo' : 'Inactivo',
                'statusClass' => $isActive ? 'badge--success' : 'badge--danger',
                'typeLabel' => $typeLabel,
                'typeClass' => $typeClass,
                'editUrl' => $this->formUrl($type, 'UPD', $id),
                'deleteUrl' => $this->formUrl($type, 'DEL', $id),
            ];
        }

        return $items;
    }

    private function formUrl(string $type, string $mode, int $id = 0): string
    {
        $url = 'index.php?page=Catalogos_Catalogo&tipo=' . urlencode($type) . '&mode=' . $mode;
        if ($id > 0) {
            $url .= '&id=' . $id;
        }

        return $url;
    }

    private function natureLabel(string $nature): string
    {
        if ($nature === 'ENTRADA') {
            return 'Entrada';
        }
        if ($nature === 'SALIDA') {
            return 'Salida';
        }

        return 'Sin definir';
    }

    private function appliesLabel(string $applies): string
    {
        $labels = [
            'AJUSTE_ENTRADA' => 'Ajuste entrada',
            'AJUSTE_SALIDA' => 'Ajuste salida',
            'ANULACION' => 'Anulación',
            'BAJA' => 'Baja',
        ];

        return $labels[$applies] ?? 'Sin definir';
    }

    private function appliesClass(string $applies): string
    {
        $classes = [
            'AJUSTE_ENTRADA' => 'badge--info',
            'AJUSTE_SALIDA' => 'badge--warning',
            'ANULACION' => 'badge--danger',
            'BAJA' => 'badge--outline',
        ];

        return $classes[$applies] ?? 'badge--outline';
    }

    private function getFlash(string $code): ?array
    {
        $flashes = [
            'created' => [
                'type' => 'success',
                'icon' => 'fa-circle-check',
                'title' => 'Registro creado',
                'message' => 'El registro se guardó correctamente en el catálogo.',
            ],
            'updated' => [
                'type' => 'success',
                'icon' => 'fa-circle-check',
                'title' => 'Registro actualizado',
                'message' => 'Los cambios se guardaron correctamente.',
            ],
            'deleted' => [
                'type' => 'success',
                'icon' => 'fa-trash-can',
                'title' => 'Registro eliminado',
                'message' => 'El registro se eliminó del catálogo.',
            ],
            'error' => [
                'type' => 'danger',
                'icon' => 'fa-circle-exclamation',
                'title' => 'No se pudo completar la operación',
                'message' => 'Revisa los datos e intenta de nuevo.',
            ],
        ];

        return $flashes[$code] ?? null;
    }
}
