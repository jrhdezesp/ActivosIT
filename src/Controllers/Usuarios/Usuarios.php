<?php

namespace Controllers\Usuarios;

use Controllers\PrivateController;
use Dao\Usuarios\Usuarios as DaoUsuarios;
use Utilities\Context;
use Utilities\Paging;
use Views\Renderer;

class Usuarios extends PrivateController
{
    private $partialName = '';
    private $status = '';
    private $orderBy = '';
    private $orderDescending = false;
    private $pageNumber = 1;
    private $itemsPerPage = 10;
    private $viewData = [];
    private $usuarios = [];
    private $usuariosCount = 0;
    private $pages = 0;

    public function run(): void
    {
        $this->getParamsFromContext();
        $this->getParams();
        $tmpUsuarios = DaoUsuarios::getUsuarios(
            $this->partialName,
            $this->status,
            $this->orderBy,
            $this->orderDescending,
            $this->pageNumber - 1,
            $this->itemsPerPage
        );
        $this->usuarios = $tmpUsuarios['usuarios'];
        $this->usuariosCount = $tmpUsuarios['total'];
        $this->pages = $this->usuariosCount > 0 ? ceil($this->usuariosCount / $this->itemsPerPage) : 1;
        if ($this->pageNumber > $this->pages) {
            $this->pageNumber = $this->pages;
        }
        $this->setParamsToContext();
        $this->setParamsToDataView();
        Renderer::render('usuarios/usuarios', $this->viewData);
    }

    private function getParams(): void
    {
        $this->partialName = isset($_GET['partialName']) ? $_GET['partialName'] : $this->partialName;
        $this->status = isset($_GET['status']) && in_array($_GET['status'], ['ACT', 'INA', 'EMP']) ? $_GET['status'] : $this->status;
        if ($this->status === 'EMP') {
            $this->status = '';
        }
        $this->orderBy = isset($_GET['orderBy']) && in_array($_GET['orderBy'], ['usercod', 'username', 'useremail', 'clear']) ? $_GET['orderBy'] : $this->orderBy;
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
        $this->partialName = Context::getContextByKey('usuarios_partialName');
        $this->status = Context::getContextByKey('usuarios_status');
        $this->orderBy = Context::getContextByKey('usuarios_orderBy');
        $this->orderDescending = boolval(Context::getContextByKey('usuarios_orderDescending'));
        $this->pageNumber = intval(Context::getContextByKey('usuarios_page'));
        $this->itemsPerPage = intval(Context::getContextByKey('usuarios_itemsPerPage'));
        if ($this->pageNumber < 1) {
            $this->pageNumber = 1;
        }
        if ($this->itemsPerPage < 1) {
            $this->itemsPerPage = 10;
        }
    }

    private function setParamsToContext(): void
    {
        Context::setContext('usuarios_partialName', $this->partialName, true);
        Context::setContext('usuarios_status', $this->status, true);
        Context::setContext('usuarios_orderBy', $this->orderBy, true);
        Context::setContext('usuarios_orderDescending', $this->orderDescending, true);
        Context::setContext('usuarios_page', $this->pageNumber, true);
        Context::setContext('usuarios_itemsPerPage', $this->itemsPerPage, true);
    }

    private function setParamsToDataView(): void
    {
        $this->viewData['partialName'] = $this->partialName;
        $this->viewData['status'] = $this->status;
        $this->viewData['orderBy'] = $this->orderBy;
        $this->viewData['orderDescending'] = $this->orderDescending;
        $this->viewData['pageNum'] = $this->pageNumber;
        $this->viewData['itemsPerPage'] = $this->itemsPerPage;
        $this->viewData['usuariosCount'] = $this->usuariosCount;
        $this->viewData['pages'] = $this->pages;
        $this->viewData['usuarios'] = $this->usuarios;
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
            $this->usuariosCount,
            $this->itemsPerPage,
            $this->pageNumber,
            'index.php?page=Usuarios_Usuarios',
            'Usuarios_Usuarios'
        );
        $this->viewData['pagination'] = $pagination;
    }
}
