<section class="container-m row px-4 py-4">
  <h1>{{!FormTitle}}</h1>
</section>
<section class="container-m row px-4 py-4">
  {{with usuario}}
  <form action="index.php?page=Usuarios_Usuario&mode={{~mode}}&usercod={{usercod}}" method="POST" class="col-12 col-m-8 offset-m-2">
    <div class="row my-2 align-center">
      <label class="col-12 col-m-3" for="usercodD">Código</label>
      <input class="col-12 col-m-9" readonly disabled type="text" name="usercodD" id="usercodD" value="{{usercod}}" />
      <input type="hidden" name="mode" value="{{~mode}}" />
      <input type="hidden" name="usercod" value="{{usercod}}" />
      <input type="hidden" name="token" value="{{~usuario_xss_token}}" />
    </div>
    <div class="row my-2 align-center">
      <label class="col-12 col-m-3" for="useremail">Correo</label>
      <input class="col-12 col-m-9" {{~readonly}} type="email" name="useremail" id="useremail" value="{{!useremail}}" />
      {{if useremail_error}}
      <div class="col-12 col-m-9 offset-m-3 error">
        {{useremail_error}}
      </div>
      {{endif useremail_error}}
    </div>
    <div class="row my-2 align-center">
      <label class="col-12 col-m-3" for="username">Nombre</label>
      <input class="col-12 col-m-9" {{~readonly}} type="text" name="username" id="username" value="{{!username}}" />
      {{if username_error}}
      <div class="col-12 col-m-9 offset-m-3 error">
        {{username_error}}
      </div>
      {{endif username_error}}
    </div>
    <div class="row my-2 align-center">
      <label class="col-12 col-m-3" for="userpswd">Contraseña</label>
      <input class="col-12 col-m-9" {{~readonly}} type="password" name="userpswd" id="userpswd" value="" />
      {{if userpswd_error}}
      <div class="col-12 col-m-9 offset-m-3 error">
        {{userpswd_error}}
      </div>
      {{endif userpswd_error}}
    </div>
    <div class="row my-2 align-center">
      <label class="col-12 col-m-3" for="userest">Estado</label>
      <select name="userest" id="userest" class="col-12 col-m-9" {{if ~readonly}} readonly disabled {{endif ~readonly}}>
        <option value="ACT" {{userest_act}}>Activo</option>
        <option value="INA" {{userest_ina}}>Inactivo</option>
      </select>
    </div>
    <div class="row my-2 align-center">
      <label class="col-12 col-m-3" for="usertipo">Tipo</label>
      <select name="usertipo" id="usertipo" class="col-12 col-m-9" {{if ~readonly}} readonly disabled {{endif ~readonly}}>
        <option value="PBL" {{usertipo_pbl}}>Público</option>
        <option value="ADM" {{usertipo_adm}}>Administrador</option>
        <option value="AUD" {{usertipo_aud}}>Auditor</option>
      </select>
    </div>
    {{endwith usuario}}
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

  {{if showRolesUsuarioManager}}
  <section class="WWList mt-4 col-12 col-m-8 offset-m-2">
    <h2>Roles del usuario</h2>
    <table>
      <thead>
        <tr>
          <th>Codigo</th>
          <th class="left">Descripcion</th>
          <th>Estado actual</th>
          <th>Actualizar asignacion</th>
        </tr>
      </thead>
      <tbody>
        {{foreach rolesUsuario}}
        <tr>
          <td>{{!rolescod}}</td>
          <td class="left">{{!rolesdsc}}</td>
          <td class="center">{{!roleuserestDsc}}</td>
          <td>
            <form action="index.php?page=Usuarios_Usuario&mode={{~mode}}&usercod={{~usercod}}" method="POST" class="flex align-center justify-between">
              <input type="hidden" name="form_action" value="roles_usuario" />
              <input type="hidden" name="token" value="{{~usuario_xss_token}}" />
              <input type="hidden" name="rolescod" value="{{!rolescod}}" />
              <select name="roleuserest">
                <option value="ACT" {{roleuserest_act}}>Activo</option>
                <option value="INA" {{roleuserest_ina}}>Inactivo</option>
              </select>
              <button type="submit" class="primary">Guardar</button>
            </form>
          </td>
        </tr>
        {{endfor rolesUsuario}}
      </tbody>
    </table>
  </section>
  {{endif showRolesUsuarioManager}}
</section>

<script>
  document.addEventListener("DOMContentLoaded", ()=>{
    const btnCancelar = document.getElementById("btnCancelar");
    btnCancelar.addEventListener("click", (e)=>{
      e.preventDefault();
      e.stopPropagation();
      window.location.assign("index.php?page=Usuarios_Usuarios");
    });
  });
</script>
