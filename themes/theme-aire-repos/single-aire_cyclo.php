<?php get_header(); ?>

<main class="site-dynamique">

    <?php
    //On récupère l'URL de l'image stockée dans ACF
    $image_accueil = get_field('image_fond_accueil');
    ?>

    <section id="accueil" class="hero-section" style="background-image: url('<?php echo esc_url($image_accueil); ?>');">
        <div class="overlay-sombre"></div>

        <button class="menu-burger" aria-label="Ouvrir le menu">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
        </button>

        <div class="hero-content">
            <h1>Bienvenue à<br><?php the_title(); ?></h1>
            <p>Prenez le temps de découvrir ce qui vous entoure</p>
        </div>

        <div class="hero-bottom">
            <span class="decouvrir-text">DÉCOUVRIR</span>
            <a href="#carte" class="btn-scroll">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </a>
        </div>
    </section>

    <section id="carte" class="carte-section">

        <div class="carte-header">
            <h2>Découverte du milieu</h2>
            <p class="carte-subtitle">Touchez un point sur la carte pour explorer.</p>
        </div>

        <?php
        // 1. On récupère tes 4 activités avec TES slugs ACF exacts
        $activites = [
                'decouvrir' => [
                        'x' => get_field('decouvrir_x'),
                        'y' => get_field('decouvrir_y'),
                        'desc' => get_field('decouvrir_desc'),
                        'web' => get_field('decouvrir_link_web'),
                        'rs' => get_field('decouvrir_link_rs'),
                        'label' => 'Découvrir'
                ],
                'proteger' => [
                        'x' => get_field('proteger_x'),
                        'y' => get_field('proteger_y'),
                        'desc' => get_field('proteger_desc'),
                        'web' => get_field('lien_site_internet_proteger'),
                        'rs' => get_field('lien_reseaux_sociaux_proteger'),
                        'label' => 'Protéger'
                ],
                'deguster' => [
                        'x' => get_field('position_horizontale_deguster'),
                        'y' => get_field('position_verticale_deguster'),
                        'desc' => get_field('description_deguster'),
                        'web' => get_field('lien_site_internet_deguster'),
                        'rs' => get_field('lien_reseaux_sociaux_deguster'),
                        'label' => 'Déguster'
                ],
                'explorer' => [
                        'x' => get_field('position_horizontale_explorer'),
                        'y' => get_field('position_verticale_explorer'),
                        'desc' => get_field('description_explorer'),
                        'web' => get_field('lien_site_internet_explorer'),
                        'rs' => get_field('lien_reseaux_sociaux_explorer'),
                        'label' => 'Explorer'
                ]
        ];
        ?>

        <div class="carte-container" id="carteContainer">
            <div class="carte-scroll-pan" id="carteScrollPan">

                <?php
                $image_carte = get_field('image_de_la_carte');
                if ($image_carte) :
                    ?>
                    <img src="<?php echo esc_url($image_carte); ?>" alt="Carte" class="carte-img-landscape">
                <?php endif; ?>

                <?php foreach ($activites as $key => $data) :
                    // On affiche le point seulement si des coordonnées ont été rentrées
                    if ($data['x'] && $data['y']) :
                        ?>
                        <div class="pin-interet" style="top: <?php echo esc_attr($data['y']); ?>%; left: <?php echo esc_attr($data['x']); ?>%;">
                            <div class="pin-label label-<?php echo esc_attr($key); ?>"><?php echo esc_html($data['label']); ?></div>

                            <button class="pin-button btn-<?php echo esc_attr($key); ?>" data-target="modal-<?php echo esc_attr($key); ?>">
                                <span class="pin-dot"></span>
                            </button>
                        </div>
                    <?php endif; endforeach; ?>

            </div>
        </div>

        <p class="carte-footer-text">← Faites glisser pour explorer la carte →</p>

        <?php foreach ($activites as $key => $data) :
            if ($data['x'] && $data['y']) : // On génère la modal si le point existe
                ?>
                <div id="modal-<?php echo esc_attr($key); ?>" class="modal-bottom-sheet">
                    <div class="modal-overlay-close"></div>
                    <div class="modal-content">
                        <button class="modal-close-btn">&times;</button>
                        <div class="modal-body">
                            <span class="badge badge-<?php echo esc_attr($key); ?>">À <?php echo strtoupper($data['label']); ?></span>

                            <h3>Le projet de l'aire</h3>

                            <?php if ($data['desc']) : ?>
                                <p class="modal-description"><?php echo nl2br(esc_html($data['desc'])); ?></p>
                            <?php endif; ?>

                            <div class="modal-actions">
                                <?php if ($data['web']) : ?>
                                    <a href="<?php echo esc_url($data['web']); ?>" target="_blank" class="btn-modal-link track-click" data-site="<?php the_title(); ?>" data-type="web">
                                        Visiter le site internet ↗
                                    </a>
                                <?php endif; ?>

                                <?php if ($data['rs']) : ?>
                                    <a href="<?php echo esc_url($data['rs']); ?>" target="_blank" class="btn-modal-link btn-so track-click" data-site="<?php the_title(); ?>" data-type="rs">
                                        Rejoindre sur les réseaux 📱
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; endforeach; ?>

    </section>

    <section id="section-activites" class="carousel-section">

        <div class="carousel-header">
            <h2>Les activités à proximité</h2>
        </div>

        <div class="carousel-container">
            <div class="carousel-track">

                <?php
                // Configuration des données des cartes via ACF
                $activites_carousel = [
                        [
                                'img'   => get_field('act_1_img'),
                                'badge' => get_field('act_1_badge'),
                                'titre' => get_field('act_1_titre'),
                                'desc'  => get_field('act_1_desc'),
                                'lien'  => get_field('act_1_lien')
                        ],
                        [
                                'img'   => get_field('act_2_img'),
                                'badge' => get_field('act_2_badge'),
                                'titre' => get_field('act_2_titre'),
                                'desc'  => get_field('act_2_desc'),
                                'lien'  => get_field('act_2_lien')
                        ],
                        [
                                'img'   => get_field('act_3_img'),
                                'badge' => get_field('act_3_badge'),
                                'titre' => get_field('act_3_titre'),
                                'desc'  => get_field('act_3_desc'),
                                'lien'  => get_field('act_3_lien')
                        ],
                        [
                                'img'   => get_field('act_4_img'),
                                'badge' => get_field('act_4_badge'),
                                'titre' => get_field('act_4_titre'),
                                'desc'  => get_field('act_4_desc'),
                                'lien'  => get_field('act_4_lien')
                        ]
                ];

                // Boucle de génération des cartes d'activités
                foreach ($activites_carousel as $act) :
                    // Affichage conditionnel si le titre existe
                    if ($act['titre']) :
                        ?>
                        <div class="carousel-card">
                            <div class="card-image-wrapper">
                                <?php if ($act['img']) : ?>
                                    <img src="<?php echo esc_url($act['img']); ?>" alt="<?php echo esc_attr($act['titre']); ?>" class="card-image">
                                <?php endif; ?>

                                <?php if ($act['badge']) : ?>
                                    <span class="card-badge"><?php echo esc_html($act['badge']); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="card-content">
                                <h3><?php echo esc_html($act['titre']); ?></h3>

                                <?php if ($act['desc']) : ?>
                                    <p><?php echo esc_html($act['desc']); ?></p>
                                <?php endif; ?>

                                <?php if ($act['lien']) : ?>
                                    <a href="<?php echo esc_url($act['lien']); ?>" target="_blank" class="card-link track-click" data-site="<?php the_title(); ?>" data-type="activite">
                                        En savoir plus
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php
                    endif;
                endforeach;
                ?>

            </div>
        </div>
    </section>

</main>

<?php get_footer(); ?>