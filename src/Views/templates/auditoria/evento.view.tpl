<!-- Detalle de un evento seleccionado de la bitacora. -->
<section class="data-panel article-panel audit-panel audit-detail">
  <div class="data-panel__header">
    <div class="data-panel__heading">
      <span class="eyebrow">Auditoría / Evento #{{EventoId}}</span>
      <h1 class="data-panel__title">{{!EventoTipoLabel}}</h1>
      <p class="data-panel__description">{{!EventoDescripcion}}</p>
    </div>
    <div class="data-panel__actions">
      <a class="button button--ghost" href="{{!BackUrl}}">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
        Volver a la bitácora
      </a>
    </div>
  </div>

  <div class="audit-detail__hero">
    <span class="badge {{!EventoResultadoClass}}">
      <i class="fa-solid {{!EventoResultadoIcon}}" aria-hidden="true"></i>
      {{!EventoResultadoLabel}}
    </span>
    <span class="badge badge--outline">
      <i class="fa-solid {{!EventoTipoIcon}}" aria-hidden="true"></i>
      {{!EventoTipoLabel}}
    </span>
    <span class="audit-mono audit-detail__raw">{{!EventoTipoRaw}}</span>
    <span class="audit-detail__readonly">
      <i class="fa-solid fa-lock" aria-hidden="true"></i>
      Registro de solo lectura
    </span>
  </div>

  <dl class="profile-details audit-detail__meta">
    <div>
      <dt>Evento</dt>
      <dd class="audit-mono">#{{EventoId}}</dd>
    </div>
    <div>
      <dt>Fecha y hora</dt>
      <dd>{{!EventoFecha}} · {{!EventoHora}}</dd>
    </div>
    <div>
      <dt>Módulo afectado</dt>
      <dd>{{!EventoModulo}}</dd>
    </div>
    <div>
      <dt>Resultado</dt>
      <dd>
        <span class="badge {{!EventoResultadoClass}}">
          <i class="fa-solid {{!EventoResultadoIcon}}" aria-hidden="true"></i>
          {{!EventoResultadoLabel}}
        </span>
      </dd>
    </div>
    <div>
      <dt>Usuario</dt>
      <dd>
        <span class="audit-user">
          <span>{{!EventoUsuario}}</span>
          {{if EventoTieneCuenta}}<span class="data-table__subtext">{{!EventoCuenta}}</span>{{endif EventoTieneCuenta}}
        </span>
      </dd>
    </div>
    <div>
      <dt>Dirección IP</dt>
      <dd class="audit-mono">{{!EventoIp}}</dd>
    </div>
    <div>
      <dt>Registro relacionado</dt>
      <dd class="audit-mono">{{!EventoRegistro}}</dd>
    </div>
    <div>
      <dt>Operación técnica</dt>
      <dd class="audit-mono">{{!EventoTipoRaw}}</dd>
    </div>
  </dl>
</section>

<div class="audit-detail-grid">
  <section class="data-panel audit-json-card">
    <div class="data-panel__header">
      <div class="data-panel__heading">
        <h2 class="data-panel__title">Datos anteriores</h2>
        <p class="data-panel__description">Valores que tenía el registro antes de la operación.</p>
      </div>
    </div>
    {{if HasDatosAnteriores}}
    <pre class="audit-json" aria-label="Datos anteriores en formato JSON">{{!DatosAnterioresJson}}</pre>
    {{endif HasDatosAnteriores}}
    {{ifnot HasDatosAnteriores}}
    <div class="audit-json-empty">
      <span class="audit-json-empty__icon" aria-hidden="true"><i class="fa-solid fa-inbox"></i></span>
      <p>Sin datos anteriores registrados para este evento.</p>
    </div>
    {{endifnot HasDatosAnteriores}}
  </section>

  <section class="data-panel audit-json-card">
    <div class="data-panel__header">
      <div class="data-panel__heading">
        <h2 class="data-panel__title">Datos nuevos</h2>
        <p class="data-panel__description">Información resultante o contexto de la operación registrada.</p>
      </div>
    </div>
    {{if HasDatosNuevos}}
    <pre class="audit-json" aria-label="Datos nuevos en formato JSON">{{!DatosNuevosJson}}</pre>
    {{endif HasDatosNuevos}}
    {{ifnot HasDatosNuevos}}
    <div class="audit-json-empty">
      <span class="audit-json-empty__icon" aria-hidden="true"><i class="fa-solid fa-inbox"></i></span>
      <p>Sin datos nuevos registrados para este evento.</p>
    </div>
    {{endifnot HasDatosNuevos}}
  </section>
</div>
