# Uso de IA en este proyecto

## 1. Herramientas utilizadas

| Herramienta | Versión / Modelo | Modo de uso | Aprox. % del trabajo |
|---|---|---|---|
| Claude Code CLI | claude-sonnet-4-6 | terminal | 70% |

El 30% restante corresponde a revisión manual, testing en el entorno real y toma de decisiones técnicas.

---

## 2. Configuración del proyecto

### CLAUDE.md

El fichero `CLAUDE.md` está en la raíz del repositorio. Claude Code lo carga automáticamente en cada sesión. Funciona como contrato de comportamiento: le indica a la IA cómo debe trabajar antes de generar cualquier cosa.

Tiene cinco directivas:

**1. Pensar antes de codificar.** La IA debe exponer sus suposiciones antes de implementar. Si algo es ambiguo, preguntar y no elegir en silencio. Esto evitó varias decisiones que habrían requerido reescrituras.

**2. Simplicidad primero.** Código mínimo que resuelve el problema. Sin abstracciones especulativas, sin features no pedidas. Si el resultado son 200 líneas y podría ser 50, reescribirlo.

**3. Cambios quirúrgicos.** Tocar solo lo necesario. Cada línea modificada debe tener una razón directa en la petición del usuario.

**4. Ejecución orientada a objetivos.** Para tareas multi-paso, definir criterios de verificación antes de empezar. Esto se tradujo en el plan por fases con checklist al final de cada una.

**5. Estándares de PrestaShop.** Toda decisión técnica debe respetar las convenciones de PS: helpers, capa de BD, sistema de hooks. Consultar la documentación oficial antes de proponer una solución propia. Esta directiva fue la que más redujo las alucinaciones en APIs de PS.

---

## 3. Skills personalizadas

https://github.com/multica-ai/andrej-karpathy-skills

Basamos nuestra skill en el fichero .md que ofrece el repositorio. Este mejora el comportamiento de Claude Code con los primeros 4 puntos comentados anteriormete. 

---

## 4. Slash commands personalizados

`/plan`  Usado al inicio del proyecto para generar el plan de implementación por fases antes de escribir ninguna línea de código. El plan quedó estructurado con estimaciones de tiempo, orden de creación de ficheros, hooks justificados y criterios de verificación por fase. Eso permitió avanzar de forma ordenada y con criterio propio en cada paso.

---

## 5. Sub-agentes invocados

No invoqué sub-agentes. En este proyecto cada decisión técnica dependía de las anteriores, qué hooks usamos, por qué, qué errores ya habíamos descartado. Un sub-agente arranca sin ese contexto acumulado, lo que en un proyecto lineal como este sería un coste sin beneficio claro.

Para proyectos de mayor escala tendría sentido un agente especializado en creación de módulos PS, con sus propios estándares preconfigurados. En este caso no era necesario.

---

## 6. MCPs (Model Context Protocol)

No usé MCPs. Revisé las opciones disponibles pero ninguna aportaba valor real sobre el acceso directo al entorno Docker. Preferí trabajar con consola MySQL y navegador directamente para tener control real sobre lo que pasaba en cada fase.

---

## 7. Prompts importantes

### Prompt inicial, Especificación del módulo

> "/plan => Como experto en el ecosistema PrestaShop y desarrollo de funcionalidades personalizadas, necesito generar un plan de desarrollo de un módulo de PrestaShop 1.7.8.x llamado `productbadges` siguiendo estrictamente el CLAUDE.md del proyecto y la documentación oficial de PrestaShop. A continuación te indico los requerimientos:"

Funcionalidad requerida

1. Gestión de etiquetas (back office)
- CRUD completo desde un AdminController
- Campos por etiqueta: texto (multilenguaje), color de fondo, color de texto, 
  posición (top-left | top-right), activo/inactivo
- Relación muchos a muchos con productos (tabla intermedia)

2. Asignación a productos
- Panel en la ficha del producto (back office) para asignar/desasignar etiquetas
- Hook: `displayAdminProductsExtra` o equivalente en PS 1.7.8.x/11 

3. Renderizado en frontend
- Sobre la imagen del producto en:
  - Listado de categoría (`displayProductListItem` o equivalente)
  - Ficha de producto (`displayProduct`)
  - Home/búsqueda solo si el tema activo soporta los hooks
- CSS posicionado (absolute sobre la imagen), respetando la posición configurada

4. Configuración global del módulo
- Activar/desactivar global
- Mostrar en listados (sí/no)
- Mostrar en ficha de producto (sí/no)
- Número máximo de badges visibles por producto

5. Multilenguaje
- El campo texto de la badge usa tablas `_lang` estándar de PrestaShop
- Soporte mínimo: es, en
- Usar el sistema de traducción nativo (no arrays hardcodeados)

6. Multitienda
- El módulo no debe romper en instalación multitienda
- Respetar el contexto de tienda activo en back office
- No es obligatorio que las badges difieran por tienda

Estructura de archivos esperada

productbadges/
│
├── productbadges.php                          
│
├── controllers/
│   └── admin/
│       └── AdminProductBadgesController.php   
│
├── sql/
│   ├── install.php                            
│   └── uninstall.php                          
│
├── views/
│   ├── css/
│   │   └── productbadges.css                  
│   ├── js/
│   │   └── productbadges.js                   
│   └── templates/
│       ├── admin/
│       │   └── product_badges_tab.tpl         
│       └── front/
│           ├── badges_listing.tpl             
│           └── badges_product.tpl             
│
├── CLAUDE.md                                  
├── DOCUMENTATION.md                           
├── IA.md                                      
├── PLAN.md                                    
├── docker-compose.yml                         
└── README.md          

## Tarea
Genera un plan de implementación para 3-4h de desarrollo con:
- Fases ordenadas y con estimación de tiempo cada una
- Orden de creación de archivos (SQL primero, luego clase principal, 
  luego controllers, luego vistas)
- Lista de hooks a registrar con justificación de cada uno
- Criterios de verificación por fase (cómo saber que funciona antes de continuar)
- Alertas sobre los puntos más propensos a errores en PS8/9 
  (multilenguaje, grid moderno, hooks de producto)

No escribas código todavía. Solo el plan.

### PROMPT, Cuestionamiento de decisión técnica antes de implementar

> "¿Estás seguro en esta decisión? Necesito asegurar la integridad de los datos y porque no hacemos un UPDATE el cual ya tiene la información anterior... ¿Por qué DELETE + INSERT en saveBadgesForProduct y no UPDATE?"

Antes de aceptar una decisión técnica propuesta por la IA, pedí que la justificara. La respuesta aclaró que la tabla de relación N:M no tiene un "valor a actualizar", solo existe o no existe la relación, y que DELETE+INSERT evita tener que calcular qué añadir y qué quitar. Con ese razonamiento lo acepté. Sin este prompt habría aplicado el patrón sin entenderlo.

Entre fases los prompts eran cortos. El valor no estaba en el prompt, estaba en haber definido bien el plan inicial.

---

### Prompt, Auditoría de seguridad y buenas prácticas

> "Vamos a proceder a realzar una pequeña auditoría del proyecto y saber si cumple con los requisitos de sanitización y escapado correcto de inputs tanto en BD como en plantillas. Validación server-side, no solo client-side. Uso correcto de las APIs propias de PrestaShop: HelperForm, HelperList, ObjectModel, sistema de hooks, `$this->l()`, `_DB_PREFIX_`, `Db::getInstance()` con `pSQL()`. Carga eficiente de assets. Estructura limpia: lógica de back office en su propio ModuleAdminController."

Lanzado al terminar la implementación, antes de dar el módulo por completado. Definimos los criterios del entregable final. Esto sacó bugs reales que no habían aparecido durante el desarrollo.

---

## 8. Errores de la IA que detecté

### Error 1, Identificador de tabla mal derivado en AdminController

- **Qué generó la IA (mal):** El controlador `AdminProductBadgesController` no declaraba `$this->identifier` antes de llamar a `parent::__construct()`.
- **Por qué estaba mal:** PrestaShop deriva automáticamente el identificador como `'id_' . $this->table`, resultando en `id_product_badge`. Esa columna no existe, la clave primaria real es `id_badge`. Lanzaba el error SQL `Unknown column 'b.id_product_badge' in 'on clause'` al abrir el listado de badges.
- **Cómo lo corregiste:** Declarar explícitamente `$this->identifier = 'id_badge'` antes de `parent::__construct()`. El orden importa: si se declara después, PS ya ha tomado el valor incorrecto.

---

### Error 2, Clase ProductBadge no disponible en el controlador

- **Qué generó la IA (mal):** `AdminProductBadgesController.php` generado sin `require_once` del fichero principal del módulo.
- **Cómo lo corregiste:** Añadir `require_once _PS_MODULE_DIR_ . 'productbadges/productbadges.php'` al inicio del controlador.

---

### Error 3, Hooks de front que no disparaban en Classic theme 1.7.8.11

- **Qué generó la IA (mal):** Propuso usar `displayProductListItem` para el listado y `displayProduct` para la ficha de producto.
- **Por qué estaba mal:** El tema Classic de PS 1.7.8.11 no llama a `displayProductListItem` en sus templates de miniatura. Y `displayProduct` directamente no existe en esta versión. Las badges nunca aparecían en front, sin ningún error visible.
- **Cómo lo corregiste:** Verificar manualmente qué hooks dispara realmente el tema Classic inspeccionando sus templates. La solución fue `displayProductPriceBlock` con filtro `type=weight` para el listado y `displayFooterProduct` para la ficha, ambos con inyección JavaScript al DOM.

---

### Error 4, Asignación de badges no se guardaba desde el tab de producto

- **Qué generó la IA (mal):** Los checkboxes del tab esperaban guardarse a través del formulario principal del producto vía `hookActionProductUpdate`.
- **Por qué estaba mal:** La IA no contempló que necesitaba un mecanismo de guardado propio e independiente del botón guardar del producto para garantizar la persistencia en todos los casos.
- **Cómo lo corregiste:** Implementar guardado por AJAX con `onchange` en los checkboxes y un endpoint `ajaxProcessSaveBadges()` en el controlador.

---

### Error 5, AJAX URL sin `ajax=1`

- **Qué generó la IA (mal):** La URL del endpoint AJAX era `getAdminLink('AdminProductBadges') . '&action=saveBadges'`, sin `&ajax=1`.
- **Por qué estaba mal:** PS `AdminController` enruta a `ajaxProcess{Action}()` solo cuando `ajax=1` está presente. Sin él, busca `processSaveBadges()`, que no existe, y devuelve HTML completo. El `fetch()` recibía HTML silenciosamente y lo descartaba. El guardado no ocurría en absoluto, sin ningún error visible.
- **Cómo lo corregiste:** Añadir `&ajax=1` a la URL del endpoint.

---

### Error 6, Checkboxes pre-marcados incorrectamente (array_flip + Smarty)

- **Qué generó la IA (mal):** Para determinar si una badge estaba asignada, usó `array_flip($assigned_ids)` en PHP y luego `{if isset($flipped[$badge.id_badge])}` en Smarty.
- **Por qué estaba mal:** PHP convierte claves numéricas string (`"1"`, `"2"`) a enteros (`1`, `2`) al hacer `array_flip`. Smarty buscaba `$flipped['1']` (string) contra clave int `1`, fallaba siempre. Los checkboxes nunca aparecían marcados aunque los datos estuvieran correctos en base de datos.
- **Cómo lo corregiste:** Calcular el booleano en PHP con `in_array($badge['id_badge'], $assigned_ids)` y pasarlo como `$badge['is_assigned']` a Smarty. Sin conversiones de tipos en el template.

---

### Error 7, Assets CSS/JS cargando en todas las páginas del front

- **Qué generó la IA (mal):** `hookDisplayHeader` añadía CSS y JS en todas las páginas front sin filtro.
- **Por qué estaba mal:** Las badges solo son relevantes en páginas de producto y listados. Cargar assets en homepage, CMS o login era innecesario.
- **Cómo lo corregiste:** Filtrar por `$this->context->controller->php_self` , CSS solo en páginas con productos, JS solo en página de producto.

---

### Error 8, Refactorización del template de listado introdujo una regresión

- **Qué generó la IA (mal):** Al reorganizar el código para seguir la estructura `views/js/`, refactorizó `badges_listing.tpl` de un IIFE autocontenido a un sistema de cola que depende de `productbadges.js`.
- **Por qué estaba mal:** Introdujo una dependencia implícita: si el JS externo no cargaba por cualquier motivo, las badges del listado desaparecían sin error. Una refactorización "más limpia" en papel rompió algo que funcionaba.
- **Cómo lo corregiste:** Revertir `badges_listing.tpl` al IIFE autocontenido original. El listado no necesita JS externo, el script inline es suficiente y no depende de nada.

---

### Error 9, badge_product_ids sin validación contra base de datos

- **Qué generó la IA (mal):** `saveBadgesForProduct()` aceptaba cualquier array de IDs, los casteaba a `int` y los insertaba directamente.
- **Por qué estaba mal:** El casteo previene SQL injection pero no valida que esos IDs existan en `ps_product_badge`. Una request manipulada podría insertar IDs de badges inexistentes o inactivas, creando registros huérfanos.
- **Cómo lo corregiste:** Añadir `filterValidBadgeIds()` que filtra contra la base de datos con `WHERE id_badge IN (...) AND active = 1`. Solo IDs existentes y activos llegan al INSERT.

---

## 9. Partes que NO usé IA

No escribí código directamente. Mi rol fue orquestar y revisar, y eso fue una decisión consciente, no una omisión.

Lo que hice yo y la IA no podía hacer:

**Definir qué auditar.** La IA auditó lo que yo le pedí con criterios concretos: escapado por contexto, validación server-side, APIs de PS, scoping de assets, separación de responsabilidades. Sin esa lista habría auditado "lo que le pareciera".

**Tomar todas las decisiones de arquitectura.** En cada bifurcación, DELETE+INSERT vs UPDATE, dos clases en un fichero vs ficheros separados, AJAX vs formulario, IIFE vs cola JS, la IA presentó opciones y yo elegí con argumento. Ninguna decisión la tomó la IA sola.

**Cuestionar antes de aceptar.** Cada propuesta técnica relevante pasó por un "¿estás seguro?" antes de aplicarse. Ese filtro detectó decisiones que parecían correctas en papel pero no encajaban en este contexto.

**Diagnosticar problemas de entorno.** La IA no tiene acceso al Docker ni al navegador. Detectar que el formulario multiidioma no mostraba tabs porque el entorno solo tenía un idioma instalado, o que ciertos hooks no disparaban en el tema Classic inspeccionando sus templates manualmente.

**Verificar que cada fase funcionaba.** El criterio de "esto está hecho" siempre fue mío, no de la IA.

---

## 10. Reflexión final

Lo primero que marcó la diferencia fue pedir un plan antes de escribir una sola línea de código. Un módulo de PrestaShop tiene muchas partes conectadas entre sí y sin esa visión global previa es muy fácil que la IA empiece a generar código que funciona de forma aislada pero no encaja con el resto. El plan por fases, con verificación al final de cada una, evitó alucinaciones en el camino y sobreingeniería donde no la necesitábamos.

El CLAUDE.md funcionó como contrato técnico. Al declarar que toda decisión debía respetar la documentación oficial de PS y sus convenciones, la IA no inventó soluciones propias cuando existía un patrón estándar. Cuando sí se equivocó, como con los hooks del front, la causa no fue ignorar la documentación, sino que la documentación no te dice qué hooks llama realmente el tema Classic en una versión concreta. Eso solo lo sabes probando.

Pedir que explicara el porqué de cada decisión antes de aplicarla fue el hábito más útil de todo el proceso. En decisiones como DELETE+INSERT vs UPDATE, o AJAX vs formulario, la IA razonaba los tradeoffs y yo decidía si el argumento tenía sentido en este contexto concreto o era una respuesta genérica.

Los errores más peligrosos no fueron los que lanzaban excepción, esos se ven de inmediato. Lo peligroso fueron los errores silenciosos: el `ajax=1` que faltaba, el `array_flip` que rompía la comparación con Smarty, la regresión del listado tras refactorizar. Código que compila, se ejecuta y no hace nada. Sin verificación manual después de cada fase, habrían llegado a producción sin que nadie lo detectara.

Uno de los errores que cometí fue no actualizar el plan tras cada fase. En una de las últimas me di cuenta de que estaba implementando algo con lo que ya había lidiado antes. No fue un problema grave porque iba razonando en cada paso lo que se había hecho y lo que venía, pero en un proyecto más grande habría sido un lío.

En resumen, la IA acelera, pero no reemplaza el criterio. Sirve para razonar en voz alta, generar estructura rápida y documentar decisiones sobre la marcha. No sirve para saber qué hace realmente tu entorno hasta que lo pruebas.
