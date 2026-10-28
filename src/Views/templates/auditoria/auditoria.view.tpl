<!-- Listado de eventos registrados en la bitacora. -->
<section class="data-panel article-panel audit-panel">
  <div class="data-panel__header">
    <div class="data-panel__heading">
      <span class="eyebrow">Seguridad / Auditoría</span>
      <h1 class="data-panel__title">Bitácora de auditoría</h1>
      <p class="data-panel__description">Consulta el historial de eventos del sistema: inicios de sesión, altas de cuenta y operaciones auditables. La bitácora es de solo lectura y no permite modificar ni eliminar registros.</p>
    </div>
    <div class="data-panel__actions">
      <span class="badge badge--outline">
        <i class="fa-solid fa-lock" aria-hidden="true"></i>
        Solo lectura
      </span>
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

  <div class="article-summary" aria-label="Resumen de la bitácora">
    <article class="article-summary__item">
      <span class="article-summary__icon" aria-hidden="true"><i class="fa-solid fa-clock-rotate-left"></i></span>
      <div class="article-summary__copy">
        <strong class="article-summary__value">{{SummaryTotal}}</strong>
        <span class="article-summary__label">{{!SummaryTotalLabel}}</span>
      </div>
    </article>
    <article class="article-summary__item article-summary__item--success">
      <span class="article-summary__icon" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
      <div class="article-summary__copy">
        <strong class="article-summary__value">{{SummaryExitos}}</strong>
        <span class="article-summary__label">{{!SummaryExitosLabel}}</span>
      </div>
    </article>
    <article class="article-summary__item article-summary__item--danger">
      <span class="article-summary__icon" aria-hidden="true"><i class="fa-solid fa-ban"></i></span>
      <div class="article-summary__copy">
        <strong class="article-summary__value">{{SummaryDenegados}}</strong>
        <span class="article-summary__label">{{!SummaryDenegadosLabel}}</span>
      </div>
    </article>
    <article class="article-summary__item article-summary__item--warning">
      <span class="article-summary__icon" aria-hidden="true"><i class="fa-solid fa-triangle-exclamation"></i></span>
      <div class="article-summary__copy">
        <strong class="article-summary__value">{{SummaryErrores}}</strong>
        <span class="article-summary__label">{{!SummaryErroresLabel}}</span>
      </div>
    </article>
  </div>

  <form class="data-toolbar audit-toolbar" action="{{!SearchAction}}" method="get" role="search">
    <input type="hidden" name="page" value="{{!ListRoute}}" />
    <input type="hidden" name="orderBy" value="{{!OrderBy}}" />
    <input type="hidden" name="orderDescending" value="{{OrderDescending}}" />
    <input type="hidden" name="itemsPerPage" value="{{ItemsPerPage}}" />
    <div class="form-field audit-toolbar__search">
      <label for="auditoriaSearch">Buscar</label>
      <input type="search" id="auditoriaSearch" name="q" value="{{!Search}}" placeholder="Operación, módulo, usuario, IP o ID" autocomplete="off" />
    </div>
    <div class="form-field">
      <label for="auditoriaModulo">Módulo</label>
      <select id="auditoriaModulo" name="modulo">
        <option value="" {{ifnot Modulo}}selected{{endifnot Modulo}}>Todos</option>
        {{foreach ModulosFiltro}}
        <option value="{{!value}}" {{selected}}>{{!label}}</option>
        {{endfor ModulosFiltro}}
      </select>
    </div>
    <div class="form-field">
      <label for="auditoriaTipo">Operación</label>
      <select id="auditoriaTipo" name="tipo">
        <option value="" {{ifnot Tipo}}selected{{endifnot Tipo}}>Todas</option>
        {{foreach TiposFiltro}}
        <option value="{{!value}}" {{selected}}>{{!label}}</option>
        {{endfor TiposFiltro}}
      </select>
    </div>
    <div class="form-field">
      <label for="auditoriaResultado">Resultado</label>
      <select id="auditoriaResultado" name="resultado">
        <option value="" {{ifnot Resultado}}selected{{endifnot Resultado}}>Todos</option>
        {{foreach ResultadosFiltro}}
        <option value="{{!value}}" {{selected}}>{{!label}}</option>
        {{endfor ResultadosFiltro}}
      </select>
    </div>
    <div class="form-field audit-toolbar__date">
      <label for="auditoriaDesde">Desde</label>
      <input type="date" id="auditoriaDesde" name="desde" value="{{!Desde}}" />
    </div>
    <div class="form-field audit-toolbar__date">
      <label for="auditoriaHasta">Hasta</label>
      <input type="date" id="auditoriaHasta" name="hasta" value="{{!Hasta}}" />
    </div>
    <div class="data-toolbar__actions">
      <button class="button button--secondary button--sm" type="submit">
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

  {{if HasEventos}}
  <div class="data-table-wrap">
    <table class="data-table data-table--list data-table--auditoria">
      <thead>
        <tr>
          <th class="data-table__col--fecha" scope="col">
            <a class="data-table__sort {{if SortFechaActive}}is-active{{endif SortFechaActive}}" href="{{!SortFechaUrl}}">
              Evento
              <i class="fa-solid {{SortFechaIcon}}" aria-hidden="true"></i>
            </a>
          </th>
          <th class="data-table__col--operacion" scope="col">
            <a class="data-table__sort {{if SortTipoActive}}is-active{{endif SortTipoActive}}" href="{{!SortTipoUrl}}">
              Operación
              <i class="fa-solid {{SortTipoIcon}}" aria-hidden="true"></i>
            </a>
          </th>
          <th class="data-table__col--modulo" scope="col">
            <a class="data-table__sort {{if SortModuloActive}}is-active{{endif SortModuloActive}}" href="{{!SortModuloUrl}}">
              Módulo
              <i class="fa-solid {{SortModuloIcon}}" aria-hidden="true"></i>
            </a>
          </th>
          <th class="data-table__col--resultado" scope="col">
            <a class="data-table__sort {{if SortResultadoActive}}is-active{{endif SortResultadoActive}}" href="{{!SortResultadoUrl}}">
              Resultado
              <i class="fa-solid {{SortResultadoIcon}}" aria-hidden="true"></i>
            </a>
          </th>
          <th class="data-table__col--usuario" scope="col">Usuario</th>
          <th class="data-table__col--origen" scope="col">Origen</th>
          <th class="data-table__col--registro" scope="col">Registro</th>
          <th class="data-table__col--acciones is-right" scope="col">Detalle</th>
        </tr>
      </thead>
      <tbody>
        {{foreach Eventos}}
        <tr>
          <td class="data-table__col--fecha">
            <div class="data-table__stack">
              <span class="data-table__nowrap">{{!fecha}}</span>
              <span class="data-table__subtext">{{!hora}} · #{{!id}}</span>
            </div>
          </td>
          <td class="data-table__col--operacion">
            <span class="badge badge--outline">
              <i class="fa-solid {{!tipoIcon}}" aria-hidden="true"></i>
              {{!tipoLabel}}
            </span>
          </td>
          <td class="data-table__col--modulo">
            <span class="data-table__truncate" title="{{!modulo}}">{{!modulo}}</span>
          </td>
          <td class="data-table__col--resultado">
            <span class="badge {{!resultadoClass}}">
              <i class="fa-solid {{!resultadoIcon}}" aria-hidden="true"></i>
              {{!resultadoLabel}}
            </span>
          </td>
          <td class="data-table__col--usuario">
            <div class="data-table__stack">
              <span class="data-table__truncate" title="{{!usuario}}">{{!usuario}}</span>
              {{if cuenta}}<span class="data-table__subtext" title="{{!cuenta}}">{{!cuenta}}</span>{{endif cuenta}}
            </div>
          </td>
          <td class="data-table__col--origen">
            <span class="audit-mono">{{!ip}}</span>
          </td>
          <td class="data-table__col--registro">
            <span class="audit-mono">{{!registro}}</span>
          </td>
          <td class="data-table__col--acciones is-right">
            <a class="button button--ghost button--sm" href="{{!detalleUrl}}" aria-label="Ver detalle del evento #{{!id}}">
              <i class="fa-solid fa-eye" aria-hidden="true"></i>
              Ver
            </a>
          </td>
        </tr>
        {{endfor Eventos}}
      </tbody>
    </table>
  </div>
  {{endif HasEventos}}

  {{ifnot HasEventos}}
  <div class="empty-state">
    <span class="empty-state__icon" aria-hidden="true"><i class="fa-solid fa-clock-rotate-left"></i></span>
    <h2 class="empty-state__title">{{!EmptyTitle}}</h2>
    <p class="empty-state__description">{{!EmptyDescription}}</p>
    <div class="empty-state__actions">
      {{if HasFilters}}
      <a class="button button--primary button--sm" href="{{!ClearUrl}}">
        <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
        Limpiar filtros
      </a>
      {{endif HasFilters}}
      <a class="button button--ghost button--sm" href="index.php?page=Dashboard_Dashboard">
        <i class="fa-solid fa-gauge-high" aria-hidden="true"></i>
        Ir al panel
      </a>
    </div>
  </div>
  {{endifnot HasEventos}}

  <div class="data-footer article-footer">
    <span class="data-footer__meta">{{!ResultsLabel}}</span>
    {{Pagination}}
  </div>
</section>
