# tienelore.com — tema de WordPress

Réplica de la estructura editorial de PlotTwist (WordPress + tema propio,
categorías con color, tarjetas, relacionados por tema) con la identidad
visual de TieneLore: estética tabloide/zine, con las 8 categorías de Dani.

Diseño visual de referencia: https://claude.ai/artifact/XnF8TSpjmhT98a9nvk6C8s

## Qué hay aquí

```
wp-theme/
  tienelore/            ← el tema, listo para copiar a wp-content/themes/
  tienelore-theme.zip   ← el mismo tema empaquetado, para subir desde el admin
```

## Cómo instalarlo

**Opción A — desde el admin de WordPress (más fácil):**
1. Escritorio → Apariencia → Temas → Añadir nuevo → Subir tema.
2. Selecciona `tienelore-theme.zip` y pulsa "Instalar ahora".
3. Actívalo.

**Opción B — por FTP/archivos:**
Copia la carpeta `wp-theme/tienelore/` entera dentro de `wp-content/themes/`
en el hosting, y actívalo desde Apariencia → Temas.

## Favicon

El tema trae un favicon de marca por defecto (el asterisco rojo del logo
sobre fondo negro, en `assets/img/favicon/`), así que la web nunca se
queda sin icono. Para cambiarlo por otro sin tocar código: Escritorio →
Apariencia → Personalizar → Identidad del sitio → Icono del sitio (subir
uno cuadrado, idealmente 512×512). En cuanto se sube uno ahí, WordPress
lo usa automáticamente y el favicon de marca deja de aplicarse.

## Cambiar la portada a mano

Por defecto la portada (la noticia grande de arriba del todo en la home)
es siempre la última publicación — igual que antes. Para fijar una
noticia concreta como portada aunque no sea la más reciente: al editar
esa entrada, en la columna derecha hay una caja **"Portada de
TieneLore"** con una casilla "Fijar esta noticia como portada". Al
marcarla y guardar/actualizar, esa entrada pasa a ser el hero de la home.
Solo puede haber una portada fijada a la vez: marcar una quita la marca
de cualquier otra automáticamente. Para volver al automatismo (última
publicación), basta con desmarcar la casilla.
En el listado de Entradas hay una columna "Portada" con una estrella (★)
en la fila de la que esté fijada ahora mismo, para verlo de un vistazo.

## Qué hace el tema solo, al activarlo

- Crea las 8 categorías (Actualidad, Corazón, Deporte, Paladar, Virales,
  Cosas de casa, Frase del día, Caja de Pandora) si no existen todavía,
  con sus colores ya asignados en el código.
- Configura las URLs limpias de categoría: `/corazon/` en vez de
  `/category/corazon/` (para todas las categorías).

No hace falta crear páginas ni menús a mano para que la web funcione:
la navegación de categorías sale directamente de esas 8 categorías fijas.
Si más adelante quieres un menú distinto, se puede montar uno normal en
Apariencia → Menús y asignarlo a la posición "Menú principal" — el tema
lo usará en cuanto exista.

## Cómo publicar contenido

Cada artículo es un post normal de WordPress:
- Asigna **una** de las 8 categorías (si asignas varias, el tema usa la
  primera para el color y el sello).
- Pon una imagen destacada (se recorta sola a las proporciones del tema).
- El extracto (excerpt) se usa como entradilla bajo el título del
  artículo — si no lo rellenas a mano, WordPress genera uno automático
  a partir del cuerpo.
- Los artículos de la categoría **Frase del día** se muestran distinto
  a propósito: el título hace de cita grande (estilo postal), y el
  cuerpo del post es el desarrollo debajo. Para el resto de categorías
  — Caja de Pandora incluida — el artículo se ve igual que cualquier
  otro, sin tratamiento especial.

Si el pipeline de automatización de PlotTwist
(`projects/plottwist-web/scripts/quick_publish.py`) se adapta para
tienelore.com más adelante, solo hace falta apuntarlo a la URL de la
API REST de ese WordPress y a estas mismas categorías.

## Decisiones que he tomado por mi cuenta (revísalas)

- **Tipografía con Google Fonts.** PlotTwist renuncia a toda fuente
  externa por velocidad ("mejor fea que lenta"). Aquí he mantenido las
  tres fuentes de la identidad de marca que ya aprobaste (Anton, Work
  Sans, Space Mono) porque son parte central del diseño tabloide — pero
  es una decisión reversible: si prefieres velocidad máxima como en
  PlotTwist, se puede quitar y pasar a fuente de sistema en dos minutos.
- **Sin AMP.** PlotTwist usa AMP (páginas ultrarrápidas para SEO/Discover)
  porque publica 54 artículos al día y ese es un factor de peso para
  Google. No lo he montado aquí porque no está construido para probarse
  en un WordPress real desde este entorno, y una implementación de AMP
  sin validar podría romper la web en vez de acelerarla. Si tienelore.com
  también va a depender mucho de Discover, dímelo y lo preparamos aparte,
  ya probado.
- **URLs de categoría sin páginas duplicadas.** PlotTwist combina páginas
  de WordPress con el mismo slug que la categoría más una regla de
  reescritura. Aquí he simplificado a solo la regla de reescritura: el
  resultado es la misma URL limpia (`/corazon/`) pero sin tener que crear
  8 páginas vacías a mano.
- **Caja de Pandora sin tratamiento especial**, tal y como pediste: es
  una categoría más, con su sello de color morado y sus artículos en la
  rejilla normal — nada de miniaturas borrosas ni candados.

## Lo que falta para que esté en producción

- Un WordPress real (hosting + dominio tienelore.com) donde instalar
  este tema — no existe todavía, hasta donde yo sé.
- Redactores/autores dados de alta en ese WordPress (aquí no he tocado
  nada de eso, a diferencia de PlotTwist que tiene 5 redactores con
  usuario propio).
- Si quieres automatizar la publicación como en PlotTwist, adaptar
  `content_generator.py` / `publisher.py` / `scheduler.py` a estas 8
  categorías y a la temática de tienelore.com (nada de eso está hecho
  todavía — esto es solo el tema visual y su estructura).
