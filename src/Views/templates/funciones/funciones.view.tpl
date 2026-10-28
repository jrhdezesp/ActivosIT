<!-- Listado de funciones y permisos disponibles para los roles. -->
<section class="data-panel">
  <div class="data-panel__header">
    <div class="data-panel__heading">
      <h1 class="data-panel__title">Funciones y permisos</h1>
      <p class="data-panel__description">Administra las funciones, su tipo y el estado de los permisos del sistema.</p>
    </div>
    <div class="data-panel__actions">
      <a class="button button--primary" href="index.php?page=Funciones_Funcion&mode=INS">
        <i class="fa-solid fa-plus"></i>
        Nuevo permiso
      </a>
    </div>
  </div>

  <form class="data-toolbar" action="index.php" method="get">
    <input type="hidden" name="page" value="Funciones_Funciones">
    <div class="form-field">
      <label for="partialName">Descripción</label>
      <input type="text" name="partialName" id="partialName" value="{{!partialName}}" placeholder="Buscar por descripción" />
    </div>
    <div class="form-field">
      <label for="status">Estado</label>
      <select name="status" id="status">
        <option value="EMP" {{status_EMP}}>Todos</option>
        <option value="ACT" {{status_ACT}}>Activo</option>
        <option value="INA" {{status_INA}}>Inactivo</option>
      </select>
    </div>
    <div class="data-toolbar__actions">
      <button class="button button--secondary button--sm" type="submit">
        <i class="fa-solid fa-filter"></i>
        Filtrar
      </button>
    </div>
  </form>

  <div class="data-table-wrap">
    <table class="data-table data-table--list data-table--funciones">
      <thead>
        <tr>
          <th class="data-table__col--code">
            {{if OrderFncodDesc}}
            <a class="data-table__sort is-active" href="index.php?page=Funciones_Funciones&orderBy=clear&orderDescending=0">Código <i class="fa-solid fa-sort-down"></i></a>
            {{endif OrderFncodDesc}}
            {{if OrderFncod}}
            <a class="data-table__sort is-active" href="index.php?page=Funciones_Funciones&orderBy=fncod&orderDescending=1">Código <i class="fa-solid fa-sort-up"></i></a>
            {{endif OrderFncod}}
            <a class="data-table__sort data-table__sort--default" href="index.php?page=Funciones_Funciones&orderBy=fncod&orderDescending=0">Código <i class="fa-solid fa-sort"></i></a>
          </th>
          <th class="data-table__col--description">
            {{if OrderFndscDesc}}
            <a class="data-table__sort is-active" href="index.php?page=Funciones_Funciones&orderBy=clear&orderDescending=0">Descripción <i class="fa-solid fa-sort-down"></i></a>
            {{endif OrderFndscDesc}}
            {{if OrderFndsc}}
            <a class="data-table__sort is-active" href="index.php?page=Funciones_Funciones&orderBy=fndsc&orderDescending=1">Descripción <i class="fa-solid fa-sort-up"></i></a>
            {{endif OrderFndsc}}
            <a class="data-table__sort data-table__sort--default" href="index.php?page=Funciones_Funciones&orderBy=fndsc&orderDescending=0">Descripción <i class="fa-solid fa-sort"></i></a>
          </th>
          <th class="data-table__col--type is-center">
            {{if OrderFntypDesc}}
            <a class="data-table__sort is-active" href="index.php?page=Funciones_Funciones&orderBy=clear&orderDescending=0">Tipo <i class="fa-solid fa-sort-down"></i></a>
            {{endif OrderFntypDesc}}
            {{if OrderFntyp}}
            <a class="data-table__sort is-active" href="index.php?page=Funciones_Funciones&orderBy=fntyp&orderDescending=1">Tipo <i class="fa-solid fa-sort-up"></i></a>
            {{endif OrderFntyp}}
            <a class="data-table__sort data-table__sort--default" href="index.php?page=Funciones_Funciones&orderBy=fntyp&orderDescending=0">Tipo <i class="fa-solid fa-sort"></i></a>
          </th>
          <th class="data-table__col--status is-center">Estado</th>
          <th class="data-table__col--actions is-right">Acciones</th>
        </tr>
      </thead>
      <tbody>
        {{foreach funciones}}
        <tr>
          <td><span class="data-table__code" title="{{!fncod}}">{{!fncod}}</span></td>
          <td>
            <a class="data-table__link data-table__truncate" href="index.php?page=Funciones_Funcion&mode=DSP&fncod={{!fncod}}" title="{{!fndsc}}">{{!fndsc}}</a>
          </td>
          <td class="is-center"><span class="badge badge--info" data-type="{{fntyp}}">{{!fntypDsc}}</span></td>
          <td class="is-center"><span class="badge" data-status="{{fnest}}">{{!fnestDsc}}</span></td>
          <td>
            <div class="data-table__actions">
              <a class="table-action" href="index.php?page=Funciones_Funcion&mode=UPD&fncod={{!fncod}}" title="Editar permiso" aria-label="Editar permiso">
                <i class="fa-solid fa-pen"></i>
              </a>
              <a class="table-action table-action--danger" href="index.php?page=Funciones_Funcion&mode=DEL&fncod={{!fncod}}" title="Eliminar permiso" aria-label="Eliminar permiso">
                <i class="fa-solid fa-trash-can"></i>
              </a>
            </div>
          </td>
        </tr>
        {{endfor funciones}}
      </tbody>
    </table>
  </div>

  <div class="data-footer">
    {{pagination}}
  </div>
</section>
