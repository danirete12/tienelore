</main>

<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="logo" style="font-size:24px;">
                <span class="t1">tiene</span><span class="t2">LORE</span>
            </a>
            <p class="footer-tag">Actualidad, cotilleo y vida cotidiana, contados como se cuentan en el grupo de siempre.</p>
        </div>

        <nav class="footer-cats" aria-label="Categorías en el pie">
            <?php foreach ( tienelore_categories_map() as $slug => $data ) :
                $term = get_category_by_slug( $slug );
                $link = $term ? get_category_link( $term->term_id ) : '#';
            ?>
                <a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $data['name'] ); ?></a>
            <?php endforeach; ?>
        </nav>
    </div>

    <div class="container footer-bottom">
        <span>&copy; <?php echo esc_html( date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></span>
        <nav class="footer-legal" aria-label="Páginas legales">
            <a href="<?php echo esc_url( home_url( '/aviso-legal/' ) ); ?>">Aviso legal</a>
            <span aria-hidden="true">·</span>
            <a href="<?php echo esc_url( home_url( '/cookies/' ) ); ?>">Cookies</a>
            <span aria-hidden="true">·</span>
            <a href="<?php echo esc_url( home_url( '/contacto/' ) ); ?>">Contacto</a>
        </nav>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
