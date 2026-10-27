<?php

namespace Controllers\Catalogos;

/**
 * Presenta los catalogos generales para su consulta y mantenimiento.
 */
class Catalogos extends CatalogosList
{
    /** Muestra los grupos de catalogos generales. */
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
