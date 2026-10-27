<!-- Estructura general para paginas publicas. -->
<!DOCTYPE html>
<html lang="es">
<head>
{{include partials/head}}
</head>
<body class="site-public">
  <a class="skip-link" href="#main-content">Saltar al contenido</a>
  {{include partials/public_header}}
  <main id="main-content" class="site-main">
    {{{page_content}}}
  </main>
  {{include partials/public_footer}}
  {{foreach EndScripts}}
    <script src="{{~BASE_DIR}}/{{this}}"></script>
  {{endfor EndScripts}}
</body>
</html>
