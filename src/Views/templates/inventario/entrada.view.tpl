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
        {{if ShowAnnul}}
        <a class="button button--danger button--sm" href="{{!AnnulUrl}}">
          <i class="fa-solid fa-ban" aria-hidden="true"></i>
          Anular entrada
        </a>
        {{endif ShowAnnul}}
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

        {{if FormWarning}}
        <div class="alert alert--warning alert--compact catalog-form__warning" role="alert">
          <span class="alert__icon" aria-hidden="true"><i class="fa-solid fa-triangle-exclamation"></i></span>
          <div class="alert__content">
            <p class="alert__message">{{!FormWarning}}</p>
          </div>
        </div>
        {{endif FormWarning}}

        <div class="form-grid form-grid--2">
          <div class="form-field">
            <label for="correlativo" class="form-label">Correlativo</label>
            <input type="text" id="correlativo" value="{{!FormCorrelativoDisplay}}" readonly class="entry-form__readonly" />
            <p class="form-help">Se asigna automáticamente con el año del movimiento.</p>
          </div>

          <div class="form-field {{if FormTipoError}}has-error{{endif FormTipoError}}">
            <label for="id_tipo_doc" class="form-label {{if FormRequired}}form-label--required{{endif FormRequired}}">Tipo de documento</label>
            <select name="id_tipo_doc" id="id_tipo_doc" {{FormRequired}} {{FormDisabled}}>
              <option value="" {{TipoPlaceholderSelected}}>Selecciona un tipo de entrada</option>
              {{foreach TiposForm}}
              <option value="{{!value}}" {{selected}}>{{!label}}</option>
              {{endfor TiposForm}}
            </select>
            {{if FormTipoError}}<p class="form-error">{{!FormTipoError}}</p>{{endif FormTipoError}}
          </div>

          <div class="form-field {{if FormFechaError}}has-error{{endif FormFechaError}}">
            <label for="fecha_movimiento" class="form-label {{if FormRequired}}form-label--required{{endif FormRequired}}">Fecha y hora del movimiento</label>
            <input type="datetime-local" name="fecha_movimiento" id="fecha_movimiento" value="{{!FormFecha}}" {{FormRequired}} {{FormReadonly}} />
            {{if FormFechaError}}<p class="form-error">{{!FormFechaError}}</p>{{endif FormFechaError}}
          </div>

          <div class="form-field {{if FormMotivoError}}has-error{{endif FormMotivoError}}">
            <label for="id_motivo" class="form-label">Motivo</label>
            <select name="id_motivo" id="id_motivo" {{FormDisabled}}>
              <option value="" {{MotivoPlaceholderSelected}}>Sin motivo</option>
              {{foreach MotivosForm}}
              <option value="{{!value}}" {{selected}}>{{!label}}</option>
              {{endfor MotivosForm}}
            </select>
            <p class="form-help">Opcional. Se usa para justificar ajustes positivos de inventario.</p>
            {{if FormMotivoError}}<p class="form-error">{{!FormMotivoError}}</p>{{endif FormMotivoError}}
          </div>

          <div class="form-field form-field--full {{if FormProveedorError}}has-error{{endif FormProveedorError}}">
            <label for="proveedor_origen" class="form-label {{if FormRequired}}form-label--required{{endif FormRequired}}">Proveedor de origen</label>
            <input type="text" name="proveedor_origen" id="proveedor_origen" value="{{!FormProveedor}}" maxlength="150" {{FormReadonly}} autocomplete="off" placeholder="Ej. Dell Technologies Honduras" />
            <p class="form-help">Obligatorio para recepciones por compra. Puedes dejarlo vacío en ajustes de inventario.</p>
            {{if FormProveedorError}}<p class="form-error">{{!FormProveedorError}}</p>{{endif FormProveedorError}}
          </div>

          <div class="form-field form-field--full {{if FormObservacionesError}}has-error{{endif FormObservacionesError}}">
            <label for="observaciones" class="form-label">Observaciones</label>
            <textarea name="observaciones" id="observaciones" maxlength="65535" {{FormReadonly}} rows="3" placeholder="Notas de la recepción, número de factura o detalle del ajuste.">{{!FormObservaciones}}</textarea>
            {{if FormObservacionesError}}<p class="form-error">{{!FormObservacionesError}}</p>{{endif FormObservacionesError}}
          </div>
        </div>

        <section class="entry-lines" aria-labelledby="entryLinesTitle">
          <div class="entry-lines__head">
            <div>
              <h2 class="entry-lines__title" id="entryLinesTitle">Detalle de la entrada</h2>
              <p class="entry-lines__description">Agrega cada artículo recibido. Los artículos por serie requieren el equipo ya registrado en el módulo Equipos.</p>
            </div>
            {{if CanEdit}}
            <button type="button" class="button button--ghost button--sm" id="entryAddLine">
              <i class="fa-solid fa-plus" aria-hidden="true"></i>
              Agregar línea
            </button>
            {{endif CanEdit}}
          </div>

          {{if FormDetallesError}}
          <p class="form-error entry-lines__error" role="alert">{{!FormDetallesError}}</p>
          {{endif FormDetallesError}}

          <div class="data-table-wrap entry-lines__wrap">
            <table class="data-table data-table--entry-lines">
              <thead>
                <tr>
                  <th class="entry-col--articulo" scope="col">Artículo</th>
                  <th class="entry-col--cantidad" scope="col">Cantidad</th>
                  <th class="entry-col--equipo" scope="col">Equipo (serie)</th>
                  <th class="entry-col--costo" scope="col">Costo unitario</th>
                  <th class="entry-col--subtotal is-right" scope="col">Subtotal</th>
                  {{if CanEdit}}
                  <th class="entry-col--actions" scope="col" aria-label="Acciones"></th>
                  {{endif CanEdit}}
                </tr>
              </thead>
              <tbody id="entryLinesBody" data-next-index="{{DetallesCount}}">
                {{foreach Detalles}}
                <tr class="entry-line" data-index="{{index}}">
                  <td>
                    {{if ~CanEdit}}
                    <select name="detalles[{{index}}][id_articulo]" class="entry-article" aria-label="Artículo de la línea" required>
                      <option value="">Selecciona un artículo</option>
                      {{articuloOptions}}
                    </select>
                    {{if articuloError}}<p class="form-error">{{!articuloError}}</p>{{endif articuloError}}
                    {{endif ~CanEdit}}
                    {{ifnot ~CanEdit}}
                    <div class="data-table__stack">
                      <span class="entry-static__name">{{!articuloNombre}}</span>
                      {{if articuloCodigo}}<span class="data-table__subtext">{{!articuloCodigo}}</span>{{endif articuloCodigo}}
                    </div>
                    {{endifnot ~CanEdit}}
                  </td>
                  <td>
                    {{if ~CanEdit}}
                    <input type="number" name="detalles[{{index}}][cantidad]" class="entry-qty" min="1" step="1" value="{{!cantidad}}" {{cantidadReadonly}} aria-label="Cantidad" required />
                    {{if cantidadError}}<p class="form-error">{{!cantidadError}}</p>{{endif cantidadError}}
                    {{endif ~CanEdit}}
                    {{ifnot ~CanEdit}}
                    <div class="entry-static__qty">
                      <span>{{!cantidad}}</span>
                      {{if unidadLabel}}<span class="data-table__subtext">{{!unidadLabel}}</span>{{endif unidadLabel}}
                    </div>
                    {{endifnot ~CanEdit}}
                  </td>
                  <td>
                    {{if ~CanEdit}}
                    <select name="detalles[{{index}}][id_equipo]" class="entry-equipment" aria-label="Equipo de la línea" {{equipoDisabled}}>
                      <option value="">{{if equipoOptions}}Selecciona un equipo{{endif equipoOptions}}{{ifnot equipoOptions}}No aplica{{endifnot equipoOptions}}</option>
                      {{equipoOptions}}
                    </select>
                    <p class="form-error entry-equipment-hint" hidden></p>
                    {{if equipoError}}<p class="form-error">{{!equipoError}}</p>{{endif equipoError}}
                    {{endif ~CanEdit}}
                    {{ifnot ~CanEdit}}
                    <span class="data-table__truncate" title="{{!equipoLabel}}">{{!equipoLabel}}</span>
                    {{endifnot ~CanEdit}}
                  </td>
                  <td>
                    {{if ~CanEdit}}
                    <input type="number" name="detalles[{{index}}][costo_unitario]" class="entry-cost" min="0" step="0.01" value="{{!costo}}" aria-label="Costo unitario" required />
                    {{if costoError}}<p class="form-error">{{!costoError}}</p>{{endif costoError}}
                    {{endif ~CanEdit}}
                    {{ifnot ~CanEdit}}
                    <span class="data-table__nowrap">{{!costoFmt}}</span>
                    {{endifnot ~CanEdit}}
                  </td>
                  <td class="is-right">
                    <span class="entry-subtotal">{{!subtotal}}</span>
                  </td>
                  {{if ~CanEdit}}
                  <td>
                    <button type="button" class="table-action table-action--danger" data-entry-remove title="Quitar línea" aria-label="Quitar línea">
                      <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                    </button>
                  </td>
                  {{endif ~CanEdit}}
                </tr>
                {{endfor Detalles}}
              </tbody>
            </table>
          </div>

          <div class="entry-totals">
            <div class="entry-totals__item">
              <span class="entry-totals__label">Total de unidades</span>
              <strong class="entry-totals__value" id="entryTotalUnits">{{TotalUnidades}}</strong>
            </div>
            <div class="entry-totals__item">
              <span class="entry-totals__label">Costo total estimado</span>
              <strong class="entry-totals__value" id="entryTotalCost">{{!TotalCosto}}</strong>
            </div>
          </div>
        </section>
      </div>

      <div class="form-card__footer">
        <div class="form-actions">
          {{if FormDanger}}
          <button class="button button--danger" type="submit">
            <i class="fa-solid fa-ban" aria-hidden="true"></i>
            Anular documento
          </button>
          <a class="button button--ghost" href="{{!FormCancelUrl}}">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            Cancelar
          </a>
          {{endif FormDanger}}
          {{ifnot FormDanger}}
            {{if CanEdit}}
            <button class="button button--ghost" type="submit" name="accion" value="borrador">
              <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
              Guardar borrador
            </button>
            <button class="button button--primary" type="submit" name="accion" value="confirmar">
              <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
              Guardar y confirmar
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
  </section>
</div>

{{if CanEdit}}
<template id="entryLineTemplate">
  <tr class="entry-line">
    <td>
      <select name="detalles[__INDEX__][id_articulo]" class="entry-article" aria-label="Artículo de la línea" required>
        <option value="">Selecciona un artículo</option>
        {{ArticulosOptionsHtml}}
      </select>
    </td>
    <td>
      <input type="number" name="detalles[__INDEX__][cantidad]" class="entry-qty" min="1" step="1" value="1" aria-label="Cantidad" required />
    </td>
    <td>
      <select name="detalles[__INDEX__][id_equipo]" class="entry-equipment" aria-label="Equipo de la línea" disabled>
        <option value="">No aplica</option>
      </select>
      <p class="form-error entry-equipment-hint" hidden></p>
    </td>
    <td>
      <input type="number" name="detalles[__INDEX__][costo_unitario]" class="entry-cost" min="0" step="0.01" value="0.00" aria-label="Costo unitario" required />
    </td>
    <td class="is-right">
      <span class="entry-subtotal">0.00</span>
    </td>
    <td>
      <button type="button" class="table-action table-action--danger" data-entry-remove title="Quitar línea" aria-label="Quitar línea">
        <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
      </button>
    </td>
  </tr>
</template>

<script>
(function () {
  var body = document.getElementById('entryLinesBody');
  if (!body) { return; }

  var addButton = document.getElementById('entryAddLine');
  var template = document.getElementById('entryLineTemplate');
  var articles = {{ArticulosJson}};
  var equipment = {{EquiposJson}};
  var nextIndex = parseInt(body.getAttribute('data-next-index'), 10) || 0;

  function formatNumber(value) {
    return new Intl.NumberFormat('en-US', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(value || 0);
  }

  function updateTotals() {
    var units = 0;
    var cost = 0;

    body.querySelectorAll('tr.entry-line').forEach(function (row) {
      var articleSelect = row.querySelector('.entry-article');
      var quantityNode = row.querySelector('.entry-qty');
      var costNode = row.querySelector('.entry-cost');
      if (!articleSelect || !quantityNode || !costNode || articleSelect.value === '') { return; }

      var quantity = parseFloat(quantityNode.value) || 0;
      var unitCost = parseFloat(costNode.value) || 0;
      units += quantity;
      cost += quantity * unitCost;
    });

    var unitsNode = document.getElementById('entryTotalUnits');
    var costNode = document.getElementById('entryTotalCost');
    if (unitsNode) { unitsNode.textContent = String(units); }
    if (costNode) { costNode.textContent = formatNumber(cost); }
  }

  function updateSubtotal(row) {
    var quantityNode = row.querySelector('.entry-qty');
    var costNode = row.querySelector('.entry-cost');
    var subtotalNode = row.querySelector('.entry-subtotal');
    if (!quantityNode || !costNode || !subtotalNode) { return; }

    var quantity = parseFloat(quantityNode.value) || 0;
    var unitCost = parseFloat(costNode.value) || 0;
    subtotalNode.textContent = formatNumber(quantity * unitCost);
    updateTotals();
  }

  function fillEquipment(select, articleId, selected) {
    select.innerHTML = '';
    var count = 0;

    var placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = 'Selecciona un equipo';
    select.appendChild(placeholder);

    equipment.forEach(function (item) {
      if (String(item.article) !== String(articleId)) { return; }

      var option = document.createElement('option');
      option.value = String(item.id);
      option.textContent = item.label;
      if (String(selected) === String(item.id)) { option.selected = true; }
      select.appendChild(option);
      count += 1;
    });

    return count;
  }

  function syncRow(row) {
    var articleSelect = row.querySelector('.entry-article');
    var quantityNode = row.querySelector('.entry-qty');
    var equipmentSelect = row.querySelector('.entry-equipment');
    if (!articleSelect || !quantityNode || !equipmentSelect) { return; }

    var articleId = articleSelect.value;
    var isSerie = !!(articles[articleId] && articles[articleId].serie);

    if (isSerie) {
      quantityNode.value = '1';
      quantityNode.readOnly = true;
      equipmentSelect.disabled = false;
      var available = fillEquipment(equipmentSelect, articleId, equipmentSelect.value);

      if (available === 0) {
        var emptyOption = document.createElement('option');
        emptyOption.value = '';
        emptyOption.textContent = 'Sin equipos disponibles';
        emptyOption.disabled = true;
        equipmentSelect.appendChild(emptyOption);
      }

      var hint = row.querySelector('.entry-equipment-hint');
      if (hint) {
        hint.hidden = available > 0;
        hint.textContent = available > 0
          ? ''
          : 'Sin equipos disponibles. Regístralo en Equipos.';
      }
    } else {
      quantityNode.readOnly = false;
      equipmentSelect.disabled = true;
      equipmentSelect.innerHTML = '';
      var option = document.createElement('option');
      option.value = '';
      option.textContent = 'No aplica';
      equipmentSelect.appendChild(option);

      var elseHint = row.querySelector('.entry-equipment-hint');
      if (elseHint) {
        elseHint.hidden = true;
        elseHint.textContent = '';
      }
    }

    updateSubtotal(row);
  }

  function resetRow(row) {
    var articleSelect = row.querySelector('.entry-article');
    var quantityNode = row.querySelector('.entry-qty');
    var costNode = row.querySelector('.entry-cost');
    var equipmentSelect = row.querySelector('.entry-equipment');

    if (articleSelect) { articleSelect.value = ''; }
    if (quantityNode) { quantityNode.value = '1'; }
    if (costNode) { costNode.value = '0.00'; }
    if (equipmentSelect) { equipmentSelect.value = ''; }

    row.querySelectorAll('.form-error').forEach(function (node) { node.remove(); });
    syncRow(row);

    if (articleSelect) { articleSelect.focus(); }
  }

  if (addButton && template) {
    addButton.addEventListener('click', function () {
      var index = nextIndex;
      nextIndex += 1;

      var wrapper = document.createElement('tbody');
      wrapper.innerHTML = template.innerHTML.replace(/__INDEX__/g, String(index));
      var row = wrapper.firstElementChild;
      if (!row) { return; }

      body.appendChild(row);
      syncRow(row);

      var articleSelect = row.querySelector('.entry-article');
      if (articleSelect) { articleSelect.focus(); }
    });
  }

  body.addEventListener('change', function (event) {
    if (event.target.classList.contains('entry-article')) {
      syncRow(event.target.closest('tr'));
    }
  });

  body.addEventListener('input', function (event) {
    if (
      event.target.classList.contains('entry-qty')
      || event.target.classList.contains('entry-cost')
    ) {
      updateSubtotal(event.target.closest('tr'));
    }
  });

  body.addEventListener('click', function (event) {
    var button = event.target.closest('[data-entry-remove]');
    if (!button) { return; }

    var row = button.closest('tr');
    if (!row) { return; }

    if (body.querySelectorAll('tr.entry-line').length === 1) {
      resetRow(row);
      return;
    }

    row.remove();
    updateTotals();
  });

  body.querySelectorAll('tr.entry-line').forEach(function (row) {
    syncRow(row);
  });
})();
</script>
{{endif CanEdit}}
