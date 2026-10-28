<?php

namespace Controllers\Dashboard;

use Controllers\PrivateController;
use Dao\Reportes\Reportes;
use Utilities\Context;
use Utilities\Nav;
use Utilities\NavRenderer;
use Views\Renderer;

/**
 * Resume el estado del sistema y muestra accesos a sus secciones principales.
 */
class Dashboard extends PrivateController
{
    /** Prepara los indicadores y accesos que aparecen al iniciar. */
    public function run(): void
    {
        $navigation = Context::getContextByKey("NAVIGATION");
        if (!is_array($navigation)) {
            $navigation = Nav::getPrivateNavigation();
        }
        $cards = NavRenderer::forDashboard($navigation);
        $cards = array_values(array_filter($cards, function ($card) {
            return !empty($card["dashboard"]);
        }));
        $summary = Reportes::getResumenGeneral();
        Renderer::render("dashboard/dashboard", [
            "DashboardCards" => $cards,
            "DashboardDate" => date("d/m/Y"),
            "DashboardUsuarios" => $summary['usuarios'] ?? 0,
            "DashboardEquipos" => $summary['equipos'] ?? 0,
            "DashboardMovimientos" => $summary['movimientos'] ?? 0,
            "DashboardMantenimientos" => $summary['mantenimientos'] ?? 0,
        ]);
    }
}
