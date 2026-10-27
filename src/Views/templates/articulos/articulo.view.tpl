<!-- Formulario para registrar o modificar un articulo. -->
<div class="form-layout catalog-form">
  <section class="form-card">
    <div class="form-card__header catalog-form__header">
      <div>
        <span class="eyebrow">{{!FormEyebrow}}</span>
        <h1 class="form-card__title">{{!FormTitle}}</h1>
        <p class="form-card__description">{{!FormDescription}}</p>
        {{if FormMeta}}
        <p class="catalog-form__meta">{{!FormMeta}}</p>
        {{endif FormMeta}}
      </div>
      <a class="button button--ghost button--sm" href="{{!FormCancelUrl}}">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
        {{!FormBackLabel}}
      </a>
    </div>

    <form action="{{!FormAction}}" method="POST">
      <div class="form-card__body">
        <input type="hidden" name="token" value="{{!FormToken}}" />
        <input type="hidden" name="mode" value="{{!FormMode}}" />
        <input type="hidden" name="id" value="{{FormId}}" />

        {{if FormHasErrors}}
        <div class="form-error-summary" role="alert">{{!FormErrorSummary}}</div>
        {{endif FormHasErrors}}

        {{if FormDanger}}
        <div class="alert alert--warning alert--compact catalog-form__warning" role="alert">
          <span class="alert__icon" aria-hidden="true"><i class="fa-solid fa-triangle-exclamation"></i></span>
          <div class="alert__content">
            <p class="alert__message">El artículo <strong>{{!FormRecordName}}</strong> quedará inactivo. No se eliminará del historial y podrás reactivarlo editando su estado.</p>
          </div>
        </div>
        {{endif FormDanger}}

        <div class="form-grid form-grid--2">
          <div class="form-field {{if FormCodeError}}has-error{{endif FormCodeError}}">
            <label for="codigo_interno_articulo" class="form-label {{if FormRequired}}form-label--required{{endif FormRequired}}">Código interno</label>
            <input type="text" name="codigo_interno_articulo" id="codigo_interno_articulo" value="{{!FormCode}}" maxlength="255" {{FormRequired}} {{FormReadonly}} autocomplete="off" placeholder="Ej. ART-LAP-001" />
            <p class="form-help">Identificador único del artículo dentro del catálogo.</p>
            {{if FormCodeError}}<p class="form-error">{{!FormCodeError}}</p>{{endif FormCodeError}}
          </div>

          <div class="form-field {{if FormNameError}}has-error{{endif FormNameError}}">
            <label for="nombre_articulo" class="form-label {{if FormRequired}}form-label--required{{endif FormRequired}}">Nombre del artículo</label>
            <input type="text" name="nombre_articulo" id="nombre_articulo" value="{{!FormName}}" maxlength="255" {{FormRequired}} {{FormReadonly}} autocomplete="off" placeholder="Ej. Laptop de oficina" />
            {{if FormNameError}}<p class="form-error">{{!FormNameError}}</p>{{endif FormNameError}}
          </div>

          <div class="form-field form-field--full {{if FormDescriptionError}}has-error{{endif FormDescriptionError}}">
            <label for="descripcion_articulo" class="form-label">Descripción</label>
            <textarea name="descripcion_articulo" id="descripcion_articulo" maxlength="65535" {{FormReadonly}} rows="4" placeholder="Detalle del artículo, uso o características principales.">{{!FormArticleDescription}}</textarea>
            {{if FormDescriptionError}}<p class="form-error">{{!FormDescriptionError}}</p>{{endif FormDescriptionError}}
          </div>

          <div class="form-field {{if FormBrandError}}has-error{{endif FormBrandError}}">
            <label for="marca_articulo" class="form-label">Marca</label>
            <input type="text" name="marca_articulo" id="marca_articulo" value="{{!FormBrand}}" maxlength="100" {{FormReadonly}} placeholder="Ej. Dell" />
            {{if FormBrandError}}<p class="form-error">{{!FormBrandError}}</p>{{endif FormBrandError}}
          </div>

          <div class="form-field {{if FormModelError}}has-error{{endif FormModelError}}">
            <label for="modelo_articulo" class="form-label">Modelo</label>
            <input type="text" name="modelo_articulo" id="modelo_articulo" value="{{!FormModel}}" maxlength="150" {{FormReadonly}} placeholder="Ej. Latitude 3420" />
            {{if FormModelError}}<p class="form-error">{{!FormModelError}}</p>{{endif FormModelError}}
          </div>

          <div class="form-field form-field--full {{if FormArticleObservationsError}}has-error{{endif FormArticleObservationsError}}">
            <label for="observaciones_articulo" class="form-label">Observaciones del artículo</label>
            <textarea name="observaciones_articulo" id="observaciones_articulo" maxlength="65535" {{FormReadonly}} rows="3" placeholder="Notas generales del artículo.">{{!FormArticleObservations}}</textarea>
            {{if FormArticleObservationsError}}<p class="form-error">{{!FormArticleObservationsError}}</p>{{endif FormArticleObservationsError}}
          </div>

          <div class="form-field {{if FormClasificacionError}}has-error{{endif FormClasificacionError}}">
            <label for="id_clasificacion" class="form-label {{if FormRequired}}form-label--required{{endif FormRequired}}">Clasificación</label>
            <select name="id_clasificacion" id="id_clasificacion" {{FormRequired}} {{FormDisabled}}>
              <option value="" {{ClasificacionPlaceholderSelected}}>Selecciona una clasificación</option>
              {{foreach ClasificacionesForm}}
              <option value="{{!value}}" {{selected}}>{{!label}}</option>
              {{endfor ClasificacionesForm}}
            </select>
            {{if FormClasificacionError}}<p class="form-error">{{!FormClasificacionError}}</p>{{endif FormClasificacionError}}
          </div>

          <div class="form-field {{if FormUnidadError}}has-error{{endif FormUnidadError}}">
            <label for="id_unidad_medida" class="form-label {{if FormRequired}}form-label--required{{endif FormRequired}}">Unidad de medida</label>
            <select name="id_unidad_medida" id="id_unidad_medida" {{FormRequired}} {{FormDisabled}}>
              <option value="" {{UnidadPlaceholderSelected}}>Selecciona una unidad</option>
              {{foreach UnidadesForm}}
              <option value="{{!value}}" {{selected}}>{{!label}}</option>
              {{endfor UnidadesForm}}
            </select>
            {{if FormUnidadError}}<p class="form-error">{{!FormUnidadError}}</p>{{endif FormUnidadError}}
          </div>

          <div class="form-field {{if FormTipoControlError}}has-error{{endif FormTipoControlError}}">
            <label for="tipo_control" class="form-label {{if FormRequired}}form-label--required{{endif FormRequired}}">Tipo de control</label>
            <select name="tipo_control" id="tipo_control" {{FormRequired}} {{FormDisabled}}>
              {{foreach TipoControlOptions}}
              <option value="{{!value}}" {{selected}}>{{!label}}</option>
              {{endfor TipoControlOptions}}
            </select>
            <p class="form-help">Usa cantidad para consumibles y serie para equipos individuales.</p>
            {{if FormTipoControlError}}<p class="form-error">{{!FormTipoControlError}}</p>{{endif FormTipoControlError}}
          </div>

          <div class="form-field {{if FormEstadoError}}has-error{{endif FormEstadoError}}">
            <label for="estado_registro" class="form-label {{if FormRequired}}form-label--required{{endif FormRequired}}">Estado</label>
            <select name="estado_registro" id="estado_registro" {{FormRequired}} {{FormDisabled}}>
              {{foreach EstadoOptions}}
              <option value="{{!value}}" {{selected}}>{{!label}}</option>
              {{endfor EstadoOptions}}
            </select>
            {{if FormEstadoError}}<p class="form-error">{{!FormEstadoError}}</p>{{endif FormEstadoError}}
          </div>

          <div class="form-field {{if FormExistenciaMinimaError}}has-error{{endif FormExistenciaMinimaError}}">
            <label for="existencia_minima" class="form-label">Existencia mínima</label>
            <input type="number" name="existencia_minima" id="existencia_minima" value="{{!FormExistenciaMinima}}" min="0" step="1" {{FormReadonly}} />
            <p class="form-help">Opcional. Se usa para alertar cuando el stock esté bajo.</p>
            {{if FormExistenciaMinimaError}}<p class="form-error">{{!FormExistenciaMinimaError}}</p>{{endif FormExistenciaMinimaError}}
          </div>

          <div class="form-field {{if FormExistenciaActualError}}has-error{{endif FormExistenciaActualError}}">
            <label for="existencia_actual" class="form-label {{if FormRequired}}form-label--required{{endif FormRequired}}">Existencia actual</label>
            <input type="number" name="existencia_actual" id="existencia_actual" value="{{!FormExistenciaActual}}" min="0" step="1" {{FormRequired}} {{FormReadonly}} {{FormExistenciaActualReadonly}} />
            {{if FormExistenciaActualReadonly}}
            <p class="form-help">Saldo calculado por el Kardex. Se modifica únicamente con entradas, salidas o ajustes de inventario.</p>
            {{endif FormExistenciaActualReadonly}}
            {{ifnot FormExistenciaActualReadonly}}
            <p class="form-help">Existencia inicial del artículo al momento de registrarlo.</p>
            {{endifnot FormExistenciaActualReadonly}}
            {{if FormExistenciaActualError}}<p class="form-error">{{!FormExistenciaActualError}}</p>{{endif FormExistenciaActualError}}
          </div>
        </div>
      </div>

      <div class="form-card__footer">
        <div class="form-actions">
          <button class="button {{if FormDanger}}button--danger{{endif FormDanger}}{{ifnot FormDanger}}button--primary{{endifnot FormDanger}}" type="submit">
            <i class="fa-solid {{FormSubmitIcon}}" aria-hidden="true"></i>
            {{!FormSubmitLabel}}
          </button>
          <a class="button button--ghost" href="{{!FormCancelUrl}}">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            Cancelar
          </a>
        </div>
      </div>
    </form>
  </section>
</div>
