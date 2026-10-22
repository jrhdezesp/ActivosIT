<section class="fullCenter auth-shell">
  <div class="container-fluid p-0">
    <div class="row justify-content-center">
      <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">
        <div class="auth-card">
          <div class="auth-card__brand">
            <span class="auth-card__mark">
              <img src="{{BASE_DIR}}/public/imgs/brand/logo-icon.png" alt="Logo de {{!SITE_TITLE}}" width="56" height="56">
            </span>
            <span class="auth-card__eyebrow">Registro de usuario</span>
            <h1>Crear cuenta</h1>
          </div>

          <form class="auth-form" method="post" action="index.php?page=sec_register">
            <input type="hidden" name="csrf_token" value="{{csrf_token}}" />

            <div class="auth-field">
              <label for="txtEmail">Correo electrónico</label>
              <input type="email" id="txtEmail" name="txtEmail" value="{{!txtEmail}}" autocomplete="email" placeholder="usuario@pinturasamericanas.com" />
              {{if errorEmail}}
                <p class="auth-error">{{errorEmail}}</p>
              {{endif errorEmail}}
            </div>

            <div class="auth-field">
              <label for="txtPswd">Contraseña</label>
              <input type="password" id="txtPswd" name="txtPswd" value="" autocomplete="new-password" placeholder="••••••••" />
              {{ifnot errorPswd}}
                <p class="form-help">Mínimo 8 caracteres, una mayúscula, un número y un carácter especial.</p>
              {{endifnot errorPswd}}
              {{if errorPswd}}
                <p class="auth-error">{{errorPswd}}</p>
              {{endif errorPswd}}
            </div>

            {{if generalError}}
              <div class="alert alert--danger alert--compact" role="alert">
                <span class="alert__icon" aria-hidden="true"><i class="fa-solid fa-circle-exclamation"></i></span>
                <div class="alert__content">
                  <p class="alert__message">{{generalError}}</p>
                </div>
              </div>
            {{endif generalError}}

            <button class="primary auth-submit" id="btnSignin" type="submit">
              <i class="fa-solid fa-user-plus"></i>
              Crear cuenta
            </button>

            <p class="auth-form__footer">¿Ya tienes una cuenta? <a href="index.php?page=Sec_Login">Iniciar sesión</a></p>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
