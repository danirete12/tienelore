<?php get_header(); ?>

<?php
$cat_data = null;
if ( is_category() ) {
    $term = get_queried_object();
    $map  = tienelore_categories_map();
    $cat_data = $map[ $term->slug ] ?? [ 'name' => $term->name, 'color' => '#5c564e', 'css' => 'default' ];
}
?>

<div class="container archive-header">
    <?php if ( $cat_data ) : ?>
        <span class="stamp stamp--<?php echo esc_attr( $cat_data['css'] ); ?> archive-stamp"><?php echo esc_html( $cat_data['name'] ); ?></span>
        <?php if ( category_description() ) : ?>
        <p class="archive-desc"><?php echo wp_kses_post( category_description() ); ?></p>
        <?php endif; ?>
    <?php else : ?>
        <h1 class="archive-title">
            <?php
            if ( is_tag() )     single_tag_title( 'Etiqueta: ' );
            elseif ( is_author() ) the_author();
            elseif ( is_date() )   the_date();
            else                    _e( 'Archivo', 'tienelore' );
            ?>
        </h1>
    <?php endif; ?>
</div>

<div class="container grid-section">
    <?php if ( have_posts() ) : ?>
    <div class="grid">
        <?php while ( have_posts() ) : the_post();
            get_template_part( 'template-parts/content', 'card' );
        endwhile; ?>
    </div>
    <div class="pagination">
        <?php the_posts_pagination( [ 'prev_text' => '&larr; Anterior', 'next_text' => 'Siguiente &rarr;' ] ); ?>
    </div>
    <?php else : ?>
        <p class="no-results">Todavía no hay artículos aquí.</p>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
