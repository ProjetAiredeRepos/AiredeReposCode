<?php
// On charge l'en-tête du site (header.php)
get_header();
?>

<main class="site-dynamique">

    <?php
    // ==========================================================================
    // --- 1. SECTION HERO (Accueil) ---
    // ==========================================================================

    $image_accueil     = get_field('image_fond_accueil');
    $image_accueil_url = cyclo_get_image_url($image_accueil, 'hero-mobile');
    $intro_bienvenue   = get_field('intro_bienvenue');
    $bouton_decouvrir  = get_field('bouton_decouvrir');
    ?>

    <section id="accueil" class="hero-section"
             style="<?php if ($image_accueil_url) echo "background-image: url('" . esc_url($image_accueil_url) . "');"; ?>">

        <div class="overlay-sombre"></div>


        <?php
        // ==========================================================================
        // --- ENLEVER LES COMMENTAIRES LORS DE LA PHASE 2 !
        // ==========================================================================
        /*  <button class="menu-burger" aria-label="Ouvrir le menu">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"
                    stroke-linecap="round">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
        */ ?>

        <div class="hero-content">
            <h1><?php echo wp_kses_post($intro_bienvenue); ?></h1>
        </div>

        <div class="hero-bottom js-hero-routing">
            <span class="decouvrir-text"><?php echo esc_html($bouton_decouvrir); ?></span>
            <a href="#" class="btn-scroll js-smooth-scroll"
               aria-label="Découvrir la suite des activités">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </a>
        </div>
    </section>


    <?php
    // ==========================================================================
    // --- 2. SECTION CARTE INTERACTIVE (Les POI dynamiques) ---
    // ==========================================================================

    $carte_titre             = get_field('carte_titre');
    $carte_consigne          = get_field('carte_consigne');
    $carte_indication_footer = get_field('carte_indication_footer');

    $aire_id = get_the_ID();

    $activites_carte = [];
    $poi_selectionnes = get_field('choix_des_poi', $aire_id);

    if ($poi_selectionnes) {
        foreach ($poi_selectionnes as $poi) {
            $poi_id = $poi->ID;
            $x = get_field('position_x', $poi_id);
            $y = get_field('position_y', $poi_id);

            if ($x !== null && $y !== null) {
                $activites_carte[] = [
                        'key'          => sanitize_title($poi->post_title),
                        'x'            => floatval($x),
                        'y'            => floatval($y),
                        'label'        => esc_html($poi->post_title),
                        'logo'         => intval(get_field('logo', $poi_id)),
                        'titre_modale' => get_field('titre_modale', $poi_id) ? esc_html(get_field('titre_modale', $poi_id)) : esc_html($poi->post_title),
                        'badge'        => esc_html(get_field('badge', $poi_id)),
                        'desc'         => wp_kses_post(get_field('description_longue', $poi_id)),
                        'web'          => esc_url(get_field('lien_web', $poi_id)),
                        'img'          => intval(get_field('image_modale', $poi_id)),
                        'texte_bouton' => esc_html(get_field('texte_bouton', $poi_id)) ?: 'En savoir plus',
                        'color'        => esc_html(get_field('couleur_theme', $poi_id)) ?: '#2F80ED',
                        'facebook'     => esc_url(get_field('lien_facebook', $poi_id)),
                        'instagram'    => esc_url(get_field('lien_instagram', $poi_id))
                ];
            }
        }
    }

    $total_actives = count($activites_carte);
    ?>
    <section id="carte" class="carte-section">
        <div class="carte-header">
            <h2><?php echo esc_html($carte_titre); ?></h2>
            <p class="carte-subtitle"><?php echo esc_html($carte_consigne); ?></p>
        </div>

        <div class="carte-container" id="carteContainer">
            <div class="carte-scroll-pan" id="carteScrollPan">

                <?php
                $image_carte = get_field('image_de_la_carte');
                if ($image_carte) {
                    echo cyclo_get_image_tag($image_carte, 'large', 'carte-img-landscape');
                }
                ?>

                <?php
                // Génération dynamique des PINS sur la carte
                foreach ($activites_carte as $index => $data) :

                    $couleur_pin = esc_attr($data['color']);
                    ?>
                    <div class="pin-interet"
                         style="top: <?php echo esc_attr($data['y']); ?>%; left: <?php echo esc_attr($data['x']); ?>%;">

                        <div class="pin-label" style="background-color: <?php echo $couleur_pin; ?>;">
                            <?php echo esc_html($data['label']); ?>
                        </div>

                        <button class="pin-button js-open-modal"
                                data-target="modal-carte-<?php echo $index; ?>"
                                aria-label="Ouvrir le point d'intérêt : <?php echo esc_attr($data['label']); ?>">
                            <span class="pin-dot" style="background-color: <?php echo $couleur_pin; ?>;"></span>
                        </button>
                    </div>
                <?php endforeach; ?>

            </div>
        </div>

        <p class="carte-footer-text"><?php echo esc_html($carte_indication_footer); ?></p>

        <?php
        // Génération dynamique des FENÊTRES MODALES DE LA CARTE
        foreach ($activites_carte as $index => $data) :
            $prev_index = ($index === 0) ? $total_actives - 1 : $index - 1;
            $next_index = ($index === $total_actives - 1) ? 0 : $index + 1;

            $color = $data['color'];
            ?>

            <div id="modal-carte-<?php echo $index; ?>" class="modal-bottom-sheet" data-index="<?php echo $index; ?>" style="--modal-theme-color: <?php echo esc_attr($color); ?>;">

                <div class="modal-overlay-close js-close-modal"></div>

                <div class="modal-content">
                    <button class="modal-close-btn js-close-modal" aria-label="Fermer la fenêtre">&times;</button>

                    <?php if ($data['img']) : ?>
                        <div class="modal-header-image">
                            <?php echo cyclo_get_image_tag($data['img'], 'format-carte'); ?>
                            <div class="modal-image-gradient"></div>
                        </div>
                    <?php endif; ?>

                    <div class="modal-body">
                        <div class="modal-content-area">
                            <div class="header-modal">
                                <?php if ($data['logo']) : ?>
                                    <div class="logo-modal">
                                        <?php echo cyclo_get_image_tag($data['logo'], 'medium', 'modal-logo'); ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($data['badge']) : ?>
                                    <!--
                                      CTO FIX : Inversion du badge.
                                      Fond 100% opaque avec la couleur dynamique.
                                      Texte forcé en blanc (#FFFFFF) pour un contraste optimal (WCAG).
                                      Sécurité : Ajout de esc_attr() sur la variable de couleur.
                                    -->
                                    <span class="badge" style="background-color: <?php echo esc_attr($color); ?>; color: #FFFFFF;">
        <?php echo esc_html($data['badge']); ?>
    </span>
                                <?php endif; ?>
                            </div>

                            <h3><?php echo esc_html($data['titre_modale']); ?></h3>

                            <?php if ($data['desc']) : ?>
                                <div class="modal-description">
                                    <?php echo $data['desc']; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($data['facebook'] || $data['instagram']) : ?>
                                <div class="modal-social-links" style="display: flex; gap: 24px; margin-bottom: 8px; margin-top: 10px; justify-content: flex-start; align-items: center;">

                                    <?php if ($data['facebook']) : ?>
                                        <a href="<?php echo $data['facebook']; ?>" target="_blank" aria-label="Suivre sur Facebook" style="color: <?php echo $color; ?>; display: inline-flex; align-items: center; transition: opacity 0.2s;">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path>
                                            </svg>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($data['instagram']) : ?>
                                        <a href="<?php echo $data['instagram']; ?>" target="_blank" aria-label="Suivre sur Instagram" style="color: <?php echo $color; ?>; display: inline-flex; align-items: center; transition: opacity 0.2s;">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                                                <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                                                <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                                            </svg>
                                        </a>
                                    <?php endif; ?>

                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="footer-wrapper">
                            <div class="modal-actions">
                                <?php if ($data['web']) : ?>
                                    <a href="<?php echo esc_url($data['web']); ?>" target="_blank"
                                       class="btn-modal-link"
                                       style="margin-top: 24px; margin-bottom: 8px; background-color: <?php echo $color; ?> !important; color: #ffffff !important; border: none;">
                                        <?php echo esc_html($data['texte_bouton']); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="modal-footer-wrapper">
                            <div class="modal-actions" style="justify-content: center;">
                                <div class="modal-navigation">

                                    <button class="modal-arrow prev js-modal-nav"
                                            data-target="modal-carte-<?php echo $prev_index; ?>"
                                            aria-label="Précédent">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                             stroke="currentColor" stroke-width="2.5"
                                             stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="15 18 9 12 15 6"></polyline>
                                        </svg>
                                    </button>

                                    <div class="modal-dots">
                                        <?php
                                        for ($i = 0; $i < $total_actives; $i++):
                                            $isActive = ($i === $index) ? 'active' : '';
                                            ?>
                                            <span class="dot <?php echo $isActive; ?>"></span>
                                        <?php endfor; ?>
                                    </div>

                                    <button class="modal-arrow next js-modal-nav"
                                            data-target="modal-carte-<?php echo $next_index; ?>"
                                            aria-label="Suivant">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                             stroke="currentColor" stroke-width="2.5"
                                             stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="9 18 15 12 9 6"></polyline>
                                        </svg>
                                    </button>

                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        <?php endforeach; ?>

    </section>


    <?php
    // ==========================================================================
    // --- 3. SECTION CARROUSEL IMMERSIF (Génération Dynamique des Activités) ---
    // ==========================================================================

    $premier_fond = get_field('image_fond_accueil'); // On utilise l'image de l'aire par défaut si pas d'activités
    $carrousel_titre      = get_field('carrousel_titre');
    $carrousel_sous_titre = get_field('carrousel_sous_titre');

    // 1. On récupère les activités liées via le champ relationnel ACF
    $activites_carousel = [];
    $activites_selectionnees = get_field('choix_des_activites', get_the_ID());

    if ($activites_selectionnees) {
        foreach ($activites_selectionnees as $act_post) {
            $act_id = $act_post->ID;

            $titre_wp = get_the_title($act_id);

            $titre_acf_interne = get_field('titre_modal', $act_id);

            $activites_carousel[] = [
                    'img'            => get_field('image_modale', $act_id),
                    'titre'          => $titre_acf_interne ? $titre_acf_interne : $titre_wp,
                    'titre_modal'    => $titre_wp,               // Utilisé SUR la carte ("Pause Gourmande")
                    'desc_longue'    => get_field('description_longue', $act_id),
                    'lien'           => get_field('lien_web', $act_id),
                    'texte_bouton'   => get_field('texte_bouton', $act_id) ?: 'En savoir plus',
                    'color'          => get_field('couleur_theme', $act_id) ?: '#27AE60',
                    'texte_inter_1'  => get_field('texte_inter_1', $act_id),
                    'bouton_bonus_1' => get_field('bouton_bonus_1', $act_id),
                    'texte_inter_2'  => get_field('texte_inter_2', $act_id),
                    'bouton_bonus_2' => get_field('bouton_bonus_2', $act_id),
                    'texte_inter_3'  => get_field('texte_inter_3', $act_id),
                    'bouton_bonus_3' => get_field('bouton_bonus_3', $act_id)
            ];
        }
    }

    $total_activites = count($activites_carousel);

    // On récupère l'image de la première activité pour le fond flouté, s'il y en a une
    if ($total_activites > 0 && $activites_carousel[0]['img']) {
        $premier_fond_url = cyclo_get_image_url($activites_carousel[0]['img'], 'format-carte');
    } else {
        $premier_fond_url = cyclo_get_image_url($premier_fond, 'format-carte');
    }
    ?>
    <?php
    // Si on a au moins 1 activité, on affiche TOUTE la section
    if ($total_activites > 0) :
        ?>
        <section id="section-activites" class="carousel-section">

            <div class="carousel-blur-bg"
                 style="<?php if ($premier_fond_url) echo "background-image: url('" . esc_url($premier_fond_url) . "');"; ?>"></div>
            <div class="carousel-dark-overlay"></div>

            <div class="carousel-content-wrapper">
                <div class="carousel-header">
                    <h2><?php echo esc_html($carrousel_titre); ?></h2>
                    <p class="carousel-subtitle"><?php echo esc_html($carrousel_sous_titre); ?></p>
                </div>

                <div class="carousel-container js-carousel-container">
                    <div class="carousel-track">

                        <?php

                        foreach ($activites_carousel as $index => $act) :
                            if ($act['titre']) :
                                $card_img_url = cyclo_get_image_url($act['img'], 'format-carte');

                                $color = $act['color'];
                                $texte_bouton_carte = $act['texte_bouton'];

                                // Calcul dynamique pour la navigation Précédent/Suivant dans les modales
                                $prev_index = ($index === 0) ? $total_activites - 1 : $index - 1;
                                $next_index = ($index === $total_activites - 1) ? 0 : $index + 1;
                                ?>

                                <div class="carousel-card-immersive" id="card-act-<?php echo $index; ?>"
                                     data-index="<?php echo $index; ?>" data-color="<?php echo esc_attr($color); ?>"
                                     data-bg="<?php echo esc_url($card_img_url); ?>">

                                    <?php echo cyclo_get_image_tag($act['img'], 'format-carte', 'card-bg-image'); ?>

                                    <div class="card-gradient"></div>

                                    <div class="card-content-overlay">

                                        <h3><?php echo esc_html($act['titre_modal']); ?></h3>

                                        <button class="btn-card-action js-open-modal"
                                                data-target="modal-act-<?php echo $index; ?>"
                                                aria-label="En savoir plus sur l'activité : <?php echo esc_attr($act['titre']); ?>"
                                                style="color: <?php echo esc_attr($color); ?>;">
                                            <?php echo esc_html($texte_bouton_carte); ?>
                                        </button>
                                    </div>
                                </div>

                                <div id="modal-act-<?php echo $index; ?>" class="modal-bottom-sheet"
                                     data-index="<?php echo $index; ?>" style="--modal-theme-color: <?php echo esc_attr($color); ?>;">
                                    <div class="modal-overlay-close js-close-modal"></div>
                                    <div class="modal-content">
                                        <button class="modal-close-btn js-close-modal" aria-label="Fermer">&times;</button>

                                        <?php if ($act['img']) : ?>
                                            <div class="modal-header-image">
                                                <?php echo cyclo_get_image_tag($act['img'], 'format-carte'); ?>
                                                <div class="modal-image-gradient"></div>
                                            </div>
                                        <?php endif; ?>

                                        <div class="modal-body">
                                            <div class="modal-content-area">
                                                <h3><?php echo esc_html($act['titre']); ?></h3>
                                                <?php if ($act['desc_longue']) : ?>
                                                    <div class="modal-description"><?php echo wp_kses_post($act['desc_longue']); ?></div>
                                                <?php endif; ?>

                                                <div class="modal-actions-list">

                                                    <?php if ($act['lien']) : ?>
                                                        <a href="<?php echo esc_url($act['lien']); ?>" target="_blank" class="btn-modal-link dynamic-bg">
                                                            <?php echo esc_html($texte_bouton_carte); ?>
                                                        </a>
                                                    <?php endif; ?>

                                                    <?php if ($act['texte_inter_1']) : ?>
                                                        <p class="modal-text-inter"><?php echo esc_html($act['texte_inter_1']); ?></p>
                                                    <?php endif; ?>

                                                    <?php if ($act['bouton_bonus_1']) : ?>
                                                        <a href="<?php echo esc_url($act['bouton_bonus_1']['url']); ?>" target="<?php echo esc_attr($act['bouton_bonus_1']['target']); ?>" class="btn-modal-link dynamic-bg">
                                                            <?php echo esc_html($act['bouton_bonus_1']['title']); ?>
                                                        </a>
                                                    <?php endif; ?>

                                                    <?php if ($act['texte_inter_2']) : ?>
                                                        <p class="modal-text-inter"><?php echo esc_html($act['texte_inter_2']); ?></p>
                                                    <?php endif; ?>

                                                    <?php if ($act['bouton_bonus_2']) : ?>
                                                        <a href="<?php echo esc_url($act['bouton_bonus_2']['url']); ?>" target="<?php echo esc_attr($act['bouton_bonus_2']['target']); ?>" class="btn-modal-link dynamic-bg">
                                                            <?php echo esc_html($act['bouton_bonus_2']['title']); ?>
                                                        </a>
                                                    <?php endif; ?>

                                                    <?php if ($act['texte_inter_3']) : ?>
                                                        <p class="modal-text-inter"><?php echo esc_html($act['texte_inter_3']); ?></p>
                                                    <?php endif; ?>

                                                    <?php if ($act['bouton_bonus_3']) : ?>
                                                        <a href="<?php echo esc_url($act['bouton_bonus_3']['url']); ?>" target="<?php echo esc_attr($act['bouton_bonus_3']['target']); ?>" class="btn-modal-link dynamic-bg">
                                                            <?php echo esc_html($act['bouton_bonus_3']['title']); ?>
                                                        </a>
                                                    <?php endif; ?>

                                                </div>
                                            </div>

                                            <div class="modal-footer-wrapper">
                                                <div class="modal-actions" style="justify-content: center;">
                                                    <div class="modal-navigation">

                                                        <button class="modal-arrow prev js-modal-nav"
                                                                data-target="modal-act-<?php echo $prev_index; ?>"
                                                                aria-label="Précédent">
                                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                                                 stroke="currentColor" stroke-width="2.5"
                                                                 stroke-linecap="round" stroke-linejoin="round">
                                                                <polyline points="15 18 9 12 15 6"></polyline>
                                                            </svg>
                                                        </button>

                                                        <div class="modal-dots">
                                                            <?php
                                                            for ($i = 0; $i < $total_activites; $i++):
                                                                $isActive = ($i === $index) ? 'active' : '';
                                                                ?>
                                                                <span class="dot <?php echo $isActive; ?>"></span>
                                                            <?php endfor; ?>
                                                        </div>

                                                        <button class="modal-arrow next js-modal-nav"
                                                                data-target="modal-act-<?php echo $next_index; ?>"
                                                                aria-label="Suivant">
                                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                                                 stroke="currentColor" stroke-width="2.5"
                                                                 stroke-linecap="round" stroke-linejoin="round">
                                                                <polyline points="9 18 15 12 9 6"></polyline>
                                                            </svg>
                                                        </button>

                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            <?php
                            endif;
                        endforeach;
                        ?>

                    </div>
                </div>

                <div class="carousel-navigation">
                    <button class="carousel-arrow prev js-car-prev" aria-label="Précédent">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                    </button>
                    <div class="carousel-dots">
                        <?php for ($i = 0; $i < $total_activites; $i++): ?>
                            <span class="dot <?php echo $i === 0 ? 'active' : ''; ?>"></span>
                        <?php endfor; ?>
                    </div>
                    <button class="carousel-arrow next js-car-next" aria-label="Suivant">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </button>
                </div>
            </div>
        </section>
    <?php endif; // Fin de la vérification des activités ?>

    <?php
    // ==========================================================================
    // --- SECTION APPEL À L'ACTION : QUESTIONNAIRE ---
    // ==========================================================================

    $titre_q    = get_field('titre_questionnaire');
    $sous_titre_q   = get_field('sous_titre_questionnaire');
    $bouton_q   = get_field('bouton_questionnaire');
    $couleur_bouton = get_field('couleur_bouton_questionnarie');

    // On vérifie qu'au moins le titre ou le bouton est rempli pour afficher la section
    if ( $titre_q || $bouton_q ) :
        ?>
        <section class="cta-questionnaire-section">
            <div class="cta-questionnaire-container">

                <div class="cta-questionnaire-text">
                    <?php if ( $titre_q ) : ?>
                        <h2><?php echo esc_html( $titre_q ); ?></h2>
                    <?php endif; ?>

                    <?php if ( $sous_titre_q ) : ?>
                        <p><?php echo esc_html( $sous_titre_q ); ?></p>
                    <?php endif; ?>
                </div>

                <?php if ( $bouton_q ) : ?>
                    <a href="<?php echo esc_url( $bouton_q['url'] ); ?>"
                       target="<?php echo esc_attr( $bouton_q['target'] ); ?>"
                       class="btn-modal-link"
                       style="background-color: <?php echo esc_attr($couleur_bouton); ?>">
                        <?php echo esc_html( $bouton_q['title'] ); ?>
                    </a>
                <?php endif; ?>

            </div>
        </section>
    <?php endif; ?>

    <?php
    // ==========================================================================
    // --- 4. MENU PLEIN ÉCRAN (Burger) ---
    // ==========================================================================
    /*
    $menu_titre_badge = get_field('menu_titre_badge') ?: 'MENU';

    ?>
       <div id="site-menu-overlay" class="menu-full-screen">
            <div class="menu-inner">
                <div class="menu-header">
                    <div>
                        <span class="menu-subtitle"><?php echo esc_html($menu_titre_badge); ?></span>
                        <h2><?php the_title(); ?></h2>
                    </div>
                    <button class="menu-close-btn js-close-menu" aria-label="Fermer le menu">&times;</button>
                </div>

                <div class="menu-activities-list">
                    <?php
                    foreach ($activites_carousel as $index => $act) :
                        if ($act['titre']) :

                            // CORRECTION CTO : On supprime les 'if' en dur, on lie ça au champ ACF
                            $color = $act['color'];
                            ?>
                            <div class="menu-activity-item js-menu-item" data-target="card-act-<?php echo $index; ?>"
                                 data-index="<?php echo $index; ?>">

                                <div class="menu-item-color-bar" style="background-color: <?php echo $color; ?>;"></div>

                                <?php echo cyclo_get_image_tag($act['img'], 'format-mini', 'menu-item-img'); ?>

                                <div class="menu-item-text">
                                    <h3><?php echo esc_html($act['titre']); ?></h3>
                                    <p><?php echo esc_html($act['desc']); ?></p>
                                </div>

                                <button class="menu-item-arrow" aria-label="Voir l'activité"
                                        style="background-color: <?php echo $color; ?>;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white"
                                         stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="7" y1="17" x2="17" y2="7"></line>
                                        <polyline points="7 7 17 7 17 17"></polyline>
                                    </svg>
                                </button>

                            </div>
                        <?php
                        endif;
                    endforeach;
                    ?>
                </div>
            </div>
        </div>
        */
    ?>

    <script>
        // Le téléphone mémorise l'URL exacte de l'aire en cours
        localStorage.setItem('cyclo_last_aire', window.location.href);
    </script>

</main>

<?php
// On charge le pied de page (footer.php)
get_footer();
?>
