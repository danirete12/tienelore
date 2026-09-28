<?php
/**
 * Tarjeta de artículo, usada en home.php, archive.php e index.php.
 * Args opcionales: [ 'span3' => true ] para que ocupe 3 columnas en vez de 2
 * en la rejilla de 6 columnas de .grid (ver assets/css/main.css).
 */
$span3 = ! empty( $args['span3'] );
$cats  = get_the_category();
?>
<article class="card<?php echo $span3 ? ' span-3' : ''; ?>">
    <a href="<?php the_permalink(); ?>" class="card-media">
        <?php if ( has_post_thumbnail() ) : ?>
            <?php the_post_thumbnail( 'tl-card' ); ?>
        <?php else : ?>
            <div class="media-placeholder"></div>
        <?php endif; ?>
    </a>
    <div class="card-body">
        <?php if ( $cats ) : ?>
        <a href="<?php echo esc_url( get_category_link( $cats[0] ) ); ?>" class="stamp stamp--<?php echo esc_attr( tienelore_cat_class() ); ?>">
            <?php echo esc_html( $cats[0]->name ); ?>
        </a>
        <?php endif; ?>
        <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
        <p class="excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 18, '…' ) ); ?></p>
        <div class="meta">
            <span><?php the_author(); ?></span>
            <span><?php echo get_the_date(); ?></span>
            <span><?php echo esc_html( tienelore_reading_time() ); ?></span>
        </div>
    </div>
</article>
