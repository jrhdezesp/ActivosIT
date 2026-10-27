<?php
namespace Controllers;
use Exception;

/**
 * Senala que la cuenta conectada no tiene permiso para abrir una pagina.
 */
class PrivateNoAuthException extends Exception
{

}
