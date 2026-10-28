<!-- Listado de mantenimientos y su estado por equipo. -->
<section class="data-panel article-panel">
  <div class="data-panel__header">
    <div class="data-panel__heading">
      <span class="eyebrow">Inventario / Mantenimientos</span>
      <h1 class="data-panel__title">Mantenimientos</h1>
      <p class="data-panel__description">Controla la recepción, el servicio técnico y la entrega de los equipos de TI.</p>
    </div>
    {{if CanManage}}
    <div class="data-panel__actions">
      <a class="button button--primary" href="{{!CreateUrl}}">
        <i class="fa-solid fa-plus" aria-hidden="true"></i>
        Nuevo mantenimiento
      </a>
    </div>
    {{endif CanManage}}
  </div>

  <div class="article-summary" aria-label="Resumen de mantenimientos">
    <article class="article-summary__item">
      <span class="article-summary__icon" aria-hidden="true"><i class="fa-solid fa-screwdriver-wrench"></i></span>
      <div class="article-summary__copy">
        <strong class="article-summary__value">{{SummaryTotal}}</strong>
        <span class="article-summary__label">Mantenimientos</span>
      </div>
    </article>
    <article class="article-summary__item article-summary__item--warning">
      <span class="article-summary__icon" aria-hidden="true"><i class="fa-solid fa-hourglass-half"></i></span>
      <div class="article-summary__copy">
        <strong class="article-summary__value">{{SummaryEnProceso}}</strong>
        <span class="article-summary__label">En proceso</span>
      </div>
    </article>
    <article class="article-summary__item">
      <span class="article-summary__icon" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
      <div class="article-summary__copy">
        <strong class="article-summary__value">{{SummaryCompletados}}</strong>
        <span class="article-summary__label">Completados</span>
      </div>
    </article>
    <article class="article-summary__item">
      <span class="article-summary__icon" aria-hidden="true"><i class="fa-solid fa-coins"></i></span>
      <div class="article-summary__copy">
        <strong class="article-summary__value">L {{!SummaryCosto}}</strong>
        <span class="article-summary__label">Costo acumulado</span>
      </div>
    </article>
  </div>

  {{if FlashHas}}
  <div class="alert alert--{{FlashType}} alert--compact article-flash" role="status" aria-live="polite">
    <span class="alert__icon" aria-hidden="true"><i class="fa-solid {{FlashIcon}}"></i></span>
    <div class="alert__content">
      <p class="alert__title">{{!FlashTitle}}</p>
      <p class="alert__message">{{!FlashMessage}}</p>
    </div>
  </div>
  {{endif FlashHas}}

  <form class="data-toolbar" action="{{!SearchAction}}" method="get" role="search">
    <input type="hidden" name="page" value="{{!ListRoute}}" />
    <input type="hidden" name="orderBy" value="{{!OrderBy}}" />
    <input type="hidden" name="orderDescending" value="{{OrderDescending}}" />
    <input type="hidden" name="itemsPerPage" value="{{ItemsPerPage}}" />
    <div class="form-field">
      <label for="maintenanceSearch">Buscar</label>
      <input type="search" id="maintenanceSearch" name="q" value="{{!Search}}" placeholder="Serie, patrimonial, artículo, técnico o diagnóstico" autocomplete="off" />
    </div>
    <div class="form-field">
      <label for="maintenanceEstado">Estado</label>
      <select id="maintenanceEstado" name="estado">
        <option value="" {{ifnot Estado}}selected{{endifnot Estado}}>Todos</option>
        {{foreach EstadosFiltro}}
        <option value="{{!value}}" {{selected}}>{{!label}}</option>
        {{endfor EstadosFiltro}}
      </select>
    </div>
    <div class="form-field">
      <label for="maintenanceTipo">Tipo de mantenimiento</label>
      <select id="maintenanceTipo" name="tipo">
        <option value="" {{ifnot TipoId}}selected{{endifnot TipoId}}>Todos</option>
        {{foreach TiposFiltro}}
        <option value="{{!value}}" {{selected}}>{{!label}}</option>
        {{endfor TiposFiltro}}
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

  {{if HasMantenimientos}}
  <div class="data-table-wrap">
    <table class="data-table data-table--list data-table--mantenimientos">
      <thead>
        <tr>
          <th class="data-table__col--equipo" scope="col">
            <a class="data-table__sort {{if SortEquipoActive}}is-active{{endif SortEquipoActive}}" href="{{!SortEquipoUrl}}">
              Equipo
              <i class="fa-solid {{SortEquipoIcon}}" aria-hidden="true"></i>
            </a>
          </th>
          <th class="data-table__col--servicio" scope="col">Tipo y técnico</th>
          <th class="data-table__col--fecha" scope="col">
            <a class="data-table__sort {{if SortFechaActive}}is-active{{endif SortFechaActive}}" href="{{!SortFechaUrl}}">
              Recepción
              <i class="fa-solid {{SortFechaIcon}}" aria-hidden="true"></i>
            </a>
          </th>
          <th class="data-table__col--costo" scope="col">
            <a class="data-table__sort {{if SortCostoActive}}is-active{{endif SortCostoActive}}" href="{{!SortCostoUrl}}">
              Costo
              <i class="fa-solid {{SortCostoIcon}}" aria-hidden="true"></i>
            </a>
          </th>
          <th class="data-table__col--estado" scope="col">
            <a class="data-table__sort {{if SortEstadoActive}}is-active{{endif SortEstadoActive}}" href="{{!SortEstadoUrl}}">
              Estado
              <i class="fa-solid {{SortEstadoIcon}}" aria-hidden="true"></i>
            </a>
          </th>
          {{if CanManage}}
          <th class="data-table__col--actions is-right" scope="col">Acciones</th>
          {{endif CanManage}}
        </tr>
      </thead>
      <tbody>
        {{foreach Mantenimientos}}
        <tr>
          <td class="data-table__col--equipo">
            <div class="equipment-stack">
              <a class="data-table__link" href="{{!editUrl}}" title="Ver mantenimiento del equipo {{!equipoLabel}}">
                <span class="data-table__code">{{!equipoLabel}}</span>
              </a>
              <span class="data-table__subtext" title="{{!equipoSubtext}}">{{!equipoSubtext}}</span>
            </div>
          </td>
          <td class="data-table__col--servicio">
            <div class="equipment-stack">
              <span class="data-table__truncate" title="{{!tipo}}">{{!tipo}}</span>
              <span class="data-table__subtext" title="{{!tecnico}}">{{!tecnico}}</span>
            </div>
          </td>
          <td class="data-table__col--fecha">
            <div class="data-table__stack">
              <span class="data-table__nowrap">{{!fechaRecepcionDate}}</span>
              {{if fechaRecepcionTime}}<span class="data-table__subtext">{{!fechaRecepcionTime}}</span>{{endif fechaRecepcionTime}}
              {{if duracionLabel}}<span class="data-table__subtext">{{!duracionLabel}}</span>{{endif duracionLabel}}
            </div>
          </td>
          <td class="data-table__col--costo">
            <span class="data-table__nowrap">L {{!costoFmt}}</span>
          </td>
          <td class="data-table__col--estado">
            <span class="badge {{estadoClass}}">
              <i class="fa-solid {{estadoIcon}}" aria-hidden="true"></i>
              {{!estadoLabel}}
            </span>
          </td>
          {{if ~CanManage}}
          <td class="data-table__col--actions is-right">
            <div class="data-table__actions">
              <a class="table-action" href="{{!editUrl}}" title="{{!rowActionTitle}}" aria-label="{{!rowActionTitle}}">
                <i class="fa-solid {{rowActionIcon}}" aria-hidden="true"></i>
              </a>
              {{if canCancel}}
              <a class="table-action table-action--danger" href="{{!cancelUrl}}" title="Cancelar mantenimiento" aria-label="Cancelar mantenimiento">
                <i class="fa-solid fa-ban" aria-hidden="true"></i>
              </a>
              {{endif canCancel}}
            </div>
          </td>
          {{endif ~CanManage}}
        </tr>
        {{endfor Mantenimientos}}
      </tbody>
    </table>
  </div>
  {{endif HasMantenimientos}}

  {{ifnot HasMantenimientos}}
  <div class="empty-state">
    <span class="empty-state__icon" aria-hidden="true"><i class="fa-solid fa-screwdriver-wrench"></i></span>
    <h2 class="empty-state__title">{{!EmptyTitle}}</h2>
    <p class="empty-state__description">{{!EmptyDescription}}</p>
    <div class="empty-state__actions">
      {{if CanManage}}
      <a class="button button--primary button--sm" href="{{!CreateUrl}}">
        <i class="fa-solid fa-plus" aria-hidden="true"></i>
        Nuevo mantenimiento
      </a>
      {{endif CanManage}}
      {{if HasFilters}}
      <a class="button button--ghost button--sm" href="{{!ClearUrl}}">
        <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
        Limpiar filtros
      </a>
      {{endif HasFilters}}
    </div>
  </div>
  {{endifnot HasMantenimientos}}

  <div class="data-footer article-footer">
    <span class="data-footer__meta">{{!ResultsLabel}}</span>
    {{Pagination}}
  </div>
</section>
