<?php

namespace Utilities;

// Consulta si un articulo forma parte de los detalles originales del documento.
final class DetallesInventario
{
    public static function contieneArticulo(array $detalles, int $idArticulo): bool
    {
        foreach ($detalles as $detalle) {
            if (intval($detalle['id_articulo'] ?? 0) === $idArticulo) {
                return true;
            }
        }

        return false;
    }

    private function __construct()
    {
    }
}
