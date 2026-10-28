<!-- Estructura general para paginas privadas con sesion. -->
<!DOCTYPE html>
<html lang="es">
<head>
{{include partials/head}}
<link rel="stylesheet" href="{{BASE_DIR}}/public/css/private.css?v=20261028-11">
</head>
<body class="site-private">
  <a class="skip-link" href="#main-content">Saltar al contenido</a>
  {{include partials/private_header}}
  <div class="private-shell">
    {{include partials/private_sidebar}}
    <div class="private-sidebar-backdrop" id="sidebarBackdrop" hidden></div>
    <main id="main-content" class="site-main">
      {{{page_content}}}
    </main>
  </div>
  {{include partials/private_footer}}
  <div class="modal modal--info" id="unavailableModal" tabindex="-1" aria-labelledby="unavailableModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog--sm">
      <div class="modal-content">
        <div class="modal-header">
          <div>
            <span class="eyebrow">Módulo</span>
            <h2 class="modal-title" id="unavailableModalLabel">No disponible</h2>
          </div>
          <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="Cerrar"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
          <p class="mb-0">Este módulo no está disponible por el momento.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="button button--primary" data-bs-dismiss="modal">Entendido</button>
        </div>
      </div>
    </div>
  </div>
  {{foreach EndScripts}}
    <script src="{{~BASE_DIR}}/{{this}}"></script>
  {{endfor EndScripts}}
  <script>
    (function () {
      var body = document.body;
      var toggle = document.getElementById('sidebarToggle');
      var backdrop = document.getElementById('sidebarBackdrop');
      var unavailableModal = document.getElementById('unavailableModal');

      function setSidebar(open) {
        body.classList.toggle('sidebar-open', open);
        if (toggle) {
          toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
        if (backdrop) {
          backdrop.hidden = !open;
        }
      }

      if (toggle) {
        toggle.addEventListener('click', function () {
          setSidebar(!body.classList.contains('sidebar-open'));
        });
      }

      if (backdrop) {
        backdrop.addEventListener('click', function () {
          setSidebar(false);
        });
      }

      document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
          setSidebar(false);
          if (unavailableModal && window.bootstrap && window.bootstrap.Modal) {
            var modalInstance = window.bootstrap.Modal.getInstance(unavailableModal);
            if (modalInstance) {
              modalInstance.hide();
            }
          }
        }
      });

      document.addEventListener('click', function (event) {
        if (!(event.target instanceof Element)) {
          return;
        }
        var pending = event.target.closest('a[data-status="pending"]');
        if (!pending) {
          return;
        }
        event.preventDefault();
        setSidebar(false);
        if (unavailableModal && window.bootstrap && window.bootstrap.Modal) {
          window.bootstrap.Modal.getOrCreateInstance(unavailableModal).show();
          return;
        }
        window.alert('No disponible');
      });
    })();
  </script>
</body>
</html>
