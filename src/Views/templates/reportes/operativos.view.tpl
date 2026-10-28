<!-- Presentacion y filtros de los reportes operativos. -->
<section class="data-panel article-panel report-panel">
  <div class="data-panel__header">
    <div class="data-panel__heading">
      <span class="eyebrow">Reportes / Reportes operativos</span>
      <h1 class="data-panel__title">{{!ReportTitle}}</h1>
      <p class="data-panel__description">{{!ReportDescription}}</p>
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
      <a class="button button--ghost" href="{{!ExportCsvUrl}}">
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

  <div class="article-summary" aria-label="Resumen del reporte operativo">
    <article class="article-summary__item">
      <span class="article-summary__icon" aria-hidden="true"><i class="fa-solid fa-list-check"></i></span>
      <div class="article-summary__copy">
        <strong class="article-summary__value">{{ResultCount}}</strong>
        <span class="article-summary__label">{{if HasRows}}Resultados encontrados{{endif HasRows}}{{ifnot HasRows}}Sin resultados{{endifnot HasRows}}</span>
      </div>
    </article>
    <article class="article-summary__item">
      <span class="article-summary__icon" aria-hidden="true"><i class="fa-solid fa-filter"></i></span>
      <div class="article-summary__copy">
        <strong class="article-summary__value">{{if HasFilters}}Activo{{endif HasFilters}}{{ifnot HasFilters}}Todos{{endifnot HasFilters}}</strong>
        <span class="article-summary__label">Alcance del filtro</span>
      </div>
    </article>
    <article class="article-summary__item">
      <span class="article-summary__icon" aria-hidden="true"><i class="fa-solid fa-calendar-days"></i></span>
      <div class="article-summary__copy">
        <strong class="article-summary__value report-summary__date">{{!ReportDate}}</strong>
        <span class="article-summary__label">Fecha de generación</span>
      </div>
    </article>
    <article class="article-summary__item">
      <span class="article-summary__icon" aria-hidden="true"><i class="fa-solid fa-table"></i></span>
      <div class="article-summary__copy">
        <strong class="article-summary__value">5,000</strong>
        <span class="article-summary__label">Máximo por consulta</span>
      </div>
    </article>
  </div>

  <form class="data-toolbar report-toolbar" action="{{!SearchAction}}" method="get" role="search">
    <input type="hidden" name="page" value="{{!ListRoute}}" />
    <div class="form-field">
      <label for="operativoTipo">Reporte</label>
      <select id="operativoTipo" name="tipo">
        {{foreach ReportTypes}}
        <option value="{{!value}}" {{selected}}>{{!label}}</option>
        {{endfor ReportTypes}}
      </select>
    </div>
    <div class="form-field">
      <label for="operativoSearch">Buscar</label>
      <input type="search" id="operativoSearch" name="q" value="{{!Search}}" placeholder="Artículo, documento, usuario o responsable" autocomplete="off" />
    </div>

    <div class="form-field">
      <label for="operativoArticulo">Articulo</label>
      <select id="operativoArticulo" name="articulo">
        <option value="0">Todos</option>
        {{foreach ArticulosFilter}}
        <option value="{{!value}}" {{selected}}>{{!label}}</option>
        {{endfor ArticulosFilter}}
      </select>
    </div>
    <div class="form-field">
      <label for="operativoUsuario">Usuario</label>
      <select id="operativoUsuario" name="usuario">
        <option value="0">Todos</option>
        {{foreach UsuariosFilter}}
        <option value="{{!value}}" {{selected}}>{{!label}}</option>
        {{endfor UsuariosFilter}}
      </select>
    </div>
    <div class="form-field">
      <label for="operativoCategoria">Categoria</label>
      <select id="operativoCategoria" name="categoria">
        <option value="0">Todas</option>
        {{foreach CategoriasFilter}}
        <option value="{{!value}}" {{selected}}>{{!label}}</option>
        {{endfor CategoriasFilter}}
      </select>
    </div>
    <div class="form-field">
      <label for="operativoUbicacion">Ubicacion</label>
      <select id="operativoUbicacion" name="ubicacion">
        <option value="0">Todas</option>
        {{foreach UbicacionesFilter}}
        <option value="{{!value}}" {{selected}}>{{!label}}</option>
        {{endfor UbicacionesFilter}}
      </select>
    </div>
    <div class="form-field">
      <label for="operativoDesde">Desde</label>
      <input type="date" id="operativoDesde" name="desde" value="{{!Desde}}" />
    </div>
    <div class="form-field">
      <label for="operativoHasta">Hasta</label>
      <input type="date" id="operativoHasta" name="hasta" value="{{!Hasta}}" />
    </div>
    <div class="form-field">
      <label for="operativoDepartamento">Departamento</label>
      <input type="text" id="operativoDepartamento" name="departamento" value="{{!Departamento}}" maxlength="150" />
    </div>
    <div class="form-field">
      <label for="operativoEstado">Estado</label>
      <input type="text" id="operativoEstado" name="estado" value="{{!Estado}}" maxlength="40" placeholder="Opcional" />
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

  {{if HasRows}}
  <div class="data-table-wrap report-table-wrap">
    <table class="data-table data-table--list data-table--reportes-operativos">
      <thead>
        <tr>
          {{foreach Columns}}<th scope="col">{{!this}}</th>{{endfor Columns}}
        </tr>
      </thead>
      <tbody>
        {{foreach Rows}}
        <tr>
          {{foreach Cells}}<td>{{!this}}</td>{{endfor Cells}}
        </tr>
        {{endfor Rows}}
      </tbody>
    </table>
  </div>
  {{endif HasRows}}
  {{ifnot HasRows}}
  <div class="report-empty">
    <strong>Sin resultados</strong>
    <p>No existen registros que coincidan con los filtros seleccionados.</p>
  </div>
  {{endifnot HasRows}}

  <div class="data-footer article-footer">
    <span class="data-footer__meta">{{!ResultsLabel}}</span>
    {{Pagination}}
  </div>
</section>
