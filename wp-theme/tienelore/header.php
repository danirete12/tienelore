<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="masthead halftone" id="site-header">
    <div class="container masthead-top">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="logo" aria-label="<?php bloginfo( 'name' ); ?>">
            <span class="t1">tiene</span><span class="t2">LORE</span><span class="dot">*</span>
        </a>

        <div class="masthead-actions">
            <button class="icon-btn" type="button" id="search-toggle" aria-label="Buscar" aria-expanded="false" aria-controls="tienelore-search">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.4" y2="16.4"/></svg>
            </button>
            <button class="icon-btn nav-toggle" type="button" id="nav-toggle" aria-label="Abrir menú" aria-expanded="false" aria-controls="site-catnav">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
        </div>
    </div>

    <form role="search" method="get" class="search-overlay" id="tienelore-search" action="<?php echo esc_url( home_url( '/' ) ); ?>" hidden>
        <input type="search" name="s" placeholder="Buscar en TieneLore…" value="<?php echo esc_attr( get_search_query() ); ?>">
        <button type="submit">Buscar</button>
    </form>

    <nav class="catnav" id="site-catnav" aria-label="Categorías">
        <?php
        if ( has_nav_menu( 'primary' ) ) :
            wp_nav_menu( [
                'theme_location' => 'primary',
                'container'      => false,
                'items_wrap'     => '%3$s',
            ] );
        else :
            foreach ( tienelore_categories_map() as $slug => $data ) :
                $term = get_category_by_slug( $slug );
                $link = $term ? get_category_link( $term->term_id ) : '#';
                printf(
                    '<a href="%s"><span class="sw sw--%s"></span>%s</a>',
                    esc_url( $link ),
                    esc_attr( $data['css'] ),
                    esc_html( $data['name'] )
                );
            endforeach;
        endif;
        ?>
    </nav>
</header>

<?php
$ticker_q = new WP_Query( [ 'posts_per_page' => 5, 'post_status' => 'publish', 'no_found_rows' => true ] );
if ( $ticker_q->have_posts() ) :
    $ticker_titles = [];
    while ( $ticker_q->have_posts() ) : $ticker_q->the_post();
        $ticker_titles[] = get_the_title();
    endwhile;
    wp_reset_postdata();
?>
<div class="ticker-bar">
    <span class="tag">Hoy tiene lore</span>
    <span class="msg"><?php echo esc_html( implode( ' · ', $ticker_titles ) ); ?></span>
</div>
<?php endif; ?>

<main class="site-main">
