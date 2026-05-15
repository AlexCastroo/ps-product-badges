# Product Badges — Módulo PrestaShop

Módulo para PrestaShop 1.7.8.x que permite asignar etiquetas visuales personalizadas (badges) a productos y mostrarlas superpuestas sobre la imagen, tanto en el listado de categoría como en la ficha de producto.

**Versión:** 1.0.0  
**Autor:** AlexCastro  
**Compatibilidad:** PrestaShop 1.7.8.0+  
**PHP:** 8.1+

---

## Funcionalidades

- CRUD completo de badges desde el Back Office (Catálogo → Product Badges)
- Texto multiidioma por badge
- Color de fondo y color de texto configurables con color picker
- Posición sobre la imagen: `top-left` o `top-right`
- Activar/desactivar badge individualmente
- Asignación de badges a productos desde la ficha de edición del producto
- Guardado automático al marcar/desmarcar (sin necesidad de guardar el producto)
- Límite configurable de badges visibles por producto
- Compatible con entornos multishop

---

## Instalación

1. Copiar la carpeta `productbadges/` en `modules/` de tu instalación de PrestaShop
2. Ir a **Back Office → Módulos → Gestión de módulos**
3. Buscar "Product Badges" e instalar
4. Las tablas de base de datos se crean automáticamente durante la instalación

---

## Configuración global

Accesible desde **Módulos → Configurar** junto al módulo.

| Opción | Descripción | Por defecto |
|---|---|---|
| Activar módulo | Activa o desactiva todo el módulo | Sí |
| Mostrar en listados | Muestra badges en páginas de categoría y búsqueda | Sí |
| Mostrar en ficha de producto | Muestra badges en la página de detalle del producto | Sí |
| Máximo de badges por producto | Número máximo de badges visibles simultáneamente | 3 |

---

## Gestión de badges

Desde **Catálogo → Product Badges** se pueden crear, editar y eliminar badges.

Campos por badge:

| Campo | Descripción |
|---|---|
| Texto | Etiqueta visible (multiidioma) — p.ej. "SALE", "NUEVO", "−20%" |
| Color de fondo | Hex `#rrggbb` |
| Color de texto | Hex `#rrggbb` |
| Posición | `top-left` o `top-right` |
| Activo | Solo las badges activas se muestran en el front |

---

## Asignación a productos

En la ficha de edición de cualquier producto, aparece el tab **Product Badges** con todas las badges activas disponibles. Marcar o desmarcar un checkbox guarda la asignación de forma inmediata por AJAX, sin necesidad de guardar el producto completo.

---

## Estructura de ficheros

```
productbadges/
│
├── productbadges.php                          # Clase principal del módulo + ObjectModel ProductBadge
│
├── controllers/
│   └── admin/
│       └── AdminProductBadgesController.php   # CRUD de badges en el Back Office
│
├── sql/
│   ├── install.php                            # Crea las 3 tablas al instalar
│   └── uninstall.php                          # Elimina las 3 tablas al desinstalar
│
├── views/
│   ├── css/
│   │   └── productbadges.css                  # Posicionamiento absoluto de badges sobre imagen
│   ├── js/
│   │   └── productbadges.js                   # Lógica de inyección DOM en página de producto
│   └── templates/
│       ├── admin/
│       │   └── product_badges_tab.tpl         # Tab de asignación en ficha de producto (BO)
│       └── front/
│           ├── badges_listing.tpl             # Inyección de badges en listado de categoría
│           └── badges_product.tpl             # Inyección de badges en ficha de producto
│
├── CLAUDE.md                                  # Guía de comportamiento para desarrollo con IA
├── DOCUMENTATION.md                           # Documentación técnica completa del módulo
├── IA.md                                      # Registro de uso de IA en el proyecto
├── PLAN.md                                    # Plan de implementación por fases
├── docker-compose.yml                         # Entorno de desarrollo local (PS 1.7.8.11 + MySQL 5.7)
└── README.md                                  # Este fichero
```

---

## Base de datos

El módulo crea tres tablas al instalar:

```
ps_product_badge          — Datos principales de cada badge (colores, posición, estado)
ps_product_badge_lang     — Textos traducidos por idioma
ps_product_badge_product  — Relación N:M entre badges y productos (con soporte multishop)
```

---

## Hooks utilizados

| Hook | Uso |
|---|---|
| `displayHeader` | Carga CSS (listado+producto) y JS (solo producto) |
| `displayProductPriceBlock` | Inyecta badges en el listado de categoría (type=weight) |
| `displayFooterProduct` | Inyecta badges en la ficha de producto |
| `displayAdminProductsExtra` | Renderiza el tab de asignación en edición de producto |
| `actionProductAdd` | Guarda asignaciones al crear producto (fallback) |
| `actionProductUpdate` | Guarda asignaciones al actualizar producto (fallback) |

---

## Entorno de desarrollo

PrestaShop disponible en `http://localhost:8080`  
Back Office en `http://localhost:8080/admin2026`
