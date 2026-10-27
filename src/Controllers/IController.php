<?php

  namespace Controllers;

  /**
   * Define el comportamiento comun que ejecuta una pagina del sistema.
   */
  interface IController
  {
    public function run() :void;
  }
