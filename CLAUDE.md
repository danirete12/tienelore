# TieneLore — guía para Claude Code

Este archivo es para que **quien controle este proyecto desde su propia
sesión de Claude Code** (código, no Dani en persona) tenga todo el
contexto sin tener que preguntarlo. Si estás leyendo esto porque Dani te
ha pasado esta carpeta: bienvenido, esto es lo que necesitas saber.

## Qué es esto

**tienelore.com** es una web editorial estilo tabloide/zine (actualidad,
corazón, deporte, virales, cotidianidad...) para la que se ha construido
un **tema de WordPress propio** desde cero. Es un proyecto personal de
Dani, hermano de este repo de **PlotTwist** (plottwist.es, otra web
editorial suya, de temática cultural) — TieneLore reutiliza la misma
arquitectura editorial (categorías con color, tarjetas, relacionados por
tema) pero con identidad visual e idea propias: estética tabloide, ocho
categorías fijas, ticker de "virales", postal de "frase del día".

Diseño visual de referencia (mockup original aprobado): https://claude.ai/artifact/XnF8TSpjmhT98a9nvk6C8s

## Dónde está cada cosa

Este repo (`danirete12/tienelore`) es independiente del de PlotTwist —
se separó el 28/09/2026 para que se pueda operar desde otra sesión de
Claude Code sin dar acceso a nada de PlotTwist (API keys, datos de
negocio, etc. de ese otro proyecto).

```
README.md                    ← instrucciones de instalación y uso, para Dani (no técnicas)
CLAUDE.md                    ← este archivo
wp-theme/
  tienelore/                 ← el tema, código fuente real
    functions.php            ← setup del tema, categorías, favicon, portada manual, relacionados
    header.php / footer.php  ← masthead (negro, con ticker), pie
    home.php                  ← portada: hero + secciones por categoría
    single.php                ← plantilla de artículo individual
    archive.php               ← listado de una categoría
    author.php                ← página de autor
    404.php / page.php / index.php
    template-parts/          ← tarjeta de artículo (content-card.php) y variante en lista
    assets/
      css/main.css            ← todo el CSS (tokens de color en :root, arriba del todo)
      js/main.js               ← buscador overlay, menú móvil, barra de progreso de lectura
      img/favicon/             ← favicon de marca (ver abajo)
  tienelore-theme.zip         ← el mismo tema empaquetado, listo para subir desde el admin de WP
wp-plugin/
  tienelore-publish-api/
    tienelore-publish-api.php ← plugin de WP: API REST propia para publicar desde este repo
  tienelore-publish-api.zip   ← el mismo plugin empaquetado, listo para subir desde el admin
scripts/
  quick_publish.py            ← script cliente que habla con el plugin (ver más abajo)
config/
  settings.example.json       ← plantilla — copiar a settings.json y rellenar credenciales reales
```

**Si cambias algo dentro de `wp-theme/tienelore/`, tienes que regenerar
el zip** (o quien instale el tema seguirá viendo la versión vieja si usa
la Opción A del README, subir el .zip):

```bash
cd wp-theme
rm -f tienelore-theme.zip
zip -r tienelore-theme.zip tienelore
```

## Estado actual (a fecha de este archivo)

- El tema está terminado y probado a nivel de código, pero **no hay
  todavía un WordPress real en producción** — no existe hosting ni
  dominio conectado, hasta donde se sabe desde este lado. Si tú tienes
  ya un hosting para tienelore.com, el primer paso es instalar WordPress
  ahí y subir `tienelore-theme.zip` desde Apariencia → Temas → Añadir
  nuevo → Subir tema.
- **No hay redactores/autores dados de alta** — a diferencia de
  PlotTwist (que tiene 5 redactores con usuario propio y un pipeline que
  publica ~54 artículos/día con Claude + WordPress API), aquí no se ha
  tocado nada de eso. Publicar contenido, de momento, es 100% manual
  desde el escritorio de WordPress.
- **Ya existe el mecanismo para publicar desde Claude Code**
  (`wp-plugin/tienelore-publish-api/` + `scripts/quick_publish.py`),
  con el mismo patrón que usa PlotTwist (clave de API propia en una
  cabecera + Application Password de WordPress para el resto). Ver la
  sección "Publicar desde aquí (API propia)" más abajo. Lo que falta es
  instalarlo en un WordPress real y rellenar `config/settings.json` —
  el código ya está.
- **No hay automatización sin intervención** tipo la de PlotTwist
  (`content_generator.py` / `scheduler.py`, que generan Y publican
  solos por cron sin que nadie los dispare). De momento publicar es
  "llamar a `quick_publish.py` con un artículo ya escrito", a mano o
  desde una sesión de Claude — no hay generación automática de
  contenido ni cadencia programada. Si se quiere eso, el pipeline de
  referencia (con sus rutinas y prompts de redactor) está en
  `plottwist-web/scripts/` del repo de PlotTwist, pero habría que
  adaptarlo a las 8 categorías y a la temática de TieneLore desde cero.

## Las 8 categorías (fijas, no se tocan sin querer)

El tema las crea solo la primera vez que se activa (`tienelore_seed_categories()`
en functions.php), con slug, nombre y color ya decididos:

| Slug | Nombre | Color |
|---|---|---|
| `actualidad` | Actualidad | `#1345c4` (azul) |
| `corazon` | Corazón | `#e2144a` (rojo/rosa — también el color de marca) |
| `deporte` | Deporte | `#0f7a3d` (verde) |
| `paladar` | Paladar | `#c25f00` (naranja) |
| `virales` | Virales | `#8a1798` (morado) |
| `cosas-de-casa` | Cosas de casa | `#0b7a6f` (verde azulado) |
| `frase-del-dia` | Frase del día | `#96700a` (ocre) |
| `caja-de-pandora` | Caja de Pandora | `#2c2482` (índigo) — sin tratamiento especial, es una categoría más |

Si hace falta añadir o quitar una categoría, se edita el array que
devuelve `tienelore_categories_map()` en `functions.php` — de ahí sale
todo: los sellos de color, la navegación, las URLs limpias (`/corazon/`
en vez de `/category/corazon/`) y qué categorías siembra el tema al
activarse.

Dos categorías tienen comportamiento especial en la home/single:
- **Frase del día**: el título del post hace de cita grande (estilo
  postal), el cuerpo es el desarrollo debajo. Se ve así tanto en la
  sección de portada como en la página individual del post.
- **Virales**: alimenta el ticker "En llamas ahora mismo" de la home
  (marquesina con scroll continuo).

## Favicon

Ya tiene uno por defecto: el asterisco rojo del logo (`*`, el mismo que
aparece junto a "tieneLORE" en el masthead) sobre fondo negro, en
`assets/img/favicon/` (ico, varios png, svg, site.webmanifest). Lo sirve
`tienelore_favicon()` en `functions.php`, enganchado a `wp_head`.

**Para cambiarlo sin tocar código**: Escritorio → Apariencia →
Personalizar → Identidad del sitio → Icono del sitio, subir uno cuadrado
(512×512 recomendado). El icono nativo de WordPress tiene prioridad
siempre — en cuanto hay uno configurado ahí, `tienelore_favicon()` se
desactiva sola (mira `has_site_icon()` al principio de la función).

**Para regenerar el favicon de marca por defecto** (por ejemplo si cambia
el logo): es un asterisco de 6 puntas dibujado por código, no una fuente.
El patrón usado (Pillow/Python) está documentado en el propio comentario
de `functions.php`; si hace falta, se puede regenerar con un script corto
de PIL dibujando 3 líneas gruesas de extremo a extremo pasando por el
centro, rotadas 60° entre sí, con los extremos redondeados — colores
`#141210` (fondo, variable `--ink`) y `#e2144a` (trazo, variable
`--brand`), ambos ya definidos en `assets/css/main.css`.

## Portada manual (hero de la home)

Por defecto el hero de la home (la noticia grande de arriba del todo) es
siempre la última publicación — automatismo simple, sin que nadie tenga
que hacer nada. Para fijar una noticia concreta como portada (por
ejemplo, una que no es la más reciente pero es la importante del día):
al editar esa entrada hay una caja en la barra lateral, **"Portada de
TieneLore"**, con una casilla "Fijar esta noticia como portada". Al
marcarla y guardar, pasa a ser el hero. Solo puede haber una fijada a la
vez — marcar una desmarca automáticamente cualquier otra. Desmarcarla
devuelve la home al automatismo (última publicación).

Implementación: meta `_tienelore_featured` en `wp_postmeta`, gestionada
por `tienelore_featured_metabox_html()` / `tienelore_featured_save()` en
`functions.php`. `tienelore_get_featured_post()` es la función que
`home.php` consulta para decidir el hero — devuelve `null` si no hay
ninguna fijada (o si la fijada ha dejado de estar publicada), y en ese
caso `home.php` cae solo al último post. Hay también una columna
"Portada" (★) en el listado de Entradas del admin para ver de un vistazo
cuál está fijada sin tener que abrir cada una.

## Convenciones de diseño heredadas de PlotTwist (y dónde se ha decidido diferir)

Este tema arrancó como una réplica de la arquitectura de PlotTwist, así
que comparte cosas como: URLs de categoría limpias vía rewrite rules,
posts relacionados calculados por nombres propios/palabras significativas
del título (no al azar), tamaños de imagen predefinidos
(`tl-hero`/`tl-card`/`tl-thumb`). Pero hay diferencias deliberadas,
explicadas también en el README:

- **Tipografía con Google Fonts** (Anton para titulares, Work Sans para
  cuerpo, Space Mono para datos/fechas) — PlotTwist renuncia a toda
  fuente externa por velocidad ("mejor fea que lenta", publica 54
  artículos/día y cada milisegundo cuenta para SEO/Discover); aquí la
  tipografía es parte central de la identidad de marca ya aprobada, así
  que se ha mantenido. Es reversible si en algún momento se prioriza
  velocidad sobre fidelidad visual.
- **Sin AMP.** PlotTwist lo usa porque depende mucho de Google Discover a
  ese volumen de publicación. Aquí no se ha montado porque no se ha
  podido probar contra un WordPress real desde este entorno de
  desarrollo, y una implementación de AMP sin validar puede romper la web
  en vez de acelerarla — si TieneLore también va a depender mucho de
  Discover, mejor prepararlo aparte y probarlo bien antes de activarlo.
- **Caja de Pandora sin tratamiento especial** — a propósito, pese al
  nombre: es una categoría más, con su sello de color y su rejilla
  normal, nada de miniaturas borrosas ni candados.

## Publicar desde aquí (API propia, mismo patrón que PlotTwist)

`wp-plugin/tienelore-publish-api/` registra `/wp-json/tienelore/v1/`:
crear borrador (`POST /posts`), leer/editar/borrar (`GET|PUT|DELETE
/posts/{id}`) y publicar (`POST /posts/{id}/publish`), autenticado con
una clave compartida en la cabecera `X-Tienelore-Key` (comparación
`hash_equals`, se genera sola al activar el plugin, visible en Ajustes
→ TieneLore Publish API). Subir imágenes y asignar categoría/autor/tags
va por la REST API estándar de WordPress (`/wp-json/wp/v2/`) con una
Application Password — el plugin no reinventa eso.

`scripts/quick_publish.py` es el cliente Python (calcado del de
PlotTwist, mismo nombre de función `publish_with_images`, mismos
argumentos). Antes de poder publicar de verdad hace falta:
1. Instalar y activar el plugin en el WordPress real (`wp-plugin/tienelore-publish-api.zip`).
2. Copiar `config/settings.example.json` a `config/settings.json` y rellenar: `base_url`, la clave del plugin, usuario + Application Password, y los IDs reales de las 8 categorías (se consultan en `/wp-json/wp/v2/categories` una vez el tema está activo — el tema las crea solo).
3. Desde Python: `sys.path.insert(0, "scripts"); from quick_publish import publish_with_images`.

Si tocas el plugin (`wp-plugin/tienelore-publish-api/tienelore-publish-api.php`),
igual que con el tema: `php -l` para validar sintaxis, y regenerar
`wp-plugin/tienelore-publish-api.zip`:
```bash
cd wp-plugin
rm -f tienelore-publish-api.zip
zip -r tienelore-publish-api.zip tienelore-publish-api
```

Nota: TieneLore no tiene redactores fijos como los 5 de PlotTwist —
`wordpress_author_ids` en settings.json puede quedarse vacío sin
problema; los posts salen entonces a nombre del usuario de la
Application Password.

## Cómo trabajar aquí como Claude Code

- El tema es PHP puro sin build step (no hay npm/webpack) — se edita
  directamente y se prueba subiendo el tema (o la carpeta) a un
  WordPress real. No hay entorno de WordPress corriendo en este sandbox,
  así que los cambios de PHP se validan con `php -l archivo.php` (lint
  de sintaxis) antes de darlos por buenos, pero el comportamiento real
  solo se puede verificar en un WordPress de verdad.
- Antes de dar una tarea por terminada: `php -l` a cada archivo PHP que
  hayas tocado, y si has tocado algo dentro de `wp-theme/tienelore/`,
  regenerar `tienelore-theme.zip` (ver arriba) para que no quede
  desactualizado respecto al código fuente.
- Los colores y variables de diseño viven todos arriba del todo de
  `assets/css/main.css`, en el bloque `:root` (con una variante para
  `prefers-color-scheme: dark`). Cambia ahí antes que tocar valores
  sueltos por el resto del CSS.
- Si añades una funcionalidad nueva que Dani vaya a operar a mano desde
  el admin de WordPress (como la portada manual), documéntala también en
  `README.md` en lenguaje no técnico — ese archivo es el que lee Dani,
  este (`CLAUDE.md`) es el que te orienta a ti.
