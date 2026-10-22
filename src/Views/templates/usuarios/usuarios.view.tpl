<section class="data-panel">
  <div class="data-panel__header">
    <div class="data-panel__heading">
      <h1 class="data-panel__title">Usuarios</h1>
      <p class="data-panel__description">Administra las cuentas, estados y tipos de acceso del sistema.</p>
    </div>
    <div class="data-panel__actions">
      <a class="button button--primary" href="index.php?page=Usuarios_Usuario&mode=INS">
        <i class="fa-solid fa-plus"></i>
        Nuevo usuario
      </a>
    </div>
  </div>

  <form class="data-toolbar" action="index.php" method="get">
    <input type="hidden" name="page" value="Usuarios_Usuarios">
    <div class="form-field">
      <label for="partialName">Nombre</label>
      <input type="text" name="partialName" id="partialName" value="{{!partialName}}" placeholder="Buscar por nombre" />
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
    <table class="data-table data-table--list data-table--usuarios">
      <thead>
        <tr>
          <th class="data-table__col--id">
            {{if OrderUsercodDesc}}
            <a class="data-table__sort is-active" href="index.php?page=Usuarios_Usuarios&orderBy=clear&orderDescending=0">Id <i class="fa-solid fa-sort-down"></i></a>
            {{endif OrderUsercodDesc}}
            {{if OrderUsercod}}
            <a class="data-table__sort is-active" href="index.php?page=Usuarios_Usuarios&orderBy=usercod&orderDescending=1">Id <i class="fa-solid fa-sort-up"></i></a>
            {{endif OrderUsercod}}
            <a class="data-table__sort data-table__sort--default" href="index.php?page=Usuarios_Usuarios&orderBy=usercod&orderDescending=0">Id <i class="fa-solid fa-sort"></i></a>
          </th>
          <th class="data-table__col--name">
            {{if OrderUsernameDesc}}
            <a class="data-table__sort is-active" href="index.php?page=Usuarios_Usuarios&orderBy=clear&orderDescending=0">Nombre <i class="fa-solid fa-sort-down"></i></a>
            {{endif OrderUsernameDesc}}
            {{if OrderUsername}}
            <a class="data-table__sort is-active" href="index.php?page=Usuarios_Usuarios&orderBy=username&orderDescending=1">Nombre <i class="fa-solid fa-sort-up"></i></a>
            {{endif OrderUsername}}
            <a class="data-table__sort data-table__sort--default" href="index.php?page=Usuarios_Usuarios&orderBy=username&orderDescending=0">Nombre <i class="fa-solid fa-sort"></i></a>
          </th>
          <th class="data-table__col--email">
            {{if OrderUseremailDesc}}
            <a class="data-table__sort is-active" href="index.php?page=Usuarios_Usuarios&orderBy=clear&orderDescending=0">Correo <i class="fa-solid fa-sort-down"></i></a>
            {{endif OrderUseremailDesc}}
            {{if OrderUseremail}}
            <a class="data-table__sort is-active" href="index.php?page=Usuarios_Usuarios&orderBy=useremail&orderDescending=1">Correo <i class="fa-solid fa-sort-up"></i></a>
            {{endif OrderUseremail}}
            <a class="data-table__sort data-table__sort--default" href="index.php?page=Usuarios_Usuarios&orderBy=useremail&orderDescending=0">Correo <i class="fa-solid fa-sort"></i></a>
          </th>
          <th class="data-table__col--status is-center">Estado</th>
          <th class="data-table__col--type is-center">Tipo</th>
          <th class="data-table__col--actions is-right">Acciones</th>
        </tr>
      </thead>
      <tbody>
        {{foreach usuarios}}
        <tr>
          <td><span class="data-table__code">{{usercod}}</span></td>
          <td>
            <a class="data-table__link data-table__truncate" href="index.php?page=Usuarios_Usuario&mode=DSP&usercod={{usercod}}" title="{{!username}}">{{!username}}</a>
          </td>
          <td>
            <span class="data-table__truncate data-table__muted" title="{{!useremail}}">{{!useremail}}</span>
          </td>
          <td class="is-center"><span class="badge" data-status="{{userest}}">{{!userestDsc}}</span></td>
          <td class="is-center"><span class="badge badge--info" data-type="{{usertipo}}">{{!usertipo}}</span></td>
          <td>
            <div class="data-table__actions">
              <a class="table-action" href="index.php?page=Usuarios_Usuario&mode=UPD&usercod={{usercod}}" title="Editar usuario" aria-label="Editar usuario">
                <i class="fa-solid fa-pen"></i>
              </a>
              <a class="table-action table-action--danger" href="index.php?page=Usuarios_Usuario&mode=DEL&usercod={{usercod}}" title="Eliminar usuario" aria-label="Eliminar usuario">
                <i class="fa-solid fa-trash-can"></i>
              </a>
            </div>
          </td>
        </tr>
        {{endfor usuarios}}
      </tbody>
    </table>
  </div>

  <div class="data-footer">
    {{pagination}}
  </div>
</section>
