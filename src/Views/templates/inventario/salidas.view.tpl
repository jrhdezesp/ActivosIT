<!-- Listado de documentos de salida y sus acciones disponibles. -->
<section class="data-panel article-panel">
  <div class="data-panel__header">
    <div class="data-panel__heading">
      <span class="eyebrow">Inventario / Salidas</span>
      <h1 class="data-panel__title">Salidas de inventario</h1>
      <p class="data-panel__description">Registra y confirma el despacho, la baja o el ajuste negativo de artículos y equipos.</p>
    </div>
    {{if CanManage}}
    <div class="data-panel__actions">
      <a class="button button--primary" href="{{!CreateUrl}}">
        <i class="fa-solid fa-plus" aria-hidden="true"></i>
        Nueva salida
      </a>
    </div>
    {{endif CanManage}}
  </div>

  <div class="article-summary" aria-label="Resumen de salidas">
    <article class="article-summary__item">
      <span class="article-summary__icon" aria-hidden="true"><i class="fa-solid fa-file-export"></i></span>
      <div class="article-summary__copy">
        <strong class="article-summary__value">{{SummaryTotal}}</strong>
        <span class="article-summary__label">Documentos</span>
      </div>
    </article>
    <article class="article-summary__item article-summary__item--warning">
      <span class="article-summary__icon" aria-hidden="true"><i class="fa-solid fa-pen-ruler"></i></span>
      <div class="article-summary__copy">
        <strong class="article-summary__value">{{SummaryBorradores}}</strong>
        <span class="article-summary__label">Borradores</span>
      </div>
    </article>
    <article class="article-summary__item">
      <span class="article-summary__icon" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
      <div class="article-summary__copy">
        <strong class="article-summary__value">{{SummaryConfirmados}}</strong>
        <span class="article-summary__label">Confirmados</span>
      </div>
    </article>
    <article class="article-summary__item">
      <span class="article-summary__icon" aria-hidden="true"><i class="fa-solid fa-box-open"></i></span>
      <div class="article-summary__copy">
        <strong class="article-summary__value">{{SummaryUnidades}}</strong>
        <span class="article-summary__label">Unidades despachadas</span>
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
      <label for="salidaSearch">Buscar</label>
      <input type="search" id="salidaSearch" name="q" value="{{!Search}}" placeholder="Correlativo, destino, autorización o tipo" autocomplete="off" />
    </div>
    <div class="form-field">
      <label for="salidaEstado">Estado</label>
      <select id="salidaEstado" name="estado">
        <option value="" {{ifnot Estado}}selected{{endifnot Estado}}>Todos</option>
        {{foreach EstadosFiltro}}
        <option value="{{!value}}" {{selected}}>{{!label}}</option>
        {{endfor EstadosFiltro}}
      </select>
    </div>
    <div class="form-field">
      <label for="salidaTipo">Tipo de documento</label>
      <select id="salidaTipo" name="tipo">
        <option value="" {{ifnot TipoDocId}}selected{{endifnot TipoDocId}}>Todos</option>
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

  {{if HasSalidas}}
  <div class="data-table-wrap">
    <table class="data-table data-table--list data-table--salidas">
      <thead>
        <tr>
          <th class="data-table__col--documento" scope="col">
            <a class="data-table__sort {{if SortDocumentoActive}}is-active{{endif SortDocumentoActive}}" href="{{!SortDocumentoUrl}}">
              Documento
              <i class="fa-solid {{SortDocumentoIcon}}" aria-hidden="true"></i>
            </a>
          </th>
          <th class="data-table__col--fecha" scope="col">
            <a class="data-table__sort {{if SortFechaActive}}is-active{{endif SortFechaActive}}" href="{{!SortFechaUrl}}">
              Fecha
              <i class="fa-solid {{SortFechaIcon}}" aria-hidden="true"></i>
            </a>
          </th>
          <th class="data-table__col--destino" scope="col">Destino / autorización</th>
          <th class="data-table__col--detalle" scope="col">Detalle</th>
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
        {{foreach Salidas}}
        <tr>
          <td>
            <div class="equipment-stack">
              {{if ~CanManage}}<a class="data-table__link" href="{{!editUrl}}" title="Ver {{!correlativo}}">
                <span class="data-table__code">{{!correlativo}}</span>
              </a>{{endif ~CanManage}}
              {{ifnot ~CanManage}}<span class="data-table__code">{{!correlativo}}</span>{{endifnot ~CanManage}}
              <span class="data-table__subtext" title="{{!tipo}}">{{!tipo}}</span>
            </div>
          </td>
          <td>
            <div class="data-table__stack">
              <span class="data-table__nowrap">{{!fechaDate}}</span>
              {{if fechaTime}}<span class="data-table__subtext">{{!fechaTime}}</span>{{endif fechaTime}}
            </div>
          </td>
          <td>
            <div class="data-table__stack">
              <span class="data-table__truncate" title="{{!destino}}">{{!destino}}</span>
              {{if destinoSubtext}}<span class="data-table__subtext" title="{{!destinoSubtext}}">{{!destinoSubtext}}</span>{{endif destinoSubtext}}
            </div>
          </td>
          <td class="data-table__col--detalle">
            <div class="equipment-stack">
              <span>{{!lineasLabel}}</span>
              <span class="data-table__subtext">{{!unidadesLabel}}</span>
            </div>
          </td>
          <td>
            <span class="badge {{estadoClass}}">
              <i class="fa-solid {{estadoIcon}}" aria-hidden="true"></i>
              {{!estadoLabel}}
            </span>
          </td>
          {{if ~CanManage}}
          <td>
            <div class="data-table__actions">
              <a class="table-action" href="{{!editUrl}}" title="{{!rowActionTitle}} {{!correlativo}}" aria-label="{{!rowActionTitle}} {{!correlativo}}">
                <i class="fa-solid {{rowActionIcon}}" aria-hidden="true"></i>
              </a>
              {{if canAnnul}}
              <a class="table-action table-action--danger" href="{{!annulUrl}}" title="Anular {{!correlativo}}" aria-label="Anular {{!correlativo}}">
                <i class="fa-solid fa-ban" aria-hidden="true"></i>
              </a>
              {{endif canAnnul}}
            </div>
          </td>
          {{endif ~CanManage}}
        </tr>
        {{endfor Salidas}}
      </tbody>
    </table>
  </div>
  {{endif HasSalidas}}

  {{ifnot HasSalidas}}
  <div class="empty-state">
    <span class="empty-state__icon" aria-hidden="true"><i class="fa-solid fa-box-open"></i></span>
    <h2 class="empty-state__title">{{!EmptyTitle}}</h2>
    <p class="empty-state__description">{{!EmptyDescription}}</p>
    <div class="empty-state__actions">
      {{if CanManage}}
      <a class="button button--primary button--sm" href="{{!CreateUrl}}">
        <i class="fa-solid fa-plus" aria-hidden="true"></i>
        Nueva salida
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
  {{endifnot HasSalidas}}

  <div class="data-footer article-footer">
    <span class="data-footer__meta">{{!ResultsLabel}}</span>
    {{Pagination}}
  </div>
</section>
