<!-- Controles para elegir el tamano de pagina y cambiar de pagina. -->
<section class="pagination" aria-label="Paginación">
  <form action="{{url}}" method="get" id="pagination" class="pagination__size">
    <input type="hidden" name="page" value="{{page}}" />
    {{foreach extraParams}}
    <input type="hidden" name="{{!name}}" value="{{!value}}" />
    {{endfor extraParams}}
    <label for="itemsPerPage" class="pagination__label">Filas por página</label>
    <select name="itemsPerPage" id="itemsPerPage" class="pagination__select">
      <option value="1" {{itemsPerPage_1}}>1</option>
      <option value="5" {{itemsPerPage_5}}>5</option>
      <option value="10" {{itemsPerPage_10}}>10</option>
      <option value="20" {{itemsPerPage_20}}>20</option>
      <option value="50" {{itemsPerPage_50}}>50</option>
      <option value="100" {{itemsPerPage_100}}>100</option>
    </select>
  </form>
  <nav class="pagination__controls" aria-label="Páginas">
    {{foreach pages}}
    <a {{if url}}href="{{url}}"{{endif url}} class="pagination__link {{if active}}is-active{{endif active}}">{{page}}</a>
    {{endfor pages}}
  </nav>
</section>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('itemsPerPage').addEventListener('change', function() {
      document.getElementById('pagination').submit();
    });
  });
</script>
