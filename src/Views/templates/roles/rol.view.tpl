<!-- Formulario para crear o modificar un rol y sus permisos. -->
<div class="form-layout">
<section class="form-card">
  <div class="form-card__header">
    <h1 class="form-card__title">{{!FormTitle}}</h1>
  </div>

  {{with rol}}
  <form action="index.php?page=Roles_Rol&mode={{~mode}}&rolescod={{!rolescod}}" method="POST">
    <div class="form-card__body">
      <div class="form-grid form-grid--2">
        <div class="form-field {{if rolescod_error}}has-error{{endif rolescod_error}}">
          <label for="rolescod">Código</label>
          <input {{~codeReadonly}} type="text" name="rolescod" id="rolescod" value="{{!rolescod}}" placeholder="Código del rol" />
          <input type="hidden" name="mode" value="{{~mode}}" />
          <input type="hidden" name="token" value="{{~rol_xss_token}}" />
          {{if rolescod_error}}
          <p class="form-error">{{rolescod_error}}</p>
          {{endif rolescod_error}}
        </div>

        <div class="form-field">
          <label for="rolesest">Estado</label>
          <select name="rolesest" id="rolesest" {{if ~readonly}} readonly disabled {{endif ~readonly}}>
            <option value="ACT" {{rolesest_act}}>Activo</option>
            <option value="INA" {{rolesest_ina}}>Inactivo</option>
          </select>
        </div>

        <div class="form-field form-field--full {{if rolesdsc_error}}has-error{{endif rolesdsc_error}}">
          <label for="rolesdsc">Descripción</label>
          <input {{~readonly}} type="text" name="rolesdsc" id="rolesdsc" value="{{!rolesdsc}}" placeholder="Descripción del rol" />
          {{if rolesdsc_error}}
          <p class="form-error">{{rolesdsc_error}}</p>
          {{endif rolesdsc_error}}
        </div>
      </div>
    </div>
  {{endwith rol}}

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

{{if showFuncionesRolManager}}
<section class="data-panel">
  <div class="data-panel__header">
    <div class="data-panel__heading">
      <h2 class="data-panel__title">Funciones del rol</h2>
      <p class="data-panel__description">Actualiza el estado de las funciones asignadas a este rol.</p>
    </div>
  </div>

  <div class="data-table-wrap">
    <table class="data-table data-table--list data-table--rol-funciones">
      <thead>
        <tr>
          <th class="data-table__col--code">Código</th>
          <th class="data-table__col--description">Descripción</th>
          <th class="data-table__col--type is-center">Tipo</th>
          <th class="data-table__col--status is-center">Estado actual</th>
          <th class="data-table__col--actions is-right">Actualizar asignación</th>
        </tr>
      </thead>
      <tbody>
        {{foreach funcionesRol}}
        <tr>
          <td><span class="data-table__code" title="{{!fncod}}">{{!fncod}}</span></td>
          <td><span class="data-table__truncate" title="{{!fndsc}}">{{!fndsc}}</span></td>
          <td class="is-center"><span class="badge badge--info" data-type="{{fntyp}}">{{!fntypDsc}}</span></td>
          <td class="is-center"><span class="badge" data-status="{{fnrolest}}">{{!fnrolestDsc}}</span></td>
          <td>
            <form action="index.php?page=Roles_Rol&mode={{~mode}}&rolescod={{!~rolescod}}" method="POST" class="data-table__actions">
              <input type="hidden" name="form_action" value="funciones_rol" />
              <input type="hidden" name="token" value="{{~rol_xss_token}}" />
              <input type="hidden" name="fncod" value="{{!fncod}}" />
              <select name="fnrolest">
                <option value="ACT" {{fnrolest_act}}>Activo</option>
                <option value="INA" {{fnrolest_ina}}>Inactivo</option>
              </select>
              <button type="submit" class="button button--secondary button--sm">
                <i class="fa-solid fa-floppy-disk"></i>
                Guardar
              </button>
            </form>
          </td>
        </tr>
        {{endfor funcionesRol}}
      </tbody>
    </table>
  </div>
</section>
{{endif showFuncionesRolManager}}
</div>

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
