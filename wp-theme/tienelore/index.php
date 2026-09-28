<?php get_header(); ?>

<div class="container grid-section">
    <div class="section-label">
        <?php echo is_search()
            ? sprintf( 'Resultados para &ldquo;%s&rdquo;', esc_html( get_search_query() ) )
            : 'Últimos artículos'; ?>
    </div>

    <?php if ( have_posts() ) : ?>
        <?php if ( is_search() ) : ?>
        <div class="list">
            <?php while ( have_posts() ) : the_post();
                get_template_part( 'template-parts/content', 'list-item' );
            endwhile; ?>
        </div>
        <?php else : ?>
        <div class="grid">
            <?php while ( have_posts() ) : the_post();
                get_template_part( 'template-parts/content', 'card' );
            endwhile; ?>
        </div>
        <?php endif; ?>
        <div class="pagination">
            <?php the_posts_pagination( [ 'prev_text' => '&larr; Anterior', 'next_text' => 'Siguiente &rarr;' ] ); ?>
        </div>
    <?php else : ?>
        <p class="no-results">No se ha encontrado nada. Prueba con otra búsqueda.</p>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
