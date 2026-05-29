<?php
// Modèle de la page d'accueil (Mobile First)
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
    <button class="burger-btn" id="openMenuBtn">☰</button>

    <div class="menu-overlay" id="mainMenu">
        <button class="modal-close-btn" id="closeMenuBtn">✕</button>
        <h2 style="color: white; margin-bottom: 30px;">MENU</h2>
        <div style="color: white;">
            <?php
            // On récupère les titres pour le menu
            $menu_query = new WP_Query(array('post_type' => 'poi', 'posts_per_page' => -1));
            if ( $menu_query->have_posts() ) :
                while ( $menu_query->have_posts() ) : $menu_query->the_post();
                    echo '<p style="margin-bottom:15px; font-size:18px;">' . get_the_title() . '</p>';
                endwhile;
                wp_reset_postdata();
            endif;
            ?>
        </div>
    </div>

    <section class="hero-section">
        <div class="hero-content">
            <h1>Bienvenue à Chaumont-sur-Loire</h1>
            <p>Prenez le temps de découvrir ce qui vous entoure</p>
        </div>
    </section>

    <section class="map-section">
        <h2>Découverte du milieu</h2>
        <p>Touchez un point sur la carte pour explorer.</p>
        <div class="map-container">
            <div class="map-pin pin-nature"></div>
            <div class="map-pin pin-histoire"></div>
        </div>
    </section>

    <?php
    // REQUÊTE PRINCIPALE : On va chercher tous les Points d'Intérêt
    $poi_query = new WP_Query(array(
            'post_type'      => 'poi',
            'posts_per_page' => -1, // -1 = Afficher tout
            'post_status'    => 'publish'
    ));
    ?>

    <section class="slider-section">
        <h2 style="padding: 0 16px; color: #333;">Laissez-vous surprendre...</h2>
        <div class="cards-slider">

            <?php
            if ( $poi_query->have_posts() ) :
                $modal_index = 1; // Compteur pour relier le bouton à la bonne modale
                while ( $poi_query->have_posts() ) : $poi_query->the_post();

                    // Récupération des champs ACF
                    $theme = get_field('theme_de_la_carte') ? get_field('theme_de_la_carte') : 'nature';
                    $sous_titre = get_field('sous_titre_court');
                    ?>
                    <div class="slider-card" data-theme="<?php echo esc_attr($theme); ?>">
                        <span class="badge">À découvrir</span>
                        <h3><?php the_title(); ?></h3>
                        <p><?php echo esc_html($sous_titre); ?></p>
                        <button class="btn-savoir-plus" data-target="modal-<?php echo $modal_index; ?>">En savoir plus ↗</button>
                    </div>
                    <?php
                    $modal_index++;
                endwhile;
            endif;
            ?>

        </div>
    </section>

    <?php
    if ( $poi_query->have_posts() ) :
        $poi_query->rewind_posts(); // On rembobine la boucle pour réutiliser les mêmes données
        $modal_index = 1;
        while ( $poi_query->have_posts() ) : $poi_query->the_post();

            $theme = get_field('theme_de_la_carte') ? get_field('theme_de_la_carte') : 'nature';
            // On récupère l'image mise en avant (Featured Image) de l'article
            $image_url = get_the_post_thumbnail_url(get_the_ID(), 'large');
            ?>
            <div class="modal-details" id="modal-<?php echo $modal_index; ?>">
                <div class="modal-header-image" style="background-image: url('<?php echo esc_url($image_url); ?>'); background-size: cover; background-position: center;">
                    <button class="modal-close-btn close-modal-btn">✕</button>
                </div>
                <div class="modal-content">
                    <span class="badge" style="background: var(--theme-<?php echo esc_attr($theme); ?>); color: white; padding: 4px 8px; border-radius: 20px; font-size: 12px; text-transform: uppercase;">À DÉCOUVRIR</span>
                    <h2 style="margin-top: 10px;"><?php the_title(); ?></h2>
                    <div style="margin-top: 20px;">
                        <?php the_content(); ?>
                    </div>
                </div>
            </div>
            <?php
            $modal_index++;
        endwhile;
        wp_reset_postdata(); // On nettoie la requête
    endif;
    ?>

    <?php wp_footer(); ?>
</body>
</html>