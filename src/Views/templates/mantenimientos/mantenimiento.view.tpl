<!-- Formulario para registrar o modificar un mantenimiento. -->
<div class="form-layout catalog-form entry-form">
  <section class="form-card">
    <div class="form-card__header catalog-form__header">
      <div>
        <span class="eyebrow">{{!FormEyebrow}}</span>
        <div class="entry-form__title-row">
          <h1 class="form-card__title">{{!FormTitle}}</h1>
          {{if FormEstadoLabel}}
          <span class="badge {{FormEstadoClass}}">{{!FormEstadoLabel}}</span>
          {{endif FormEstadoLabel}}
        </div>
        <p class="form-card__description">{{!FormDescription}}</p>
        {{if FormMeta}}
        <p class="catalog-form__meta">{{!FormMeta}}</p>
        {{endif FormMeta}}
      </div>
      <div class="catalog-form__header-actions">
        {{if ShowCancel}}
        <a class="button button--danger button--sm" href="{{!CancelUrl}}">
          <i class="fa-solid fa-ban" aria-hidden="true"></i>
          Cancelar mantenimiento
        </a>
        {{endif ShowCancel}}
        <a class="button button--ghost button--sm" href="{{!FormCancelUrl}}">
          <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
          {{!FormBackLabel}}
        </a>
      </div>
    </div>

    <form action="{{!FormAction}}" method="POST">
      <div class="form-card__body">
        <input type="hidden" name="token" value="{{!FormToken}}" />
        <input type="hidden" name="mode" value="{{!FormMode}}" />
        <input type="hidden" name="id" value="{{FormId}}" />

        {{if FormHasErrors}}
        <div class="form-error-summary" role="alert">{{!FormErrorSummary}}</div>
        {{endif FormHasErrors}}

        {{if FormInfo}}
        <div class="alert alert--info alert--compact catalog-form__warning" role="status">
          <span class="alert__icon" aria-hidden="true"><i class="fa-solid fa-circle-info"></i></span>
          <div class="alert__content">
            <p class="alert__message">{{!FormInfo}}</p>
          </div>
        </div>
        {{endif FormInfo}}

        {{if FormWarning}}
        <div class="alert alert--warning alert--compact catalog-form__warning" role="alert">
          <span class="alert__icon" aria-hidden="true"><i class="fa-solid fa-triangle-exclamation"></i></span>
          <div class="alert__content">
            <p class="alert__message">{{!FormWarning}}</p>
          </div>
        </div>
        {{endif FormWarning}}

        <div class="form-grid form-grid--2">
          {{if FormDanger}}
          <div class="form-field form-field--full">
            <label for="motivo_cancelacion" class="form-label form-label--required">Motivo de cancelación</label>
            <textarea name="motivo_cancelacion" id="motivo_cancelacion" maxlength="65535" rows="3" required placeholder="Indica por qué se cancela el mantenimiento."></textarea>
          </div>
          {{endif FormDanger}}
          <div class="form-field form-field--full {{if FormEquipoError}}has-error{{endif FormEquipoError}}">
            <label for="id_equipo" class="form-label {{if FormRequired}}form-label--required{{endif FormRequired}}">Equipo</label>
            <select name="id_equipo" id="id_equipo" {{FormRequired}} {{FormDisabled}}>
              <option value="" {{EquipoPlaceholderSelected}}>Selecciona el equipo a mantener</option>
              {{EquiposOptionsHtml}}
            </select>
            <p class="form-help">Solo se listan equipos activos sin otro mantenimiento en proceso.</p>
            {{ifnot HasEquiposOptions}}
            <p class="form-error">No hay equipos disponibles: todos están inactivos o ya tienen un mantenimiento en proceso.</p>
            {{endifnot HasEquiposOptions}}
            {{if FormEquipoError}}<p class="form-error">{{!FormEquipoError}}</p>{{endif FormEquipoError}}
          </div>

          <div class="form-field {{if FormTipoError}}has-error{{endif FormTipoError}}">
            <label for="id_tipo_mantenimiento" class="form-label {{if FormRequired}}form-label--required{{endif FormRequired}}">Tipo de mantenimiento</label>
            <select name="id_tipo_mantenimiento" id="id_tipo_mantenimiento" {{FormRequired}} {{FormDisabled}}>
              <option value="" {{TipoPlaceholderSelected}}>Selecciona un tipo</option>
              {{foreach TiposForm}}
              <option value="{{!value}}" {{selected}}>{{!label}}</option>
              {{endfor TiposForm}}
            </select>
            {{if FormTipoError}}<p class="form-error">{{!FormTipoError}}</p>{{endif FormTipoError}}
          </div>

          <div class="form-field {{if FormFechaError}}has-error{{endif FormFechaError}}">
            <label for="fecha_recepcion" class="form-label {{if FormRequired}}form-label--required{{endif FormRequired}}">Fecha y hora de recepción</label>
            <input type="datetime-local" name="fecha_recepcion" id="fecha_recepcion" value="{{!FormFechaRecepcion}}" {{FormRequired}} {{FormReadonly}} />
            {{if FormFechaError}}<p class="form-error">{{!FormFechaError}}</p>{{endif FormFechaError}}
          </div>

          <div class="form-field {{if FormTecnicoError}}has-error{{endif FormTecnicoError}}">
            <label for="tecnico_responsable" class="form-label {{if FormRequired}}form-label--required{{endif FormRequired}}">Técnico responsable</label>
            <input type="text" name="tecnico_responsable" id="tecnico_responsable" value="{{!FormTecnico}}" maxlength="150" placeholder="Ej. Juan Pérez" autocomplete="off" {{FormRequired}} {{FormReadonly}} />
            {{if FormTecnicoError}}<p class="form-error">{{!FormTecnicoError}}</p>{{endif FormTecnicoError}}
          </div>

          <div class="form-field {{if FormCostoError}}has-error{{endif FormCostoError}}">
            <label for="costo_mantenimiento" class="form-label">Costo del mantenimiento (L)</label>
            <input type="number" name="costo_mantenimiento" id="costo_mantenimiento" value="{{!FormCosto}}" min="0" step="0.01" {{FormReadonly}} />
            <p class="form-help">Opcional. Registra repuestos y mano de obra.</p>
            {{if FormCostoError}}<p class="form-error">{{!FormCostoError}}</p>{{endif FormCostoError}}
          </div>

          <div class="form-field">
            <label for="fecha_finalizacion" class="form-label">Fecha de finalización</label>
            <input type="text" id="fecha_finalizacion" value="{{!FormFechaFinalizacionDisplay}}" readonly class="entry-form__readonly" />
            <p class="form-help">Se registra automáticamente al completar o cancelar.</p>
          </div>

          <div class="form-field form-field--full {{if FormDiagnosticoError}}has-error{{endif FormDiagnosticoError}}">
            <label for="diagnostico_inicial" class="form-label">Diagnóstico inicial</label>
            <textarea name="diagnostico_inicial" id="diagnostico_inicial" maxlength="65535" rows="3" placeholder="Describe la falla reportada o el motivo del servicio." {{FormReadonly}}>{{!FormDiagnostico}}</textarea>
            {{if FormDiagnosticoError}}<p class="form-error">{{!FormDiagnosticoError}}</p>{{endif FormDiagnosticoError}}
          </div>

          <div class="form-field form-field--full {{if FormTrabajoError}}has-error{{endif FormTrabajoError}}">
            <label for="trabajo_realizado" class="form-label">Trabajo realizado</label>
            <textarea name="trabajo_realizado" id="trabajo_realizado" maxlength="65535" rows="3" placeholder="Detalla las tareas ejecutadas sobre el equipo." {{FormReadonly}}>{{!FormTrabajo}}</textarea>
            {{if FormTrabajoError}}<p class="form-error">{{!FormTrabajoError}}</p>{{endif FormTrabajoError}}
          </div>

          <div class="form-field form-field--full {{if FormResultadoError}}has-error{{endif FormResultadoError}}">
            <label for="resultado_mantenimiento" class="form-label">Resultado final</label>
            <textarea name="resultado_mantenimiento" id="resultado_mantenimiento" maxlength="65535" rows="3" placeholder="Describe el resultado y la condición final del equipo." {{FormReadonly}}>{{!FormResultado}}</textarea>
            {{if FormResultadoError}}<p class="form-error">{{!FormResultadoError}}</p>{{endif FormResultadoError}}
          </div>

          <div class="form-field form-field--full {{if FormRecomendacionesError}}has-error{{endif FormRecomendacionesError}}">
            <label for="recomendaciones" class="form-label">Recomendaciones</label>
            <textarea name="recomendaciones" id="recomendaciones" maxlength="65535" rows="3" placeholder="Recomendaciones para el siguiente mantenimiento o uso del equipo." {{FormReadonly}}>{{!FormRecomendaciones}}</textarea>
            {{if FormRecomendacionesError}}<p class="form-error">{{!FormRecomendacionesError}}</p>{{endif FormRecomendacionesError}}
          </div>

          {{if FormMotivoCancelacion}}
          <div class="form-field form-field--full">
            <label for="motivo_cancelacion_registrado" class="form-label">Motivo de cancelación</label>
            <textarea id="motivo_cancelacion_registrado" rows="3" readonly class="entry-form__readonly">{{!FormMotivoCancelacion}}</textarea>
          </div>
          {{endif FormMotivoCancelacion}}

          <div class="form-field form-field--full {{if FormRepuestosError}}has-error{{endif FormRepuestosError}}">
            <label for="repuestos_utilizados" class="form-label">Repuestos utilizados</label>
            <textarea name="repuestos_utilizados" id="repuestos_utilizados" maxlength="65535" rows="2" placeholder="Ej. 1 SSD 480 GB, 1 pasta térmica." {{FormReadonly}}>{{!FormRepuestos}}</textarea>
            {{if FormRepuestosError}}<p class="form-error">{{!FormRepuestosError}}</p>{{endif FormRepuestosError}}
          </div>
        </div>
      </div>

      <div class="form-card__footer">
        <div class="form-actions">
          {{if FormDanger}}
          <button class="button button--danger" type="submit">
            <i class="fa-solid fa-ban" aria-hidden="true"></i>
            Cancelar mantenimiento
          </button>
          <a class="button button--ghost" href="{{!FormCancelUrl}}">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            Volver sin cancelar
          </a>
          {{endif FormDanger}}
          {{ifnot FormDanger}}
            {{if CanEdit}}
            <button class="button button--ghost" type="submit" name="accion" value="guardar">
              <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
              {{!FormSaveLabel}}
            </button>
            <button class="button button--primary" type="submit" name="accion" value="completar">
              <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
              {{!FormCompleteLabel}}
            </button>
            <a class="button button--ghost" href="{{!FormCancelUrl}}">
              <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
              Cancelar
            </a>
            {{endif CanEdit}}
            {{ifnot CanEdit}}
            <a class="button button--ghost" href="{{!FormCancelUrl}}">
              <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
              {{!FormBackLabel}}
            </a>
            {{endifnot CanEdit}}
          {{endifnot FormDanger}}
        </div>
      </div>
    </form>

    {{if ShowImagesPanel}}
    <section class="image-panel" aria-labelledby="maintenance-images-title">
      <div class="image-panel__header">
        <div>
          <h2 class="image-panel__title" id="maintenance-images-title">Evidencias fotográficas</h2>
          <p class="image-panel__description">Fotos del diagnóstico, del trabajo realizado o del estado final del equipo. También quedan visibles en la ficha del equipo.</p>
        </div>
        <span class="badge badge--outline">{{!ImageCountLabel}}</span>
      </div>

      {{if ImageMessage}}
      <div class="alert alert--success alert--compact" role="status">{{!ImageMessage}}</div>
      {{endif ImageMessage}}
      {{if ImageError}}
      <div class="form-error-summary" role="alert">{{!ImageError}}</div>
      {{endif ImageError}}

      {{if HasImagenes}}
      <div class="image-gallery">
        {{foreach Imagenes}}
        <figure class="image-card">
          <a class="image-card__link" href="{{!url}}" target="_blank" rel="noopener">
            <img class="image-card__thumb" src="{{!url}}" alt="{{!alt}}" loading="lazy" decoding="async" />
          </a>
          <figcaption class="image-card__body">
            <span class="image-card__meta">{{!fecha}} · {{!usuario}}</span>
            {{if descripcion}}<span class="image-card__description">{{!descripcion}}</span>{{endif descripcion}}
            <span class="badge badge--outline image-card__context">Mantenimiento</span>
          </figcaption>
          {{if ~CanManageImages}}
          <form class="image-card__delete" action="{{!~FormAction}}" method="POST">
            <input type="hidden" name="token" value="{{!~ImageToken}}" />
            <input type="hidden" name="accion" value="eliminar_imagen" />
            <input type="hidden" name="id_imagen" value="{{!id}}" />
            <button class="button button--ghost button--sm" type="submit">
              <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
              Eliminar
            </button>
          </form>
          {{endif ~CanManageImages}}
        </figure>
        {{endfor Imagenes}}
      </div>
      {{endif HasImagenes}}
      {{ifnot HasImagenes}}
      <p class="image-empty">Aún no hay evidencias registradas para este mantenimiento.</p>
      {{endifnot HasImagenes}}

      {{if CanManageImages}}
      <form class="image-upload" action="{{!FormAction}}" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="token" value="{{!ImageToken}}" />
        <input type="hidden" name="accion" value="subir_imagen" />
        <div class="form-field">
          <label for="evidencias" class="form-label">Agregar evidencias</label>
          <input type="file" id="evidencias" name="imagenes[]" accept="image/jpeg,image/png,image/webp" multiple required />
          <p class="form-help">JPG, PNG o WebP. Hasta 8 fotos por carga, máximo 5 MB cada una.</p>
        </div>
        <div class="form-field">
          <label for="descripcion_evidencia" class="form-label">Descripción (opcional)</label>
          <input type="text" id="descripcion_evidencia" name="descripcion" maxlength="255" placeholder="Ej. Estado después del mantenimiento" />
        </div>
        <button class="button button--primary" type="submit">
          <i class="fa-solid fa-upload" aria-hidden="true"></i>
          Subir evidencias
        </button>
      </form>
      {{endif CanManageImages}}
    </section>
    {{endif ShowImagesPanel}}
  </section>
</div>
