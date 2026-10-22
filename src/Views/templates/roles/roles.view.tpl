<section class="data-panel">
  <div class="data-panel__header">
    <div class="data-panel__heading">
      <h1 class="data-panel__title">Roles</h1>
      <p class="data-panel__description">Define los roles disponibles y su estado dentro del sistema.</p>
    </div>
    <div class="data-panel__actions">
      <a class="button button--primary" href="index.php?page=Roles_Rol&mode=INS">
        <i class="fa-solid fa-plus"></i>
        Nuevo rol
      </a>
    </div>
  </div>

  <form class="data-toolbar" action="index.php" method="get">
    <input type="hidden" name="page" value="Roles_Roles">
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
      <button class="button button--secondary" type="submit">
        <i class="fa-solid fa-filter"></i>
        Filtrar
      </button>
    </div>
  </form>

  <div class="data-table-wrap">
    <table class="data-table data-table--list data-table--roles">
      <thead>
        <tr>
          <th class="data-table__col--code">
            {{if OrderRolescodDesc}}
            <a class="data-table__sort is-active" href="index.php?page=Roles_Roles&orderBy=clear&orderDescending=0">Código <i class="fa-solid fa-sort-down"></i></a>
            {{endif OrderRolescodDesc}}
            {{if OrderRolescod}}
            <a class="data-table__sort is-active" href="index.php?page=Roles_Roles&orderBy=rolescod&orderDescending=1">Código <i class="fa-solid fa-sort-up"></i></a>
            {{endif OrderRolescod}}
            <a class="data-table__sort data-table__sort--default" href="index.php?page=Roles_Roles&orderBy=rolescod&orderDescending=0">Código <i class="fa-solid fa-sort"></i></a>
          </th>
          <th class="data-table__col--description">
            {{if OrderRolesdscDesc}}
            <a class="data-table__sort is-active" href="index.php?page=Roles_Roles&orderBy=clear&orderDescending=0">Descripción <i class="fa-solid fa-sort-down"></i></a>
            {{endif OrderRolesdscDesc}}
            {{if OrderRolesdsc}}
            <a class="data-table__sort is-active" href="index.php?page=Roles_Roles&orderBy=rolesdsc&orderDescending=1">Descripción <i class="fa-solid fa-sort-up"></i></a>
            {{endif OrderRolesdsc}}
            <a class="data-table__sort data-table__sort--default" href="index.php?page=Roles_Roles&orderBy=rolesdsc&orderDescending=0">Descripción <i class="fa-solid fa-sort"></i></a>
          </th>
          <th class="data-table__col--status is-center">Estado</th>
          <th class="data-table__col--actions is-right">Acciones</th>
        </tr>
      </thead>
      <tbody>
        {{foreach roles}}
        <tr>
          <td><span class="data-table__code">{{!rolescod}}</span></td>
          <td>
            <a class="data-table__link data-table__truncate" href="index.php?page=Roles_Rol&mode=DSP&rolescod={{!rolescod}}" title="{{!rolesdsc}}">{{!rolesdsc}}</a>
          </td>
          <td class="is-center"><span class="badge" data-status="{{rolesest}}">{{!rolesestDsc}}</span></td>
          <td>
            <div class="data-table__actions">
              <a class="table-action" href="index.php?page=Roles_Rol&mode=UPD&rolescod={{!rolescod}}" title="Editar rol" aria-label="Editar rol">
                <i class="fa-solid fa-pen"></i>
              </a>
              <a class="table-action table-action--danger" href="index.php?page=Roles_Rol&mode=DEL&rolescod={{!rolescod}}" title="Eliminar rol" aria-label="Eliminar rol">
                <i class="fa-solid fa-trash-can"></i>
              </a>
            </div>
          </td>
        </tr>
        {{endfor roles}}
      </tbody>
    </table>
  </div>

  <div class="data-footer">
    {{pagination}}
  </div>
</section>
