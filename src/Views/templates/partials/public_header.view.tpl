<!-- Encabezado de las pantallas publicas. -->
<header class="site-header">
  <div class="site-header__bar">
    <div class="site-header__inner">
      <a class="brand" href="index.php?page={{PUBLIC_DEFAULT_CONTROLLER}}" aria-label="{{!SITE_TITLE}} - Inicio">
        <span class="brand__mark">
          <img src="{{BASE_DIR}}/public/imgs/brand/logo-icon.png" alt="Logo de {{!SITE_TITLE}}" width="44" height="44">
        </span>
        <span class="brand__text">
          <span class="brand__name">Activos<span class="brand__name-accent">IT</span></span>
          <span class="brand__tagline">Gestión de activos TI</span>
        </span>
      </a>

      <input type="checkbox" class="menu_toggle" id="menu_toggle" aria-label="Abrir menú de navegación">
      <label for="menu_toggle" class="menu_toggle_icon" aria-label="Abrir menú de navegación" aria-controls="menu">
        <span class="hmb"></span>
        <span class="hmb"></span>
        <span class="hmb"></span>
      </label>

      <nav id="menu" class="site-nav" aria-label="Navegación pública">
        <ul>
          <li><a href="index.php?page={{PUBLIC_DEFAULT_CONTROLLER}}"><i class="fa-solid fa-house"></i><span>Inicio</span></a></li>
          {{foreach PUBLIC_NAVIGATION}}
            <li><a href="{{nav_url}}">{{nav_label}}</a></li>
          {{endfor PUBLIC_NAVIGATION}}
        </ul>
      </nav>
    </div>
  </div>
  <div class="site-header__accent" aria-hidden="true"></div>
</header>
