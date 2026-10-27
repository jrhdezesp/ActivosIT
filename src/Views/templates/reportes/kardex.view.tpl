<!-- Historial de movimientos de inventario y sus filtros. -->
<section class="data-panel article-panel report-panel">
  <div class="data-panel__header">
    <div class="data-panel__heading">
      <span class="eyebrow">Reportes / Kardex</span>
      <h1 class="data-panel__title">Kardex de movimientos</h1>
      <p class="data-panel__description">Consulta el historial de entradas, salidas y ajustes con las existencias antes y después de cada movimiento. Descarga el resultado filtrado en CSV, Excel o PDF.</p>
    </div>
    <div class="data-panel__actions">
      <a class="button button--ghost" href="{{!CentroUrl}}">
        <i class="fa-solid fa-chart-pie" aria-hidden="true"></i>
        Centro de reportes
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

  <div class="article-summary" aria-label="Resumen del Kardex">
    <article class="article-summary__item">
      <span class="article-summary__icon" aria-hidden="true"><i class="fa-solid fa-right-left"></i></span>
      <div class="article-summary__copy">
        <strong class="article-summary__value">{{SummaryTotal}}</strong>
        <span class="article-summary__label">{{!SummaryTotalLabel}}</span>
      </div>
    </article>
    <article class="article-summary__item">
      <span class="article-summary__icon" aria-hidden="true"><i class="fa-solid fa-circle-arrow-down"></i></span>
      <div class="article-summary__copy">
        <strong class="article-summary__value">{{SummaryEntradas}}</strong>
        <span class="article-summary__label">{{!SummaryEntradasLabel}}</span>
      </div>
    </article>
    <article class="article-summary__item">
      <span class="article-summary__icon" aria-hidden="true"><i class="fa-solid fa-circle-arrow-up"></i></span>
      <div class="article-summary__copy">
        <strong class="article-summary__value">{{SummarySalidas}}</strong>
        <span class="article-summary__label">{{!SummarySalidasLabel}}</span>
      </div>
    </article>
    <article class="article-summary__item">
      <span class="article-summary__icon" aria-hidden="true"><i class="fa-solid fa-boxes-stacked"></i></span>
      <div class="article-summary__copy">
        <strong class="article-summary__value">{{SummaryArticulos}}</strong>
        <span class="article-summary__label">{{!SummaryArticulosLabel}}</span>
      </div>
    </article>
  </div>

  {{if HasMovimientos}}
  <section class="report-visuals" aria-label="Panel visual del Kardex">
    <div class="report-visuals__head">
      <div>
        <h2 class="report-visuals__title">Panel visual</h2>
        <p class="report-visuals__description">Evolución, composición y artículos con más movimientos según los filtros aplicados.</p>
      </div>
      <span class="report-visuals__hint">
        <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
        {{!ChartSerieRango}}
      </span>
    </div>

    <div class="report-charts">
      <article class="report-card report-card--chart" aria-label="Evolución de movimientos">
        <div class="report-card__header">
          <div>
            <h3 class="report-card__title">Evolución de movimientos</h3>
            <p class="report-card__description">Unidades por {{!ChartSerieUnidad}} · {{!ChartSerieRango}}</p>
          </div>
        </div>
        <figure class="report-chart">
          <div class="report-chart__canvas">
            <canvas data-chart="kardexSerie" role="img" aria-label="{{!ChartSerieAlt}}"></canvas>
          </div>
          <noscript>
            <p class="report-chart__noscript">{{!ChartSerieAlt}}</p>
          </noscript>
        </figure>
      </article>

      {{if HasDistribucion}}
      <article class="report-card report-card--chart" aria-label="Distribución por tipo de movimiento">
        <div class="report-card__header">
          <div>
            <h3 class="report-card__title">Distribución por tipo</h3>
            <p class="report-card__description">Conteo de movimientos según los filtros aplicados.</p>
          </div>
        </div>
        <figure class="report-chart">
          <div class="report-chart__canvas">
            <canvas data-chart="kardexTipos" role="img" aria-label="{{!ChartTiposAlt}}"></canvas>
          </div>
          <noscript>
            <p class="report-chart__noscript">{{!ChartTiposAlt}}</p>
          </noscript>
        </figure>
      </article>
      {{endif HasDistribucion}}

      {{if HasTopArticulos}}
      <article class="report-card report-card--chart" aria-label="Artículos con más movimientos">
        <div class="report-card__header">
          <div>
            <h3 class="report-card__title">Artículos con más movimientos</h3>
            <p class="report-card__description">Los artículos con mayor actividad dentro del filtro actual.</p>
          </div>
        </div>
        <figure class="report-chart">
          <div class="report-chart__canvas">
            <canvas data-chart="kardexTopArticulos" role="img" aria-label="{{!ChartTopAlt}}"></canvas>
          </div>
          <noscript>
            <p class="report-chart__noscript">{{!ChartTopAlt}}</p>
          </noscript>
        </figure>
      </article>
      {{endif HasTopArticulos}}
    </div>
  </section>
  {{endif HasMovimientos}}

  <form class="data-toolbar report-toolbar" action="{{!SearchAction}}" method="get" role="search">
    <input type="hidden" name="page" value="{{!ListRoute}}" />
    <input type="hidden" name="orderBy" value="{{!OrderBy}}" />
    <input type="hidden" name="orderDescending" value="{{OrderDescending}}" />
    <input type="hidden" name="itemsPerPage" value="{{ItemsPerPage}}" />
    <div class="form-field">
      <label for="kardexSearch">Buscar</label>
      <input type="search" id="kardexSearch" name="q" value="{{!Search}}" placeholder="Artículo, documento, usuario u observación" autocomplete="off" />
    </div>
    <div class="form-field">
      <label for="kardexTipo">Tipo</label>
      <select id="kardexTipo" name="tipo">
        <option value="" {{ifnot Tipo}}selected{{endifnot Tipo}}>Todos</option>
        {{foreach TiposFiltro}}
        <option value="{{!value}}" {{selected}}>{{!label}}</option>
        {{endfor TiposFiltro}}
      </select>
    </div>
    <div class="form-field report-toolbar__article">
      <label for="kardexArticulo">Artículo</label>
      <select id="kardexArticulo" name="articulo">
        <option value="0" {{ifnot ArticuloId}}selected{{endifnot ArticuloId}}>Todos</option>
        {{foreach ArticulosFiltro}}
        <option value="{{!value}}" {{selected}}>{{!label}}</option>
        {{endfor ArticulosFiltro}}
      </select>
    </div>
    <div class="form-field report-toolbar__date">
      <label for="kardexDesde">Desde</label>
      <input type="date" id="kardexDesde" name="desde" value="{{!Desde}}" />
    </div>
    <div class="form-field report-toolbar__date">
      <label for="kardexHasta">Hasta</label>
      <input type="date" id="kardexHasta" name="hasta" value="{{!Hasta}}" />
    </div>
    <div class="data-toolbar__actions">
      <button class="button button--primary button--sm" type="submit">
        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
        Filtrar
      </button>
      {{if HasFilters}}
      <a class="button button--ghost button--sm" href="{{!ClearUrl}}">
        <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
        Limpiar
      </a>
      {{endif HasFilters}}
    </div>
  </form>

  {{if HasMovimientos}}
  <div class="data-table-wrap">
    <table class="data-table data-table--list data-table--kardex">
      <thead>
        <tr>
          <th class="data-table__col--movimiento" scope="col">
            <a class="data-table__sort {{if SortMovimientoActive}}is-active{{endif SortMovimientoActive}}" href="{{!SortMovimientoUrl}}">
              Movimiento
              <i class="fa-solid {{SortMovimientoIcon}}" aria-hidden="true"></i>
            </a>
          </th>
          <th class="data-table__col--articulo" scope="col">
            <a class="data-table__sort {{if SortArticuloActive}}is-active{{endif SortArticuloActive}}" href="{{!SortArticuloUrl}}">
              Artículo
              <i class="fa-solid {{SortArticuloIcon}}" aria-hidden="true"></i>
            </a>
          </th>
          <th class="data-table__col--tipo" scope="col">
            <a class="data-table__sort {{if SortTipoActive}}is-active{{endif SortTipoActive}}" href="{{!SortTipoUrl}}">
              Tipo
              <i class="fa-solid {{SortTipoIcon}}" aria-hidden="true"></i>
            </a>
          </th>
          <th class="data-table__col--cantidad is-right" scope="col">
            <a class="data-table__sort {{if SortCantidadActive}}is-active{{endif SortCantidadActive}}" href="{{!SortCantidadUrl}}">
              Cant.
              <i class="fa-solid {{SortCantidadIcon}}" aria-hidden="true"></i>
            </a>
          </th>
          <th class="data-table__col--existencias" scope="col">
            <a class="data-table__sort {{if SortExistenciaActive}}is-active{{endif SortExistenciaActive}}" href="{{!SortExistenciaUrl}}">
              Existencias
              <i class="fa-solid {{SortExistenciaIcon}}" aria-hidden="true"></i>
            </a>
          </th>
          <th class="data-table__col--documento" scope="col">Documento</th>
          <th class="data-table__col--usuario" scope="col">Usuario</th>
        </tr>
      </thead>
      <tbody>
        {{foreach Movimientos}}
        <tr>
          <td class="data-table__col--movimiento">
            <div class="data-table__stack">
              <span class="data-table__nowrap">{{!fecha}}</span>
              <span class="data-table__subtext">{{!hora}} · #{{!id}}</span>
            </div>
          </td>
          <td class="data-table__col--articulo">
            <div class="data-table__stack">
              <span class="data-table__truncate" title="{{!articulo}}">{{!articulo}}</span>
              <span class="data-table__subtext">{{!codigo}}</span>
              <span class="data-table__subtext" title="{{!observacion}}">{{!observacion}}</span>
            </div>
          </td>
          <td class="data-table__col--tipo">
            <span class="badge {{!tipoClass}}">
              <i class="fa-solid {{!tipoIcon}}" aria-hidden="true"></i>
              {{!tipoLabel}}
            </span>
          </td>
          <td class="data-table__col--cantidad is-right">
            <span class="report-quantity {{!cantidadTone}}">{{!cantidad}}</span>
          </td>
          <td class="data-table__col--existencias">
            <div class="data-table__stack">
              <span class="data-table__nowrap">{{!existenciaAnterior}} &rarr; {{!existenciaNueva}}</span>
              <span class="data-table__subtext">Saldo final</span>
            </div>
          </td>
          <td class="data-table__col--documento">
            <span class="data-table__truncate" title="{{!documento}}">{{!documento}}</span>
          </td>
          <td class="data-table__col--usuario">
            <div class="data-table__stack">
              <span class="data-table__truncate" title="{{!usuario}}">{{!usuario}}</span>
              {{if cuenta}}<span class="data-table__subtext">{{!cuenta}}</span>{{endif cuenta}}
            </div>
          </td>
        </tr>
        {{endfor Movimientos}}
      </tbody>
    </table>
  </div>
  {{endif HasMovimientos}}

  {{ifnot HasMovimientos}}
  <div class="empty-state">
    <span class="empty-state__icon" aria-hidden="true"><i class="fa-solid fa-table-list"></i></span>
    <h2 class="empty-state__title">{{!EmptyTitle}}</h2>
    <p class="empty-state__description">{{!EmptyDescription}}</p>
    <div class="empty-state__actions">
      {{if HasFilters}}
      <a class="button button--primary button--sm" href="{{!ClearUrl}}">
        <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
        Limpiar filtros
      </a>
      {{endif HasFilters}}
      <a class="button button--ghost button--sm" href="{{!CentroUrl}}">
        <i class="fa-solid fa-chart-pie" aria-hidden="true"></i>
        Volver al centro
      </a>
    </div>
  </div>
  {{endifnot HasMovimientos}}

  <div class="data-footer article-footer">
    <span class="data-footer__meta">{{!ResultsLabel}}</span>
    {{Pagination}}
  </div>
</section>

<script type="application/json" id="reportChartsData">{{ChartsJson}}</script>
