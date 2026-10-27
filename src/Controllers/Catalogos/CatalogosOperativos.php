<?php

namespace Controllers\Catalogos;

/**
 * Presenta los catalogos operativos usados por los procesos del sistema.
 */
class CatalogosOperativos extends CatalogosList
{
    /** Muestra los grupos de catalogos operativos. */
    public function run(): void
    {
        $this->renderCatalogPage(
            'operativo',
            'Catalogos_CatalogosOperativos',
            'catalogos/operativos',
            'Configuración operativa',
            'Catálogos operativos',
            'Define los tipos de documento, mantenimiento y motivos que clasifican la operación diaria.'
        );
    }
}
