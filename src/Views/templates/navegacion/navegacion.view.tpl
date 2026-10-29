<!-- Editor de orden y visibilidad de la navegacion privada. -->
<section class="editor-hero">
  <div>
    <span class="eyebrow">Configuración</span>
    <h1>Distribución de navegación</h1>
    <p>Ordena los grupos y elementos del menú privado y decide cuáles se muestran. Las rutas, los iconos y los permisos siguen definidos por el sistema; aquí solo se cambia la presentación.</p>
  </div>
  <div class="editor-hero__actions">
    <a class="button button--ghost" href="index.php?page=Dashboard_Dashboard">
      <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
      Volver al panel
    </a>
    <button type="submit" form="navForm" class="button button--primary">
      <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
      Guardar cambios
    </button>
  </div>
</section>

{{if FlashHas}}
<div class="alert alert--{{FlashType}} alert--compact" role="status" aria-live="polite">
  <span class="alert__icon" aria-hidden="true"><i class="fa-solid {{FlashIcon}}"></i></span>
  <div class="alert__content">
    <p class="alert__title">{{!FlashTitle}}</p>
    <p class="alert__message">{{!FlashMessage}}</p>
  </div>
</div>
{{endif FlashHas}}

<div class="editor-summary">
  <span><strong>{{EditorGroupCount}}</strong> grupos</span>
  <span><strong>{{EditorItemCount}}</strong> elementos</span>
  <span><strong>{{EditorLinkCount}}</strong> enlaces directos</span>
  <span class="editor-summary__note"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Los cambios se guardan al confirmar</span>
</div>

<div class="nav-editor__toolbar">
  <div>
    <h2>Grupos y enlaces</h2>
    <p>Usa las flechas para cambiar el orden y el ojo para mostrar u ocultar cada fila. Ocultar no retira permisos: solo deja de mostrarse en el menú. El acceso a Navegación y su grupo permanecen siempre visibles.</p>
  </div>
</div>

<form id="navForm" method="post" action="index.php?page=Navegacion_Navegacion">
  <input type="hidden" name="csrf_token" value="{{CSRFToken}}" />
  <input type="hidden" name="distribucion" id="navDistribution" value="" />

  <div class="nav-editor__layout">
    <section class="nav-editor__canvas" id="navEditor" aria-label="Estructura de navegación">
      {{foreach EditorEntries}}
      <article class="nav-editor-group{{hidden_class}}" data-group data-group-id="{{!id}}" data-type="{{!type}}" data-status="{{!status}}" data-visible="{{visible_attr}}">
        <div class="nav-editor-group__header">
          <div class="nav-editor-group__title">
            <span class="nav-editor-group__handle" aria-hidden="true"><i class="fa-solid fa-grip-vertical"></i></span>
            <span class="nav-editor-group__icon"><i class="fa-solid {{icon}}" aria-hidden="true"></i></span>
            <div>
              <h3>{{!nav_label}}</h3>
              <span class="nav-editor-group__meta">{{!meta}}</span>
            </div>
          </div>
          <div class="nav-editor-group__controls">
            {{hidden_badge_html}}
            {{visibility_control_html}}
            <button type="button" class="editor-icon-button" data-move="up" title="Subir grupo" aria-label="Subir grupo {{!nav_label}}"><i class="fa-solid fa-arrow-up"></i></button>
            <button type="button" class="editor-icon-button" data-move="down" title="Bajar grupo" aria-label="Bajar grupo {{!nav_label}}"><i class="fa-solid fa-arrow-down"></i></button>
          </div>
        </div>
        <ul class="nav-editor-items">{{items_html}}</ul>
        <p class="nav-editor-empty">Sin elementos.</p>
      </article>
      {{endfor EditorEntries}}
    </section>

    <aside class="nav-editor__preview" aria-label="Vista previa">
      <span class="eyebrow">Vista previa</span>
      <h2>Menú privado</h2>
      <p>Así se verá la estructura guardada en el panel.</p>
      <div class="nav-preview">
        {{foreach EditorEntries}}
        <div class="nav-preview__group{{hidden_class}}" data-type="{{!type}}">
          <span class="nav-preview__title"><i class="fa-solid {{icon}}" aria-hidden="true"></i>{{!nav_label}}</span>
          <ul>{{preview_items_html}}</ul>
        </div>
        {{endfor EditorEntries}}
      </div>
    </aside>
  </div>
</form>

<div class="toast-region">
  <div id="editorToast" class="app-toast app-toast--info" role="status" aria-live="polite" hidden></div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    var editor = document.getElementById('navEditor');
    var form = document.getElementById('navForm');
    var distribution = document.getElementById('navDistribution');
    var toast = document.getElementById('editorToast');
    var toastTimer = null;

    function showToast(message) {
      if (!toast) {
        return;
      }
      toast.textContent = message;
      toast.hidden = false;
      clearTimeout(toastTimer);
      toastTimer = setTimeout(function () {
        toast.hidden = true;
      }, 2800);
    }

    function setVisible(row, visible) {
      row.dataset.visible = visible ? '1' : '0';
      row.classList.toggle('is-hidden', !visible);
      var button = row.querySelector('[data-toggle-visibility]');
      if (!button) {
        return;
      }
      button.setAttribute('aria-pressed', visible ? 'true' : 'false');
      button.setAttribute('title', visible ? 'Ocultar del menú' : 'Mostrar en el menú');
      var icon = button.querySelector('i');
      if (icon) {
        icon.className = 'fa-solid ' + (visible ? 'fa-eye' : 'fa-eye-slash');
      }
    }

    if (editor) {
      editor.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-toggle-visibility]');
        if (toggle) {
          var target = toggle.closest('[data-item], [data-group]');
          if (!target) {
            return;
          }
          var willShow = target.dataset.visible !== '1';
          setVisible(target, willShow);
          showToast(willShow ? 'Elemento visible. Guarda los cambios.' : 'Elemento oculto. Guarda los cambios.');
          return;
        }

        var button = event.target.closest('[data-move]');
        if (!button) {
          return;
        }
        var row = button.closest('[data-item], [data-group]');
        if (!row) {
          return;
        }
        if (button.dataset.move === 'up' && row.previousElementSibling) {
          row.parentNode.insertBefore(row, row.previousElementSibling);
          showToast('Orden actualizado. Guarda los cambios.');
        } else if (button.dataset.move === 'down' && row.nextElementSibling) {
          row.parentNode.insertBefore(row.nextElementSibling, row);
          showToast('Orden actualizado. Guarda los cambios.');
        }
      });
    }

    if (form && editor && distribution) {
      form.addEventListener('submit', function () {
        var groups = [];
        editor.querySelectorAll('[data-group]').forEach(function (group) {
          var items = [];
          group.querySelectorAll('[data-item]').forEach(function (item) {
            items.push({
              id: item.dataset.itemId,
              visible: item.dataset.visible === '1'
            });
          });
          groups.push({
            id: group.dataset.groupId,
            visible: group.dataset.visible === '1',
            items: items
          });
        });
        distribution.value = JSON.stringify({ groups: groups });
      });
    }
  });
</script>
