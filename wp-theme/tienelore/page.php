<?php get_header(); ?>

<div class="container page-wrap">
    <?php while ( have_posts() ) : the_post(); ?>
    <article>
        <h1 class="page-title"><?php the_title(); ?></h1>
        <div class="prose"><?php the_content(); ?></div>
    </article>
    <?php endwhile; ?>
</div>

<?php get_footer(); ?>
