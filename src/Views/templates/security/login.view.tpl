<section class="fullCenter auth-shell">
  <div class="container-fluid p-0">
    <div class="row justify-content-center">
      <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">
        <div class="auth-card">
          <div class="auth-card__brand">
            <span class="auth-card__mark">
              <img src="{{BASE_DIR}}/public/imgs/brand/logo-icon.png" alt="Logo de {{!SITE_TITLE}}" width="56" height="56">
            </span>
            <span class="auth-card__eyebrow">Acceso administrativo</span>
            <h1>Iniciar Sesión</h1>
          </div>

          <form class="auth-form" method="post" action="index.php?page=sec_login{{if redirto}}&redirto={{!redirto}}{{endif redirto}}">
            <input type="hidden" name="csrf_token" value="{{csrf_token}}" />

            <div class="auth-field">
              <label for="txtEmail">Correo electrónico</label>
              <input type="email" id="txtEmail" name="txtEmail" value="{{!txtEmail}}" autocomplete="username" placeholder="usuario@pinturasamericanas.com" />
              {{if errorEmail}}
                <p class="auth-error">{{errorEmail}}</p>
              {{endif errorEmail}}
            </div>

            <div class="auth-field">
              <label for="txtPswd">Contraseña</label>
              <input type="password" id="txtPswd" name="txtPswd" value="" autocomplete="current-password" placeholder="••••••••" />
              {{if errorPswd}}
                <p class="auth-error">{{errorPswd}}</p>
              {{endif errorPswd}}
            </div>

            {{if generalError}}
              <div class="auth-error auth-error--general">{{generalError}}</div>
            {{endif generalError}}

            <button class="primary auth-submit" id="btnLogin" type="submit">
              <i class="fa-solid fa-right-to-bracket"></i>
              Iniciar Sesión
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
