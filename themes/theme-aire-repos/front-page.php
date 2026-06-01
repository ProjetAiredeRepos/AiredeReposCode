<?php
// Modèle de la page d'accueil (Fidélité absolue Figma)
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
        <div class="menu-header">
            <span class="menu-label">MENU</span>
            <h2 class="menu-title">Chaumont-sur-Loire</h2>
        </div>
        <div class="menu-links">
            <?php
            $menu_query = new WP_Query(array('post_type' => 'poi', 'posts_per_page' => -1, 'order' => 'ASC'));
            $modal_link_index = 1;
            if ( $menu_query->have_posts() ) :
                while ( $menu_query->have_posts() ) : $menu_query->the_post();
                    $theme = get_field('theme_de_la_carte') ? get_field('theme_de_la_carte') : 'nature';
                    $sous_titre = get_field('sous_titre_court');
                    // On récupère l'image en format miniature pour charger plus vite
                    $thumb_url = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail');
                    ?>
                    <div class="menu-item" data-target="modal-<?php echo $modal_link_index; ?>" data-theme="<?php echo esc_attr($theme); ?>">
                        <?php if($thumb_url): ?>
                            <div class="menu-item-thumb" style="background-image: url('<?php echo esc_url($thumb_url); ?>');"></div>
                        <?php endif; ?>

                        <div class="menu-item-content">
                            <h3><?php the_title(); ?></h3>
                            <p><?php echo esc_html($sous_titre); ?></p>
                        </div>

                        <div class="menu-item-arrow" style="background-color: var(--theme-<?php echo esc_attr($theme); ?>);">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="7" y1="17" x2="17" y2="7"></line>
                                <polyline points="7 7 17 7 17 17"></polyline>
                            </svg>
                        </div>
                    </div>
                    <?php
                    $modal_link_index++;
                endwhile;
                wp_reset_postdata();
            endif;
            ?>
        </div>

        <div class="menu-footer">
            <hr>
            <p><strong>Aire de repos · La Loire à Vélo</strong></p>
            <p>Val de Loire 41</p>
        </div>
    </div>

    <section class="hero-section">
        <div class="hero-spacer"></div> <div class="hero-content">
            <h1>Bienvenue à<br>Chaumont-sur-Loire</h1>
            <p>Prenez le temps de découvrir ce qui vous entoure</p>
        </div>

        <div class="hero-bottom">
            <button class="btn-decouvrir" id="scrollDownBtn">
                DÉCOUVRIR
                <span class="circle-arrow">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </span>
            </button>
        </div>
    </section>

    <section class="map-section">
        <div class="map-header">
            <h2>Découverte du milieu</h2>
            <p>Touchez un point sur la carte pour explorer.</p>
        </div>

        <div class="map-scroll-area">
            <div class="map-wrapper">
                <div class="map-image-layer" style="background-image: url('https://images.unsplash.com/photo-1469522857488-8256e6d194af?q=80&w=1600&auto=format&fit=crop');"></div>

                <?php
                // Génération des 4 pins interactifs
                $map_query = new WP_Query(array('post_type' => 'poi', 'posts_per_page' => -1, 'order' => 'ASC'));
                $pin_index = 1;
                if ( $map_query->have_posts() ) :
                    while ( $map_query->have_posts() ) : $map_query->the_post();
                        $theme = get_field('theme_de_la_carte') ? get_field('theme_de_la_carte') : 'nature';
                        ?>
                        <div class="map-pin pos-<?php echo $pin_index; ?> theme-<?php echo esc_attr($theme); ?> btn-savoir-plus" data-target="modal-<?php echo $pin_index; ?>">
                            <span class="pin-pill"><?php the_title(); ?></span>
                            <div class="pin-circle">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                            </div>
                        </div>
                        <?php
                        $pin_index++;
                    endwhile;
                    wp_reset_postdata();
                endif;
                ?>
            </div>
        </div>
        <p class="swipe-hint">← Faites glisser pour explorer la carte →</p>
    </section>

    <?php
    $poi_query = new WP_Query(array('post_type' => 'poi', 'posts_per_page' => -1, 'order' => 'ASC'));
    $total_cards = $poi_query->found_posts;
    ?>

    <section class="slider-section" id="section-surprendre" data-active-id="0">

        <div class="slider-immersive-bg" id="immersiveBg"></div>

        <div class="slider-content-layer">
            <div class="slider-header">
                <h2>Laissez-vous surprendre...</h2>
                <p>Quatre invitations à prolonger votre escale.</p>
            </div>

            <div class="cards-slider" id="mainSlider">
                <?php
                if ( $poi_query->have_posts() ) :
                    $modal_index = 1;
                    $card_counter = 0; // Ajout d'un compteur pour le JS
                    while ( $poi_query->have_posts() ) : $poi_query->the_post();
                        $theme = get_field('theme_de_la_carte') ? get_field('theme_de_la_carte') : 'nature';
                        $sous_titre = get_field('sous_titre_court');
                        // Récupération de l'image pour la mettre en data-attribute
                        $card_image_url = get_the_post_thumbnail_url(get_the_ID(), 'large');
                        ?>
                        <div class="slider-card" data-index="<?php echo $card_counter; ?>" data-src="<?php echo esc_url($card_image_url); ?>" data-theme="<?php echo esc_attr($theme); ?>" style="background-image: linear-gradient(to bottom, rgba(0,0,0,0) 30%, rgba(0,0,0,0.85) 100%), url('<?php echo esc_url($card_image_url); ?>');">
                            <span class="card-surtitle">À DÉCOUVRIR</span>
                            <h3><?php the_title(); ?></h3>
                            <p><?php echo esc_html($sous_titre); ?></p>

                            <button class="btn-savoir-plus" style="color: var(--theme-<?php echo esc_attr($theme); ?>);" data-target="modal-<?php echo $modal_index; ?>">
                                En savoir plus
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="7" y1="17" x2="17" y2="7"></line>
                                    <polyline points="7 7 17 7 17 17"></polyline>
                                </svg>
                            </button>
                        </div>
                        <?php
                        $modal_index++;
                        $card_counter++;
                    endwhile;
                endif;
                ?>
            </div>

            <div class="slider-pagination">
                <button class="slider-nav-btn prev-btn">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                </button>
                <div class="slider-dots">
                    <?php for($i=0; $i<$total_cards; $i++) { echo '<span class="dot"></span>'; } ?>
                </div>
                <button class="slider-nav-btn next-btn">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </button>
            </div>
        </div>
    </section>

    <?php
    if ( $poi_query->have_posts() ) :
        $poi_query->rewind_posts();
        $modal_index = 1;
        while ( $poi_query->have_posts() ) : $poi_query->the_post();
            $theme = get_field('theme_de_la_carte') ? get_field('theme_de_la_carte') : 'nature';
            $sous_titre = get_field('sous_titre_court');
            $image_url = get_the_post_thumbnail_url(get_the_ID(), 'large');
            ?>
            <div class="modal-details" id="modal-<?php echo $modal_index; ?>">

                <div class="modal-backdrop close-modal-btn"></div>

                <div class="modal-card">
                    <div class="modal-header-image" style="background-image: url('<?php echo esc_url($image_url); ?>');">
                        <button class="modal-close-btn close-modal-btn">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#333" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        </button>
                        <div class="modal-header-content">
                            <span class="badge" style="background: var(--theme-<?php echo esc_attr($theme); ?>); color: white;">À DÉCOUVRIR</span>
                            <h2><?php the_title(); ?></h2>
                            <p><?php echo esc_html($sous_titre); ?></p>
                        </div>
                    </div>

                    <div class="modal-content">
                        <div class="modal-text">
                            <?php the_content(); ?>
                        </div>

                        <div class="modal-info-grid">
                            <?php if(get_field('info_1_titre')) : ?>
                                <div class="info-box">
                                    <span class="info-title"><?php echo esc_html(get_field('info_1_titre')); ?></span>
                                    <span class="info-value" style="color: var(--theme-<?php echo esc_attr($theme); ?>)"><?php echo esc_html(get_field('info_1_valeur')); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if(get_field('info_2_titre')) : ?>
                                <div class="info-box">
                                    <span class="info-title"><?php echo esc_html(get_field('info_2_titre')); ?></span>
                                    <span class="info-value" style="color: var(--theme-<?php echo esc_attr($theme); ?>)"><?php echo esc_html(get_field('info_2_valeur')); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if(get_field('info_3_titre')) : ?>
                                <div class="info-box">
                                    <span class="info-title"><?php echo esc_html(get_field('info_3_titre')); ?></span>
                                    <span class="info-value" style="color: var(--theme-<?php echo esc_attr($theme); ?>)"><?php echo esc_html(get_field('info_3_valeur')); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button class="modal-nav-btn prev-modal-btn" data-current="<?php echo $modal_index; ?>" style="color: var(--theme-<?php echo esc_attr($theme); ?>);">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                        </button>

                        <div class="modal-dots">
                            <?php for($i=1; $i<=$total_cards; $i++) {
                                $active = ($i == $modal_index) ? 'active' : '';
                                $dot_bg = ($i == $modal_index) ? 'background-color: var(--theme-' . esc_attr($theme) . ');' : '';
                                echo '<span class="dot '.$active.'" style="'.$dot_bg.'"></span>';
                            } ?>
                        </div>

                        <?php if($modal_index < $total_cards): ?>
                            <button class="btn-suivant next-modal-btn" data-current="<?php echo $modal_index; ?>" style="background: var(--theme-<?php echo esc_attr($theme); ?>)">
                                Suivant
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                            </button>
                        <?php else: ?>
                            <button class="btn-suivant close-modal-btn" style="background: var(--theme-<?php echo esc_attr($theme); ?>)">Fermer</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php
            $modal_index++;
        endwhile;
        wp_reset_postdata();
    endif;
    ?>

    <?php wp_footer(); ?>
</body>
</html>