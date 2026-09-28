<?php get_header(); ?>

<?php
$author_id   = get_queried_object_id();
$author_name = get_the_author_meta( 'display_name', $author_id );
$author_bio  = get_the_author_meta( 'description', $author_id );
?>

<div class="container archive-header author-header">
    <?php echo get_avatar( $author_id, 88 ); ?>
    <div class="author-header-body">
        <h1><?php echo esc_html( $author_name ); ?></h1>
        <?php if ( $author_bio ) : ?>
        <p class="author-bio"><?php echo esc_html( $author_bio ); ?></p>
        <?php endif; ?>
    </div>
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
        <p class="no-results">Todavía no hay artículos de esta firma.</p>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
