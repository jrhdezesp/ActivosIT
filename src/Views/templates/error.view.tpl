<section class="empty-state" aria-labelledby="error-title">
  <span class="empty-state__icon" aria-hidden="true">
    <i class="fa-solid {{CLIENT_ERROR_ICON}}"></i>
  </span>
  <span class="badge badge--outline">Error {{CLIENT_ERROR_CODE}}</span>
  <h1 class="empty-state__title" id="error-title">{{!CLIENT_ERROR_TITLE}}</h1>
  <p class="empty-state__description">{{!CLIENT_ERROR_MSG}}</p>
  <div class="empty-state__actions">
    {{if login}}
    <a class="button button--primary" href="index.php?page=Dashboard_Dashboard">
      <i class="fa-solid fa-gauge-high" aria-hidden="true"></i>
      Ir al panel
    </a>
    {{endif login}}
    {{ifnot login}}
    <a class="button button--primary" href="index.php?page={{PUBLIC_DEFAULT_CONTROLLER}}">
      <i class="fa-solid fa-house" aria-hidden="true"></i>
      Volver al inicio
    </a>
    {{endifnot login}}
  </div>
</section>
