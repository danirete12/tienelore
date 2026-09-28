<?php
/** Fila de resultado, usada en index.php (resultados de búsqueda). */
$cats = get_the_category();
?>
<article class="list-item">
    <a href="<?php the_permalink(); ?>" class="list-item-image" tabindex="-1" aria-hidden="true">
        <?php if ( has_post_thumbnail() ) : ?>
            <?php the_post_thumbnail( 'tl-thumb' ); ?>
        <?php else : ?>
            <div class="media-placeholder"></div>
        <?php endif; ?>
    </a>
    <div class="list-item-body">
        <?php if ( $cats ) : ?>
        <a href="<?php echo esc_url( get_category_link( $cats[0] ) ); ?>" class="stamp stamp--<?php echo esc_attr( tienelore_cat_class() ); ?>">
            <?php echo esc_html( $cats[0]->name ); ?>
        </a>
        <?php endif; ?>
        <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
        <p class="excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 24, '…' ) ); ?></p>
        <div class="meta">
            <span><?php the_author(); ?></span>
            <span><?php echo get_the_date(); ?></span>
        </div>
    </div>
</article>
