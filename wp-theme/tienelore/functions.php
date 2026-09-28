<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/* ─── Setup ─────────────────────────────────────────────────────────── */
function tienelore_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', [ 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption' ] );
    add_theme_support( 'automatic-feed-links' );
    add_theme_support( 'customize-selective-refresh-widgets' );

    add_image_size( 'tl-hero',  1600, 900, [ 'center', 'top' ] );
    add_image_size( 'tl-card',  800,  533, [ 'center', 'top' ] );
    add_image_size( 'tl-thumb', 320,  213, [ 'center', 'top' ] );

    register_nav_menus( [
        'primary' => __( 'Menú principal', 'tienelore' ),
    ] );
}
add_action( 'after_setup_theme', 'tienelore_setup' );

/* ─── Enqueue ────────────────────────────────────────────────────────── */
function tienelore_scripts() {
    // A diferencia de PlotTwist (que renuncia a toda fuente externa por
    // rendimiento), aquí la tipografía SÍ es parte central de la
    // identidad de marca aprobada por Dani (Anton para titulares,
    // Work Sans para cuerpo, Space Mono para datos/fechas) — se mantiene
    // deliberadamente, con display=swap para no bloquear el render.
    wp_enqueue_style(
        'tienelore-fonts',
        'https://fonts.googleapis.com/css2?family=Anton&family=Work+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400;1,600&family=Space+Mono:wght@400;700&display=swap',
        [],
        null
    );
    wp_enqueue_style(
        'tienelore-main',
        get_template_directory_uri() . '/assets/css/main.css',
        [ 'tienelore-fonts' ],
        '1.0'
    );
    wp_enqueue_script(
        'tienelore-main',
        get_template_directory_uri() . '/assets/js/main.js',
        [],
        '1.0',
        true
    );
}
add_action( 'wp_enqueue_scripts', 'tienelore_scripts' );

/* ─── Las 8 categorías fijas de TieneLore ────────────────────────────
   slug => [ nombre, color, clase css ]. Se usa para pintar los sellos de
   categoría, sembrar las categorías al activar el tema, y generar las
   reglas de reescritura de URL limpia (sin /category/). */
function tienelore_categories_map() {
    return [
        'actualidad'      => [ 'name' => 'Actualidad',      'color' => '#1345c4', 'css' => 'actualidad' ],
        'corazon'         => [ 'name' => 'Corazón',         'color' => '#e2144a', 'css' => 'corazon' ],
        'deporte'         => [ 'name' => 'Deporte',         'color' => '#0f7a3d', 'css' => 'deporte' ],
        'paladar'         => [ 'name' => 'Paladar',         'color' => '#c25f00', 'css' => 'paladar' ],
        'virales'         => [ 'name' => 'Virales',         'color' => '#8a1798', 'css' => 'virales' ],
        'cosas-de-casa'   => [ 'name' => 'Cosas de casa',   'color' => '#0b7a6f', 'css' => 'casa' ],
        'frase-del-dia'   => [ 'name' => 'Frase del día',   'color' => '#96700a', 'css' => 'frase' ],
        'caja-de-pandora' => [ 'name' => 'Caja de Pandora', 'color' => '#2c2482', 'css' => 'pandora' ],
    ];
}

/* ─── Siembra las 8 categorías al activar el tema, si no existen ────── */
function tienelore_seed_categories() {
    foreach ( tienelore_categories_map() as $slug => $data ) {
        if ( ! term_exists( $slug, 'category' ) ) {
            wp_insert_term( $data['name'], 'category', [ 'slug' => $slug ] );
        }
    }
    flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'tienelore_seed_categories' );

/* ─── Clase CSS de categoría (para <body>, tarjetas, sellos…) ───────── */
function tienelore_cat_class( $post_id = null ) {
    $cats = get_the_category( $post_id ?: get_the_ID() );
    if ( empty( $cats ) ) return 'default';
    $map = tienelore_categories_map();
    return isset( $map[ $cats[0]->slug ] ) ? $map[ $cats[0]->slug ]['css'] : 'default';
}

/* ─── Tiempo de lectura estimado ─────────────────────────────────────── */
function tienelore_reading_time( $post_id = null ) {
    $content = get_post_field( 'post_content', $post_id ?: get_the_ID() );
    $words   = str_word_count( wp_strip_all_tags( $content ) );
    $minutes = max( 1, (int) round( $words / 200 ) );
    return $minutes . ' min';
}

/* ─── Posts relacionados por palabra clave del título ────────────────
   Mismo enfoque que PlotTwist: busca por nombres propios / palabras
   significativas del título dentro de la misma categoría, en vez de
   elegir posts al azar sin relación temática. Con caché de 1h. */
function tienelore_related_posts( $post_id, $cat_id, $count = 3 ) {
    $cache_key  = 'tienelore_related_' . $post_id . '_' . $cat_id;
    $cached_ids = get_transient( $cache_key );
    if ( false !== $cached_ids ) {
        return array_filter( array_map( 'get_post', $cached_ids ) );
    }

    $stopwords = [
        'de','la','el','en','un','una','y','que','los','las','del','con',
        'para','su','se','es','o','a','al','lo','le','les','como','más',
        'sus','esta','este','estos','estas','ha','han','fue','ser','son',
        'muy','ya','no','sin','entre','sobre','hasta','desde','tras',
        'qué','cómo','por','porqué','hoy','esto','eso',
    ];
    $title     = get_the_title( $post_id );
    $raw_words = preg_split( '/[\s,.:;¿?¡!"\'()«»]+/u', $title );

    $proper_nouns = array_filter( $raw_words, function( $w ) {
        return mb_strlen( $w ) > 2
            && mb_substr( $w, 0, 1, 'UTF-8' ) === mb_strtoupper( mb_substr( $w, 0, 1, 'UTF-8' ), 'UTF-8' )
            && ! in_array( mb_strtolower( $w, 'UTF-8' ), [ 'el', 'la', 'los', 'las', 'un', 'una' ], true );
    } );

    if ( $proper_nouns ) {
        $words = array_map( fn( $w ) => mb_strtolower( $w, 'UTF-8' ), $proper_nouns );
    } else {
        $lower_words = array_map( fn( $w ) => mb_strtolower( $w, 'UTF-8' ), $raw_words );
        $words = array_filter( $lower_words, fn( $w ) => mb_strlen( $w ) > 3 && ! in_array( $w, $stopwords, true ) );
    }
    usort( $words, fn( $a, $b ) => mb_strlen( $b ) - mb_strlen( $a ) );
    $words = array_slice( array_unique( $words ), 0, 4 );

    $scores = [];
    foreach ( $words as $word ) {
        $q = new WP_Query( [
            'category__in'   => [ $cat_id ],
            'post__not_in'   => [ $post_id ],
            'posts_per_page' => 10,
            's'              => $word,
            'no_found_rows'  => true,
            'fields'         => 'ids',
        ] );
        foreach ( $q->posts as $found_id ) {
            $scores[ $found_id ] = ( $scores[ $found_id ] ?? 0 ) + 1;
        }
    }
    arsort( $scores );
    $ranked_ids    = array_slice( array_keys( $scores ), 0, $count );
    $related_posts = $ranked_ids ? array_map( 'get_post', $ranked_ids ) : [];

    if ( count( $related_posts ) < $count ) {
        $fallback = new WP_Query( [
            'category__in'   => [ $cat_id ],
            'post__not_in'   => array_merge( [ $post_id ], $ranked_ids ),
            'posts_per_page' => $count - count( $related_posts ),
            'orderby'        => 'rand',
            'no_found_rows'  => true,
        ] );
        $related_posts = array_merge( $related_posts, $fallback->posts );
    }

    set_transient( $cache_key, wp_list_pluck( $related_posts, 'ID' ), HOUR_IN_SECONDS );
    return $related_posts;
}

/* Invalida el caché de relacionados al publicar contenido nuevo. */
add_action( 'transition_post_status', function( $new_status, $old_status, $post ) {
    if ( 'publish' !== $new_status || 'publish' === $old_status ) return;
    global $wpdb;
    $wpdb->query(
        "DELETE FROM {$wpdb->options}
         WHERE option_name LIKE '\_transient\_tienelore\_related\_%'
            OR option_name LIKE '\_transient\_timeout\_tienelore\_related\_%'"
    );
}, 10, 3 );

/* ─── Excerpt ────────────────────────────────────────────────────────── */
add_filter( 'excerpt_length', fn() => 26 );
add_filter( 'excerpt_more',   fn() => '…' );

/* ─── URLs de categoría limpias, sin /category/ ──────────────────────
   Mismo mecanismo que PlotTwist: reescribe get_category_link() y añade
   las reglas de reescritura correspondientes para que /corazon/ apunte
   directamente al archivo de la categoría, sin necesitar una Página
   duplicada con el mismo slug (simplificación respecto al método de
   PlotTwist — ver README del proyecto). */
add_filter( 'category_link', function( $link, $term_id ) {
    $term = get_term( $term_id, 'category' );
    if ( is_wp_error( $term ) || ! $term ) return $link;
    return trailingslashit( home_url( $term->slug ) );
}, 20, 2 );

function tienelore_category_rewrites() {
    foreach ( array_keys( tienelore_categories_map() ) as $slug ) {
        add_rewrite_rule(
            '^' . preg_quote( $slug, '#' ) . '/page/([0-9]+)/?$',
            'index.php?category_name=' . $slug . '&paged=$matches[1]',
            'top'
        );
        add_rewrite_rule(
            '^' . preg_quote( $slug, '#' ) . '/?$',
            'index.php?category_name=' . $slug,
            'top'
        );
    }
}
add_action( 'init', 'tienelore_category_rewrites' );

/* ─── Favicon de marca ────────────────────────────────────────────────
   WordPress ya deja cambiar el icono del sitio a mano, sin tocar código,
   desde Escritorio → Apariencia → Personalizar → Identidad del sitio →
   Icono del sitio (o, en temas de bloques, Apariencia → Editor). Ese
   ajuste nativo (site icon) tiene SIEMPRE prioridad: en cuanto alguien
   suba uno ahí, WordPress imprime su propio favicon automáticamente y
   este bloque no hace nada.
   Mientras nadie lo haya configurado, esta función sirve el favicon de
   marca (el asterisco rojo sobre negro del logo) para que la web nunca
   se quede sin icono. Los archivos viven en assets/img/favicon/. */
function tienelore_favicon() {
    if ( function_exists( 'has_site_icon' ) && has_site_icon() ) {
        return; // Alguien ya puso uno a mano desde el admin: no interferir.
    }
    $base = get_template_directory_uri() . '/assets/img/favicon/';
    ?>
    <link rel="icon" type="image/svg+xml" href="<?php echo esc_url( $base . 'favicon.svg' ); ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo esc_url( $base . 'favicon-32x32.png' ); ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo esc_url( $base . 'favicon-16x16.png' ); ?>">
    <link rel="shortcut icon" href="<?php echo esc_url( $base . 'favicon.ico' ); ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo esc_url( $base . 'apple-touch-icon.png' ); ?>">
    <link rel="manifest" href="<?php echo esc_url( $base . 'site.webmanifest' ); ?>">
    <meta name="theme-color" content="#141210">
    <?php
}
add_action( 'wp_head', 'tienelore_favicon', 1 );

/* ─── Portada manual (hero de la home) ───────────────────────────────
   Por defecto la portada muestra siempre la última noticia publicada
   (comportamiento automático de antes, que se mantiene como fallback).
   Esto añade una casilla "Fijar como portada" en el editor de cada
   entrada para poder anular ese automatismo a mano: la entrada marcada
   pasa a ser el hero de la home hasta que se desmarque o se marque otra
   (solo puede haber una a la vez — marcar una desmarca automáticamente
   cualquier otra que lo estuviera).
   tienelore_get_featured_post() es lo que usa home.php para decidir el
   hero: devuelve la entrada fijada a mano, o null si no hay ninguna
   (y entonces home.php sigue usando la última publicada, como siempre). */

function tienelore_featured_metabox() {
    add_meta_box(
        'tienelore_featured',
        'Portada de TieneLore',
        'tienelore_featured_metabox_html',
        'post',
        'side',
        'high'
    );
}
add_action( 'add_meta_boxes', 'tienelore_featured_metabox' );

function tienelore_featured_metabox_html( $post ) {
    wp_nonce_field( 'tienelore_featured_save', 'tienelore_featured_nonce' );
    $is_featured = get_post_meta( $post->ID, '_tienelore_featured', true );

    $current = tienelore_get_featured_post();
    ?>
    <label style="display:flex;align-items:center;gap:8px;font-weight:600;">
        <input type="checkbox" name="tienelore_featured" value="1" <?php checked( $is_featured, '1' ); ?>>
        Fijar esta noticia como portada
    </label>
    <p style="color:#666;font-size:12px;margin-top:8px;">
        Si la marcas, esta entrada pasa a ser la noticia grande de la
        portada (arriba del todo) en vez de la última publicada, hasta
        que la desmarques o fijes otra distinta.
    </p>
    <?php if ( $current && (int) $current->ID !== (int) $post->ID ) : ?>
        <p style="font-size:12px;background:#f6f4ee;border:1px solid #d9d3c4;padding:8px;border-radius:4px;">
            Ahora mismo la portada fijada es
            «<a href="<?php echo esc_url( get_edit_post_link( $current->ID ) ); ?>"><?php echo esc_html( get_the_title( $current->ID ) ); ?></a>».
            Si marcas esta casilla y guardas, esa otra dejará de estarlo.
        </p>
    <?php elseif ( ! $current ) : ?>
        <p style="font-size:12px;color:#666;">
            Ahora mismo no hay ninguna portada fijada a mano: la home
            muestra la última noticia publicada.
        </p>
    <?php endif; ?>
    <?php
}

function tienelore_featured_save( $post_id ) {
    if ( ! isset( $_POST['tienelore_featured_nonce'] ) ||
         ! wp_verify_nonce( $_POST['tienelore_featured_nonce'], 'tienelore_featured_save' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;
    if ( 'post' !== get_post_type( $post_id ) ) return;

    if ( ! empty( $_POST['tienelore_featured'] ) ) {
        // Solo puede haber una portada fijada: quita la marca de
        // cualquier otra entrada que la tuviera antes de ponerla aquí.
        global $wpdb;
        $wpdb->delete( $wpdb->postmeta, [ 'meta_key' => '_tienelore_featured' ] );
        update_post_meta( $post_id, '_tienelore_featured', '1' );
    } else {
        delete_post_meta( $post_id, '_tienelore_featured' );
    }
}
add_action( 'save_post', 'tienelore_featured_save' );

/* Devuelve el WP_Post fijado a mano como portada, o null si no hay ninguno
   (post publicado; si la entrada fijada se pasa a borrador o se borra,
   deja de contar y la home vuelve sola al automatismo). */
function tienelore_get_featured_post() {
    $q = new WP_Query( [
        'posts_per_page' => 1,
        'post_status'    => 'publish',
        'meta_key'       => '_tienelore_featured',
        'meta_value'     => '1',
        'no_found_rows'  => true,
    ] );
    return $q->have_posts() ? $q->posts[0] : null;
}

/* Columna en el listado de entradas (Escritorio → Entradas) para ver de
   un vistazo cuál es la portada fijada ahora mismo, sin abrir cada una. */
add_filter( 'manage_posts_columns', function( $columns ) {
    $columns['tienelore_featured'] = 'Portada';
    return $columns;
} );
add_action( 'manage_posts_custom_column', function( $column, $post_id ) {
    if ( 'tienelore_featured' !== $column ) return;
    if ( '1' === get_post_meta( $post_id, '_tienelore_featured', true ) ) {
        echo '★';
    }
}, 10, 2 );
