<!-- Metadatos, estilos y recursos comunes de las paginas. -->
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#1F3B73">
<meta name="description" content="Sistema de gestión de activos de TI de Pinturas Americanas.">
<title>{{!SITE_TITLE}}</title>
<link rel="icon" type="image/png" href="{{BASE_DIR}}/public/imgs/brand/favicon.png">
<link rel="apple-touch-icon" href="{{BASE_DIR}}/public/imgs/brand/logo-icon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap-grid.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap-utilities.min.css">
<link rel="stylesheet" href="{{BASE_DIR}}/public/css/appstyle.css?v=20261024-01">
<link rel="stylesheet" href="{{BASE_DIR}}/public/css/theme.css?v=20261024-01">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
{{foreach SiteLinks}}
  <link rel="stylesheet" href="{{~BASE_DIR}}/{{this}}" />
{{endfor SiteLinks}}
{{foreach BeginScripts}}
  <script src="{{~BASE_DIR}}/{{this}}"></script>
{{endfor BeginScripts}}
