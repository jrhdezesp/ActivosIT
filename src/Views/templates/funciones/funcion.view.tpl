<!-- Formulario para crear o modificar una funcion del sistema. -->
<div class="form-layout">
<section class="form-card">
  <div class="form-card__header">
    <h1 class="form-card__title">{{!FormTitle}}</h1>
  </div>

  {{with funcion}}
  <form action="index.php?page=Funciones_Funcion&mode={{~mode}}&fncod={{!fncod}}" method="POST">
    <div class="form-card__body">
      <div class="form-grid form-grid--3">
        <div class="form-field {{if fncod_error}}has-error{{endif fncod_error}}">
          <label for="fncod">Código</label>
          <input {{~codeReadonly}} type="text" name="fncod" id="fncod" value="{{!fncod}}" placeholder="Código de la función" />
          <input type="hidden" name="mode" value="{{~mode}}" />
          <input type="hidden" name="token" value="{{~funcion_xss_token}}" />
          {{if fncod_error}}
          <p class="form-error">{{fncod_error}}</p>
          {{endif fncod_error}}
        </div>

        <div class="form-field {{if fntyp_error}}has-error{{endif fntyp_error}}">
          <label for="fntyp">Tipo</label>
          <select name="fntyp" id="fntyp" {{if ~readonly}} readonly disabled {{endif ~readonly}}>
            <option value="CTR" {{fntyp_ctr}}>Controlador</option>
            <option value="MNU" {{fntyp_mnu}}>Menú</option>
            <option value="MNT" {{fntyp_mnt}}>Mantenimiento</option>
            <option value="RPT" {{fntyp_rpt}}>Reporte</option>
          </select>
          {{if fntyp_error}}
          <p class="form-error">{{fntyp_error}}</p>
          {{endif fntyp_error}}
        </div>

        <div class="form-field {{if fnest_error}}has-error{{endif fnest_error}}">
          <label for="fnest">Estado</label>
          <select name="fnest" id="fnest" {{if ~readonly}} readonly disabled {{endif ~readonly}}>
            <option value="ACT" {{fnest_act}}>Activo</option>
            <option value="INA" {{fnest_ina}}>Inactivo</option>
          </select>
          {{if fnest_error}}
          <p class="form-error">{{fnest_error}}</p>
          {{endif fnest_error}}
        </div>

        <div class="form-field form-field--full {{if fndsc_error}}has-error{{endif fndsc_error}}">
          <label for="fndsc">Descripción</label>
          <input {{~readonly}} type="text" name="fndsc" id="fndsc" value="{{!fndsc}}" placeholder="Descripción de la función" />
          {{if fndsc_error}}
          <p class="form-error">{{fndsc_error}}</p>
          {{endif fndsc_error}}
        </div>
      </div>
    </div>
  {{endwith funcion}}

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
</div>

<script>
  document.addEventListener("DOMContentLoaded", ()=>{
    const btnCancelar = document.getElementById("btnCancelar");
    btnCancelar.addEventListener("click", (e)=>{
      e.preventDefault();
      e.stopPropagation();
      window.location.assign("index.php?page=Funciones_Funciones");
    });
  });
</script>
