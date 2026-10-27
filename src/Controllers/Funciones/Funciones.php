<?php

namespace Controllers\Funciones;

use Controllers\PrivateController;
use Dao\Funciones\Funciones as DaoFunciones;
use Utilities\Context;
use Utilities\Paging;
use Views\Renderer;

/**
 * Presenta el listado de funciones que se pueden asignar a los roles.
 */
class Funciones extends PrivateController
{
    private $partialName = '';
    private $status = '';
    private $orderBy = '';
    private $orderDescending = false;
    private $pageNumber = 1;
    private $itemsPerPage = 10;
    private $viewData = [];
    private $funciones = [];
    private $funcionesCount = 0;
    private $pages = 0;

    /** Muestra las funciones disponibles con los filtros seleccionados. */
    public function run(): void
    {
        $this->getParamsFromContext();
        $this->getParams();
        $tmpFunciones = DaoFunciones::getFunciones(
            $this->partialName,
            $this->status,
            $this->orderBy,
            $this->orderDescending,
            $this->pageNumber - 1,
            $this->itemsPerPage
        );
        $this->funciones = $tmpFunciones['funciones'];
        $this->funcionesCount = $tmpFunciones['total'];
        $this->pages = $this->funcionesCount > 0 ? ceil($this->funcionesCount / $this->itemsPerPage) : 1;
        if ($this->pageNumber > $this->pages) {
            $this->pageNumber = $this->pages;
        }
        $this->setParamsToContext();
        $this->setParamsToDataView();
        Renderer::render('funciones/funciones', $this->viewData);
    }

    private function getParams(): void
    {
        $this->partialName = isset($_GET['partialName']) ? $_GET['partialName'] : $this->partialName;
        $this->status = isset($_GET['status']) && in_array($_GET['status'], ['ACT', 'INA', 'EMP']) ? $_GET['status'] : $this->status;
        if ($this->status === 'EMP') {
            $this->status = '';
        }
        $this->orderBy = isset($_GET['orderBy']) && in_array($_GET['orderBy'], ['fncod', 'fndsc', 'fntyp', 'clear']) ? $_GET['orderBy'] : $this->orderBy;
        if ($this->orderBy === 'clear') {
            $this->orderBy = '';
        }
        $this->orderDescending = isset($_GET['orderDescending']) ? boolval($_GET['orderDescending']) : $this->orderDescending;
        $this->pageNumber = isset($_GET['pageNum']) ? intval($_GET['pageNum']) : $this->pageNumber;
        $this->itemsPerPage = isset($_GET['itemsPerPage']) ? intval($_GET['itemsPerPage']) : $this->itemsPerPage;
        $this->itemsPerPage = max(1, min(100, $this->itemsPerPage));
        $this->pageNumber = max(1, $this->pageNumber);
    }

    private function getParamsFromContext(): void
    {
        $this->partialName = Context::getContextByKey('funciones_partialName');
        $this->status = Context::getContextByKey('funciones_status');
        $this->orderBy = Context::getContextByKey('funciones_orderBy');
        $this->orderDescending = boolval(Context::getContextByKey('funciones_orderDescending'));
        $this->pageNumber = intval(Context::getContextByKey('funciones_page'));
        $this->itemsPerPage = intval(Context::getContextByKey('funciones_itemsPerPage'));
        if ($this->pageNumber < 1) {
            $this->pageNumber = 1;
        }
        if ($this->itemsPerPage < 1) {
            $this->itemsPerPage = 10;
        }
    }

    private function setParamsToContext(): void
    {
        Context::setContext('funciones_partialName', $this->partialName, true);
        Context::setContext('funciones_status', $this->status, true);
        Context::setContext('funciones_orderBy', $this->orderBy, true);
        Context::setContext('funciones_orderDescending', $this->orderDescending, true);
        Context::setContext('funciones_page', $this->pageNumber, true);
        Context::setContext('funciones_itemsPerPage', $this->itemsPerPage, true);
    }

    private function setParamsToDataView(): void
    {
        $this->viewData['partialName'] = $this->partialName;
        $this->viewData['status'] = $this->status;
        $this->viewData['orderBy'] = $this->orderBy;
        $this->viewData['orderDescending'] = $this->orderDescending;
        $this->viewData['pageNum'] = $this->pageNumber;
        $this->viewData['itemsPerPage'] = $this->itemsPerPage;
        $this->viewData['funcionesCount'] = $this->funcionesCount;
        $this->viewData['pages'] = $this->pages;
        $this->viewData['funciones'] = $this->funciones;
        if ($this->orderBy !== '') {
            $orderByKey = 'Order'.ucfirst($this->orderBy);
            $orderByKeyNoOrder = 'OrderBy'.ucfirst($this->orderBy);
            $this->viewData[$orderByKeyNoOrder] = true;
            if ($this->orderDescending) {
                $orderByKey .= 'Desc';
            }
            $this->viewData[$orderByKey] = true;
        }
        $statusKey = 'status_'.($this->status === '' ? 'EMP' : $this->status);
        $this->viewData[$statusKey] = 'selected';
        $pagination = Paging::getPagination(
            $this->funcionesCount,
            $this->itemsPerPage,
            $this->pageNumber,
            'index.php?page=Funciones_Funciones',
            'Funciones_Funciones'
        );
        $this->viewData['pagination'] = $pagination;
    }
}
