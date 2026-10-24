<section class="data-panel article-panel report-panel">
  <div class="data-panel__header">
    <div class="data-panel__heading">
      <span class="eyebrow">Reportes / Centro de reportes</span>
      <h1 class="data-panel__title">Centro de reportes</h1>
      <p class="data-panel__description">Consulta los indicadores del inventario y accede a los reportes de movimientos y custodia de equipos. Descarga el resumen en CSV, Excel o PDF.</p>
    </div>
    <div class="data-panel__actions">
      <a class="button button--primary" href="{{!KardexUrl}}">
        <i class="fa-solid fa-table-list" aria-hidden="true"></i>
        Abrir Kardex
      </a>
      <a class="button button--ghost" href="{{!CustodiosUrl}}">
        <i class="fa-solid fa-map-location-dot" aria-hidden="true"></i>
        Equipos por custodio
      </a>
      <a class="button button--ghost" href="{{!ExportUrl}}">
        <i class="fa-solid fa-file-csv" aria-hidden="true"></i>
        Descargar CSV
      </a>
      <a class="button button--ghost" href="{{!ExportXlsxUrl}}">
        <i class="fa-solid fa-file-excel" aria-hidden="true"></i>
        Descargar Excel
      </a>
      <a class="button button--ghost" href="{{!ExportPdfUrl}}">
        <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
        Descargar PDF
      </a>
    </div>
  </div>

  <div class="report-kpis" aria-label="Indicadores del inventario">
    {{foreach Kpis}}
    <article class="report-kpi report-kpi--{{!tone}}">
      <span class="report-kpi__icon" aria-hidden="true"><i class="fa-solid {{!icon}}"></i></span>
      <div class="report-kpi__copy">
        <span class="report-kpi__label">{{!label}}</span>
        <strong class="report-kpi__value">{{!value}}</strong>
        <span class="report-kpi__hint">{{!hint}}</span>
      </div>
    </article>
    {{endfor Kpis}}
  </div>

  <section class="report-visuals" aria-label="Panel visual de reportes">
    <div class="report-visuals__head">
      <div>
        <h2 class="report-visuals__title">Panel visual</h2>
        <p class="report-visuals__description">Indicadores gráficos del inventario y la operación.</p>
      </div>
      <span class="report-visuals__hint">
        <i class="fa-solid fa-chart-simple" aria-hidden="true"></i>
        Datos al {{!ReportDate}}
      </span>
    </div>

    <div class="report-charts">
      <article class="report-card report-card--chart" aria-label="Equipos por estado">
        <div class="report-card__header">
          <div>
            <h3 class="report-card__title">Equipos por estado</h3>
            <p class="report-card__description">Distribución de los equipos activos según su estado operativo.</p>
          </div>
        </div>
        <figure class="report-chart">
          <div class="report-chart__canvas">
            <canvas data-chart="centroEquiposEstado" role="img" aria-label="{{!ChartEstadosAlt}}"></canvas>
          </div>
          <noscript>
            <p class="report-chart__noscript">{{!ChartEstadosAlt}}</p>
          </noscript>
        </figure>
      </article>

      <article class="report-card report-card--chart" aria-label="Movimientos por mes">
        <div class="report-card__header">
          <div>
            <h3 class="report-card__title">Movimientos por mes</h3>
            <p class="report-card__description">Periodo {{!SeriePeriodoLabel}} · {{!SerieTotalEntradas}} {{!SerieEntradasLabel}} y {{!SerieTotalSalidas}} {{!SerieSalidasLabel}}.</p>
          </div>
        </div>
        <figure class="report-chart">
          <div class="report-chart__canvas">
            <canvas data-chart="centroMovimientosMes" role="img" aria-label="{{!ChartSerieAlt}}"></canvas>
          </div>
          <noscript>
            <p class="report-chart__noscript">{{!ChartSerieAlt}}</p>
          </noscript>
        </figure>
      </article>

      {{if HasDistribucion}}
      <article class="report-card report-card--chart" aria-label="Movimientos por tipo">
        <div class="report-card__header">
          <div>
            <h3 class="report-card__title">Movimientos por tipo</h3>
            <p class="report-card__description">Composición histórica de entradas, salidas y ajustes.</p>
          </div>
        </div>
        <figure class="report-chart">
          <div class="report-chart__canvas">
            <canvas data-chart="centroMovimientosTipo" role="img" aria-label="{{!ChartTiposAlt}}"></canvas>
          </div>
          <noscript>
            <p class="report-chart__noscript">{{!ChartTiposAlt}}</p>
          </noscript>
        </figure>
      </article>
      {{endif HasDistribucion}}
    </div>
  </section>

  <div class="report-body">
    <section class="report-links" aria-label="Reportes disponibles">
      {{foreach QuickReports}}
      <a class="report-link" href="{{!url}}">
        <span class="report-link__icon" aria-hidden="true"><i class="fa-solid {{!icon}}"></i></span>
        <span class="report-link__copy">
          <strong>{{!title}}</strong>
          <span>{{!description}}</span>
          <span class="report-link__hint">{{!hint}}</span>
        </span>
        <span class="report-link__metric">
          {{!metric}}
          <small>{{!metricLabel}}</small>
        </span>
        <i class="fa-solid fa-arrow-right report-link__arrow" aria-hidden="true"></i>
      </a>
      {{endfor QuickReports}}
    </section>

    <section class="report-columns" aria-label="Detalle del inventario">
      <article class="report-card">
        <div class="report-card__header">
          <div>
            <h2 class="report-card__title">Existencias bajo mínimo</h2>
            <p class="report-card__description">Artículos por cantidad que requieren reposición.</p>
          </div>
          <a class="text-link" href="{{!ArticulosUrl}}">
            Ver catálogo
            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
          </a>
        </div>

        {{if HasStockBajo}}
        <ul class="report-list">
          {{foreach StockBajo}}
          <li class="report-list__item">
            <div class="report-list__main">
              <span class="report-list__title" title="{{!nombre}}">{{!nombre}}</span>
              <span class="report-list__meta">{{!codigo}}{{if clasificacion}} · {{!clasificacion}}{{endif clasificacion}}</span>
              <span class="report-list__meta">{{!faltanteLabel}}</span>
            </div>
            <div class="report-list__side">
              <span class="report-stock">{{!stock}}<small>{{!unidad}}</small></span>
              <span class="badge {{!estadoClass}}">{{!estadoLabel}}</span>
            </div>
          </li>
          {{endfor StockBajo}}
        </ul>
        {{endif HasStockBajo}}

        {{ifnot HasStockBajo}}
        <div class="report-empty">
          <span class="report-empty__icon" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
          <strong>Sin alertas de stock</strong>
          <p>Todos los artículos por cantidad están por encima de su existencia mínima.</p>
        </div>
        {{endifnot HasStockBajo}}
      </article>

      <article class="report-card report-card--table">
        <div class="report-card__header">
          <div>
            <h2 class="report-card__title">Últimos movimientos</h2>
            <p class="report-card__description">Entradas, salidas y ajustes registrados recientemente.</p>
          </div>
          <a class="text-link" href="{{!KardexUrl}}">
            Ver Kardex
            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
          </a>
        </div>

        {{if HasMovimientos}}
        <ul class="report-list report-list--movimientos">
          {{foreach MovimientosRecientes}}
          <li class="report-list__item">
            <div class="report-list__main">
              <span class="report-list__title" title="{{!articulo}}">{{!articulo}}</span>
              <span class="report-list__meta">{{!codigo}} · {{!fecha}} {{!hora}}</span>
              <span class="report-list__meta" title="{{!documento}}">{{!documento}}</span>
            </div>
            <div class="report-list__side">
              <span class="badge {{!tipoClass}}">
                <i class="fa-solid {{!tipoIcon}}" aria-hidden="true"></i>
                {{!tipoLabel}}
              </span>
              <span class="report-quantity {{!cantidadTone}}">{{!cantidad}}</span>
              <span class="report-stock">{{!existenciaAnterior}} &rarr; {{!existenciaNueva}}</span>
            </div>
          </li>
          {{endfor MovimientosRecientes}}
        </ul>
        {{endif HasMovimientos}}

        {{ifnot HasMovimientos}}
        <div class="report-empty">
          <span class="report-empty__icon" aria-hidden="true"><i class="fa-solid fa-right-left"></i></span>
          <strong>Aún no hay movimientos</strong>
          <p>Los movimientos aparecerán aquí cuando se confirmen entradas, salidas o ajustes.</p>
        </div>
        {{endifnot HasMovimientos}}
      </article>
    </section>

    <article class="report-card" aria-label="Resumen operativo">
      <div class="report-card__header">
        <div>
          <h2 class="report-card__title">Resumen operativo</h2>
          <p class="report-card__description">Actividad vigente de asignaciones y mantenimientos.</p>
        </div>
      </div>
      <div class="report-ops">
        {{foreach Ops}}
        <div class="report-op">
          <span class="report-op__icon" aria-hidden="true"><i class="fa-solid {{!icon}}"></i></span>
          <span class="report-op__copy">
            <strong>{{!value}}</strong>
            <span>{{!label}}</span>
          </span>
        </div>
        {{endfor Ops}}
      </div>
      <p class="report-footnote">Reporte generado el {{!ReportDate}}.</p>
    </article>
  </div>
</section>

<script type="application/json" id="reportChartsData">{{ChartsJson}}</script>
