<?php
  namespace Utilities;

  // Prepara arreglos de datos y opciones para las vistas.
  class ArrUtils
  {

    // Copia valores solo en las claves que ya existen en el destino.
    public static function mergeArrayTo(&$origin, &$destiny)
    {
        if (is_array($origin) && is_array($destiny)) {
            foreach ($origin as $okey => $ovalue) {
                if (isset($destiny[$okey])) {
                    $destiny[$okey] = $ovalue;
                }
            }
        }
    }

    // Copia todos los valores del origen al arreglo de destino.
    public static function mergeFullArrayTo(&$origin, &$destiny)
    {
        if (is_array($origin) && is_array($destiny)) {
            foreach ($origin as $okey => $ovalue) {
                $destiny[$okey] = $ovalue;
            }
        }
    }

    // Convierte pares de valores en opciones y marca la opcion seleccionada.
    public static function toOptionsArray($baseArray, $codeName, $textName, $selectedName, $selectedValue)
    {
        $tmpArray = array();
        foreach ($baseArray as $key => $value) {
            $tmpArray[] = array(
                $codeName => $key,
                $textName => $value,
                $selectedName => ($selectedValue == $key) ? 'selected' : ''
            );
        }
        return $tmpArray;
    }

    // Adapta una lista de registros al formato de opciones de una vista.
    public static function objectArrToOptionsArray(
        $baseArray,
        $codeName,
        $textName,
        $selectedName,
        $selectedValue,
        $codeKey = "value",
        $textKey = "text",
        $selectedKey = "selected"
    ) {
        $tmpArray = array();
        foreach ($baseArray as $value) {
            $tmpArray[] = array(
                $codeKey => $value[$codeName],
                $textKey => $value[$textName],
                $selectedKey => ($selectedValue == $value[$selectedName])
                    ? 'selected'
                    : ''
            );
        }
        return $tmpArray;
    }

    // Marca la opcion cuyo valor coincide con la seleccion actual.
    public static function marcarOpcionesSeleccionadas(array $opciones, string $seleccionado): array
    {
        foreach ($opciones as &$opcion) {
            $opcion['selected'] = strval($opcion['value']) === $seleccionado ? 'selected' : '';
        }
        unset($opcion);

        return $opciones;
    }

    private function __construct()
    {

    }
  }
