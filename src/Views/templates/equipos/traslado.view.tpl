<!-- Traslado de un equipo a otra ubicacion. -->
<div class="form-layout">
  <section class="form-card">
    <div class="form-card__header">
      <div>
        <span class="eyebrow">Activos / Equipos / Traslados</span>
        <h1 class="form-card__title">Traslado de equipo</h1>
        <p class="form-card__description">
          Cambia la ubicación actual de un equipo. El movimiento queda registrado en la bitácora
          con la ubicación anterior y la nueva, junto con la observación.
        </p>
      </div>
      <a class="button button--ghost button--sm" href="index.php?page=Equipos_Equipos">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
        Volver a equipos
      </a>
    </div>

    <form action="index.php?page=Equipos_Traslado" method="POST">
      <div class="form-card__body">
        <input type="hidden" name="token" value="{{!FormToken}}" />

        {{if HayFlash}}
        <div class="alert alert--success alert--compact" role="status">
          <span class="alert__icon" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
          <div class="alert__content">
            <p class="alert__message">{{!FlashMessage}}</p>
          </div>
        </div>
        {{endif HayFlash}}

        {{if HayErrores}}
        <div class="form-error-summary" role="alert">{{!ErrorMessage}}</div>
        {{endif HayErrores}}

        <div class="form-grid form-grid--2">
          <div class="form-field form-field--full">
            <label for="trasladoEquipo" class="form-label form-label--required">Equipo</label>
            <select name="equipo" id="trasladoEquipo" required>
              <option value="">Selecciona un equipo</option>
              {{foreach Equipos}}
              <option value="{{!value}}" {{selected}}>{{!label}} (actual: {{!ubicacion}})</option>
              {{endfor Equipos}}
            </select>
            <p class="form-help">No se listan equipos dados de baja ni inactivos.</p>
          </div>

          <div class="form-field form-field--full">
            <label for="trasladoUbicacion" class="form-label form-label--required">Ubicación destino</label>
            <select name="ubicacion" id="trasladoUbicacion" required>
              <option value="">Selecciona la ubicación</option>
              {{foreach Ubicaciones}}
              <option value="{{!value}}" {{selected}}>{{!label}}</option>
              {{endfor Ubicaciones}}
            </select>
          </div>

          <div class="form-field form-field--full">
            <label for="trasladoObservacion" class="form-label">Observación</label>
            <textarea name="observacion" id="trasladoObservacion" maxlength="255"
                      placeholder="Ej: reubicación por remodelación del área, solicitud del responsable">{{!Observacion}}</textarea>
            <p class="form-help">Opcional. Se guarda junto con el movimiento en la bitácora.</p>
          </div>
        </div>
      </div>

      <div class="form-card__footer">
        <div class="form-actions">
          <button class="button button--primary" type="submit">
            <i class="fa-solid fa-truck-arrow-right" aria-hidden="true"></i>
            Registrar traslado
          </button>
        </div>
      </div>
    </form>
  </section>
</div>
