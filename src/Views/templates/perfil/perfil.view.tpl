<!-- Vista de datos y opciones del perfil de la cuenta actual. -->
{{with PerfilUsuario}}
<section class="profile-hero">
  <div class="profile-hero__main">
    <span class="profile-avatar" aria-hidden="true">{{!initials}}</span>
    <div class="profile-hero__copy">
      <span class="eyebrow">Mi cuenta</span>
      <h1>{{!username}}</h1>
      <p>{{!useremail}}</p>
      <div class="profile-hero__badges">
        <span class="badge" data-status="{{userest}}">{{!userestDsc}}</span>
        <span class="badge badge--info">
          <i class="fa-solid fa-id-badge" aria-hidden="true"></i>
          {{!usertipoDsc}}
        </span>
        <span class="badge badge--outline">
          <i class="fa-solid fa-user-shield" aria-hidden="true"></i>
          {{!~PerfilRolesLabel}}
        </span>
      </div>
    </div>
  </div>

  <div class="profile-hero__aside">
    <span class="profile-hero__label">Cuenta creada</span>
    <strong>{{!userfchingFmt}}</strong>
    <div class="profile-hero__actions">
      <a class="button button--ghost" href="index.php?page=Dashboard_Dashboard">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
        Volver al panel
      </a>
      <a class="button button--danger" href="index.php?page=Sec_Logout">
        <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>
        Cerrar sesión
      </a>
    </div>
  </div>
</section>

<div class="profile-grid">
  <section class="data-panel profile-panel">
    <div class="data-panel__header">
      <div class="data-panel__heading">
        <h2 class="data-panel__title">Datos de la cuenta</h2>
        <p class="data-panel__description">Información asociada a tu usuario en Activos IT.</p>
      </div>
    </div>
    <dl class="profile-details">
      <div>
        <dt>Código</dt>
        <dd>#{{usercod}}</dd>
      </div>
      <div>
        <dt>Nombre</dt>
        <dd>{{!username}}</dd>
      </div>
      <div class="profile-details__wide">
        <dt>Correo electrónico</dt>
        <dd>{{!useremail}}</dd>
      </div>
      <div>
        <dt>Tipo de usuario</dt>
        <dd>{{!usertipoDsc}}</dd>
      </div>
      <div>
        <dt>Estado</dt>
        <dd><span class="badge" data-status="{{userest}}">{{!userestDsc}}</span></dd>
      </div>
    </dl>
  </section>

  <section class="data-panel profile-panel">
    <div class="data-panel__header">
      <div class="data-panel__heading">
        <h2 class="data-panel__title">Seguridad</h2>
        <p class="data-panel__description">Estado de la clave y vigencia de la cuenta.</p>
      </div>
    </div>
    <div class="profile-security">
      <div class="profile-security__status">
        <span class="profile-security__icon" aria-hidden="true">
          <i class="fa-solid fa-shield-halved"></i>
        </span>
        <div class="profile-security__copy">
          <strong>{{!passwordStatus}}</strong>
          <span>{{!passwordNotice}}</span>
        </div>
        <span class="badge {{!passwordBadge}}">{{!passwordStatus}}</span>
      </div>
      <dl class="profile-details profile-details--compact">
        <div>
          <dt>Último cambio</dt>
          <dd>{{!userpswdchgFmt}}</dd>
        </div>
        <div>
          <dt>Vence</dt>
          <dd>{{!userpswdexpFmt}}</dd>
        </div>
      </dl>
    </div>

    <div class="profile-security profile-password">
      <div class="profile-password__heading">
        <h3>Cambiar clave</h3>
        <p>Actualiza la clave de acceso de tu cuenta.</p>
      </div>

      {{if &PasswordSuccess}}
      <div class="alert alert--success alert--compact" role="status">
        <span class="alert__icon" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
        <div class="alert__body">
          <p class="alert__message">{{!&PasswordSuccess}}</p>
        </div>
      </div>
      {{endif &PasswordSuccess}}

      {{if &PasswordErrorGeneral}}
      <div class="form-error-summary" role="alert">{{!&PasswordErrorGeneral}}</div>
      {{endif &PasswordErrorGeneral}}

      <form action="index.php?page=Perfil_Perfil" method="post">
        <input type="hidden" name="form_action" value="cambiar_clave" />
        <input type="hidden" name="token" value="{{!&PasswordFormToken}}" />
        <div class="form-grid form-grid--2">
          <div class="form-field form-field--full {{if &PasswordErrorCurrent}}has-error{{endif &PasswordErrorCurrent}}">
            <label for="perfilCurrentPassword">Clave actual</label>
            <input type="password" id="perfilCurrentPassword" name="current_password" autocomplete="current-password" required />
            {{if &PasswordErrorCurrent}}<p class="form-error">{{!&PasswordErrorCurrent}}</p>{{endif &PasswordErrorCurrent}}
          </div>
          <div class="form-field {{if &PasswordErrorNew}}has-error{{endif &PasswordErrorNew}}">
            <label for="perfilNewPassword">Nueva clave</label>
            <input type="password" id="perfilNewPassword" name="new_password" autocomplete="new-password" minlength="8" maxlength="32" required />
            {{if &PasswordErrorNew}}<p class="form-error">{{!&PasswordErrorNew}}</p>{{endif &PasswordErrorNew}}
          </div>
          <div class="form-field {{if &PasswordErrorConfirm}}has-error{{endif &PasswordErrorConfirm}}">
            <label for="perfilConfirmPassword">Confirmar clave</label>
            <input type="password" id="perfilConfirmPassword" name="confirm_password" autocomplete="new-password" minlength="8" maxlength="32" required />
            {{if &PasswordErrorConfirm}}<p class="form-error">{{!&PasswordErrorConfirm}}</p>{{endif &PasswordErrorConfirm}}
          </div>
        </div>
        <p class="form-help">Usa entre 8 y 32 caracteres con mayuscula, minuscula, numero y simbolo.</p>
        <div class="form-actions">
          <button class="button button--primary" type="submit">
            <i class="fa-solid fa-key" aria-hidden="true"></i>
            Actualizar clave
          </button>
        </div>
      </form>
    </div>
  </section>
</div>
{{endwith PerfilUsuario}}

<section class="data-panel profile-panel profile-panel--roles">
  <div class="data-panel__header">
    <div class="data-panel__heading">
      <h2 class="data-panel__title">Roles asignados</h2>
      <p class="data-panel__description">Los roles activos que determinan tus permisos en el sistema.</p>
    </div>
    <span class="badge badge--info">{{!PerfilRolesLabel}}</span>
  </div>

  {{if PerfilHasRoles}}
  <div class="profile-roles">
    {{foreach PerfilRoles}}
    <article class="profile-role">
      <span class="profile-role__icon" aria-hidden="true">
        <i class="fa-solid fa-user-shield"></i>
      </span>
      <div class="profile-role__copy">
        <strong>{{!rolesdsc}}</strong>
        <span>{{!rolescod}}</span>
      </div>
      <span class="badge badge--success">Activo</span>
    </article>
    {{endfor PerfilRoles}}
  </div>
  {{endif PerfilHasRoles}}

  {{ifnot PerfilHasRoles}}
  <div class="empty-state profile-empty">
    <span class="empty-state__icon" aria-hidden="true"><i class="fa-solid fa-user-shield"></i></span>
    <h3 class="empty-state__title">Sin roles activos</h3>
    <p class="empty-state__description">Tu cuenta no tiene roles activos asignados en este momento.</p>
  </div>
  {{endifnot PerfilHasRoles}}
</section>
