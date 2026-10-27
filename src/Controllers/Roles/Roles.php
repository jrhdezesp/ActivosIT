<?php

namespace Controllers\Roles;

use Controllers\PrivateController;
use Dao\Roles\Roles as DaoRoles;
use Utilities\Context;
use Utilities\Paging;
use Views\Renderer;

/**
 * Presenta los roles disponibles y permite encontrarlos por sus datos.
 */
class Roles extends PrivateController
{
    private $partialName = '';
    private $status = '';
    private $orderBy = '';
    private $orderDescending = false;
    private $pageNumber = 1;
    private $itemsPerPage = 10;
    private $viewData = [];
    private $roles = [];
    private $rolesCount = 0;
    private $pages = 0;

    /**
     * Recupera los roles y conserva los filtros usados en el listado.
     */
    public function run(): void
    {
        $this->getParamsFromContext();
        $this->getParams();
        $tmpRoles = DaoRoles::getRoles(
            $this->partialName,
            $this->status,
            $this->orderBy,
            $this->orderDescending,
            $this->pageNumber - 1,
            $this->itemsPerPage
        );
        $this->roles = $tmpRoles['roles'];
        $this->rolesCount = $tmpRoles['total'];
        $this->pages = $this->rolesCount > 0 ? ceil($this->rolesCount / $this->itemsPerPage) : 1;
        if ($this->pageNumber > $this->pages) {
            $this->pageNumber = $this->pages;
        }
        $this->setParamsToContext();
        $this->setParamsToDataView();
        Renderer::render('roles/roles', $this->viewData);
    }

    private function getParams(): void
    {
        $this->partialName = isset($_GET['partialName']) ? $_GET['partialName'] : $this->partialName;
        $this->status = isset($_GET['status']) && in_array($_GET['status'], ['ACT', 'INA', 'EMP']) ? $_GET['status'] : $this->status;
        if ($this->status === 'EMP') {
            $this->status = '';
        }
        $this->orderBy = isset($_GET['orderBy']) && in_array($_GET['orderBy'], ['rolescod', 'rolesdsc', 'clear']) ? $_GET['orderBy'] : $this->orderBy;
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
        $this->partialName = Context::getContextByKey('roles_partialName');
        $this->status = Context::getContextByKey('roles_status');
        $this->orderBy = Context::getContextByKey('roles_orderBy');
        $this->orderDescending = boolval(Context::getContextByKey('roles_orderDescending'));
        $this->pageNumber = intval(Context::getContextByKey('roles_page'));
        $this->itemsPerPage = intval(Context::getContextByKey('roles_itemsPerPage'));
        if ($this->pageNumber < 1) {
            $this->pageNumber = 1;
        }
        if ($this->itemsPerPage < 1) {
            $this->itemsPerPage = 10;
        }
    }

    private function setParamsToContext(): void
    {
        Context::setContext('roles_partialName', $this->partialName, true);
        Context::setContext('roles_status', $this->status, true);
        Context::setContext('roles_orderBy', $this->orderBy, true);
        Context::setContext('roles_orderDescending', $this->orderDescending, true);
        Context::setContext('roles_page', $this->pageNumber, true);
        Context::setContext('roles_itemsPerPage', $this->itemsPerPage, true);
    }

    private function setParamsToDataView(): void
    {
        $this->viewData['partialName'] = $this->partialName;
        $this->viewData['status'] = $this->status;
        $this->viewData['orderBy'] = $this->orderBy;
        $this->viewData['orderDescending'] = $this->orderDescending;
        $this->viewData['pageNum'] = $this->pageNumber;
        $this->viewData['itemsPerPage'] = $this->itemsPerPage;
        $this->viewData['rolesCount'] = $this->rolesCount;
        $this->viewData['pages'] = $this->pages;
        $this->viewData['roles'] = $this->roles;
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
            $this->rolesCount,
            $this->itemsPerPage,
            $this->pageNumber,
            'index.php?page=Roles_Roles',
            'Roles_Roles'
        );
        $this->viewData['pagination'] = $pagination;
    }
}
