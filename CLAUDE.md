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

```
tienelore-web/
  README.md                    ← instrucciones de instalación y uso, para Dani (no técnicas)
  CLAUDE.md                    ← este archivo
  wp-theme/
    tienelore/                 ← el tema, código fuente real
      functions.php            ← setup del tema, categorías, favicon, portada manual, relacionados
      header.php / footer.php  ← masthead (negro, con ticker), pie
      home.php                 ← portada: hero + secciones por categoría
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
- **No hay automatización de publicación.** Si en algún momento se
  quiere automatizar (generar artículos con IA y publicarlos solos, como
  hace PlotTwist), el pipeline de referencia está en
  `../plottwist-web/scripts/` (`content_generator.py`, `publisher.py`,
  `scheduler.py`) — pero habría que adaptarlo a las 8 categorías y a la
  temática de TieneLore desde cero, no es plug-and-play.

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
