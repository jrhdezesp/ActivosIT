<!-- Editor visual de opciones de navegacion del sistema. -->
<section class="editor-hero">
  <div>
    <span class="eyebrow">Configuración</span>
    <h1>Distribución de navegación</h1>
    <p>Ordena los grupos y elementos del menú privado. Los controles funcionan en esta vista; la persistencia se conectará en una fase posterior.</p>
  </div>
  <div class="editor-hero__actions">
    <a class="button button--ghost" href="index.php?page=Dashboard_Dashboard">
      <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
      Volver al panel
    </a>
    <button type="button" class="button button--primary" id="btnSaveNav">
      <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
      Guardar cambios
    </button>
  </div>
</section>

<div class="editor-summary">
  <span><strong>{{EditorGroupCount}}</strong> grupos</span>
  <span><strong>{{EditorItemCount}}</strong> elementos</span>
  <span><strong>{{EditorLinkCount}}</strong> enlaces directos</span>
  <span class="editor-summary__note"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Cambios solo en esta vista</span>
</div>

<div class="nav-editor__toolbar">
  <div>
    <h2>Grupos y enlaces</h2>
    <p>Usa las flechas para cambiar el orden de cada fila.</p>
  </div>
  <div class="nav-editor__actions">
    <button type="button" class="button button--ghost" data-bs-toggle="modal" data-bs-target="#groupModal">
      <i class="fa-solid fa-folder-plus" aria-hidden="true"></i>
      Crear grupo
    </button>
    <button type="button" class="button button--primary" data-bs-toggle="modal" data-bs-target="#itemModal">
      <i class="fa-solid fa-plus" aria-hidden="true"></i>
      Agregar elemento
    </button>
  </div>
</div>

<div class="nav-editor__layout">
  <section class="nav-editor__canvas" id="navEditor" aria-label="Estructura de navegación">
    {{foreach EditorEntries}}
    <article class="nav-editor-group" data-group data-type="{{!type}}" data-status="{{!status}}">
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
          <button type="button" class="editor-icon-button" data-move="up" title="Subir grupo" aria-label="Subir grupo {{!nav_label}}"><i class="fa-solid fa-arrow-up"></i></button>
          <button type="button" class="editor-icon-button" data-move="down" title="Bajar grupo" aria-label="Bajar grupo {{!nav_label}}"><i class="fa-solid fa-arrow-down"></i></button>
        </div>
      </div>
      <ul class="nav-editor-items">{{items_html}}</ul>
      <p class="nav-editor-empty">Sin elementos. Usa "Agregar elemento" para crear uno.</p>
    </article>
    {{endfor EditorEntries}}
  </section>

  <aside class="nav-editor__preview" aria-label="Vista previa">
    <span class="eyebrow">Vista previa</span>
    <h2>Menú privado</h2>
    <p>Así se verá la estructura en el panel.</p>
    <div class="nav-preview">
      {{foreach EditorEntries}}
      <div class="nav-preview__group" data-type="{{!type}}">
        <span class="nav-preview__title"><i class="fa-solid {{icon}}" aria-hidden="true"></i>{{!nav_label}}</span>
        <ul>{{preview_items_html}}</ul>
      </div>
      {{endfor EditorEntries}}
    </div>
  </aside>
</div>

<div class="toast-region">
  <div id="editorToast" class="app-toast app-toast--info" role="status" aria-live="polite" hidden></div>
</div>

<div class="modal" id="groupModal" tabindex="-1" aria-labelledby="groupModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <span class="eyebrow">Navegación</span>
          <h2 class="modal-title" id="groupModalLabel">Crear grupo</h2>
        </div>
        <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="Cerrar"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="modal-body">
        <div class="alert alert--warning alert--compact" role="note">
          <span class="alert__icon" aria-hidden="true"><i class="fa-solid fa-triangle-exclamation"></i></span>
          <div class="alert__content">
            <p class="alert__message">Solo diseño. El grupo no se guardará todavía.</p>
          </div>
        </div>
        <div class="form-field">
          <label for="groupName">Nombre del grupo</label>
          <input id="groupName" type="text" placeholder="Ej. Inventario">
        </div>
        <div class="form-row">
          <div class="form-field">
            <label for="groupIcon">Icono</label>
            <input id="groupIcon" type="text" value="fa-folder">
          </div>
          <div class="form-field">
            <label for="groupOrder">Orden</label>
            <input id="groupOrder" type="number" value="1" min="1">
          </div>
        </div>
        <div class="form-field">
          <label for="groupVisibility">Visibilidad</label>
          <select id="groupVisibility">
            <option>Todos los usuarios</option>
            <option>Solo administradores</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="button button--ghost" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="button button--primary" id="btnCreateGroup" data-bs-dismiss="modal">Crear grupo</button>
      </div>
    </div>
  </div>
</div>

<div class="modal" id="itemModal" tabindex="-1" aria-labelledby="itemModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <span class="eyebrow">Navegación</span>
          <h2 class="modal-title" id="itemModalLabel">Agregar elemento</h2>
        </div>
        <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="Cerrar"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="modal-body">
        <div class="alert alert--warning alert--compact" role="note">
          <span class="alert__icon" aria-hidden="true"><i class="fa-solid fa-triangle-exclamation"></i></span>
          <div class="alert__content">
            <p class="alert__message">Solo diseño. El elemento no se guardará todavía.</p>
          </div>
        </div>
        <div class="form-field">
          <label for="itemGroup">Grupo</label>
          <select id="itemGroup">
            {{foreach EditorGroups}}
            <option value="{{!id}}">{{!nav_label}}</option>
            {{endfor EditorGroups}}
          </select>
        </div>
        <div class="form-field">
          <label for="itemName">Etiqueta</label>
          <input id="itemName" type="text" placeholder="Ej. Mantenimientos">
        </div>
        <div class="form-field">
          <label for="itemUrl">Ruta</label>
          <input id="itemUrl" type="text" placeholder="index.php?page=Modulo_Modulo">
        </div>
        <div class="form-row">
          <div class="form-field">
            <label for="itemIcon">Icono</label>
            <input id="itemIcon" type="text" value="fa-circle">
          </div>
          <div class="form-field">
            <label for="itemOrder">Orden</label>
            <input id="itemOrder" type="number" value="1" min="1">
          </div>
        </div>
        <div class="form-field">
          <label for="itemStatus">Estado</label>
          <select id="itemStatus">
            <option value="active">Activo</option>
            <option value="pending">Pendiente</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="button button--ghost" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="button button--primary" id="btnCreateItem" data-bs-dismiss="modal">Agregar elemento</button>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    var editor = document.getElementById('navEditor');
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

    if (editor) {
      editor.addEventListener('click', function (event) {
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
          showToast('Orden actualizado en la vista. Aún no se guarda.');
        } else if (button.dataset.move === 'down' && row.nextElementSibling) {
          row.parentNode.insertBefore(row.nextElementSibling, row);
          showToast('Orden actualizado en la vista. Aún no se guarda.');
        }
      });
    }

    var saveButton = document.getElementById('btnSaveNav');
    if (saveButton) {
      saveButton.addEventListener('click', function () {
        showToast('Vista de diseño: los cambios no se guardan.');
      });
    }

    var createItem = document.getElementById('btnCreateItem');
    if (createItem) {
      createItem.addEventListener('click', function () {
        showToast('Vista de diseño: el elemento no se guarda.');
      });
    }

    var createGroup = document.getElementById('btnCreateGroup');
    if (createGroup) {
      createGroup.addEventListener('click', function () {
        showToast('Vista de diseño: el grupo no se guarda.');
      });
    }
  });
</script>
