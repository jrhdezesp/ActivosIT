<!-- Registro de ajustes manuales de inventario. -->
<div class="form-layout">
  <section class="form-card">
    <div class="form-card__header">
      <div>
        <span class="eyebrow">Inventario / Ajustes</span>
        <h1 class="form-card__title">Ajuste de inventario</h1>
        <p class="form-card__description">
          Corrige la existencia de un artículo tras un conteo físico, una merma o un error de captura.
          El movimiento queda registrado en el Kardex con el saldo anterior y el nuevo.
        </p>
      </div>
      <a class="button button--ghost button--sm" href="index.php?page=Reportes_Kardex&tipo=AJUSTE_POSITIVO">
        <i class="fa-solid fa-table-list" aria-hidden="true"></i>
        Ver Kardex
      </a>
    </div>

    <form action="index.php?page=Inventario_Ajuste" method="POST">
      <div class="form-card__body">
        <input type="hidden" name="token" value="{{!FormToken}}" />

        {{if HayFlash}}
        <div class="alert alert--{{!FlashType}} alert--compact" role="status">
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
            <label for="ajusteArticulo" class="form-label form-label--required">Artículo</label>
            <select name="articulo" id="ajusteArticulo" required>
              <option value="">Selecciona un artículo</option>
              {{foreach Articulos}}
              <option value="{{!value}}" {{selected}}>{{!label}} (existencia: {{!stock}})</option>
              {{endfor Articulos}}
            </select>
            <p class="form-help">Solo se listan artículos activos controlados por cantidad.</p>
          </div>

          <div class="form-field form-field--full">
            <span class="form-label form-label--required">Tipo de ajuste</span>
            <label class="form-check">
              <input type="radio" name="tipo" value="POSITIVO" {{if TipoPositivo}}checked{{endif TipoPositivo}} />
              Aumento de existencia
            </label>
            <label class="form-check">
              <input type="radio" name="tipo" value="NEGATIVO" {{if TipoNegativo}}checked{{endif TipoNegativo}} />
              Disminución de existencia
            </label>
          </div>

          <div class="form-field">
            <label for="ajusteCantidad" class="form-label form-label--required">Cantidad</label>
            <input type="number" min="1" step="1" name="cantidad" id="ajusteCantidad"
                   value="{{!Cantidad}}" required />
          </div>

          <div class="form-field">
            <label for="ajusteMotivo" class="form-label form-label--required">Motivo</label>
            <select name="motivo" id="ajusteMotivo" required>
              <option value="">Selecciona un motivo</option>
              {{foreach Motivos}}
              <option value="{{!value}}" {{selected}}>{{!label}}</option>
              {{endfor Motivos}}
            </select>
            <p class="form-help">El motivo debe coincidir con el tipo de ajuste.</p>
          </div>

          <div class="form-field form-field--full">
            <label for="ajusteObservacion" class="form-label form-label--required">Justificación</label>
            <textarea name="observacion" id="ajusteObservacion" maxlength="255" required
                      placeholder="Ej: conteo físico de fin de mes, diferencia de 3 unidades">{{!Observacion}}</textarea>
            <p class="form-help">Máximo 255 caracteres. Esta nota queda en el Kardex y en la bitácora.</p>
          </div>
        </div>
      </div>

      <div class="form-card__footer">
        <div class="form-actions">
          <button class="button button--primary" type="submit">
            <i class="fa-solid fa-scale-balanced" aria-hidden="true"></i>
            Registrar ajuste
          </button>
        </div>
      </div>
    </form>
  </section>
</div>

<section class="data-panel">
  <div class="data-panel__header">
    <div class="data-panel__heading">
      <h2 class="data-panel__title">Últimos ajustes</h2>
      <p class="data-panel__description">Movimientos de ajuste registrados recientemente.</p>
    </div>
  </div>

  {{if HayUltimos}}
  <div class="data-table-wrap">
    <table class="data-table data-table--list">
      <thead>
        <tr>
          <th scope="col">Fecha</th>
          <th scope="col">Artículo</th>
          <th scope="col">Tipo</th>
          <th class="is-right" scope="col">Cantidad</th>
          <th class="is-right" scope="col">Anterior</th>
          <th class="is-right" scope="col">Nuevo</th>
          <th scope="col">Usuario</th>
        </tr>
      </thead>
      <tbody>
        {{foreach UltimosAjustes}}
        <tr>
          <td><span class="data-table__nowrap">{{!fecha_movimiento}}</span></td>
          <td>
            <div class="equipment-stack">
              <span class="data-table__code">{{!codigo_interno_articulo}}</span>
              <span class="data-table__subtext">{{!nombre_articulo}}</span>
            </div>
          </td>
          <td>{{!tipo_movimiento}}</td>
          <td class="is-right">{{!cantidad}}</td>
          <td class="is-right">{{!existencia_anterior}}</td>
          <td class="is-right">{{!existencia_nueva}}</td>
          <td>{{!nombre_usuario}}</td>
        </tr>
        {{endfor UltimosAjustes}}
      </tbody>
    </table>
  </div>
  {{endif HayUltimos}}

  {{ifnot HayUltimos}}
  <div class="empty-state">
    <h3 class="empty-state__title">Sin ajustes registrados</h3>
    <p class="empty-state__text">Los ajustes que registres aparecerán aquí.</p>
  </div>
  {{endifnot HayUltimos}}
</section>
