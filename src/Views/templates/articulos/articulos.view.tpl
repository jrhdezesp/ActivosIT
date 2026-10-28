<!-- Listado de articulos con filtros, acciones y resumen. -->
<section class="data-panel article-panel">
  <div class="data-panel__header">
    <div class="data-panel__heading">
      <span class="eyebrow">Activos / Catálogo</span>
      <h1 class="data-panel__title">Catálogo de artículos</h1>
      <p class="data-panel__description">Administra los artículos por cantidad y los equipos que se controlan por número de serie.</p>
    </div>
    {{if CanManage}}
    <div class="data-panel__actions">
      <a class="button button--primary" href="{{!CreateUrl}}">
        <i class="fa-solid fa-plus" aria-hidden="true"></i>
        Nuevo artículo
      </a>
    </div>
    {{endif CanManage}}
  </div>

  <div class="article-summary" aria-label="Resumen de artículos activos">
    <article class="article-summary__item">
      <span class="article-summary__icon" aria-hidden="true"><i class="fa-solid fa-boxes-stacked"></i></span>
      <div class="article-summary__copy">
        <strong class="article-summary__value">{{SummaryTotal}}</strong>
        <span class="article-summary__label">Artículos activos</span>
      </div>
    </article>
    <article class="article-summary__item">
      <span class="article-summary__icon" aria-hidden="true"><i class="fa-solid fa-layer-group"></i></span>
      <div class="article-summary__copy">
        <strong class="article-summary__value">{{SummaryCantidad}}</strong>
        <span class="article-summary__label">Control por cantidad</span>
      </div>
    </article>
    <article class="article-summary__item">
      <span class="article-summary__icon" aria-hidden="true"><i class="fa-solid fa-hashtag"></i></span>
      <div class="article-summary__copy">
        <strong class="article-summary__value">{{SummarySerie}}</strong>
        <span class="article-summary__label">Control por serie</span>
      </div>
    </article>
    <article class="article-summary__item article-summary__item--warning">
      <span class="article-summary__icon" aria-hidden="true"><i class="fa-solid fa-triangle-exclamation"></i></span>
      <div class="article-summary__copy">
        <strong class="article-summary__value">{{SummaryBajo}}</strong>
        <span class="article-summary__label">Con stock bajo</span>
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
      <label for="articleSearch">Buscar</label>
      <input type="search" id="articleSearch" name="q" value="{{!Search}}" placeholder="Código o nombre" autocomplete="off" />
    </div>
    <div class="form-field">
      <label for="articleClassification">Clasificación</label>
      <select id="articleClassification" name="clasificacion">
        <option value="" {{ifnot ClasificacionId}}selected{{endifnot ClasificacionId}}>Todas</option>
        {{foreach ClasificacionesFiltro}}
        <option value="{{!value}}" {{selected}}>{{!label}}</option>
        {{endfor ClasificacionesFiltro}}
      </select>
    </div>
    <div class="form-field">
      <label for="articleStatus">Estado</label>
      <select id="articleStatus" name="status">
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

  {{if HasArticulos}}
  <div class="data-table-wrap">
    <table class="data-table data-table--list data-table--articulos">
      <thead>
        <tr>
          <th class="data-table__col--code" scope="col">
            <a class="data-table__sort {{if SortCodeActive}}is-active{{endif SortCodeActive}}" href="{{!SortCodeUrl}}">
              Código
              <i class="fa-solid {{SortCodeIcon}}" aria-hidden="true"></i>
            </a>
          </th>
          <th class="data-table__col--name" scope="col">
            <a class="data-table__sort {{if SortNameActive}}is-active{{endif SortNameActive}}" href="{{!SortNameUrl}}">
              Artículo
              <i class="fa-solid {{SortNameIcon}}" aria-hidden="true"></i>
            </a>
          </th>
          <th class="data-table__col--control" scope="col">Control</th>
          <th class="data-table__col--stock" scope="col">
            <a class="data-table__sort {{if SortStockActive}}is-active{{endif SortStockActive}}" href="{{!SortStockUrl}}">
              Existencia
              <i class="fa-solid {{SortStockIcon}}" aria-hidden="true"></i>
            </a>
          </th>
          <th class="data-table__col--status is-center" scope="col">Estado</th>
          {{if CanManage}}
          <th class="data-table__col--actions is-right" scope="col">Acciones</th>
          {{endif CanManage}}
        </tr>
      </thead>
      <tbody>
        {{foreach Articulos}}
        <tr>
          <td><span class="data-table__code" title="{{!code}}">{{!code}}</span></td>
          <td>
            <div class="article-cell">
              {{if imagenUrl}}
              <img class="article-cell__thumb" src="{{!imagenUrl}}" alt="" loading="lazy" decoding="async" />
              {{endif imagenUrl}}
              <div class="data-table__stack">
                {{if ~CanManage}}<a class="data-table__link data-table__truncate" href="{{!editUrl}}" title="Editar {{!name}}">{{!name}}</a>{{endif ~CanManage}}
                {{ifnot ~CanManage}}<span class="data-table__truncate">{{!name}}</span>{{endifnot ~CanManage}}
                <span class="data-table__subtext" title="{{!description}}">{{!description}}</span>
                <span class="data-table__subtext">{{!classification}} · {{!unit}}</span>
              </div>
            </div>
          </td>
          <td><span class="badge {{controlClass}}">{{!controlLabel}}</span></td>
          <td>
            <div class="article-stock {{stockClass}}">
              <strong>{{actual}}</strong>
              <span>{{!minLabel}}</span>
            </div>
            {{if isLow}}
            <span class="badge badge--warning article-stock__badge">Bajo</span>
            {{endif isLow}}
          </td>
          <td class="is-center"><span class="badge {{statusClass}}">{{!statusLabel}}</span></td>
          {{if ~CanManage}}
          <td>
            <div class="data-table__actions">
              <a class="table-action" href="{{!editUrl}}" title="Editar {{!name}}" aria-label="Editar {{!name}}">
                <i class="fa-solid fa-pen" aria-hidden="true"></i>
              </a>
              <a class="table-action table-action--danger" href="{{!deactivateUrl}}" title="Desactivar {{!name}}" aria-label="Desactivar {{!name}}">
                <i class="fa-solid fa-ban" aria-hidden="true"></i>
              </a>
            </div>
          </td>
          {{endif ~CanManage}}
        </tr>
        {{endfor Articulos}}
      </tbody>
    </table>
  </div>
  {{endif HasArticulos}}

  {{ifnot HasArticulos}}
  <div class="empty-state">
    <span class="empty-state__icon" aria-hidden="true"><i class="fa-solid fa-box-open"></i></span>
    <h2 class="empty-state__title">{{!EmptyTitle}}</h2>
    <p class="empty-state__description">{{!EmptyDescription}}</p>
    <div class="empty-state__actions">
      {{if CanManage}}
      <a class="button button--primary button--sm" href="{{!CreateUrl}}">
        <i class="fa-solid fa-plus" aria-hidden="true"></i>
        Nuevo artículo
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
  {{endifnot HasArticulos}}

  <div class="data-footer article-footer">
    <span class="data-footer__meta">{{!ResultsLabel}}</span>
    {{Pagination}}
  </div>
</section>
