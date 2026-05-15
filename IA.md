# Uso de IA en este proyecto

## 1. Herramientas utilizadas

| Herramienta | Versión / Modelo | Modo de uso | Aprox. % del trabajo |
|---|---|---|---|
| Claude Code CLI| 4.7 Opus | terminal | 70% |
| Criterio propio | — | — | 30% |

## 2. Configuración del proyecto

### CLAUDE.md / AGENTS.md

El fichero CLAUDE.md está en la raíz del repositorio. Funciona como una guía de comportamiento, le indica a la IA cómo debe trabajar en este proyecto antes de que empiece a generar cualquier cosa.

Tiene cinco directivas principales:

**1. Pensar antes de codificar.** La IA debe exponer sus suposiciones antes de implementar. Si algo es ambiguo, preguntar y no elegir en silencio. Esto evitó varias decisiones que habrían requerido reescrituras.

**2. Simplicidad primero.** Código mínimo que resuelve el problema. Sin abstracciones especulativas, sin features no pedidas, sin "por si acaso". Si el resultado son 200 líneas y podría ser 50, reescribirlo.

**3. Cambios quirúrgicos.** Tocar solo lo necesario. No "mejorar" código adyacente que no es parte de la tarea. Cada línea modificada debe tener una razón directa en la petición del usuario.

**4. Ejecución orientada a objetivos.** Para tareas multi-paso, definir criterios de verificación antes de empezar. Esto se tradujo en el plan por fases con checklist de verificación al final de cada una.

**5. Estándares de PrestaShop.** Toda decisión técnica debe respetar las convenciones de PS: usar sus helpers, su capa de BD, su sistema de hooks. Consultar la documentación oficial antes de proponer una solución propia. Esta directiva fue la que redujo más las alucinaciones en APIs de PS.

## 3. Skills personalizadas

No usé skills personalizadas porque el CLAUDE.md ya cubría el comportamiento necesario para todo el proyecto. Una skill habría tenido sentido si necesitara repetir un flujo complejo en múltiples sesiones. Por ejemplo, un skill de "nuevo módulo PS" que arranque siempre con plan, estructura de ficheros y checklist de hooks. Para este proyecto, el CLAUDE.md fue suficiente y añadir una skill habría sido sobreingeniería.

## 4. Slash commands personalizados

/plan Con este slash propio de Claude, ejecutamos el propmpt inicial con el contxeto del proyecto para crear un plan por fases ya conectado entre si y bien estrcuturado para que la implementación pudiera ser paso a paso, sin alucionacion y pudiendo aplicar nuestro criterio personal.

## 5. Sub-agentes invocados

No invoqué sub-agentes. En este proyecto, cada decisión técnica dependía de decisiones anteriores, qué hooks usamos y por qué. Para trabajo acumulativo como este, mantener todo en la conversación principal fue más eficiente.

Para proyectos futuros de mayor escala, tendría sentido un agente especializado en creación de módulos PS desde cero, con sus propios estándares y hooks preconfigurados.

## 6. MCPs (Model Context Protocol)

Vi disponible el MCP de Prestashop para qeu nuestro CLI pudiese navegar por nuestra tienda, pero preferí probar todo a mano para tener control y critereio real.

## 7. Prompts importantes

### PROMT — Módulo PrestaShop: productbadges 

> "Desarrolla un módulo de PrestaShop 1.7.8.x llamado `productbadges` siguiendo estrictamente el CLAUDE.md del proyecto y la documentación oficial de PrestaShop"

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

### PROMPT — Cuestionamiento de decisión técnica antes de implementar

> "¿Estás seguro en esta decisión? Un update ya tiene la información anterior... ¿Por qué DELETE + INSERT en saveBadgesForProduct y no UPDATE?"
> "Razona y evalua la siguiente fase según los criterios establecidos acorde a (modificaiones realizadas anteriormente)"

Entre fases, los prompts eran cortos y directos "Razona y evalua la siguiente fase según los criterios establecidos acorde a (modificaiones realizadas anteriormente) ". El valor no estaba en el prompt sino en haber definido bien el plan incial.

Este tipo de propmts , antes de aceptar una decisión técnica propuesta por la IA, pedí que la justificara. La respuesta me aclaró que la tabla de relación N:M no tiene un "valor a actualizar" — solo existe o no existe la relación — y que DELETE+INSERT evita calcular diff de qué añadir y qué quitar. Con ese razonamiento decidí aceptarlo. Sin este prompt habría aplicado el patrón sin entenderlo.


### PROMPT — Auditoría de seguridad y buenas prácticas

> "Necesito saber si el código cumple con los requisitos de sanitización y escapado correcto de inputs tanto en BD como en plantillas. Validación server-side, no solo client-side. Uso correcto de las APIs propias de PrestaShop: HelperForm, HelperList, ObjectModel, sistema de hooks, $this->l() para textos, _DB_PREFIX_, Db::getInstance() con pSQL() o consultas preparadas. Carga eficiente de assets. Estructura limpia: lógica de back office en su propio ModuleAdminController."

Lanzado al terminar la implementación, antes de dar el módulo por completado. Definí los criterios de auditoría yo mismo para que la IA no auditara "lo que le pareciera" sino lo que realmente importa en un módulo. Esto sacó 4 bugs reales que no habían aparecido durante el desarrollo.

## 8. Errores de la IA que detecté

---

### Error 1 — Identificador de tabla mal derivado en AdminController

- **Qué generó la IA (mal):** El controlador `AdminProductBadgesController` no declaraba `$this->identifier` antes de llamar a `parent::__construct()`.
- **Por qué estaba mal:** PrestaShop deriva automáticamente el identificador como `'id_' . $this->table`, resultando en `id_product_badge`. Esa columna no existe — la clave primaria real es `id_badge`. Lanzaba el error SQL `Unknown column 'b.id_product_badge' in 'on clause'` al abrir el listado de badges.
- **Cómo lo corregiste:** Añadir explícitamente `$this->identifier = 'id_badge'` antes de `parent::__construct()`. El orden importa: si se declara después, PS ya ha tomado el valor incorrecto.

---

### Error 2 — Clase ProductBadge no disponible en el controlador

- **Qué generó la IA (mal):** `AdminProductBadgesController.php` generado sin `require_once` del fichero principal del módulo.
- **Por qué estaba mal:** El controlador necesitaba `ProductBadge` (el ObjectModel), definido en `productbadges.php`. Sin la carga explícita, PS lanzaba `ClassNotFoundException: ProductBadge` al intentar cualquier operación CRUD.
- **Cómo lo corregiste:** Añadir `require_once _PS_MODULE_DIR_ . 'productbadges/productbadges.php'` al inicio del controlador.

---

### Error 3 — Hooks de front inventados o no soportados en Classic theme 1.7.8.11

- **Qué generó la IA (mal):** Propuso usar `displayProductListItem` para el listado y `displayProduct` para la ficha de producto.
- **Por qué estaba mal:** El tema Classic de PS 1.7.8.11 no llama a `displayProductListItem` en sus templates de miniatura. Y el hook `displayProduct` directamente no existe en esta versión. Las badges nunca aparecían en front, sin error visible.
- **Cómo lo corregiste:** Identificar manualmente qué hooks dispara realmente el tema Classic inspeccionando sus templates. La solución fue `displayProductPriceBlock` con filtro `type=weight` para el listado y `displayFooterProduct` para la ficha, ambos con inyección JavaScript al DOM.

---

### Error 4 — Asignación de badges no se guardaba desde el tab de producto

- **Qué generó la IA (mal):** Los checkboxes del tab de asignación esperaban guardarse a través del formulario principal del producto vía `hookActionProductUpdate`.
- **Por qué estaba mal:** En PS 1.7, el contenido de `displayAdminProductsExtra` está dentro del `<form>` principal visualmente, pero la IA no contempló que el flujo de guardado necesitaba un mecanismo propio e independiente para asegurar la persistencia en todas las situaciones.
- **Cómo lo corregiste:** Implementar guardado por AJAX con `onchange` en los checkboxes y un endpoint `ajaxProcessSaveBadges()` en el controlador. El tab guarda de forma autónoma sin depender del botón guardar del producto.

---

### Error 5 — AJAX URL sin `ajax=1`

- **Qué generó la IA (mal):** La URL del endpoint AJAX era `getAdminLink('AdminProductBadges') . '&action=saveBadges'`, sin `&ajax=1`.
- **Por qué estaba mal:** PS `AdminController` enruta a `ajaxProcess{Action}()` solo cuando `ajax=1` está presente. Sin él, busca `processSaveBadges()` que no existe y devuelve HTML. El `fetch()` recibía HTML silenciosamente y lo descartaba. El guardado no ocurría en absoluto, sin ningún error visible.
- **Cómo lo corregiste:** Añadir `&ajax=1` a la URL del endpoint.

---

### Error 6 — Checkboxes pre-marcados incorrectamente (array_flip + Smarty)

- **Qué generó la IA (mal):** Para determinar si una badge estaba asignada, usó `array_flip($assigned_ids)` en PHP y luego `{if isset($flipped[$badge.id_badge])}` en Smarty.
- **Por qué estaba mal:** PHP convierte las claves numéricas string (`"1"`, `"2"`) a enteros (`1`, `2`) al hacer `array_flip`. Smarty buscaba `$flipped['1']` (string) contra clave int `1` fallaba siempre. Los checkboxes nunca aparecían marcados aunque los datos estuvieran correctos en base de datos.
- **Cómo lo corregiste:** Calcular el booleano en PHP con `in_array($badge['id_badge'], $assigned_ids)` y pasarlo como `$badge['is_assigned']` a Smarty. Booleano limpio, sin conversiones de tipos en template.

---

### Error 7 — Assets CSS/JS cargando en todas las páginas del front

- **Qué generó la IA (mal):** `hookDisplayHeader` añadía CSS y JS en todas las páginas front sin filtro.
- **Por qué estaba mal:** Las badges solo son relevantes en páginas de producto y listados. Cargar assets en homepage, CMS, login o contacto era innecesario
- **Cómo lo corregiste:** Filtrar por `$this->context->controller->php_self` y cargar CSS solo en páginas donde hay productos (`category`, `product`, `search`, etc.) y JS solo en página de producto.

---

### Error 9 — badge_product_ids sin validación contra base de datos

- **Qué generó la IA (mal):** `saveBadgesForProduct()` aceptaba cualquier array de IDs, los casteaba a `int` y los insertaba directamente.
- **Por qué estaba mal:** Aunque el casteo previene SQL injection, no valida que esos IDs existan en `ps_product_badge`. Una request manipulada podría insertar IDs inexistentes o de badges inactivas, creando registros huérfanos en `ps_product_badge_product`.
- **Cómo lo corregiste:** Añadir `filterValidBadgeIds()` que filtra el array contra la base de datos con `WHERE id_badge IN (...) AND active = 1`. Solo IDs existentes y activos llegan al INSERT.

## 9. Partes que NO usé IA

### Verificación de cada fase
Cada fase del plan la probé manualmente en el navegador y en el back office de PrestaShop. El criterio de cada fase siempre fue mío. Instalé y desinstalé el módulo varias veces para verificar que las tablas se creaban y eliminaban correctamente, que los hooks quedaban registrados y que no había residuos en base de datos.

### Diagnóstico de base de datos

La conexión a MySQL dentro del contenedor Docker, la inspección de tablas (`SHOW TABLES`, `SELECT * FROM ps_hook_module`) y la verificación de que los datos se persistían correctamente la hice yo directamente por consola

### Instalación del segundo idioma en PrestaShop

Detecté que el formulario multiidioma de badges no mostraba tabs de idioma porque el entorno Docker solo tenía inglés instalado. Fui al Back Office → Internacional → Idiomas e instalé el español manualmente.

### Criterio final sobre cada decisión técnica

En varias ocasiones la IA presentó opciones (p.ej. DELETE+INSERT vs UPDATE en la tabla de relación, separar `ProductBadge` en fichero propio vs mismo fichero que el módulo). La decisión sobre qué opción tomar, con qué argumentos fue siempre mía. La IA explicaba las implicaciones pero no elegía.

## 10. Reflexión final

El punto de partida pidiendo un plan por fases antes de escribir una línea de código obligó a la IA a razonar la arquitectura completa antes de ejecutar. Eso evitó que generara código sin entender el contexto de PrestaShop 1.7 

El CLAUDE.md funcionó como cña técnico. Al indicar que toda decisión debía respetar la documentación oficial de PS y seguir sus convenciones. Cuando sí se equivocó , la causa fue desconocimiento del comportamiento real del tema Classic, esto fue otro punto con el que no contaba yo ni la IA pero a partir de ahí empezamos a tomar decisiones en base a ello.

La dinámica de "explícame por qué antes de aplicarlo" fue muy útil. En cada decisión técnica relevante (DELETE+INSERT, AJAX vs formulario, dos clases en un fichero) la IA expuso el razonamiento. Eso me permitió detectar cuándo el argumento era sólido y cuándo era una justificación genérica que no aplicaba a este caso concreto. La IA acelera, pero no reemplaza el criterio. Sirve para razonar en voz alta, generar estructura rápida y documentar decisiones sobre la marcha. No sirve para saber qué hace realmente tu entorno hasta que lo pruebas

Los errores silenciosos fueron los más peligrosos. El bug del ajax=1, el bug del array_flip ninguno lanzó un error visible. Sin verificación manual fase a fase esta malas prácticas fuesen llegado a produccion en un tal caso.

Usada con criterio previo, estructura clara y verificación constante, la IA acelera significativamente el desarrollo de módulos PS. Sin esas tres condiciones, genera código plausible pero frágil. El valor real no está en que escriba el código — está en que permite razonar en voz alta sobre la arquitectura, identificar tradeoffs y documentar decisiones técnicas de forma inmediata.

Uno de los fallos más importantes que tuce fue el no actualizar el plan tras cada una de las fases. En una de las ultimas fases me dí cuenta que estaba implenetando algo con lo que ya había liadiado. No tuve gran problema porque fase a fase iba haciendo razonamiento de todo lo implementado y lo próximo a implementar.

