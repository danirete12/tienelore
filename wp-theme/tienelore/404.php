<?php get_header(); ?>

<div class="container error-404">
    <h1>404</h1>
    <p>Esto no estaba en el guion. Esta página no existe o se ha movido.</p>
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn-primary">Volver a portada</a>
</div>

<?php get_footer(); ?>
