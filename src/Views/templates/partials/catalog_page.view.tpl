<section class="catalog-hero">
  <div class="catalog-hero__copy">
    <span class="eyebrow">{{!CatalogEyebrow}}</span>
    <h1>{{!CatalogTitle}}</h1>
    <p>{{!CatalogDescription}}</p>
  </div>
  <div class="catalog-hero__aside">
    <span class="badge badge--success">
      <i class="fa-solid fa-database" aria-hidden="true"></i>
      Base de datos
    </span>
    <span class="catalog-hero__meta">{{!CatalogTotalLabel}}</span>
  </div>
</section>

<nav class="catalog-tabs" aria-label="Secciones de catálogos">
  <a class="catalog-tab {{if CatalogGeneralActive}}is-active{{endif CatalogGeneralActive}}" href="index.php?page=Catalogos_Catalogos" {{if CatalogGeneralActive}}aria-current="page"{{endif CatalogGeneralActive}}>
    <span class="catalog-tab__icon"><i class="fa-solid fa-list-check" aria-hidden="true"></i></span>
    <span class="catalog-tab__copy">
      <strong>Generales</strong>
      <small>Clasificaciones, ubicaciones y unidades</small>
    </span>
  </a>
  <a class="catalog-tab {{if CatalogOperativoActive}}is-active{{endif CatalogOperativoActive}}" href="index.php?page=Catalogos_CatalogosOperativos" {{if CatalogOperativoActive}}aria-current="page"{{endif CatalogOperativoActive}}>
    <span class="catalog-tab__icon"><i class="fa-solid fa-sliders" aria-hidden="true"></i></span>
    <span class="catalog-tab__copy">
      <strong>Operativos</strong>
      <small>Documentos, mantenimiento y motivos</small>
    </span>
  </a>
</nav>

{{if CatalogHasFlash}}
<div class="alert alert--{{CatalogFlashType}} alert--compact catalog-flash" role="status" aria-live="polite">
  <span class="alert__icon" aria-hidden="true"><i class="fa-solid {{CatalogFlashIcon}}"></i></span>
  <div class="alert__content">
    <p class="alert__title">{{!CatalogFlashTitle}}</p>
    <p class="alert__message">{{!CatalogFlashMessage}}</p>
  </div>
</div>
{{endif CatalogHasFlash}}

<form class="catalog-toolbar" action="{{!CatalogSearchAction}}" method="get" role="search">
  <input type="hidden" name="page" value="{{!CatalogPageRoute}}" />
  <div class="form-field catalog-toolbar__field catalog-toolbar__field--search">
    <label for="catalogSearch">Buscar</label>
    <input type="search" id="catalogSearch" name="q" value="{{!CatalogSearch}}" placeholder="Nombre o descripción" autocomplete="off" />
  </div>
  <div class="form-field catalog-toolbar__field catalog-toolbar__field--status">
    <label for="catalogStatus">Estado</label>
    <select id="catalogStatus" name="status">
      <option value="" {{CatalogStatusAllSelected}}>Todos</option>
      <option value="1" {{CatalogStatusActiveSelected}}>Activos</option>
      <option value="0" {{CatalogStatusInactiveSelected}}>Inactivos</option>
    </select>
  </div>
  <div class="catalog-toolbar__actions">
    <button class="button button--primary button--sm" type="submit">
      <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
      Buscar
    </button>
    {{if CatalogHasFilters}}
    <a class="button button--ghost button--sm" href="{{!CatalogClearUrl}}">
      <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
      Limpiar
    </a>
    {{endif CatalogHasFilters}}
  </div>
</form>

<div class="catalog-grid">
  {{foreach CatalogGroups}}
  <section class="catalog-card" aria-labelledby="catalog-title-{{!type}}">
    <div class="catalog-card__header">
      <span class="catalog-card__icon" data-tone="{{!tone}}">
        <i class="fa-solid {{icon}}" aria-hidden="true"></i>
      </span>
      <div class="catalog-card__heading">
        <div class="catalog-card__title-row">
          <h2 id="catalog-title-{{!type}}">{{!title}}</h2>
          <span class="badge badge--outline">{{!countLabel}}</span>
        </div>
        <p>{{!description}}</p>
      </div>
    </div>

    {{if hasItems}}
    <ul class="catalog-list">
      {{foreach items}}
      <li class="catalog-item">
        <div class="catalog-item__copy">
          <strong>{{!name}}</strong>
          <span>{{!detail}}</span>
        </div>
        <div class="catalog-item__side">
          <span class="catalog-item__code">{{!code}}</span>
          {{if typeLabel}}
          <span class="badge {{typeClass}}">{{!typeLabel}}</span>
          {{endif typeLabel}}
          <span class="badge {{statusClass}}">{{!statusLabel}}</span>
          <div class="catalog-item__actions">
            <a class="catalog-item__action" href="{{!editUrl}}" title="Editar {{!name}}" aria-label="Editar {{!name}}">
              <i class="fa-solid fa-pen" aria-hidden="true"></i>
            </a>
            <a class="catalog-item__action catalog-item__action--danger" href="{{!deleteUrl}}" title="Eliminar {{!name}}" aria-label="Eliminar {{!name}}">
              <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
            </a>
          </div>
        </div>
      </li>
      {{endfor items}}
    </ul>
    {{endif hasItems}}

    {{ifnot hasItems}}
    <div class="catalog-empty">
      <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
      <p>{{!emptyLabel}}</p>
    </div>
    {{endifnot hasItems}}

    <div class="catalog-card__footer">
      <span class="catalog-card__hint">{{!hint}}</span>
      <a class="button button--ghost button--sm" href="{{!createUrl}}">
        <i class="fa-solid fa-plus" aria-hidden="true"></i>
        Agregar
      </a>
    </div>
  </section>
  {{endfor CatalogGroups}}
</div>
