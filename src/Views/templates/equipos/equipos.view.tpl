<!-- Listado de equipos con filtros, acciones y resumen. -->
<section class="data-panel article-panel">
  <div class="data-panel__header">
    <div class="data-panel__heading">
      <span class="eyebrow">Activos / Equipos</span>
      <h1 class="data-panel__title">Equipos individuales</h1>
      <p class="data-panel__description">Controla los equipos serializados, su ubicación, garantía y estado operativo.</p>
    </div>
    {{if CanManage}}
    <div class="data-panel__actions">
      <a class="button button--primary" href="{{!CreateUrl}}">
        <i class="fa-solid fa-plus" aria-hidden="true"></i>
        Nuevo equipo
      </a>
    </div>
    {{endif CanManage}}
  </div>

  <div class="article-summary" aria-label="Resumen de equipos activos">
    <article class="article-summary__item">
      <span class="article-summary__icon" aria-hidden="true"><i class="fa-solid fa-laptop"></i></span>
      <div class="article-summary__copy">
        <strong class="article-summary__value">{{SummaryTotal}}</strong>
        <span class="article-summary__label">Equipos activos</span>
      </div>
    </article>
    <article class="article-summary__item">
      <span class="article-summary__icon" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
      <div class="article-summary__copy">
        <strong class="article-summary__value">{{SummaryDisponibles}}</strong>
        <span class="article-summary__label">Disponibles</span>
      </div>
    </article>
    <article class="article-summary__item">
      <span class="article-summary__icon" aria-hidden="true"><i class="fa-solid fa-user-check"></i></span>
      <div class="article-summary__copy">
        <strong class="article-summary__value">{{SummaryAsignados}}</strong>
        <span class="article-summary__label">Asignados</span>
      </div>
    </article>
    <article class="article-summary__item article-summary__item--warning">
      <span class="article-summary__icon" aria-hidden="true"><i class="fa-solid fa-screwdriver-wrench"></i></span>
      <div class="article-summary__copy">
        <strong class="article-summary__value">{{SummaryServicio}}</strong>
        <span class="article-summary__label">En servicio técnico</span>
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
      <label for="equipmentSearch">Buscar</label>
      <input type="search" id="equipmentSearch" name="q" value="{{!Search}}" placeholder="Serie, patrimonial, artículo o proveedor" autocomplete="off" />
    </div>
    <div class="form-field">
      <label for="equipmentOperative">Estado operativo</label>
      <select id="equipmentOperative" name="operativo">
        <option value="" {{ifnot EstadoOperativo}}selected{{endifnot EstadoOperativo}}>Todos</option>
        {{foreach EstadosFiltro}}
        <option value="{{!value}}" {{selected}}>{{!label}}</option>
        {{endfor EstadosFiltro}}
      </select>
    </div>
    <div class="form-field">
      <label for="equipmentPhysical">Estado fisico</label>
      <select id="equipmentPhysical" name="fisico">
        <option value="" {{ifnot EstadoFisico}}selected{{endifnot EstadoFisico}}>Todos</option>
        {{foreach EstadosFisicosFiltro}}
        <option value="{{!value}}" {{selected}}>{{!label}}</option>
        {{endfor EstadosFisicosFiltro}}
      </select>
    </div>
    <div class="form-field">
      <label for="equipmentArticle">Artículo</label>
      <select id="equipmentArticle" name="articulo">
        <option value="" {{ifnot ArticuloId}}selected{{endifnot ArticuloId}}>Todos</option>
        {{foreach ArticulosFiltro}}
        <option value="{{!value}}" {{selected}}>{{!label}}</option>
        {{endfor ArticulosFiltro}}
      </select>
    </div>
    <div class="form-field">
      <label for="equipmentStatus">Registro</label>
      <select id="equipmentStatus" name="status">
        <option value="" {{StatusAllSelected}}>Todos</option>
        <option value="1" {{StatusActiveSelected}}>Activos</option>
        <option value="0" {{StatusInactiveSelected}}>Inactivos</option>
      </select>
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

  {{if HasEquipos}}
  <div class="data-table-wrap">
    <table class="data-table data-table--list data-table--equipos">
      <thead>
        <tr>
          <th class="data-table__col--equipo" scope="col">
            <a class="data-table__sort {{if SortSerialActive}}is-active{{endif SortSerialActive}}" href="{{!SortSerialUrl}}">
              Equipo
              <i class="fa-solid {{SortSerialIcon}}" aria-hidden="true"></i>
            </a>
          </th>
          <th class="data-table__col--articulo" scope="col">
            <a class="data-table__sort {{if SortArticleActive}}is-active{{endif SortArticleActive}}" href="{{!SortArticleUrl}}">
              Artículo
              <i class="fa-solid {{SortArticleIcon}}" aria-hidden="true"></i>
            </a>
          </th>
          <th class="data-table__col--ubicacion" scope="col">Ubicación</th>
          <th class="data-table__col--estado" scope="col">Estado</th>
          <th class="data-table__col--fechas" scope="col">
            <a class="data-table__sort {{if SortDateActive}}is-active{{endif SortDateActive}}" href="{{!SortDateUrl}}">
              Adquisición
              <i class="fa-solid {{SortDateIcon}}" aria-hidden="true"></i>
            </a>
          </th>
          {{if CanManage}}
          <th class="data-table__col--actions is-right" scope="col">Acciones</th>
          {{endif CanManage}}
        </tr>
      </thead>
      <tbody>
        {{foreach Equipos}}
        <tr>
          <td>
            <div class="equipment-stack">
              {{if ~CanManage}}<a class="data-table__link" href="{{!editUrl}}" title="Editar {{!serial}}">
                <span class="data-table__code">{{!serial}}</span>
              </a>{{endif ~CanManage}}
              {{ifnot ~CanManage}}<span class="data-table__code">{{!serial}}</span>{{endifnot ~CanManage}}
              <span class="data-table__subtext" title="{{!patrimonial}}">{{!patrimonial}}</span>
              {{if isInactive}}<span class="badge badge--danger">Inactivo</span>{{endif isInactive}}
            </div>
          </td>
          <td>
            <div class="data-table__stack">
              {{if ~CanManage}}<a class="data-table__link data-table__truncate" href="{{!editUrl}}" title="Editar {{!article}}">{{!article}}</a>{{endif ~CanManage}}
              {{ifnot ~CanManage}}<span class="data-table__truncate">{{!article}}</span>{{endifnot ~CanManage}}
              <span class="data-table__subtext">{{!articleCode}}</span>
            </div>
          </td>
          <td><span class="data-table__truncate" title="{{!location}}">{{!location}}</span></td>
          <td>
            <div class="equipment-stack">
              <span class="badge {{estadoClass}}">
                <i class="fa-solid {{estadoIcon}}" aria-hidden="true"></i>
                {{!estadoLabel}}
              </span>
              <span class="badge {{estadoFisicoClass}}">Fisico: {{!estadoFisicoLabel}}</span>
              {{if motivoLabel}}<span class="data-table__subtext" title="{{!motivoLabel}}">{{!motivoLabel}}</span>{{endif motivoLabel}}
            </div>
          </td>
          <td class="data-table__col--fechas">
            <div class="equipment-stack">
              <span>{{!acquisitionLabel}}</span>
              <span class="data-table__subtext">{{!warrantyLabel}}</span>
              {{if warrantyBadge}}
              <span class="badge {{warrantyBadgeClass}}">{{!warrantyBadge}}</span>
              {{endif warrantyBadge}}
            </div>
          </td>
          {{if ~CanManage}}
          <td>
            <div class="data-table__actions">
              <a class="table-action" href="{{!editUrl}}" title="Editar {{!serial}}" aria-label="Editar {{!serial}}">
                <i class="fa-solid fa-pen" aria-hidden="true"></i>
              </a>
              <a class="table-action table-action--danger" href="{{!deactivateUrl}}" title="Desactivar {{!serial}}" aria-label="Desactivar {{!serial}}">
                <i class="fa-solid fa-ban" aria-hidden="true"></i>
              </a>
            </div>
          </td>
          {{endif ~CanManage}}
        </tr>
        {{endfor Equipos}}
      </tbody>
    </table>
  </div>
  {{endif HasEquipos}}

  {{ifnot HasEquipos}}
  <div class="empty-state">
    <span class="empty-state__icon" aria-hidden="true"><i class="fa-solid fa-laptop"></i></span>
    <h2 class="empty-state__title">{{!EmptyTitle}}</h2>
    <p class="empty-state__description">{{!EmptyDescription}}</p>
    <div class="empty-state__actions">
      {{if CanManage}}
      <a class="button button--primary button--sm" href="{{!CreateUrl}}">
        <i class="fa-solid fa-plus" aria-hidden="true"></i>
        Nuevo equipo
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
  {{endifnot HasEquipos}}

  <div class="data-footer article-footer">
    <span class="data-footer__meta">{{!ResultsLabel}}</span>
    {{Pagination}}
  </div>
</section>
