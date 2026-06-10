<?php
// On charge l'en-tête du site (header.php)
get_header();
?>

<main class="site-dynamique">

    <?php
    // --- 1. SECTION HERO (Accueil) ---

    // On récupère l'image de fond et les textes dynamiques depuis ACF
    $image_accueil     = get_field('image_fond_accueil');
    $image_accueil_url = cyclo_get_image_url($image_accueil, 'hero-mobile');

    // On récupère le contenu depuis ACF
    $intro_bienvenue   = get_field('intro_bienvenue');
    $bouton_decouvrir  = get_field('bouton_decouvrir');
    ?>

    <section id="accueil" class="hero-section"
             style="<?php if ($image_accueil_url) echo "background-image: url('" . esc_url($image_accueil_url) . "');"; ?>">

        <div class="overlay-sombre"></div>

        <button class="menu-burger" aria-label="Ouvrir le menu">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"
                 stroke-linecap="round">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>

        <div class="hero-content">
            <h1><?php echo esc_html($intro_bienvenue); ?></h1>
        </div>

        <div class="hero-bottom">
            <span class="decouvrir-text"><?php echo esc_html($bouton_decouvrir); ?></span>
            <a href="#section-activites" class="btn-scroll js-smooth-scroll"
               aria-label="Découvrir la suite des activités">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </a>
        </div>
    </section>


    <?php
    // --- 2. SECTION CARTE INTERACTIVE ---

    // Récupération des configurations globales de la carte via ACF
    $carte_titre             = get_field('carte_titre') ;
    $carte_consigne          = get_field('carte_consigne') ;
    $carte_indication_footer = get_field('carte_indication_footer');
    $btn_modal_carte_texte   = get_field('bouton_modal_carte_texte') ?: 'En savoir plus';
    ?>
    <section id="carte" class="carte-section">
        <div class="carte-header">
            <h2><?php echo esc_html($carte_titre); ?></h2>
            <p class="carte-subtitle"><?php echo esc_html($carte_consigne); ?></p>
        </div>

        <?php
        // On regroupe les données des 4 points d'intérêt dans un tableau
        // Les 'label' vont désormais chercher le vrai nom configuré dans ACF
        $activites = [
                'decouvrir' => [
                        'x' => get_field('decouvrir_x'),
                        'y' => get_field('decouvrir_y'),
                        'desc' => get_field('decouvrir_desc'),
                        'web' => get_field('decouvrir_link_web'),
                        'rs' => get_field('decouvrir_link_rs'),
                        'label' => get_field('titre_decouvrir') ?: 'Découvrir'
                ],
                'proteger' => [
                        'x' => get_field('proteger_x'),
                        'y' => get_field('proteger_y'),
                        'desc' => get_field('proteger_desc'),
                        'web' => get_field('lien_site_internet_proteger'),
                        'rs' => get_field('lien_site_internet_proteger'),
                        'label' => get_field('titre_proteger') ?: 'Protéger'
                ],
                'deguster' => [
                        'x' => get_field('position_horizontale_deguster'),
                        'y' => get_field('position_verticale_deguster'),
                        'desc' => get_field('description_deguster'),
                        'web' => get_field('lien_site_internet_deguster'),
                        'rs' => get_field('lien_reseaux_sociaux_deguster'),
                        'label' => get_field('titre_deguster') ?: 'Déguster'
                ],
                'explorer' => [
                        'x' => get_field('position_horizontale_explorer'),
                        'y' => get_field('position_verticale_explorer'),
                        'desc' => get_field('description_explorer'),
                        'web' => get_field('lien_site_internet_explorer'),
                        'rs' => get_field('lien_reseaux_sociaux_explorer'),
                        'label' => get_field('titre_explorer') ?: 'Explorer'
                ]
        ];
        ?>

        <div class="carte-container" id="carteContainer">
            <div class="carte-scroll-pan" id="carteScrollPan">

                <?php
                // Affichage de l'image de fond de la carte
                $image_carte = get_field('image_de_la_carte');
                if ($image_carte) {
                    echo cyclo_get_image_tag($image_carte, 'large', 'carte-img-landscape');
                }
                ?>

                <?php
                // Création des points sur la carte
                foreach ($activites as $key => $data) :
                    // On vérifie que les coordonnées X et Y existent
                    if ($data['x'] && $data['y']) :
                        ?>
                        <div class="pin-interet"
                             style="top: <?php echo esc_attr($data['y']); ?>%; left: <?php echo esc_attr($data['x']); ?>%;">
                            <div class="pin-label label-<?php echo esc_attr($key); ?>"><?php echo esc_html($data['label']); ?></div>

                            <button class="pin-button btn-<?php echo esc_attr($key); ?> js-open-modal"
                                    data-target="modal-<?php echo esc_attr($key); ?>"
                                    aria-label="Ouvrir le point d'intérêt">
                                <span class="pin-dot"></span>
                            </button>
                        </div>
                    <?php endif; endforeach; ?>

            </div>
        </div>

        <p class="carte-footer-text"><?php echo esc_html($carte_indication_footer); ?></p>

        <?php
        // Génération du code HTML des fenêtres modales de la carte
        foreach ($activites as $key => $data) :
            if ($data['x'] && $data['y']) :
                ?>
                <div id="modal-<?php echo esc_attr($key); ?>" class="modal-bottom-sheet">

                    <div class="modal-overlay-close js-close-modal"></div>

                    <div class="modal-content">
                        <button class="modal-close-btn js-close-modal" aria-label="Fermer la fenêtre">&times;</button>

                        <?php
                        // Image d'en-tête de la modale
                        $img_modal = get_field('img_modal_' . $key);
                        if ($img_modal) :
                            ?>
                            <div class="modal-header-image">
                                <?php echo cyclo_get_image_tag($img_modal, 'format-carte'); ?>
                                <div class="modal-image-gradient"></div>
                            </div>
                        <?php endif; ?>

                        <div class="modal-body">
                            <div class="modal-content-area">
                                    <span class="badge badge-<?php echo esc_attr($key); ?>">
                                        <?php echo esc_html($data['label']); ?>
                                    </span>
                                <h3><?php echo esc_html(get_field('titre_' . $key)); ?></h3>
                                <p class="modal-description">
                                    <?php echo esc_html(get_field('description_' . $key)); ?>
                                </p>
                            </div>
                            <div class="footer-wrapper">
                                <div class="modal-actions">
                                    <?php if (get_field('lien_site_' . $key)) : ?>
                                        <a href="<?php echo esc_url(get_field('lien_site_' . $key)); ?>"
                                           class="btn-modal-link"><?php echo esc_html($btn_modal_carte_texte); ?></a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif;
        endforeach; ?>

    </section>


    <?php
    // --- 3. SECTION CARROUSEL IMMERSIF ---

    // On prépare l'image floutée de fond avec la première activité
    $premier_fond = get_field('act_1_img');
    $premier_fond_url = cyclo_get_image_url($premier_fond, 'format-carte');

    // Récupération des textes de l'en-tête du carrousel via ACF
    $carrousel_titre      = get_field('carrousel_titre');
    $carrousel_sous_titre = get_field('carrousel_sous_titre');
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
                    // On rassemble les données des cartes du carrousel
                    $activites_carousel = [
                            [
                                    'img' => get_field('act_1_img'),
                                    'badge' => get_field('act_1_badge'),
                                    'titre' => get_field('act_1_titre'),
                                    'desc' => get_field('act_1_desc'),
                                    'desc_longue' => get_field('act_1_desc_longue'),
                                    'lien' => get_field('act_1_lien'),
                                    'texte_bouton' => get_field('act_1_texte_bouton')],
                            [
                                    'img' => get_field('act_2_img'),
                                    'badge' => get_field('act_2_badge'),
                                    'titre' => get_field('act_2_titre'),
                                    'desc' => get_field('act_2_desc'),
                                    'desc_longue' => get_field('act_2_desc_longue'),
                                    'lien' => get_field('act_2_lien'),
                                    'texte_bouton' => get_field('act_2_texte_bouton')
                            ],
                            [
                                    'img' => get_field('act_3_img'),
                                    'badge' => get_field('act_3_badge'),
                                    'titre' => get_field('act_3_titre'),
                                    'desc' => get_field('act_3_desc'),
                                    'desc_longue' => get_field('act_3_desc_longue'),
                                    'lien' => get_field('act_3_lien'),
                                    'texte_bouton' => get_field('act_3_texte_bouton')
                            ],
                            [
                                    'img' => get_field('act_4_img'),
                                    'badge' => get_field('act_4_badge'),
                                    'titre' => get_field('act_4_titre'),
                                    'desc' => get_field('act_4_desc'),
                                    'desc_longue' => get_field('act_4_desc_longue'),
                                    'lien' => get_field('act_4_lien'),
                                    'texte_bouton' => get_field('act_4_texte_bouton')
                            ]
                    ];

                    // Création HTML de chaque carte
                    foreach ($activites_carousel as $index => $act) :
                        if ($act['titre']) :

                            $card_img_url = cyclo_get_image_url($act['img'], 'format-carte');

                            // Attribue une couleur spécifique à chaque carte
                            $color = '#27AE60';
                            if ($index === 1) $color = '#E2B93B';
                            if ($index === 2) $color = '#2F80ED';
                            if ($index === 3) $color = '#9B51E0';

                            $texte_bouton_carte = $act['texte_bouton'] ?: 'En savoir plus';
                            ?>

                            <div class="carousel-card-immersive" id="card-act-<?php echo $index; ?>"
                                 data-index="<?php echo $index; ?>" data-color="<?php echo $color; ?>"
                                 data-bg="<?php echo esc_url($card_img_url); ?>">

                                <?php echo cyclo_get_image_tag($act['img'], 'format-carte', 'card-bg-image'); ?>

                                <div class="card-gradient"></div>

                                <div class="card-content-overlay">
                                    <?php if ($act['badge']) : ?>
                                        <span class="badge-text"><?php echo esc_html($act['badge']); ?></span>
                                    <?php endif; ?>
                                    <h3><?php echo esc_html($act['titre']); ?></h3>
                                    <?php if ($act['desc']) : ?>
                                        <p class="card-desc"><?php echo esc_html($act['desc']); ?></p>
                                    <?php endif; ?>

                                    <button class="btn-card-action js-open-modal"
                                            data-target="modal-act-<?php echo $index; ?>"
                                            aria-label="En savoir plus sur l'activité : <?php echo esc_attr($act['titre']); ?>"
                                            style="color: <?php echo $color; ?>;">
                                        <?php echo esc_html($texte_bouton_carte); ?>
                                    </button>
                                </div>
                            </div>

                            <div id="modal-act-<?php echo $index; ?>" class="modal-bottom-sheet"
                                 data-index="<?php echo $index; ?>">
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
                                            <?php if ($act['badge']) : ?>
                                                <span class="badge"><?php echo esc_html($act['badge']); ?></span>
                                            <?php endif; ?>
                                            <h3><?php echo esc_html($act['titre']); ?></h3>
                                            <?php if ($act['desc_longue']) : ?>
                                                <p class="modal-description"><?php echo esc_html($act['desc_longue']); ?></p>
                                            <?php endif; ?>

                                            <?php if ($act['lien']) : ?>
                                                <a href="<?php echo esc_url($act['lien']); ?>" target="_blank"
                                                   class="btn-modal-link"
                                                   style="margin-top: 24px; margin-bottom: 8px; background-color: <?php echo $color; ?> !important; color: #ffffff !important; border: none;">
                                                    <?php echo esc_html($texte_bouton_carte); ?>
                                                </a>
                                            <?php endif; ?>
                                        </div>

                                        <div class="modal-footer-wrapper">
                                            <div class="modal-actions" style="justify-content: center;">
                                                <div class="modal-navigation">

                                                    <button class="modal-arrow prev js-modal-nav"
                                                            data-target="modal-act-<?php echo $index - 1; ?>"
                                                            aria-label="Précédent">
                                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                                             stroke="currentColor" stroke-width="2.5"
                                                             stroke-linecap="round" stroke-linejoin="round">
                                                            <polyline points="15 18 9 12 15 6"></polyline>
                                                        </svg>
                                                    </button>

                                                    <div class="modal-dots">
                                                        <?php
                                                        for ($i = 0; $i < 4; $i++):
                                                            $isActive = ($i === $index) ? 'active' : '';
                                                            ?>
                                                            <span class="dot <?php echo $isActive; ?>"></span>
                                                        <?php endfor; ?>
                                                    </div>

                                                    <button class="modal-arrow next js-modal-nav"
                                                            data-target="modal-act-<?php echo $index + 1; ?>"
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
                    <span class="dot active"></span><span class="dot"></span><span class="dot"></span><span
                            class="dot"></span>
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

</main>


<?php
// --- 4. MENU PLEIN ÉCRAN (Burger) ---
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
            // Affichage des éléments du menu basés sur les cartes du carrousel
            foreach ($activites_carousel as $index => $act) :
                if ($act['titre']) :

                    $color = '#27AE60';
                    if ($index === 1) $color = '#E2B93B';
                    if ($index === 2) $color = '#2F80ED';
                    if ($index === 3) $color = '#9B51E0';
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

<?php
// On charge le pied de page (footer.php)
get_footer();
?>
