<aside class="private-sidebar" id="privateSidebar" aria-label="Navegación privada">
  <div class="private-sidebar__inner">
    <p class="private-sidebar__eyebrow">Navegación</p>
    <nav class="sidebar-nav">
      <ul>
        {{foreach NAVIGATION}}
          <li class="nav-entry" data-type="{{!type}}" data-status="{{!status}}">
            <a class="nav-entry__link{{active_class}}" href="{{!nav_url}}" data-status="{{!status}}">
              <i class="fa-solid {{icon}}" aria-hidden="true"></i>
              <span>{{!nav_label}}</span>
            </a>
            <details class="nav-entry__group"{{open_attr}}>
              <summary class="nav-entry__summary{{active_class}}">
                <i class="fa-solid {{icon}}" aria-hidden="true"></i>
                <span>{{!nav_label}}</span>
                <i class="fa-solid fa-chevron-down nav-entry__caret" aria-hidden="true"></i>
              </summary>
              <ul class="nav-submenu">{{items_html}}</ul>
            </details>
          </li>
        {{endfor NAVIGATION}}
      </ul>
    </nav>
    <div class="private-sidebar__footer">
      <a class="sidebar-edit-link" href="index.php?page=Navegacion_Navegacion">
        <i class="fa-solid fa-sitemap" aria-hidden="true"></i>
        <span>Editar navegación</span>
      </a>
    </div>
  </div>
</aside>
