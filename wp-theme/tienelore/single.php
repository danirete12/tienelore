<?php get_header(); ?>

<?php while ( have_posts() ) : the_post();
    $cats    = get_the_category();
    $is_frase = $cats && 'frase-del-dia' === $cats[0]->slug;
?>

<?php if ( $is_frase ) : ?>

    <div class="frase-wrap container" style="padding-top:32px;">
        <article class="frase-card frase-card--single">
            <span class="mark">&ldquo;</span>
            <blockquote><?php the_title(); ?></blockquote>
            <span class="frase-cap"><?php the_author(); ?> &middot; <?php echo get_the_date(); ?></span>
        </article>
        <div class="prose" style="margin-top:26px;">
            <?php the_content(); ?>
        </div>
    </div>

<?php else : ?>

    <div class="reading-bar" id="reading-bar" role="progressbar" aria-hidden="true"></div>

    <article class="single-post" id="post-<?php the_ID(); ?>">
        <div class="container article-head">
            <a class="back-link" href="<?php echo esc_url( home_url( '/' ) ); ?>">&larr; Volver a portada</a>
            <?php if ( $cats ) : ?>
            <a href="<?php echo esc_url( get_category_link( $cats[0] ) ); ?>" class="stamp stamp--<?php echo esc_attr( tienelore_cat_class() ); ?>">
                <?php echo esc_html( $cats[0]->name ); ?>
            </a>
            <?php endif; ?>
            <h1><?php the_title(); ?></h1>
            <?php $entradilla = get_the_excerpt(); if ( $entradilla ) : ?>
            <p class="article-dek"><?php echo esc_html( $entradilla ); ?></p>
            <?php endif; ?>
        </div>

        <?php if ( has_post_thumbnail() ) : ?>
        <div class="article-lead-media" id="article-body">
            <?php the_post_thumbnail( 'tl-hero' ); ?>
        </div>
        <?php $hero_credit = wp_get_attachment_caption( get_post_thumbnail_id() ); if ( $hero_credit ) : ?>
        <p class="caption"><?php echo esc_html( $hero_credit ); ?></p>
        <?php endif; endif; ?>

        <div class="container">
            <div class="prose"<?php if ( ! has_post_thumbnail() ) echo ' id="article-body"'; ?>>
                <div class="meta">
                    <span><?php the_author(); ?></span>
                    <span><?php echo get_the_date(); ?></span>
                    <span><?php echo esc_html( tienelore_reading_time() ); ?></span>
                </div>

                <?php the_content(); ?>

                <?php $tags = get_the_tags(); if ( $tags ) : ?>
                <div class="tag-row">
                    <?php foreach ( $tags as $tag ) : ?>
                    <a class="tag-pill" href="<?php echo esc_url( get_tag_link( $tag ) ); ?>">#<?php echo esc_html( $tag->name ); ?></a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php
                $author_id  = get_the_author_meta( 'ID' );
                $author_bio = get_the_author_meta( 'description', $author_id );
                ?>
                <div class="author-box">
                    <?php echo get_avatar( $author_id, 64 ); ?>
                    <div class="author-box-body">
                        <a class="author-name" href="<?php echo esc_url( get_author_posts_url( $author_id ) ); ?>"><?php the_author(); ?></a>
                        <?php if ( $author_bio ) : ?>
                        <p><?php echo esc_html( wp_trim_words( $author_bio, 26, '…' ) ); ?></p>
                        <?php endif; ?>
                        <a class="author-link" href="<?php echo esc_url( get_author_posts_url( $author_id ) ); ?>">Ver perfil y más artículos &rarr;</a>
                    </div>
                </div>
            </div>

            <?php
            if ( $cats ) :
                $related = tienelore_related_posts( get_the_ID(), $cats[0]->term_id, 3 );
                if ( $related ) :
            ?>
            <div class="related-strip">
                <div class="section-label">Más de <?php echo esc_html( $cats[0]->name ); ?></div>
                <div class="grid grid--3col">
                    <?php foreach ( $related as $r ) : setup_postdata( $r ); ?>
                        <article class="card">
                            <a href="<?php echo esc_url( get_permalink( $r ) ); ?>" class="card-media">
                                <?php if ( has_post_thumbnail( $r->ID ) ) : echo get_the_post_thumbnail( $r->ID, 'tl-card' ); else : ?>
                                    <div class="media-placeholder"></div>
                                <?php endif; ?>
                            </a>
                            <div class="card-body">
                                <h3><a href="<?php echo esc_url( get_permalink( $r ) ); ?>"><?php echo esc_html( get_the_title( $r ) ); ?></a></h3>
                                <div class="meta"><span><?php echo esc_html( tienelore_reading_time( $r->ID ) ); ?></span></div>
                            </div>
                        </article>
                    <?php endforeach; wp_reset_postdata(); ?>
                </div>
            </div>
            <?php endif; endif; ?>
        </div>
    </article>

<?php endif; ?>

<?php endwhile; ?>

<?php get_footer(); ?>
