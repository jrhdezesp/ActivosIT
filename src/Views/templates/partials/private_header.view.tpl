<header class="site-header site-header--private">
  <div class="site-header__bar">
    <div class="site-header__inner">
      <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-controls="privateSidebar" aria-expanded="false" aria-label="Abrir menú de navegación">
        <span class="hmb"></span>
        <span class="hmb"></span>
        <span class="hmb"></span>
      </button>

      <a class="brand" href="index.php?page={{PRIVATE_DEFAULT_CONTROLLER}}" aria-label="{{!SITE_TITLE}} - Panel principal">
        <span class="brand__mark">
          <img src="{{BASE_DIR}}/public/imgs/brand/logo-icon.png" alt="Logo de {{!SITE_TITLE}}" width="44" height="44">
        </span>
        <span class="brand__text">
          <span class="brand__name">Activos<span class="brand__name-accent">IT</span></span>
          <span class="brand__tagline">Panel de administración</span>
        </span>
      </a>

      <div class="site-header__user">
        {{with login}}
          <span class="user-chip" title="{{!userName}}">
            <i class="fa-solid fa-circle-user"></i>
            <span>{{!userName}}</span>
          </span>
          <a class="icon-button" href="index.php?page=sec_logout" title="Cerrar sesión" aria-label="Cerrar sesión">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
          </a>
        {{endwith login}}
      </div>
    </div>
  </div>
  <div class="site-header__accent" aria-hidden="true"></div>
</header>
