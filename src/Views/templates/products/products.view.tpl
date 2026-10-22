<section class="data-panel">
  <div class="data-panel__header">
    <div class="data-panel__heading">
      <h1 class="data-panel__title">Productos</h1>
      <p class="data-panel__description">Administra el catálogo de productos, precios y estados disponibles.</p>
    </div>
    <div class="data-panel__actions">
      <a class="button button--primary" href="index.php?page=Products_Product&mode=INS">
        <i class="fa-solid fa-plus"></i>
        Nuevo producto
      </a>
    </div>
  </div>

  <form class="data-toolbar" action="index.php" method="get">
    <input type="hidden" name="page" value="Products_Products">
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
    <table class="data-table data-table--list data-table--products">
      <thead>
        <tr>
          <th class="data-table__col--id">
            {{if OrderProductIdDesc}}
            <a class="data-table__sort is-active" href="index.php?page=Products_Products&orderBy=clear&orderDescending=0">Id <i class="fa-solid fa-sort-down"></i></a>
            {{endif OrderProductIdDesc}}
            {{if OrderProductId}}
            <a class="data-table__sort is-active" href="index.php?page=Products_Products&orderBy=productId&orderDescending=1">Id <i class="fa-solid fa-sort-up"></i></a>
            {{endif OrderProductId}}
            <a class="data-table__sort data-table__sort--default" href="index.php?page=Products_Products&orderBy=productId&orderDescending=0">Id <i class="fa-solid fa-sort"></i></a>
          </th>
          <th class="data-table__col--name">
            {{if OrderProductNameDesc}}
            <a class="data-table__sort is-active" href="index.php?page=Products_Products&orderBy=clear&orderDescending=0">Nombre <i class="fa-solid fa-sort-down"></i></a>
            {{endif OrderProductNameDesc}}
            {{if OrderProductName}}
            <a class="data-table__sort is-active" href="index.php?page=Products_Products&orderBy=productName&orderDescending=1">Nombre <i class="fa-solid fa-sort-up"></i></a>
            {{endif OrderProductName}}
            <a class="data-table__sort data-table__sort--default" href="index.php?page=Products_Products&orderBy=productName&orderDescending=0">Nombre <i class="fa-solid fa-sort"></i></a>
          </th>
          <th class="data-table__col--price is-right">
            {{if OrderProductPriceDesc}}
            <a class="data-table__sort is-active" href="index.php?page=Products_Products&orderBy=clear&orderDescending=0">Precio <i class="fa-solid fa-sort-down"></i></a>
            {{endif OrderProductPriceDesc}}
            {{if OrderProductPrice}}
            <a class="data-table__sort is-active" href="index.php?page=Products_Products&orderBy=productPrice&orderDescending=1">Precio <i class="fa-solid fa-sort-up"></i></a>
            {{endif OrderProductPrice}}
            <a class="data-table__sort data-table__sort--default" href="index.php?page=Products_Products&orderBy=productPrice&orderDescending=0">Precio <i class="fa-solid fa-sort"></i></a>
          </th>
          <th class="data-table__col--status is-center">Estado</th>
          <th class="data-table__col--actions is-right">Acciones</th>
        </tr>
      </thead>
      <tbody>
        {{foreach products}}
        <tr>
          <td><span class="data-table__code">{{productId}}</span></td>
          <td>
            <div class="data-table__stack">
              <a class="data-table__link data-table__truncate" href="index.php?page=Products_Product&mode=DSP&productId={{productId}}" title="{{!productName}}">{{!productName}}</a>
              {{if productDescription}}
              <span class="data-table__subtext" title="{{!productDescription}}">{{!productDescription}}</span>
              {{endif productDescription}}
            </div>
          </td>
          <td class="is-right"><span class="data-table__price">{{!productPrice}}</span></td>
          <td class="is-center"><span class="badge" data-status="{{productStatus}}">{{!productStatusDsc}}</span></td>
          <td>
            <div class="data-table__actions">
              <a class="table-action" href="index.php?page=Products_Product&mode=UPD&productId={{productId}}" title="Editar producto" aria-label="Editar producto">
                <i class="fa-solid fa-pen"></i>
              </a>
              <a class="table-action table-action--danger" href="index.php?page=Products_Product&mode=DEL&productId={{productId}}" aria-label="Eliminar producto" title="Eliminar producto">
                <i class="fa-solid fa-trash-can"></i>
              </a>
            </div>
          </td>
        </tr>
        {{endfor products}}
      </tbody>
    </table>
  </div>

  <div class="data-footer">
    {{pagination}}
  </div>
</section>
