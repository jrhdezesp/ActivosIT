<section class="data-panel article-panel">
  <div class="data-panel__header">
    <div class="data-panel__heading">
      <span class="eyebrow">Inventario / Asignaciones</span>
      <h1 class="data-panel__title">Asignaciones de equipos</h1>
      <p class="data-panel__description">Controla qué equipo tiene cada responsable y registra su devolución.</p>
    </div>
    {{if CanManage}}
    <div class="data-panel__actions">
      <a class="button button--primary" href="{{!CreateUrl}}">
        <i class="fa-solid fa-plus" aria-hidden="true"></i>
        Nueva asignación
      </a>
    </div>
    {{endif CanManage}}
  </div>

  <div class="article-summary" aria-label="Resumen de asignaciones">
    <article class="article-summary__item">
      <span class="article-summary__icon" aria-hidden="true"><i class="fa-solid fa-people-arrows"></i></span>
      <div class="article-summary__copy">
        <strong class="article-summary__value">{{SummaryTotal}}</strong>
        <span class="article-summary__label">Asignaciones</span>
      </div>
    </article>
    <article class="article-summary__item">
      <span class="article-summary__icon" aria-hidden="true"><i class="fa-solid fa-user-check"></i></span>
      <div class="article-summary__copy">
        <strong class="article-summary__value">{{SummaryActivas}}</strong>
        <span class="article-summary__label">Activas</span>
      </div>
    </article>
    <article class="article-summary__item">
      <span class="article-summary__icon" aria-hidden="true"><i class="fa-solid fa-rotate-left"></i></span>
      <div class="article-summary__copy">
        <strong class="article-summary__value">{{SummaryDevueltas}}</strong>
        <span class="article-summary__label">Devueltas</span>
      </div>
    </article>
    <article class="article-summary__item">
      <span class="article-summary__icon" aria-hidden="true"><i class="fa-solid fa-laptop"></i></span>
      <div class="article-summary__copy">
        <strong class="article-summary__value">{{SummaryEquiposActivos}}</strong>
        <span class="article-summary__label">Equipos en custodia</span>
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
      <label for="assignmentSearch">Buscar</label>
      <input type="search" id="assignmentSearch" name="q" value="{{!Search}}" placeholder="Responsable, departamento, serie, patrimonio o artículo" autocomplete="off" />
    </div>
    <div class="form-field">
      <label for="assignmentEstado">Estado</label>
      <select id="assignmentEstado" name="estado">
        <option value="" {{ifnot Estado}}selected{{endifnot Estado}}>Todos</option>
        {{foreach EstadosFiltro}}
        <option value="{{!value}}" {{selected}}>{{!label}}</option>
        {{endfor EstadosFiltro}}
      </select>
    </div>
    <div class="form-field">
      <label for="assignmentDepartamento">Departamento</label>
      <select id="assignmentDepartamento" name="departamento">
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

  {{if HasAsignaciones}}
  <div class="data-table-wrap">
    <table class="data-table data-table--list data-table--asignaciones">
      <thead>
        <tr>
          <th class="data-table__col--equipo" scope="col">
            <a class="data-table__sort {{if SortEquipoActive}}is-active{{endif SortEquipoActive}}" href="{{!SortEquipoUrl}}">
              Equipo
              <i class="fa-solid {{SortEquipoIcon}}" aria-hidden="true"></i>
            </a>
          </th>
          <th class="data-table__col--responsable" scope="col">
            <a class="data-table__sort {{if SortPersonaActive}}is-active{{endif SortPersonaActive}}" href="{{!SortPersonaUrl}}">
              Responsable
              <i class="fa-solid {{SortPersonaIcon}}" aria-hidden="true"></i>
            </a>
          </th>
          <th class="data-table__col--entrega" scope="col">
            <a class="data-table__sort {{if SortFechaActive}}is-active{{endif SortFechaActive}}" href="{{!SortFechaUrl}}">
              Entrega
              <i class="fa-solid {{SortFechaIcon}}" aria-hidden="true"></i>
            </a>
          </th>
          <th class="data-table__col--devolucion" scope="col">
            <a class="data-table__sort {{if SortDevolucionActive}}is-active{{endif SortDevolucionActive}}" href="{{!SortDevolucionUrl}}">
              Devolución
              <i class="fa-solid {{SortDevolucionIcon}}" aria-hidden="true"></i>
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
        {{foreach Asignaciones}}
        <tr>
          <td>
            <div class="equipment-stack">
              <a class="data-table__link" href="{{!editUrl}}" title="Ver asignación del equipo {{!equipoLabel}}">
                <span class="data-table__code">{{!equipoLabel}}</span>
              </a>
              <span class="data-table__subtext" title="{{!equipoSubtext}}">{{!equipoSubtext}}</span>
            </div>
          </td>
          <td>
            <div class="equipment-stack">
              <span class="data-table__truncate" title="{{!persona}}">{{!persona}}</span>
              <span class="data-table__subtext" title="{{!departamento}}">{{!departamento}}</span>
            </div>
          </td>
          <td>
            <div class="data-table__stack">
              <span class="data-table__nowrap">{{!fechaAsignacionDate}}</span>
              <span class="data-table__subtext">{{!fechaAsignacionTime}}</span>
              {{if entregaNombre}}<span class="data-table__subtext" title="{{!entregaNombre}}">{{!entregaNombre}}</span>{{endif entregaNombre}}
            </div>
          </td>
          <td>
            <div class="data-table__stack">
              {{if fechaDevolucionDate}}
              <span class="data-table__nowrap">{{!fechaDevolucionDate}}</span>
              <span class="data-table__subtext">{{!fechaDevolucionTime}}</span>
              {{if recibeNombre}}<span class="data-table__subtext" title="{{!recibeNombre}}">{{!recibeNombre}}</span>{{endif recibeNombre}}
              {{endif fechaDevolucionDate}}
              {{if devolucionLabel}}<span class="data-table__subtext">{{!devolucionLabel}}</span>{{endif devolucionLabel}}
            </div>
          </td>
          <td>
            <span class="badge {{estadoClass}}">
              <i class="fa-solid {{estadoIcon}}" aria-hidden="true"></i>
              {{!estadoLabel}}
            </span>
          </td>
          {{if CanManage}}
          <td>
            <div class="data-table__actions">
              <a class="table-action" href="{{!editUrl}}" title="{{!rowActionTitle}}" aria-label="{{!rowActionTitle}}">
                <i class="fa-solid {{rowActionIcon}}" aria-hidden="true"></i>
              </a>
              {{if canReturn}}
              <a class="table-action" href="{{!returnUrl}}" title="Registrar devolución" aria-label="Registrar devolución">
                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
              </a>
              {{endif canReturn}}
            </div>
          </td>
          {{endif CanManage}}
        </tr>
        {{endfor Asignaciones}}
      </tbody>
    </table>
  </div>
  {{endif HasAsignaciones}}

  {{ifnot HasAsignaciones}}
  <div class="empty-state">
    <span class="empty-state__icon" aria-hidden="true"><i class="fa-solid fa-people-arrows"></i></span>
    <h2 class="empty-state__title">{{!EmptyTitle}}</h2>
    <p class="empty-state__description">{{!EmptyDescription}}</p>
    <div class="empty-state__actions">
      {{if CanManage}}
      <a class="button button--primary button--sm" href="{{!CreateUrl}}">
        <i class="fa-solid fa-plus" aria-hidden="true"></i>
        Nueva asignación
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
  {{endifnot HasAsignaciones}}

  <div class="data-footer article-footer">
    <span class="data-footer__meta">{{!ResultsLabel}}</span>
    {{Pagination}}
  </div>
</section>
