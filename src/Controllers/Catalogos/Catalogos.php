<?php

namespace Controllers\Catalogos;

class Catalogos extends CatalogosList
{
    public function run(): void
    {
        $this->renderCatalogPage(
            'general',
            'Catalogos_Catalogos',
            'catalogos/catalogos',
            'Configuración maestra',
            'Catálogos generales',
            'Administra las listas base que alimentan el catálogo de artículos, los equipos y el inventario.'
        );
    }
}
