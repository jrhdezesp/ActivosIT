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
            <p class="alert__message">El equipo <strong>{{!FormRecordName}}</strong> quedará inactivo. No se eliminará del historial y podrás reactivarlo editando su estado.</p>
          </div>
        </div>
        {{endif FormDanger}}

        <div class="form-grid form-grid--2">
          <div class="form-field form-field--full {{if FormArticuloError}}has-error{{endif FormArticuloError}}">
            <label for="id_articulo" class="form-label {{if FormRequired}}form-label--required{{endif FormRequired}}">Artículo</label>
            <select name="id_articulo" id="id_articulo" {{FormRequired}} {{FormDisabled}}>
              <option value="" {{ArticuloPlaceholderSelected}}>Selecciona un artículo controlado por serie</option>
              {{foreach ArticulosForm}}
              <option value="{{!value}}" {{selected}}>{{!label}}</option>
              {{endfor ArticulosForm}}
            </select>
            <p class="form-help">Solo aparecen artículos con control por número de serie.</p>
            {{if FormArticuloError}}<p class="form-error">{{!FormArticuloError}}</p>{{endif FormArticuloError}}
          </div>

          <div class="form-field {{if FormSerieError}}has-error{{endif FormSerieError}}">
            <label for="numero_serie_equipo" class="form-label">Número de serie</label>
            <input type="text" name="numero_serie_equipo" id="numero_serie_equipo" value="{{!FormSerie}}" maxlength="100" {{FormReadonly}} autocomplete="off" placeholder="Ej. DELL-LAT-8841A" />
            <p class="form-help">Identificador del fabricante. Obligatorio si no registras código patrimonial.</p>
            {{if FormSerieError}}<p class="form-error">{{!FormSerieError}}</p>{{endif FormSerieError}}
          </div>

          <div class="form-field {{if FormPatrimonialError}}has-error{{endif FormPatrimonialError}}">
            <label for="codigo_patrimonial_equipo" class="form-label">Código patrimonial</label>
            <input type="text" name="codigo_patrimonial_equipo" id="codigo_patrimonial_equipo" value="{{!FormPatrimonial}}" maxlength="100" {{FormReadonly}} autocomplete="off" placeholder="Ej. PA-IT-00101" />
            <p class="form-help">Código interno de inventario. Obligatorio si no registras número de serie.</p>
            {{if FormPatrimonialError}}<p class="form-error">{{!FormPatrimonialError}}</p>{{endif FormPatrimonialError}}
          </div>

          <div class="form-field {{if FormUbicacionError}}has-error{{endif FormUbicacionError}}">
            <label for="id_ubicacion" class="form-label {{if FormRequired}}form-label--required{{endif FormRequired}}">Ubicación</label>
            <select name="id_ubicacion" id="id_ubicacion" {{FormRequired}} {{FormDisabled}}>
              <option value="" {{UbicacionPlaceholderSelected}}>Selecciona una ubicación</option>
              {{foreach UbicacionesForm}}
              <option value="{{!value}}" {{selected}}>{{!label}}</option>
              {{endfor UbicacionesForm}}
            </select>
            {{if FormUbicacionError}}<p class="form-error">{{!FormUbicacionError}}</p>{{endif FormUbicacionError}}
          </div>

          <div class="form-field {{if FormEstadoOperativoError}}has-error{{endif FormEstadoOperativoError}}">
            <label for="estado_operativo" class="form-label {{if FormRequired}}form-label--required{{endif FormRequired}}">Estado operativo</label>
            <select name="estado_operativo" id="estado_operativo" {{FormRequired}} {{FormDisabled}}>
              {{foreach EstadoOperativoOptions}}
              <option value="{{!value}}" {{selected}}>{{!label}}</option>
              {{endfor EstadoOperativoOptions}}
            </select>
            <p class="form-help">Disponible, asignado, en mantenimiento, en revisión o de baja.</p>
            {{if FormEstadoOperativoError}}<p class="form-error">{{!FormEstadoOperativoError}}</p>{{endif FormEstadoOperativoError}}
          </div>

          <div class="form-field {{if FormFechaAdquisicionError}}has-error{{endif FormFechaAdquisicionError}}">
            <label for="fecha_adquisicion" class="form-label">Fecha de adquisición</label>
            <input type="date" name="fecha_adquisicion" id="fecha_adquisicion" value="{{!FormFechaAdquisicion}}" {{FormReadonly}} />
            <p class="form-help">Opcional. Fecha en que el equipo ingresó a la empresa.</p>
            {{if FormFechaAdquisicionError}}<p class="form-error">{{!FormFechaAdquisicionError}}</p>{{endif FormFechaAdquisicionError}}
          </div>

          <div class="form-field {{if FormGarantiaError}}has-error{{endif FormGarantiaError}}">
            <label for="garantia_vencimiento_equipo" class="form-label">Vencimiento de garantía</label>
            <input type="date" name="garantia_vencimiento_equipo" id="garantia_vencimiento_equipo" value="{{!FormGarantia}}" {{FormReadonly}} />
            <p class="form-help">Opcional. No puede ser anterior a la fecha de adquisición.</p>
            {{if FormGarantiaError}}<p class="form-error">{{!FormGarantiaError}}</p>{{endif FormGarantiaError}}
          </div>

          <div class="form-field {{if FormProveedorError}}has-error{{endif FormProveedorError}}">
            <label for="proveedor_origen" class="form-label">Proveedor de origen</label>
            <input type="text" name="proveedor_origen" id="proveedor_origen" value="{{!FormProveedor}}" maxlength="150" {{FormReadonly}} autocomplete="off" placeholder="Ej. Dell Technologies Honduras" />
            {{if FormProveedorError}}<p class="form-error">{{!FormProveedorError}}</p>{{endif FormProveedorError}}
          </div>

          <div class="form-field {{if FormMotivoError}}has-error{{endif FormMotivoError}}">
            <label for="id_motivo" class="form-label">Motivo</label>
            <select name="id_motivo" id="id_motivo" {{FormDisabled}}>
              <option value="" {{MotivoPlaceholderSelected}}>Sin motivo</option>
              {{foreach MotivosForm}}
              <option value="{{!value}}" {{selected}}>{{!label}}</option>
              {{endfor MotivosForm}}
            </select>
            <p class="form-help">Opcional. Documenta bajas, ajustes o revisiones del equipo.</p>
            {{if FormMotivoError}}<p class="form-error">{{!FormMotivoError}}</p>{{endif FormMotivoError}}
          </div>

          <div class="form-field form-field--full {{if FormObservacionesError}}has-error{{endif FormObservacionesError}}">
            <label for="observaciones_equipo" class="form-label">Observaciones</label>
            <textarea name="observaciones_equipo" id="observaciones_equipo" maxlength="65535" {{FormReadonly}} rows="4" placeholder="Detalle del estado físico, accesorios incluidos o notas de soporte.">{{!FormObservaciones}}</textarea>
            {{if FormObservacionesError}}<p class="form-error">{{!FormObservacionesError}}</p>{{endif FormObservacionesError}}
          </div>

          <div class="form-field {{if FormEstadoRegistroError}}has-error{{endif FormEstadoRegistroError}}">
            <label for="estado_registro" class="form-label {{if FormRequired}}form-label--required{{endif FormRequired}}">Estado del registro</label>
            <select name="estado_registro" id="estado_registro" {{FormRequired}} {{FormDisabled}}>
              {{foreach EstadoOptions}}
              <option value="{{!value}}" {{selected}}>{{!label}}</option>
              {{endfor EstadoOptions}}
            </select>
            <p class="form-help">Inactivo conserva el historial sin permitir nuevas operaciones.</p>
            {{if FormEstadoRegistroError}}<p class="form-error">{{!FormEstadoRegistroError}}</p>{{endif FormEstadoRegistroError}}
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
