<div class="form-layout">
<section class="form-card">
  <div class="form-card__header">
    <h1 class="form-card__title">{{!FormTitle}}</h1>
  </div>

  {{with product}}
  <form action="index.php?page=Products_Product&mode={{~mode}}&productId={{productId}}" method="POST">
    <div class="form-card__body">
      <div class="form-grid form-grid--2">
        <div class="form-field form-field--full">
          <label for="productIdD">Código</label>
          <input readonly disabled type="text" name="productIdD" id="productIdD" placeholder="Código" value="{{productId}}" />
          <input type="hidden" name="mode" value="{{~mode}}" />
          <input type="hidden" name="productId" value="{{productId}}" />
          <input type="hidden" name="token" value="{{~product_xss_token}}" />
        </div>

        <div class="form-field form-field--full {{if productName_error}}has-error{{endif productName_error}}">
          <label for="productName">Producto</label>
          <input {{~readonly}} type="text" name="productName" id="productName" placeholder="Nombre del producto" value="{{!productName}}" />
          {{if productName_error}}
          <p class="form-error">{{productName_error}}</p>
          {{endif productName_error}}
        </div>

        <div class="form-field form-field--full {{if productDescription_error}}has-error{{endif productDescription_error}}">
          <label for="productDescription">Descripción</label>
          <textarea {{~readonly}} name="productDescription" id="productDescription" placeholder="Descripción del producto">{{!productDescription}}</textarea>
          {{if productDescription_error}}
          <p class="form-error">{{productDescription_error}}</p>
          {{endif productDescription_error}}
        </div>

        <div class="form-field {{if productPrice_error}}has-error{{endif productPrice_error}}">
          <label for="productPrice">Precio</label>
          <input {{~readonly}} type="number" name="productPrice" id="productPrice" placeholder="Precio" value="{{!productPrice}}" />
          {{if productPrice_error}}
          <p class="form-error">{{productPrice_error}}</p>
          {{endif productPrice_error}}
        </div>

        <div class="form-field">
          <label for="productStatus">Estado</label>
          <select name="productStatus" id="productStatus" {{if ~readonly}} readonly disabled {{endif ~readonly}}>
            <option value="ACT" {{productStatus_act}}>Activo</option>
            <option value="INA" {{productStatus_ina}}>Inactivo</option>
          </select>
        </div>

        <div class="form-field form-field--full {{if productImgUrl_error}}has-error{{endif productImgUrl_error}}">
          <label for="productImgUrl">URL de imagen (250 x 200)</label>
          <input {{~readonly}} type="text" name="productImgUrl" id="productImgUrl" placeholder="Url de la imagen" value="{{!productImgUrl}}" />
          {{if productImgUrl_error}}
          <p class="form-error">{{productImgUrl_error}}</p>
          {{endif productImgUrl_error}}
        </div>
      </div>
    </div>
  {{endwith product}}

    <div class="form-card__footer">
      <div class="form-actions">
        {{if showCommitBtn}}
        <button class="button button--primary" type="submit" name="btnConfirmar">
          <i class="fa-solid fa-floppy-disk"></i>
          Confirmar
        </button>
        {{endif showCommitBtn}}
        <button class="button button--ghost" type="button" id="btnCancelar">
          <i class="fa-solid fa-arrow-left"></i>
          {{if showCommitBtn}}
          Cancelar
          {{endif showCommitBtn}}
          {{ifnot showCommitBtn}}
          Regresar
          {{endifnot showCommitBtn}}
        </button>
      </div>
    </div>
  </form>
</section>
</div>

<script>
  document.addEventListener("DOMContentLoaded", ()=>{
    const btnCancelar = document.getElementById("btnCancelar");
    btnCancelar.addEventListener("click", (e)=>{
      e.preventDefault();
      e.stopPropagation();
      window.location.assign("index.php?page=Products_Products");
    });
  });
</script>
