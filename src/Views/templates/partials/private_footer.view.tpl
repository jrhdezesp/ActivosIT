<!-- Pie de pagina usado por las pantallas privadas. -->
<footer class="site-footer site-footer--private">
  <div class="site-footer__inner">
    <div class="footer-brand">
      <span class="footer-brand__mark">
        <img src="{{BASE_DIR}}/public/imgs/brand/logo-icon.png" alt="Logo de {{!SITE_TITLE}}" width="52" height="52">
      </span>
      <div class="footer-brand__copy">
        <div class="footer-brand__name">Activos<span>IT</span></div>
        <p>Panel de administración de activos de TI para Pinturas Americanas.</p>
        <span class="footer-badge">
          <i class="fa-solid fa-shield-halved"></i>
          Sesión administrativa
        </span>
      </div>
    </div>

    <div class="footer-column footer-column--meta">
      <h2>Departamento de IT</h2>
      <p>Pinturas Americanas</p>
      {{with login}}<p>Sesión: {{!userName}}</p>{{endwith login}}
      <p class="footer-copy">&copy; {{CURRENT_YEAR}} Todos los derechos reservados.</p>
    </div>
  </div>
  <div class="site-footer__accent" aria-hidden="true"></div>
</footer>
