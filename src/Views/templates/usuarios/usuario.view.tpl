<div class="form-layout">
<section class="form-card">
  <div class="form-card__header">
    <h1 class="form-card__title">{{!FormTitle}}</h1>
  </div>

  {{with usuario}}
  <form action="index.php?page=Usuarios_Usuario&mode={{~mode}}&usercod={{usercod}}" method="POST">
    <div class="form-card__body">
      <div class="form-grid form-grid--2">
        <div class="form-field form-field--full">
          <label for="usercodD">Código</label>
          <input readonly disabled type="text" name="usercodD" id="usercodD" value="{{usercod}}" />
          <input type="hidden" name="mode" value="{{~mode}}" />
          <input type="hidden" name="usercod" value="{{usercod}}" />
          <input type="hidden" name="token" value="{{~usuario_xss_token}}" />
        </div>

        <div class="form-field {{if useremail_error}}has-error{{endif useremail_error}}">
          <label for="useremail">Correo</label>
          <input {{~readonly}} type="email" name="useremail" id="useremail" value="{{!useremail}}" placeholder="correo@ejemplo.com" />
          {{if useremail_error}}
          <p class="form-error">{{useremail_error}}</p>
          {{endif useremail_error}}
        </div>

        <div class="form-field {{if username_error}}has-error{{endif username_error}}">
          <label for="username">Nombre</label>
          <input {{~readonly}} type="text" name="username" id="username" value="{{!username}}" placeholder="Nombre completo" />
          {{if username_error}}
          <p class="form-error">{{username_error}}</p>
          {{endif username_error}}
        </div>

        <div class="form-field form-field--full {{if userpswd_error}}has-error{{endif userpswd_error}}">
          <label for="userpswd">Contraseña</label>
          <input {{~readonly}} type="password" name="userpswd" id="userpswd" value="" placeholder="Contraseña" />
          {{if userpswd_error}}
          <p class="form-error">{{userpswd_error}}</p>
          {{endif userpswd_error}}
        </div>

        <div class="form-field">
          <label for="userest">Estado</label>
          <select name="userest" id="userest" {{if ~readonly}} readonly disabled {{endif ~readonly}}>
            <option value="ACT" {{userest_act}}>Activo</option>
            <option value="INA" {{userest_ina}}>Inactivo</option>
          </select>
        </div>

        <div class="form-field">
          <label for="usertipo">Tipo</label>
          <select name="usertipo" id="usertipo" {{if ~readonly}} readonly disabled {{endif ~readonly}}>
            <option value="PBL" {{usertipo_pbl}}>Público</option>
            <option value="ADM" {{usertipo_adm}}>Administrador</option>
            <option value="AUD" {{usertipo_aud}}>Auditor</option>
            <option value="TEC" {{usertipo_tec}}>Técnico</option>
          </select>
        </div>
      </div>
    </div>
  {{endwith usuario}}

    <div class="form-card__footer">
      <div class="form-actions">
        {{if showCommitBtn}}
        <button class="button button--primary" type="submit" name="btnConfirmar">
          <i class="fa-solid fa-floppy-disk"></i>
          Confirmar
        </button>
        {{endif showCommitBtn}}
        <button class="button button--ghost" type="button" id="btnCancelar">
          <i class="fa-solid fa-arrow-left"></i>
          {{if showCommitBtn}}
          Cancelar
          {{endif showCommitBtn}}
          {{ifnot showCommitBtn}}
          Regresar
          {{endifnot showCommitBtn}}
        </button>
      </div>
    </div>
  </form>
</section>

{{if showRolesUsuarioManager}}
<section class="data-panel">
  <div class="data-panel__header">
    <div class="data-panel__heading">
      <h2 class="data-panel__title">Roles del usuario</h2>
      <p class="data-panel__description">Actualiza el estado de los roles asignados a esta cuenta.</p>
    </div>
  </div>

  <div class="data-table-wrap">
    <table class="data-table data-table--list data-table--usuario-roles">
      <thead>
        <tr>
          <th class="data-table__col--code">Código</th>
          <th class="data-table__col--description">Descripción</th>
          <th class="data-table__col--status is-center">Estado actual</th>
          <th class="data-table__col--actions is-right">Actualizar asignación</th>
        </tr>
      </thead>
      <tbody>
        {{foreach rolesUsuario}}
        <tr>
          <td><span class="data-table__code">{{!rolescod}}</span></td>
          <td><span class="data-table__truncate" title="{{!rolesdsc}}">{{!rolesdsc}}</span></td>
          <td class="is-center"><span class="badge" data-status="{{roleuserest}}">{{!roleuserestDsc}}</span></td>
          <td>
            <form action="index.php?page=Usuarios_Usuario&mode={{~mode}}&usercod={{~usercod}}" method="POST" class="data-table__actions">
              <input type="hidden" name="form_action" value="roles_usuario" />
              <input type="hidden" name="token" value="{{~usuario_xss_token}}" />
              <input type="hidden" name="rolescod" value="{{!rolescod}}" />
              <select name="roleuserest">
                <option value="ACT" {{roleuserest_act}}>Activo</option>
                <option value="INA" {{roleuserest_ina}}>Inactivo</option>
              </select>
              <button type="submit" class="button button--secondary button--sm">
                <i class="fa-solid fa-floppy-disk"></i>
                Guardar
              </button>
            </form>
          </td>
        </tr>
        {{endfor rolesUsuario}}
      </tbody>
    </table>
  </div>
</section>
{{endif showRolesUsuarioManager}}
</div>

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
