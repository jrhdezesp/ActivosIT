<section class="container-m row px-4 py-4">
  <h1>{{!FormTitle}}</h1>
</section>
<section class="container-m row px-4 py-4">
  {{with rol}}
  <form action="index.php?page=Roles_Rol&mode={{~mode}}&rolescod={{!rolescod}}" method="POST" class="col-12 col-m-8 offset-m-2">
    <div class="row my-2 align-center">
      <label class="col-12 col-m-3" for="rolescod">Código</label>
      <input class="col-12 col-m-9" {{~codeReadonly}} type="text" name="rolescod" id="rolescod" value="{{!rolescod}}" />
      <input type="hidden" name="mode" value="{{~mode}}" />
      <input type="hidden" name="token" value="{{~rol_xss_token}}" />
      {{if rolescod_error}}
      <div class="col-12 col-m-9 offset-m-3 error">
        {{rolescod_error}}
      </div>
      {{endif rolescod_error}}
    </div>
    <div class="row my-2 align-center">
      <label class="col-12 col-m-3" for="rolesdsc">Descripción</label>
      <input class="col-12 col-m-9" {{~readonly}} type="text" name="rolesdsc" id="rolesdsc" value="{{!rolesdsc}}" />
      {{if rolesdsc_error}}
      <div class="col-12 col-m-9 offset-m-3 error">
        {{rolesdsc_error}}
      </div>
      {{endif rolesdsc_error}}
    </div>
    <div class="row my-2 align-center">
      <label class="col-12 col-m-3" for="rolesest">Estado</label>
      <select name="rolesest" id="rolesest" class="col-12 col-m-9" {{if ~readonly}} readonly disabled {{endif ~readonly}}>
        <option value="ACT" {{rolesest_act}}>Activo</option>
        <option value="INA" {{rolesest_ina}}>Inactivo</option>
      </select>
    </div>
    {{endwith rol}}
    <div class="row my-4 align-center flex-end">
      {{if showCommitBtn}}
      <button class="primary col-12 col-m-2" type="submit" name="btnConfirmar">Confirmar</button>
      &nbsp;
      {{endif showCommitBtn}}
      <button class="col-12 col-m-2" type="button" id="btnCancelar">
        {{if showCommitBtn}}
        Cancelar
        {{endif showCommitBtn}}
        {{ifnot showCommitBtn}}
        Regresar
        {{endifnot showCommitBtn}}
      </button>
    </div>
  </form>

  {{if showFuncionesRolManager}}
  <section class="WWList mt-4 col-12 col-m-8 offset-m-2">
    <h2>Funciones del rol</h2>
    <table>
      <thead>
        <tr>
          <th>Codigo</th>
          <th class="left">Descripcion</th>
          <th>Tipo</th>
          <th>Estado actual</th>
          <th>Actualizar asignacion</th>
        </tr>
      </thead>
      <tbody>
        {{foreach funcionesRol}}
        <tr>
          <td>{{!fncod}}</td>
          <td class="left">{{!fndsc}}</td>
          <td class="center">{{!fntyp}}</td>
          <td class="center">{{!fnrolestDsc}}</td>
          <td>
            <form action="index.php?page=Roles_Rol&mode={{~mode}}&rolescod={{!~rolescod}}" method="POST" class="flex align-center justify-between">
              <input type="hidden" name="form_action" value="funciones_rol" />
              <input type="hidden" name="token" value="{{~rol_xss_token}}" />
              <input type="hidden" name="fncod" value="{{!fncod}}" />
              <select name="fnrolest">
                <option value="ACT" {{fnrolest_act}}>Activo</option>
                <option value="INA" {{fnrolest_ina}}>Inactivo</option>
              </select>
              <button type="submit" class="primary">Guardar</button>
            </form>
          </td>
        </tr>
        {{endfor funcionesRol}}
      </tbody>
    </table>
  </section>
  {{endif showFuncionesRolManager}}
</section>

<script>
  document.addEventListener("DOMContentLoaded", ()=>{
    const btnCancelar = document.getElementById("btnCancelar");
    btnCancelar.addEventListener("click", (e)=>{
      e.preventDefault();
      e.stopPropagation();
      window.location.assign("index.php?page=Roles_Roles");
    });
  });
</script>
