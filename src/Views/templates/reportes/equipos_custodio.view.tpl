<section class="data-panel article-panel report-panel">
  <div class="data-panel__header">
    <div class="data-panel__heading">
      <span class="eyebrow">Reportes / Equipos por custodio</span>
      <h1 class="data-panel__title">Equipos por custodio</h1>
      <p class="data-panel__description">Consulta qué equipos tiene cada responsable, su departamento y la fecha desde la que están en custodia.</p>
    </div>
    <div class="data-panel__actions">
      <a class="button button--ghost" href="{{!CentroUrl}}">
        <i class="fa-solid fa-chart-pie" aria-hidden="true"></i>
        Centro de reportes
      </a>
      <a class="button button--ghost" href="{{!KardexUrl}}">
        <i class="fa-solid fa-table-list" aria-hidden="true"></i>
        Kardex
      </a>
      <a class="button button--ghost" href="{{!ExportUrl}}">
        <i class="fa-solid fa-file-csv" aria-hidden="true"></i>
        Descargar CSV
      </a>
    </div>
  </div>

  <div class="article-summary" aria-label="Resumen de equipos por custodio">
    <article class="article-summary__item">
      <span class="article-summary__icon" aria-hidden="true"><i class="fa-solid fa-user-check"></i></span>
      <div class="article-summary__copy">
        <strong class="article-summary__value">{{SummaryCustodios}}</strong>
        <span class="article-summary__label">{{!SummaryCustodiosLabel}}</span>
      </div>
    </article>
    <article class="article-summary__item">
      <span class="article-summary__icon" aria-hidden="true"><i class="fa-solid fa-laptop"></i></span>
      <div class="article-summary__copy">
        <strong class="article-summary__value">{{SummaryEquipos}}</strong>
        <span class="article-summary__label">{{!SummaryEquiposLabel}}</span>
      </div>
    </article>
    <article class="article-summary__item">
      <span class="article-summary__icon" aria-hidden="true"><i class="fa-solid fa-building"></i></span>
      <div class="article-summary__copy">
        <strong class="article-summary__value">{{SummaryDepartamentos}}</strong>
        <span class="article-summary__label">{{!SummaryDepartamentosLabel}}</span>
      </div>
    </article>
    <article class="article-summary__item">
      <span class="article-summary__icon" aria-hidden="true"><i class="fa-solid fa-calendar-days"></i></span>
      <div class="article-summary__copy">
        <strong class="article-summary__value report-summary__date">{{!SummaryPrimeraAsignacion}}</strong>
        <span class="article-summary__label">Entrega más antigua</span>
      </div>
    </article>
  </div>

  {{if HasPorDepartamento}}
  <section class="report-visuals" aria-label="Panel visual de equipos por custodio">
    <div class="report-visuals__head">
      <div>
        <h2 class="report-visuals__title">Panel visual</h2>
        <p class="report-visuals__description">Distribución de los equipos en custodia según departamento, estado operativo y custodio.</p>
      </div>
      <span class="report-visuals__hint">
        <i class="fa-solid fa-user-shield" aria-hidden="true"></i>
        {{SummaryEquipos}} {{!SummaryEquiposLabel}}
      </span>
    </div>

    <div class="report-charts">
      <article class="report-card report-card--chart" aria-label="Equipos por departamento">
        <div class="report-card__header">
          <div>
            <h3 class="report-card__title">Equipos por departamento</h3>
            <p class="report-card__description">Departamentos con más equipos bajo responsabilidad.</p>
          </div>
        </div>
        <figure class="report-chart">
          <div class="report-chart__canvas">
            <canvas data-chart="custodiosDepartamentos" role="img" aria-label="{{!ChartDeptosAlt}}"></canvas>
          </div>
          <noscript>
            <p class="report-chart__noscript">{{!ChartDeptosAlt}}</p>
          </noscript>
        </figure>
      </article>

      {{if HasEstados}}
      <article class="report-card report-card--chart" aria-label="Equipos por estado operativo">
        <div class="report-card__header">
          <div>
            <h3 class="report-card__title">Estado operativo</h3>
            <p class="report-card__description">Condición actual de los equipos en custodia.</p>
          </div>
        </div>
        <figure class="report-chart">
          <div class="report-chart__canvas">
            <canvas data-chart="custodiosEstados" role="img" aria-label="{{!ChartEstadosAlt}}"></canvas>
          </div>
          <noscript>
            <p class="report-chart__noscript">{{!ChartEstadosAlt}}</p>
          </noscript>
        </figure>
      </article>
      {{endif HasEstados}}

      {{if HasTopCustodios}}
      <article class="report-card report-card--chart" aria-label="Custodios con más equipos">
        <div class="report-card__header">
          <div>
            <h3 class="report-card__title">Custodios con más equipos</h3>
            <p class="report-card__description">Responsables con mayor cantidad de equipos asignados.</p>
          </div>
        </div>
        <figure class="report-chart">
          <div class="report-chart__canvas">
            <canvas data-chart="custodiosTop" role="img" aria-label="{{!ChartTopAlt}}"></canvas>
          </div>
          <noscript>
            <p class="report-chart__noscript">{{!ChartTopAlt}}</p>
          </noscript>
        </figure>
      </article>
      {{endif HasTopCustodios}}
    </div>
  </section>
  {{endif HasPorDepartamento}}

  <form class="data-toolbar" action="{{!SearchAction}}" method="get" role="search">
    <input type="hidden" name="page" value="{{!ListRoute}}" />
    <input type="hidden" name="itemsPerPage" value="{{ItemsPerPage}}" />
    <div class="form-field">
      <label for="custodioSearch">Buscar</label>
      <input type="search" id="custodioSearch" name="q" value="{{!Search}}" placeholder="Nombre del custodio o departamento" autocomplete="off" />
    </div>
    <div class="form-field">
      <label for="custodioDepartamento">Departamento</label>
      <select id="custodioDepartamento" name="departamento">
        <option value="" {{ifnot Departamento}}selected{{endifnot Departamento}}>Todos</option>
        {{foreach DepartamentosFiltro}}
        <option value="{{!value}}" {{selected}}>{{!label}}</option>
        {{endfor DepartamentosFiltro}}
      </select>
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

  {{if HasCustodios}}
  <div class="data-table-wrap">
    <table class="data-table data-table--list data-table--custodios">
      <thead>
        <tr>
          <th class="data-table__col--custodio" scope="col">Custodio</th>
          <th class="data-table__col--equipos" scope="col">Equipos en custodia</th>
          <th class="data-table__col--entregas" scope="col">Entregas</th>
          <th class="data-table__col--total is-center" scope="col">Total</th>
        </tr>
      </thead>
      <tbody>
        {{foreach Custodios}}
        <tr>
          <td class="data-table__col--custodio">
            <div class="custodio-cell">
              <span class="custodio-avatar" aria-hidden="true">{{!iniciales}}</span>
              <div class="custodio-cell__copy">
                <span class="data-table__truncate" title="{{!persona}}">{{!persona}}</span>
                <span class="data-table__subtext" title="{{!departamento}}">{{!departamento}}</span>
              </div>
            </div>
          </td>
          <td class="data-table__col--equipos">
            {{if hasEquipos}}
            <ul class="custodio-equipos">
              {{foreach equipos}}
              <li class="custodio-equipo">
                <span class="custodio-equipo__serie">{{!serie}}</span>
                <span class="custodio-equipo__meta">{{!articulo}}</span>
                <span class="custodio-equipo__meta">{{!detalle}}</span>
              </li>
              {{endfor equipos}}
            </ul>
            {{endif hasEquipos}}
            {{ifnot hasEquipos}}
            <span class="data-table__subtext">Sin equipos vigentes</span>
            {{endifnot hasEquipos}}
          </td>
          <td class="data-table__col--entregas">
            <div class="data-table__stack">
              <span class="data-table__nowrap">{{!primeraFecha}}</span>
              <span class="data-table__subtext">Última: {{!ultimaFecha}}</span>
            </div>
          </td>
          <td class="data-table__col--total is-center">
            <span class="badge badge--info">{{!equiposCount}}</span>
          </td>
        </tr>
        {{endfor Custodios}}
      </tbody>
    </table>
  </div>
  {{endif HasCustodios}}

  {{ifnot HasCustodios}}
  <div class="empty-state">
    <span class="empty-state__icon" aria-hidden="true"><i class="fa-solid fa-map-location-dot"></i></span>
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
  {{endifnot HasCustodios}}

  <div class="data-footer article-footer">
    <span class="data-footer__meta">{{!ResultsLabel}}</span>
    {{Pagination}}
  </div>
</section>

<script type="application/json" id="reportChartsData">{{ChartsJson}}</script>
