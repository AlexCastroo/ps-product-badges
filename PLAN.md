# Plan de implementación — módulo `productbadges`

**Target:** PrestaShop 1.7.8.x (tienda actual: 1.7.8.11), compatible hacia arriba con PS8.1  
**Arquitectura:** Legacy AdminController (renderList/renderForm), sin Symfony Grid, sin actionProductFormBuilderModifier  
**PHP:** 8.1+  
**Tema front:** Classic theme PS 1.7  
**Tiempo estimado:** ~3.5h

---

## Orden de creación de archivos

```
1.  sql/install.php
2.  sql/uninstall.php
3.  productbadges.php
4.  config.xml
5.  controllers/admin/AdminProductBadgesController.php
6.  views/templates/admin/configure.tpl
7.  views/templates/admin/product_badges_tab.tpl
8.  views/css/productbadges.css
9.  views/templates/front/badges.tpl
10. translations/es.php
11. translations/en.php
```

---

## Fase 1 — SQL + Estructura base [20 min]

### Tablas

| Tabla | Columnas |
|---|---|
| `PREFIX_product_badge` | `id_badge` (PK AI), `bgcolor` VARCHAR(7), `textcolor` VARCHAR(7), `position` ENUM('top-left','top-right'), `active` TINYINT(1) |
| `PREFIX_product_badge_lang` | `id_badge` INT, `id_lang` INT, `text` VARCHAR(255) — PK compuesta |
| `PREFIX_product_badge_product` | `id_badge` INT, `id_product` INT, `id_shop` INT — PK compuesta |

### Criterio de verificación
- `install()` ejecuta sin errores
- Las 3 tablas existen en DB
- `uninstall()` las borra limpiamente

---

## Fase 2 — Clase principal `productbadges.php` [35 min]

### Hooks a registrar

| Hook | Justificación |
|---|---|
| `displayAdminProductsExtra` | Pestaña de asignación de badges en ficha de producto BO |
| `displayProductListItem` | Renderizar badges en listado/categoría frontend |
| `displayProduct` | Renderizar badges en ficha de producto frontend |
| `displayHeader` | Inyectar CSS en front |
| `displayBackOfficeHeader` | Inyectar CSS/JS en BO (color pickers) |
| `actionProductAdd` | Guardar asignaciones al crear producto nuevo |
| `actionProductUpdate` | Guardar asignaciones al editar producto existente |

### Métodos auxiliares

- `getBadgesByProduct(int $id_product): array` — query tabla intermedia + join lang
- `getActiveBadges(int $id_lang): array` — todos los badges activos con texto traducido
- `saveBadgesForProduct(int $id_product, array $badge_ids): void` — DELETE + INSERT en tabla intermedia

### Configuración global (via `Configuration`)

| Key | Tipo | Default |
|---|---|---|
| `PRODUCTBADGES_ENABLED` | bool | 1 |
| `PRODUCTBADGES_SHOW_LISTING` | bool | 1 |
| `PRODUCTBADGES_SHOW_PRODUCT` | bool | 1 |
| `PRODUCTBADGES_MAX_BADGES` | int | 3 |

### Criterio de verificación
- Módulo instala/desinstala sin errores
- Hooks aparecen en BO → Diseño → Hooks
- Tab aparece en menú BO → Catálogo

---

## Fase 3 — AdminController CRUD [75 min]

**Archivo:** `controllers/admin/AdminProductBadgesController.php`

### `renderList()`
Columnas: texto (lang activo), preview color fondo, preview color texto, posición, activo (toggle), acciones (editar/eliminar).

### `renderForm()`

| Campo | Tipo HelperForm | Notas |
|---|---|---|
| `text` | `text` con `lang: true` | Multilenguaje nativo |
| `bgcolor` | `color` | Color picker nativo PS |
| `textcolor` | `color` | Color picker nativo PS |
| `position` | `select` | Opciones: top-left / top-right |
| `active` | `switch` | |

### Configuración global
Accesible desde `getContent()` — HelperForm separado con las 4 keys de `Configuration`.

### Registro de Tab
En `install()`:
```php
$this->installTab('AdminProductBadges', 'Product Badges', 'AdminCatalog');
```
En `uninstall()`:
```php
$this->uninstallTab('AdminProductBadges');
```

### Criterio de verificación
- CRUD completo funcional
- Crear badge en es + en, editar, toggle activo, eliminar
- Verificar en DB que `_lang` guarda ambos idiomas
- Configuración global guarda en `Configuration`

---

## Fase 4 — Panel de asignación en ficha de producto [40 min]

**Hook:** `hookDisplayAdminProductsExtra($params)`

### Flujo
1. Obtener `$id_product = (int)Tools::getValue('id_product')`
2. Cargar todos los badges activos
3. Cargar badges ya asignados al producto
4. Renderizar template con checkboxes

### Template `views/templates/admin/product_badges_tab.tpl`
- Lista de badges con preview de color + texto
- Checkboxes `name="badge_product_ids[]"` con `value="{$badge.id_badge}"`

### Guardado via hooks de producto
```php
public function hookActionProductAdd($params)
{
    $id_product = (int)$params['id_product'];
    $badge_ids = (array)Tools::getValue('badge_product_ids', []);
    $this->saveBadgesForProduct($id_product, $badge_ids);
}

public function hookActionProductUpdate($params)
{
    $id_product = (int)$params['id_product'];
    $badge_ids = (array)Tools::getValue('badge_product_ids', []);
    $this->saveBadgesForProduct($id_product, $badge_ids);
}
```

### Criterio de verificación
- Pestaña "Badges" visible en ficha de producto BO
- Asignar badges, guardar producto, recargar → asignaciones persisten
- Desasignar todos los badges y guardar → tabla intermedia queda vacía (sin registro huérfano)

---

## Fase 5 — Renderizado frontend [30 min]

### Hooks

```php
public function hookDisplayProductListItem($params)
{
    if (!Configuration::get('PRODUCTBADGES_ENABLED')) return;
    if (!Configuration::get('PRODUCTBADGES_SHOW_LISTING')) return;
    // ...
}

public function hookDisplayProduct($params)
{
    if (!Configuration::get('PRODUCTBADGES_ENABLED')) return;
    if (!Configuration::get('PRODUCTBADGES_SHOW_PRODUCT')) return;
    // ...
}
```

### Template `views/templates/front/badges.tpl`
```smarty
<div class="productbadges-wrapper">
{foreach from=$badges item=badge}
<span class="product-badge product-badge--{$badge.position|escape:'html'}"
      style="background-color:{$badge.bgcolor|escape:'html'};color:{$badge.textcolor|escape:'html'};">
  {$badge.text|escape:'html'}
</span>
{/foreach}
</div>
```

### CSS `views/css/productbadges.css`
```css
.productbadges-wrapper { position: absolute; top: 0; left: 0; width: 100%; pointer-events: none; z-index: 10; }
.product-badge { position: absolute; padding: 3px 8px; font-size: 12px; font-weight: bold; pointer-events: auto; }
.product-badge--top-left  { top: 8px; left: 8px; }
.product-badge--top-right { top: 8px; right: 8px; }
```

### Criterio de verificación
- Badges visibles en listado de categoría y ficha de producto
- Posición top-left / top-right correcta visualmente
- Config "mostrar en listado = no" oculta badges en listados
- Máximo de badges respetado (solo se muestran los N primeros)

---

## Fase 6 — Traducciones [15 min]

- `translations/es.php` + `translations/en.php`
- Usar `$this->l('string')` en PHP
- Usar `{l s='string' mod='productbadges'}` en Smarty

### Criterio de verificación
- Cambiar idioma BO → textos del módulo cambian
- Sin strings hardcodeadas en templates

---

## Alertas: puntos propensos a error

### 1. Guardado desde `displayAdminProductsExtra` — CRÍTICO
Sin badges seleccionados, `Tools::getValue('badge_product_ids')` devuelve `false`, no `[]`.
Siempre castear: `(array)Tools::getValue('badge_product_ids', [])`.
Sin esto, desasignar todos los badges no borra nada en DB.

### 2. Multilenguaje en HelperForm
Al guardar campo con `lang: true`, PS envía `text[{id_lang}]`.
El controller debe iterar `Language::getLanguages(false)` y hacer INSERT/UPDATE en `_lang` por cada idioma.
Error común: guardar solo el idioma activo y perder las otras traducciones.

### 3. Posicionamiento CSS sobre imagen
`displayProductListItem` renderiza dentro del `<article>` pero puede quedar fuera del contenedor de imagen según el markup del tema.
Si los badges no se posicionan correctamente sobre la imagen en la prueba visual de Fase 5, evaluar cambiar a `displayProductPriceBlock` con `type='flags'` (que sí está dentro del contenedor de imagen en Classic theme).

### 4. `id_shop` en tabla intermedia
Incluir desde el SQL inicial aunque no se gestione por tienda.
Sin él, instalación multitienda puede generar errores de FK o comportamiento inesperado.

### 5. Rollback en `install()`
Si `install()` registra el Tab y luego falla por otro motivo, el Tab queda huérfano.
Wrappear toda la instalación en try/catch con rollback completo (`uninstallTab` + `dropTables`) si algo falla.

### 6. Compatibilidad PS8
`AdminController` legacy y `displayAdminProductsExtra` siguen funcionando en PS8 sin cambios de código.
En PS8, la pestaña aparece en "Módulos" del nuevo product form — diferente UX pero mismo comportamiento.

---

## Estructura de directorios final

```
modules/productbadges/
├── productbadges.php
├── config.xml
├── logo.png
├── sql/
│   ├── install.php
│   └── uninstall.php
├── controllers/
│   └── admin/
│       └── AdminProductBadgesController.php
├── views/
│   ├── templates/
│   │   ├── admin/
│   │   │   ├── configure.tpl
│   │   │   └── product_badges_tab.tpl
│   │   └── front/
│   │       └── badges.tpl
│   ├── css/
│   │   └── productbadges.css
│   └── js/
└── translations/
    ├── es.php
    └── en.php
```
