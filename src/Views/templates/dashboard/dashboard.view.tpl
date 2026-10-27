<!-- Panel principal con indicadores y accesos del sistema. -->
<section class="dashboard-hero">
  <div class="dashboard-hero__copy">
    <span class="eyebrow">Panel principal</span>
    <h1>Bienvenido{{with login}}, {{!userName}}{{endwith login}}</h1>
    <p>Organiza la operación de activos TI y accede a cada módulo desde un solo lugar.</p>
    <div class="dashboard-hero__actions">
      <a class="button button--primary" href="index.php?page=Navegacion_Navegacion">
        <i class="fa-solid fa-sitemap" aria-hidden="true"></i>
        Editar navegación
      </a>
      <a class="button button--ghost" href="index.php?page=Articulos_Articulos">
        <i class="fa-solid fa-boxes-stacked" aria-hidden="true"></i>
        Ir al catálogo
      </a>
    </div>
  </div>
  <div class="dashboard-hero__aside">
    <span class="dashboard-hero__date">{{DashboardDate}}</span>
    <strong>Activos IT</strong>
    <p>Panel de administración</p>
  </div>
</section>

<section class="dashboard-stats" aria-label="Resumen de activos">
  <article class="stat-card">
    <span class="stat-card__icon"><i class="fa-solid fa-users" aria-hidden="true"></i></span>
    <div>
      <span class="stat-card__label">Usuarios</span>
      <strong>—</strong>
      <small>Datos pendientes</small>
    </div>
  </article>
  <article class="stat-card">
    <span class="stat-card__icon"><i class="fa-solid fa-laptop" aria-hidden="true"></i></span>
    <div>
      <span class="stat-card__label">Equipos</span>
      <strong>—</strong>
      <small>Datos pendientes</small>
    </div>
  </article>
  <article class="stat-card">
    <span class="stat-card__icon"><i class="fa-solid fa-right-left" aria-hidden="true"></i></span>
    <div>
      <span class="stat-card__label">Movimientos</span>
      <strong>—</strong>
      <small>Datos pendientes</small>
    </div>
  </article>
  <article class="stat-card">
    <span class="stat-card__icon"><i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i></span>
    <div>
      <span class="stat-card__label">Mantenimientos</span>
      <strong>—</strong>
      <small>Datos pendientes</small>
    </div>
  </article>
</section>

<div class="section-heading">
  <div>
    <span class="eyebrow">Módulos</span>
    <h2>Distribución de navegación</h2>
  </div>
  <a class="text-link" href="index.php?page=Navegacion_Navegacion">
    Administrar
    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
  </a>
</div>

<section class="dashboard-grid" aria-label="Módulos del panel">
  {{foreach DashboardCards}}
  <article class="module-card" data-type="{{!type}}" data-status="{{!status}}">
    <div class="module-card__header">
      <span class="module-card__icon"><i class="fa-solid {{icon}}" aria-hidden="true"></i></span>
      <div class="module-card__heading">
        <a class="module-card__link" href="{{!nav_url}}" data-status="{{!status}}">{{!nav_label}}</a>
        <span class="module-card__label">{{!nav_label}}</span>
        <span class="module-card__meta">{{!status_label}}</span>
        <p class="module-card__description">{{!description}}</p>
      </div>
    </div>
    <ul class="module-card__items">{{items_html}}</ul>
    <div class="module-card__footer">
      <a class="module-card__action" href="{{!nav_url}}" data-status="{{!status}}">
        Abrir módulo
        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
      </a>
    </div>
  </article>
  {{endfor DashboardCards}}
</section>
