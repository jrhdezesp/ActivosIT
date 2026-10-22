<?php

namespace Controllers\Catalogos;

class CatalogosOperativos extends CatalogosList
{
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
