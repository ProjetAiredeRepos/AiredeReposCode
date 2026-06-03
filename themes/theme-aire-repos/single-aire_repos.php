<?php
// Charge l'en-tête du site (header.php), incluant la balise <head> et les styles
get_header();
?>

<main class="site-dynamique">

    <?php
    // Récupère l'image de fond globale depuis l'interface ACF
    $image_accueil = get_field('image_de_fond_accueil');
    ?>

    <section id="accueil" class="hero-section" style="background-image: url('<?php echo esc_url($image_accueil); ?>');">
        <div class="overlay-sombre"></div>

        <button class="menu-burger" aria-label="Ouvrir le menu">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
        </button>

        <div class="hero-content">
            <h1>Bienvenue à<br><?php the_title(); // Affiche le titre de la page WordPress ?></h1>
            <p>Prenez le temps de découvrir ce qui vous entoure</p>
        </div>

        <div class="hero-bottom">
            <span class="decouvrir-text">DÉCOUVRIR</span>
            <a href="#section-activites" class="btn-scroll" aria-label="Découvrir la suite des activités">
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
        // Tableau PHP stockant toutes les données des 4 points d'intérêt récupérées via ACF
        $activites = [
                'decouvrir' => [
                        'x' => get_field('decouvrir_x'), // Position horizontale (%)
                        'y' => get_field('decouvrir_y'), // Position verticale (%)
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
                // Affichage de l'image de la carte si elle est renseignée dans ACF
                $image_carte = get_field('image_de_la_carte');
                if ($image_carte) :
                    ?>
                    <img src="<?php echo esc_url($image_carte); ?>" alt="Carte" class="carte-img-landscape">
                <?php endif; ?>

                <?php
                // Boucle générant les points d'intérêt (Pins) sur la carte
                foreach ($activites as $key => $data) :
                    // On vérifie que les coordonnées X et Y existent avant de placer le point
                    if ($data['x'] && $data['y']) :
                        ?>
                        <div class="pin-interet" style="top: <?php echo esc_attr($data['y']); ?>%; left: <?php echo esc_attr($data['x']); ?>%;">
                            <div class="pin-label label-<?php echo esc_attr($key); ?>"><?php echo esc_html($data['label']); ?></div>

                            <button class="pin-button btn-<?php echo esc_attr($key); ?>" data-target="modal-<?php echo esc_attr($key); ?>" aria-label="Ouvrir le point d'intérêt <?php echo esc_attr($data['label']); ?>">
                                <span class="pin-dot"></span>
                            </button>
                        </div>
                    <?php endif; endforeach; ?>

            </div>
        </div>

        <p class="carte-footer-text">← Faites glisser pour explorer la carte →</p>

        <?php
        // Boucle générant les fenêtres modales invisibles pour chaque point de la carte
        foreach ($activites as $key => $data) :
            if ($data['x'] && $data['y']) :
                ?>
                <div id="modal-<?php echo esc_attr($key); ?>" class="modal-bottom-sheet">
                    <div class="modal-overlay-close"></div> <div class="modal-content">
                        <button class="modal-close-btn" aria-label="Fermer la fenêtre">&times;</button>

                        <?php
                        // Image spécifique à la modale
                        $img_modal = get_field('img_modal_' . $key);
                        ?>

                        <?php if($img_modal) : ?>
                            <div class="modal-header-image">
                                <img src="<?php echo esc_url($img_modal); ?>" alt="">
                                <div class="modal-image-gradient"></div>
                            </div>
                        <?php endif; ?>

                        <div class="modal-body">
                                <span class="badge badge-<?php echo esc_attr($key); ?>">
                                    <?php echo esc_html($data['label']); ?>
                                </span>

                            <h3><?php echo esc_html(get_field('titre_' . $key)); ?></h3>

                            <p class="modal-description">
                                <?php echo esc_html(get_field('description_' . $key)); ?>
                            </p>

                            <div class="modal-actions">
                                <?php if(get_field('lien_site_' . $key)) : ?>
                                    <a href="<?php echo esc_url(get_field('lien_site_' . $key)); ?>" class="btn-modal-link">En savoir plus</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; endforeach; ?>

    </section>


    <section id="section-activites" class="carousel-section">

        <?php
        // Récupère l'image de la première activité pour initialiser le fond flouté
        $premier_fond = get_field('act_1_img') ? esc_url(get_field('act_1_img')) : '';
        ?>
        <div class="carousel-blur-bg" style="<?php if($premier_fond) echo "background-image: url('$premier_fond');"; ?>"></div>
        <div class="carousel-dark-overlay"></div>

        <div class="carousel-content-wrapper">
            <div class="carousel-header">
                <h2>Laissez-vous surprendre...</h2>
                <p class="carousel-subtitle">Quatre invitations à prolonger votre escale.</p>
            </div>

            <div class="carousel-container">
                <div class="carousel-track">

                    <?php
                    // Tableau groupant les données ACF des 4 cartes d'activités
                    $activites_carousel = [
                            [
                                    'img'         => get_field('act_1_img'),
                                    'badge'       => get_field('act_1_badge'),
                                    'titre'       => get_field('act_1_titre'),
                                    'desc'        => get_field('act_1_desc'),
                                    'desc_longue' => get_field('act_1_desc_longue'),
                                    'lien'        => get_field('act_1_lien')
                            ],
                            [
                                    'img'         => get_field('act_2_img'),
                                    'badge'       => get_field('act_2_badge'),
                                    'titre'       => get_field('act_2_titre'),
                                    'desc'        => get_field('act_2_desc'),
                                    'desc_longue' => get_field('act_2_desc_longue'),
                                    'lien'        => get_field('act_2_lien')
                            ],
                            [
                                    'img'         => get_field('act_3_img'),
                                    'badge'       => get_field('act_3_badge'),
                                    'titre'       => get_field('act_3_titre'),
                                    'desc'        => get_field('act_3_desc'),
                                    'desc_longue' => get_field('act_3_desc_longue'),
                                    'lien'        => get_field('act_3_lien')
                            ],
                            [
                                    'img'         => get_field('act_4_img'),
                                    'badge'       => get_field('act_4_badge'),
                                    'titre'       => get_field('act_4_titre'),
                                    'desc'        => get_field('act_4_desc'),
                                    'desc_longue' => get_field('act_4_desc_longue'),
                                    'lien'        => get_field('act_4_lien')
                            ]
                    ];

                    // Boucle générant chaque carte du carrousel et sa modale associée
                    foreach ($activites_carousel as $index => $act) :
                        if ($act['titre']) : // Ne s'affiche que si un titre existe
                            $card_img_url = $act['img'] ? esc_url($act['img']) : '';

                            // Attribution d'une couleur thématique en fonction de l'index de la boucle
                            $color = '#27AE60'; // Vert par défaut (Index 0)
                            if ($index === 1) $color = '#E2B93B'; // Jaune
                            if ($index === 2) $color = '#2F80ED'; // Bleu
                            if ($index === 3) $color = '#9B51E0'; // Violet
                            ?>

                            <div class="carousel-card-immersive" id="card-act-<?php echo $index; ?>" data-bg="<?php echo $card_img_url; ?>" data-color="<?php echo $color; ?>">

                                <?php if ($act['img']) : ?>
                                    <img src="<?php echo $card_img_url; ?>" alt="" class="card-bg-image">
                                <?php endif; ?>

                                <div class="card-gradient"></div>

                                <div class="card-content-overlay">
                                    <?php if ($act['badge']) : ?>
                                        <span class="badge-text"><?php echo esc_html($act['badge']); ?></span>
                                    <?php endif; ?>

                                    <h3><?php echo esc_html($act['titre']); ?></h3>

                                    <?php if ($act['desc']) : ?>
                                        <p class="card-desc"><?php echo esc_html($act['desc']); ?></p>
                                    <?php endif; ?>

                                    <button class="btn-card-action" data-target="modal-act-<?php echo $index; ?>" style="color: <?php echo $color; ?>;">
                                        En savoir plus
                                    </button>
                                </div>
                            </div>

                            <div id="modal-act-<?php echo $index; ?>" class="modal-bottom-sheet">
                                <div class="modal-overlay-close"></div>

                                <div class="modal-content">
                                    <button class="modal-close-btn" aria-label="Fermer la fenêtre">&times;</button>

                                    <?php if($act['img']) : ?>
                                        <div class="modal-header-image">
                                            <img src="<?php echo $card_img_url; ?>" alt="">
                                            <div class="modal-image-gradient"></div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="modal-body">
                                        <?php if ($act['badge']) : ?>
                                            <span class="badge" style="background-color: <?php echo $color; ?>;">
                                                <?php echo esc_html($act['badge']); ?>
                                            </span>
                                        <?php endif; ?>

                                        <h3><?php echo esc_html($act['titre']); ?></h3>

                                        <?php if ($act['desc_longue']) : ?>
                                            <p class="modal-description">
                                                <?php echo esc_html($act['desc_longue']); ?>
                                            </p>
                                        <?php endif; ?>

                                        <div class="modal-actions">
                                            <?php if($act['lien']) : ?>
                                                <a href="<?php echo esc_url($act['lien']); ?>" target="_blank" class="btn-modal-link" style="background-color: <?php echo $color; ?>;">
                                                    En savoir plus
                                                </a>
                                            <?php endif; ?>
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
                <button class="carousel-arrow prev" aria-label="Précédent">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                </button>

                <div class="carousel-dots">
                    <span class="dot active"></span>
                    <span class="dot"></span>
                    <span class="dot"></span>
                    <span class="dot"></span>
                </div>

                <button class="carousel-arrow next" aria-label="Suivant">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </button>
            </div>
        </div>
    </section>

</main>


<div id="site-menu-overlay" class="menu-full-screen">
    <div class="menu-inner">
        <div class="menu-header">
            <div>
                <span class="menu-subtitle">MENU</span>
                <h2><?php the_title(); ?></h2>
            </div>
            <button class="menu-close-btn" aria-label="Fermer le menu">&times;</button>
        </div>

        <div class="menu-activities-list">
            <?php
            // Récupération simplifiée des activités pour construire le menu
            $activites_menu = [
                    [
                            'img' => get_field('act_1_img'),
                            'titre' => get_field('act_1_titre'),
                            'desc' => get_field('act_1_desc')
                    ],
                    [
                            'img' => get_field('act_2_img'),
                            'titre' => get_field('act_2_titre'),
                            'desc' => get_field('act_2_desc')
                    ],
                    [
                            'img' => get_field('act_3_img'),
                            'titre' => get_field('act_3_titre'),
                            'desc' => get_field('act_3_desc')
                    ],
                    [
                            'img' => get_field('act_4_img'),
                            'titre' => get_field('act_4_titre'),
                            'desc' => get_field('act_4_desc')
                    ]
            ];

            // Boucle générant les lignes du menu avec les couleurs correspondantes
            foreach ($activites_menu as $index => $act) :
                if ($act['titre']) :
                    $color = '#2D5A37FF';
                    if ($index === 1) $color = '#E2B93B';
                    if ($index === 2) $color = '#2F80ED';
                    if ($index === 3) $color = '#9B51E0';
                    ?>
                    <div class="menu-activity-item" data-target="card-act-<?php echo $index; ?>">
                        <div class="menu-item-color-bar" style="background-color: <?php echo $color; ?>;"></div>

                        <?php if ($act['img']) : ?>
                            <img src="<?php echo esc_url($act['img']); ?>" alt="" class="menu-item-img">
                        <?php endif; ?>

                        <div class="menu-item-text">
                            <h3><?php echo esc_html($act['titre']); ?></h3>
                            <p><?php echo esc_html($act['desc']); ?></p>
                        </div>

                        <button class="menu-item-arrow" style="background-color: <?php echo $color; ?>;" aria-label="Aller à la section <?php echo esc_attr($act['titre']); ?>">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>
                        </button>
                    </div>
                <?php
                endif;
            endforeach;
            ?>
        </div>
    </div>
</div>


<script>
    document.addEventListener("DOMContentLoaded", function() {
        // -- DOM Selectors --
        const burger = document.querySelector('.menu-burger');
        const menu = document.getElementById('site-menu-overlay');
        const closeMenu = document.querySelector('.menu-close-btn');
        const menuItems = document.querySelectorAll('.menu-activity-item');

        const pins = document.querySelectorAll('.pin-button');
        const carouselButtons = document.querySelectorAll('.btn-card-action');
        const modalCloseButtons = document.querySelectorAll('.modal-close-btn, .modal-overlay-close');

        const cards = document.querySelectorAll('.carousel-card-immersive');
        const blurBg = document.querySelector('.carousel-blur-bg');
        const carouselContainer = document.querySelector('.carousel-container');
        const prevArrow = document.querySelector('.carousel-arrow.prev');
        const nextArrow = document.querySelector('.carousel-arrow.next');
        const dots = document.querySelectorAll('.carousel-dots .dot');

        const container = document.getElementById('carteContainer');
        const pan = document.getElementById('carteScrollPan');

        // Centre la carte horizontalement au chargement
        if(container && pan) {
            container.scrollLeft = (pan.offsetWidth - container.offsetWidth) / 2;
        }

        // Fonction pour afficher une modale
        function openModal(targetId) {
            const modal = document.getElementById(targetId);
            if (modal) {
                modal.classList.add('is-open');
                document.body.style.overflow = 'hidden';
            }
        }

        // Ouvre le menu burger
        if (burger && menu) {
            burger.addEventListener('click', function(e) {
                e.preventDefault();
                menu.classList.add('is-open');
                document.body.style.overflow = 'hidden';
            });
        }

        // Ferme le menu burger
        if (closeMenu && menu) {
            closeMenu.addEventListener('click', function(e) {
                e.preventDefault();
                menu.classList.remove('is-open');
                document.body.style.overflow = '';
            });
        }

        // Navigation depuis une ligne du menu vers la carte du carrousel correspondante
        menuItems.forEach(item => {
            item.addEventListener('click', function() {
                const targetCardId = this.getAttribute('data-target');
                const targetCard = document.getElementById(targetCardId);

                const index = targetCardId.replace('card-act-', '');
                const targetModalId = 'modal-act-' + index;

                if (menu) {
                    menu.classList.remove('is-open');
                    document.body.style.overflow = '';
                }

                if (targetCard) {
                    targetCard.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center',
                        inline: 'center'
                    });
                }

                setTimeout(() => {
                    openModal(targetModalId);
                }, 300);
            });
        });

        // Ouvre les modales depuis les pins de la carte
        pins.forEach(pin => {
            pin.addEventListener('click', function() {
                const modalId = this.getAttribute('data-target');
                openModal(modalId);
            });
        });

        // Ouvre les modales depuis les boutons des cartes du carrousel
        carouselButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const modalId = this.getAttribute('data-target');
                openModal(modalId);
            });
        });

        // Gère la fermeture de toutes les modales
        modalCloseButtons.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const activeModal = this.closest('.modal-bottom-sheet');
                if (activeModal) {
                    activeModal.classList.remove('is-open');
                    document.body.style.overflow = '';
                }
            });
        });

        // Navigation du carrousel via les flèches (Desktop)
        if (carouselContainer && prevArrow && nextArrow) {
            function getScrollStep() {
                const firstCard = carouselContainer.querySelector('.carousel-card-immersive');
                return firstCard ? firstCard.offsetWidth + 20 : 300;
            }

            prevArrow.addEventListener('click', function() {
                carouselContainer.scrollBy({ left: -getScrollStep(), behavior: 'smooth' });
            });

            nextArrow.addEventListener('click', function() {
                carouselContainer.scrollBy({ left: getScrollStep(), behavior: 'smooth' });
            });
        }

        // CORRECTION DE L'ERREUR SYNTAXE (Le mot "flou" qui traînait)
        // Observer pour les cartes du carrousel (gère la carte active, fond flou et couleurs)
        if (cards.length > 0 && blurBg) {
            const observerOptions = {
                root: carouselContainer,
                threshold: 0.5
            };

            const observer = new IntersectionObserver(function (entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-active');

                        // Change l'image floutée en arrière-plan
                        const newBg = entry.target.getAttribute('data-bg');
                        if (newBg) {
                            blurBg.style.backgroundImage = `url('${newBg}')`;
                        }

                        // Mise à jour de la couleur thématique
                        const newColor = entry.target.getAttribute('data-color');
                        const wrapper = carouselContainer.closest('.carousel-content-wrapper');

                        if (wrapper && newColor) {
                            wrapper.style.setProperty('--nav-active-color', newColor);
                        }

                        // Mise à jour des points de navigation (dots)
                        const cardIndex = Array.from(cards).indexOf(entry.target);
                        dots.forEach((dot, idx) => {
                            if (idx === cardIndex) {
                                dot.classList.add('active');
                            } else {
                                dot.classList.remove('active');
                            }
                        });
                    } else {
                        entry.target.classList.remove('is-active');
                    }
                });
            }, observerOptions);

            cards.forEach(card => {
                observer.observe(card);
            });
        }
    });
</script>

<?php
// Charge le pied de page du site (footer.php), incluant la fermeture des balises body/html
get_footer();
?>
