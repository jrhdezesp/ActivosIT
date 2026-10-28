<!-- Formulario para crear o modificar un elemento de catalogo. -->
<div class="form-layout catalog-form">
  <section class="form-card">
    <div class="form-card__header catalog-form__header">
      <div>
        <span class="eyebrow">{{!CatalogFormEyebrow}}</span>
        <h1 class="form-card__title">{{!FormTitle}}</h1>
        <p class="form-card__description">{{!FormDescription}}</p>
      </div>
      <a class="button button--ghost button--sm" href="{{!FormCancelUrl}}">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
        {{!FormBackLabel}}
      </a>
    </div>

    <form action="{{!FormAction}}" method="POST">
      <div class="form-card__body">
        <input type="hidden" name="token" value="{{!FormToken}}" />
        <input type="hidden" name="tipo" value="{{!CatalogType}}" />
        <input type="hidden" name="mode" value="{{!FormMode}}" />
        <input type="hidden" name="id" value="{{FormId}}" />

        {{if FormHasErrors}}
        <div class="form-error-summary" role="alert">{{!FormErrorSummary}}</div>
        {{endif FormHasErrors}}

        {{if FormDanger}}
        <div class="alert alert--danger alert--compact catalog-form__warning" role="alert">
          <span class="alert__icon" aria-hidden="true"><i class="fa-solid fa-triangle-exclamation"></i></span>
          <div class="alert__content">
            <p class="alert__message">Se desactivará <strong>{{!FormRecordName}}</strong>. Dejará de estar disponible para nuevas operaciones, pero se conserva en el historial.</p>
          </div>
        </div>
        {{endif FormDanger}}

        <div class="form-grid form-grid--2">
          {{foreach fields}}
          <div class="form-field {{if full}}form-field--full{{endif full}} {{if hasError}}has-error{{endif hasError}}">
            <label for="{{!name}}" class="form-label {{if required}}form-label--required{{endif required}}">{{!label}}</label>

            {{if isText}}
            <input type="text" name="{{!name}}" id="{{!name}}" value="{{!value}}" {{requiredAttr}} {{readonlyAttr}} {{if maxlength}}maxlength="{{maxlength}}"{{endif maxlength}} />
            {{endif isText}}

            {{if isTextarea}}
            <textarea name="{{!name}}" id="{{!name}}" {{requiredAttr}} {{readonlyAttr}} {{if maxlength}}maxlength="{{maxlength}}"{{endif maxlength}}>{{!value}}</textarea>
            {{endif isTextarea}}

            {{if isSelect}}
            <select name="{{!name}}" id="{{!name}}" {{requiredAttr}} {{readonlyAttr}}>
              {{foreach options}}
              <option value="{{!value}}" {{selected}}>{{!label}}</option>
              {{endfor options}}
            </select>
            {{endif isSelect}}

            {{if help}}
            <p class="form-help">{{!help}}</p>
            {{endif help}}

            {{if hasError}}
            <p class="form-error">{{!error}}</p>
            {{endif hasError}}
          </div>
          {{endfor fields}}
        </div>
      </div>

      <div class="form-card__footer">
        <div class="form-actions">
          <button class="button {{if FormDanger}}button--danger{{endif FormDanger}}{{ifnot FormDanger}}button--primary{{endifnot FormDanger}}" type="submit">
            <i class="fa-solid {{FormSubmitIcon}}" aria-hidden="true"></i>
            {{!FormSubmitLabel}}
          </button>
          <a class="button button--ghost" href="{{!FormCancelUrl}}">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            Cancelar
          </a>
        </div>
      </div>
    </form>
  </section>
</div>
