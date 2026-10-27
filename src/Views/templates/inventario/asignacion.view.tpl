<!-- Formulario para asignar un equipo o registrar su devolucion. -->
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
        {{if ShowReturn}}
        <a class="button button--ghost button--sm" href="{{!ReturnUrl}}">
          <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
          Registrar devolución
        </a>
        {{endif ShowReturn}}
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
          <div class="form-field form-field--full {{if FormEquipoError}}has-error{{endif FormEquipoError}}">
            <label for="id_equipo" class="form-label {{if FormRequired}}form-label--required{{endif FormRequired}}">Equipo</label>
            <select name="id_equipo" id="id_equipo" {{FormRequired}} {{FormDisabled}}>
              <option value="" {{EquipoPlaceholderSelected}}>Selecciona el equipo a entregar</option>
              {{EquiposOptionsHtml}}
            </select>
            <p class="form-help">Solo se listan equipos activos, disponibles y sin otra asignación o mantenimiento en proceso.</p>
            {{ifnot HasEquiposOptions}}
            <p class="form-error">No hay equipos disponibles: todos están inactivos, asignados o en mantenimiento.</p>
            {{endifnot HasEquiposOptions}}
            {{if FormEquipoError}}<p class="form-error">{{!FormEquipoError}}</p>{{endif FormEquipoError}}
          </div>

          <div class="form-field {{if FormPersonaError}}has-error{{endif FormPersonaError}}">
            <label for="persona_responsable" class="form-label {{if FormRequired}}form-label--required{{endif FormRequired}}">Persona responsable</label>
            <input type="text" name="persona_responsable" id="persona_responsable" value="{{!FormPersona}}" maxlength="150" placeholder="Ej. Lic. Carlos Rivera" autocomplete="off" {{FormRequired}} {{FormReadonly}} />
            {{if FormPersonaError}}<p class="form-error">{{!FormPersonaError}}</p>{{endif FormPersonaError}}
          </div>

          <div class="form-field {{if FormDepartamentoError}}has-error{{endif FormDepartamentoError}}">
            <label for="departamento_destino" class="form-label {{if FormRequired}}form-label--required{{endif FormRequired}}">Departamento de destino</label>
            <input type="text" name="departamento_destino" id="departamento_destino" value="{{!FormDepartamento}}" maxlength="150" placeholder="Ej. Gerencia de Ventas" autocomplete="off" {{FormRequired}} {{FormReadonly}} />
            {{if FormDepartamentoError}}<p class="form-error">{{!FormDepartamentoError}}</p>{{endif FormDepartamentoError}}
          </div>

          <div class="form-field {{if FormFechaAsignacionError}}has-error{{endif FormFechaAsignacionError}}">
            <label for="fecha_asignacion" class="form-label {{if FormRequired}}form-label--required{{endif FormRequired}}">Fecha y hora de entrega</label>
            <input type="datetime-local" name="fecha_asignacion" id="fecha_asignacion" value="{{!FormFechaAsignacion}}" {{FormRequired}} {{FormReadonly}} />
            {{if FormFechaAsignacionError}}<p class="form-error">{{!FormFechaAsignacionError}}</p>{{endif FormFechaAsignacionError}}
          </div>

          <div class="form-field form-field--full {{if FormCondicionEntregaError}}has-error{{endif FormCondicionEntregaError}}">
            <label for="condicion_entrega" class="form-label">Condición de entrega</label>
            <textarea name="condicion_entrega" id="condicion_entrega" maxlength="65535" rows="3" placeholder="Estado físico, accesorios entregados, cargador, maletín, etc." {{FormReadonly}}>{{!FormCondicionEntrega}}</textarea>
            {{if FormCondicionEntregaError}}<p class="form-error">{{!FormCondicionEntregaError}}</p>{{endif FormCondicionEntregaError}}
          </div>

          <div class="form-field form-field--full {{if FormAccesoriosError}}has-error{{endif FormAccesoriosError}}">
            <label for="accesorios_entregados" class="form-label">Accesorios entregados</label>
            <textarea name="accesorios_entregados" id="accesorios_entregados" maxlength="65535" rows="3" placeholder="Ej. cargador, mouse, maletín, adaptador HDMI." {{FormReadonly}}>{{!FormAccesorios}}</textarea>
            {{if FormAccesoriosError}}<p class="form-error">{{!FormAccesoriosError}}</p>{{endif FormAccesoriosError}}
          </div>

          <div class="form-field form-field--full {{if FormObservacionesError}}has-error{{endif FormObservacionesError}}">
            <label for="observaciones" class="form-label">Observaciones</label>
            <textarea name="observaciones" id="observaciones" maxlength="65535" rows="2" placeholder="Notas adicionales sobre la asignación." {{FormReadonly}}>{{!FormObservaciones}}</textarea>
            {{if FormObservacionesError}}<p class="form-error">{{!FormObservacionesError}}</p>{{endif FormObservacionesError}}
          </div>
        </div>

        {{if ShowReturnSection}}
        <section class="entry-lines" aria-labelledby="assignmentReturnTitle">
          <div class="entry-lines__head">
            <div>
              <h2 class="entry-lines__title" id="assignmentReturnTitle">Devolución del equipo</h2>
              <p class="entry-lines__description">Registra cuándo y en qué condición se recibió el equipo de vuelta.</p>
            </div>
          </div>

          <div class="form-grid form-grid--2">
            <div class="form-field {{if FormFechaDevolucionError}}has-error{{endif FormFechaDevolucionError}}">
              <label for="fecha_devolucion" class="form-label {{if ReturnRequired}}form-label--required{{endif ReturnRequired}}">Fecha y hora de devolución</label>
              <input type="datetime-local" name="fecha_devolucion" id="fecha_devolucion" value="{{!FormFechaDevolucion}}" {{ReturnRequired}} {{ReturnReadonly}} />
              {{if FormFechaDevolucionError}}<p class="form-error">{{!FormFechaDevolucionError}}</p>{{endif FormFechaDevolucionError}}
            </div>

            <div class="form-field">
              <label for="recibe_nombre" class="form-label">Recibido por</label>
              <input type="text" id="recibe_nombre" value="{{!FormRecepcionNombre}}" readonly class="entry-form__readonly" />
              <p class="form-help">{{if IsReturn}}Se registrará a nombre del usuario en sesión.{{endif IsReturn}}{{ifnot IsReturn}}Usuario que registró la devolución.{{endifnot IsReturn}}</p>
            </div>

            <div class="form-field form-field--full {{if FormCondicionDevolucionError}}has-error{{endif FormCondicionDevolucionError}}">
              <label for="condicion_devolucion" class="form-label">Condición de devolución</label>
              <textarea name="condicion_devolucion" id="condicion_devolucion" maxlength="65535" rows="3" placeholder="Estado físico, accesorios devueltos, faltantes o daños." {{ReturnReadonly}}>{{!FormCondicionDevolucion}}</textarea>
              {{if FormCondicionDevolucionError}}<p class="form-error">{{!FormCondicionDevolucionError}}</p>{{endif FormCondicionDevolucionError}}
            </div>
          </div>
        </section>
        {{endif ShowReturnSection}}
      </div>

      <div class="form-card__footer">
        <div class="form-actions">
          {{if IsReturn}}
          <button class="button button--primary" type="submit">
            <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
            Registrar devolución
          </button>
          <a class="button button--ghost" href="{{!FormCancelUrl}}">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            Volver sin devolver
          </a>
          {{endif IsReturn}}
          {{ifnot IsReturn}}
            {{if CanEdit}}
            <button class="button button--primary" type="submit">
              <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
              {{!FormSaveLabel}}
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
          {{endifnot IsReturn}}
        </div>
      </div>
    </form>
  </section>
</div>
