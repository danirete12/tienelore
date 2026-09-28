<?php get_header(); ?>

<?php $shown_ids = []; ?>

<?php
/* ── Hero: la portada fijada a mano, o si no hay ninguna, la última
   publicación (automatismo de siempre) — + 3 secundarias. ── */
$featured_post = tienelore_get_featured_post();
if ( $featured_post ) {
    $hero_q = new WP_Query( [ 'p' => $featured_post->ID, 'post_status' => 'publish', 'no_found_rows' => true ] );
} else {
    $hero_q = new WP_Query( [ 'posts_per_page' => 1, 'post_status' => 'publish', 'no_found_rows' => true ] );
}
if ( $hero_q->have_posts() ) :
    $hero_q->the_post();
    $shown_ids[] = get_the_ID();
?>
<div class="frontpage container">
<section class="hero-grid">
    <article class="hero-card">
        <a href="<?php the_permalink(); ?>" class="hero-media">
            <span class="stamp stamp--<?php echo esc_attr( tienelore_cat_class() ); ?> on-image"><?php $c = get_the_category(); echo $c ? esc_html( $c[0]->name ) : ''; ?></span>
            <?php if ( has_post_thumbnail() ) : the_post_thumbnail( 'tl-hero' ); else : ?>
                <div class="media-placeholder"></div>
            <?php endif; ?>
        </a>
        <div class="hero-body">
            <div class="meta">
                <span><?php the_author(); ?></span>
                <span><?php echo get_the_date(); ?></span>
                <span><?php echo esc_html( tienelore_reading_time() ); ?></span>
            </div>
            <h1><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1>
            <p class="hero-dek"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 28, '…' ) ); ?></p>
        </div>
    </article>

    <div class="side-stack">
        <?php
        $side_q = new WP_Query( [ 'posts_per_page' => 3, 'post_status' => 'publish', 'post__not_in' => $shown_ids, 'no_found_rows' => true ] );
        if ( $side_q->have_posts() ) : while ( $side_q->have_posts() ) : $side_q->the_post();
            $shown_ids[] = get_the_ID();
        ?>
        <a href="<?php the_permalink(); ?>" class="mini-card">
            <div class="mini-thumb">
                <?php if ( has_post_thumbnail() ) : the_post_thumbnail( 'tl-thumb' ); else : ?>
                    <div class="media-placeholder"></div>
                <?php endif; ?>
            </div>
            <div class="mini-body">
                <?php $mc = get_the_category(); if ( $mc ) : ?>
                <span class="stamp stamp--<?php echo esc_attr( tienelore_cat_class() ); ?>"><?php echo esc_html( $mc[0]->name ); ?></span>
                <?php endif; ?>
                <h3><?php the_title(); ?></h3>
            </div>
        </a>
        <?php endwhile; wp_reset_postdata(); endif; ?>
    </div>
</section>
</div>
<?php endif; wp_reset_postdata(); ?>

<?php
/* ── Frase del día: la última publicación de esa categoría ── */
$frase_term = get_category_by_slug( 'frase-del-dia' );
if ( $frase_term ) :
    $frase_q = new WP_Query( [ 'posts_per_page' => 1, 'cat' => $frase_term->term_id, 'post_status' => 'publish', 'no_found_rows' => true ] );
    if ( $frase_q->have_posts() ) : $frase_q->the_post();
?>
<div class="frase-wrap container">
    <a href="<?php the_permalink(); ?>" class="frase-card">
        <span class="mark">&ldquo;</span>
        <blockquote><?php echo esc_html( wp_trim_words( get_the_title(), 40, '…' ) ); ?></blockquote>
        <span class="frase-cap">FRASE DEL DÍA &middot; <?php the_date(); ?></span>
    </a>
</div>
<?php endif; wp_reset_postdata(); endif; ?>

<?php
/* ── Virales: ticker con las últimas publicaciones de esa categoría ── */
$virales_term = get_category_by_slug( 'virales' );
if ( $virales_term ) :
    $virales_q = new WP_Query( [ 'posts_per_page' => 8, 'cat' => $virales_term->term_id, 'post_status' => 'publish', 'no_found_rows' => true ] );
    if ( $virales_q->have_posts() ) :
        $vitems = [];
        $n = 1;
        while ( $virales_q->have_posts() ) : $virales_q->the_post();
            $vitems[] = '<a href="' . esc_url( get_permalink() ) . '" class="vitem"><b>' . sprintf( '%02d', $n ) . '</b> ' . esc_html( get_the_title() ) . '</a>';
            $n++;
        endwhile;
        wp_reset_postdata();
?>
<section class="virales-section halftone">
    <div class="virales-head">
        <svg class="flame" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2c1 3-2 4-2 7a3 3 0 003 3c2 0 3-1.5 3-3 2 2 3 4 3 6a6 6 0 01-12 0c0-4 2-5 3-8 0-2-1-3-1-5 1 0 2.5.5 3 3z"/></svg>
        <h2>En llamas ahora mismo</h2>
    </div>
    <div class="marquee">
        <div class="marquee-track">
            <?php echo implode( '', $vitems ) . implode( '', $vitems ); // duplicado para el scroll continuo ?>
        </div>
    </div>
</section>
<?php endif; endif; ?>

<div class="grid-section container">
    <div class="section-label">Lo último</div>
    <div class="grid">
        <?php
        $grid_q = new WP_Query( [ 'posts_per_page' => 9, 'post_status' => 'publish', 'post__not_in' => $shown_ids, 'no_found_rows' => true ] );
        if ( $grid_q->have_posts() ) : $i = 0; while ( $grid_q->have_posts() ) : $grid_q->the_post(); $i++;
            $shown_ids[] = get_the_ID();
            get_template_part( 'template-parts/content', 'card', [ 'span3' => ( 0 === $i % 4 ) ] );
        endwhile; wp_reset_postdata(); endif;
        ?>
    </div>
</div>

<?php
/* ── Una sección por cada una de las 8 categorías (Caja de Pandora
   incluida, tratada exactamente igual que el resto — sin caja oscura
   ni miniaturas borrosas). ── */
foreach ( tienelore_categories_map() as $slug => $data ) :
    $cat_obj = get_category_by_slug( $slug );
    if ( ! $cat_obj ) continue;

    $cat_q = new WP_Query( [ 'category_name' => $slug, 'posts_per_page' => 3, 'no_found_rows' => true ] );
    if ( ! $cat_q->have_posts() ) { wp_reset_postdata(); continue; }
?>
<section class="category-section container">
    <div class="section-label">
        <span class="stamp stamp--<?php echo esc_attr( $data['css'] ); ?>"><?php echo esc_html( $data['name'] ); ?></span>
        <a href="<?php echo esc_url( get_category_link( $cat_obj ) ); ?>" class="section-more">Ver todo &rarr;</a>
    </div>
    <div class="grid">
        <?php while ( $cat_q->have_posts() ) : $cat_q->the_post();
            get_template_part( 'template-parts/content', 'card' );
        endwhile; wp_reset_postdata(); ?>
    </div>
</section>
<?php endforeach; ?>

<?php get_footer(); ?>
