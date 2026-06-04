<?php
// Je charge l'en-tête de mon site WordPress (header.php), ce qui inclut la balise <head> et mes fichiers CSS/JS déclarés.
get_header();
?>

<main class="site-dynamique">

    <?php
    // --- 1. SECTION HERO (Accueil) ---
    // 1. Je récupère d'abord l'URL brute saisie dans ACF
    $image_accueil_raw = get_field('image_fond_accueil');
    $image_accueil_opti = '';

    if ($image_accueil_raw) {
        // 2. Je convertis l'URL en ID de média WordPress pour accéder aux différents formats
        $image_id = attachment_url_to_postid($image_accueil_raw);

        // 3. OPTIMISATION PERFORMANCE : Je demande le format 'hero-mobile' (déclaré dans functions.php)
        // Si l'ID n'est pas trouvé, je bascule sur l'image brute par sécurité (fallback).
        $image_accueil_opti = $image_id ? wp_get_attachment_image_url($image_id, 'hero-mobile') : $image_accueil_raw;
    }
    ?>

    <section id="accueil" class="hero-section" style="background-image: url('<?php echo esc_url($image_accueil_opti); ?>');">
        <div class="overlay-sombre"></div>

        <button class="menu-burger" aria-label="Ouvrir le menu">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
        </button>

        <div class="hero-content">
            <h1>Bienvenue à<br><?php the_title(); ?></h1>
        </div>

        <div class="hero-bottom">
            <span class="decouvrir-text">DÉCOUVRIR</span>
            <a href="#section-activites" class="btn-scroll js-smooth-scroll" aria-label="Découvrir la suite des activités">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </a>
        </div>
    </section>


    <?php
    // --- 2. SECTION CARTE INTERACTIVE ---
    ?>
    <section id="carte" class="carte-section">
        <div class="carte-header">
            <h2>Découverte du milieu</h2>
            <p class="carte-subtitle">Touchez un point sur la carte pour explorer.</p>
        </div>

        <?php
        // Je centralise toutes les données des 4 points d'intérêt dans un tableau PHP.
        // Cela m'évite de dupliquer 4 fois le même code HTML et me permet de boucler dessus proprement (Principe DRY).
        $activites = [
                'decouvrir' => [
                        'x' => get_field('decouvrir_x'),
                        'y' => get_field('decouvrir_y'),
                        'desc' => get_field('decouvrir_desc'),
                        'web' => get_field('decouvrir_link_web'),
                        'label' => 'Découvrir'
                ],
                'proteger' => [
                        'x' => get_field('proteger_x'),
                        'y' => get_field('proteger_y'),
                        'desc' => get_field('proteger_desc'),
                        'web' => get_field('lien_site_internet_proteger'),
                        'label' => 'Protéger'
                ],
                'deguster' => [
                        'x' => get_field('position_horizontale_deguster'),
                        'y' => get_field('position_verticale_deguster'),
                        'desc' => get_field('description_deguster'),
                        'web' => get_field('lien_site_internet_deguster'),
                        'label' => 'Déguster'
                ],
                'explorer' => [
                        'x' => get_field('position_horizontale_explorer'),
                        'y' => get_field('position_verticale_explorer'),
                        'desc' => get_field('description_explorer'),
                        'web' => get_field('lien_site_internet_explorer'),
                        'label' => 'Explorer'
                ]
        ];
        ?>

        <div class="carte-container" id="carteContainer">
            <div class="carte-scroll-pan" id="carteScrollPan">

                <?php
                // J'affiche l'image de la carte uniquement si mon client l'a renseignée dans ACF
                $image_carte = get_field('image_de_la_carte');
                if ($image_carte) :
                    ?>
                    <img src="<?php echo esc_url($image_carte); ?>" alt="Carte" class="carte-img-landscape">
                <?php endif; ?>

                <?php
                // Je génère les "pins" sur la carte.
                foreach ($activites as $key => $data) :
                    // Sécurité : Je m'assure que les coordonnées X et Y existent avant de tenter de placer le point
                    if ($data['x'] && $data['y']) :
                        ?>
                        <div class="pin-interet" style="top: <?php echo esc_attr($data['y']); ?>%; left: <?php echo esc_attr($data['x']); ?>%;">
                            <div class="pin-label label-<?php echo esc_attr($key); ?>"><?php echo esc_html($data['label']); ?></div>

                            <button class="pin-button btn-<?php echo esc_attr($key); ?> js-open-modal" data-target="modal-<?php echo esc_attr($key); ?>" aria-label="Ouvrir le point d'intérêt">
                                <span class="pin-dot"></span>
                            </button>
                        </div>
                    <?php endif; endforeach; ?>

            </div>
        </div>

        <p class="carte-footer-text">← Faites glisser pour explorer la carte →</p>

        <?php
        // Je génère en avance le HTML des modales associées à la carte (elles sont masquées par défaut en CSS).
        foreach ($activites as $key => $data) :
        if ($data['x'] && $data['y']) :
        ?>
        <div id="modal-<?php echo esc_attr($key); ?>" class="modal-bottom-sheet">
            <div class="modal-overlay-close js-close-modal"></div>

            <div class="modal-content">
                <button class="modal-close-btn js-close-modal" aria-label="Fermer la fenêtre">&times;</button>

                <?php
                // Optimisation de la performance (Clean Code) : Je force WordPress à me renvoyer
                // la version "large" de l'image (plus légère) plutôt que l'originale géante uploadée par l'utilisateur.
                $img_modal = get_field('img_modal_' . $key);
                if($img_modal) :
                    $modal_carte_id = attachment_url_to_postid($img_modal);
                    $optimized_modal_carte = $modal_carte_id ? wp_get_attachment_image_url($modal_carte_id, 'format-carte') : $img_modal;
                    ?>
                    <div class="modal-header-image">
                        <img src="<?php echo esc_url($optimized_modal_carte); ?>" alt="">
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
                            <?php if(get_field('lien_site_' . $key)) : ?>
                                <a href="<?php echo esc_url(get_field('lien_site_' . $key)); ?>" class="btn-modal-link" >En savoir plus</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; endforeach; ?>

    </section>


    <?php
    // --- 3. SECTION CARROUSEL IMMERSIF ---
    ?>
    <section id="section-activites" class="carousel-section">

        <?php
        // J'initialise le fond flouté avec l'image de la 1ère activité pour éviter un flash noir au chargement de la page.
        $premier_fond = get_field('act_1_img') ? esc_url(get_field('act_1_img')) : '';
        ?>
        <div class="carousel-blur-bg" style="<?php if($premier_fond) echo "background-image: url('$premier_fond');"; ?>"></div>
        <div class="carousel-dark-overlay"></div>

        <div class="carousel-content-wrapper">
            <div class="carousel-header">
                <h2>Laissez-vous surprendre...</h2>
                <p class="carousel-subtitle">Quatre invitations à prolonger votre escale.</p>
            </div>

            <div class="carousel-container js-carousel-container">
                <div class="carousel-track">

                    <?php
                    // Je regroupe mes 4 activités dans un tableau pour boucler proprement dessus.
                    $activites_carousel = [
                            [
                                    'img' => get_field('act_1_img'),
                                    'badge' => get_field('act_1_badge'),
                                    'titre' => get_field('act_1_titre'),
                                    'desc' => get_field('act_1_desc'),
                                    'desc_longue' => get_field('act_1_desc_longue'),
                                    'lien' => get_field('act_1_lien'),
                                    'texte_bouton' => get_field('act_1_texte_bouton') ?: 'En savoir plus'
                            ],
                            [
                                    'img' => get_field('act_2_img'),
                                    'badge' => get_field('act_2_badge'),
                                    'titre' => get_field('act_2_titre'),
                                    'desc' => get_field('act_2_desc'),
                                    'desc_longue' => get_field('act_2_desc_longue'),
                                    'lien' => get_field('act_2_lien'),
                                    'texte_bouton' => get_field('act_2_texte_bouton') ?: 'En savoir plus'
                            ],
                            [
                                    'img' => get_field('act_3_img'),
                                    'badge' => get_field('act_3_badge'),
                                    'titre' => get_field('act_3_titre'),
                                    'desc' => get_field('act_3_desc'),
                                    'desc_longue' => get_field('act_3_desc_longue'),
                                    'lien' => get_field('act_3_lien'),
                                    'texte_bouton' => get_field('act_3_texte_bouton') ?: 'En savoir plus'
                            ],
                            [
                                    'img' => get_field('act_4_img'),
                                    'badge' => get_field('act_4_badge'),
                                    'titre' => get_field('act_4_titre'),
                                    'desc' => get_field('act_4_desc'),
                                    'desc_longue' => get_field('act_4_desc_longue'),
                                    'lien' => get_field('act_4_lien'),
                                    'texte_bouton' => get_field('act_4_texte_bouton') ?: 'En savoir plus'
                            ]
                    ];
                    foreach ($activites_carousel as $index => $act) :
                        if ($act['titre']) :

                            if ($act['img']) {
                                $img_id = attachment_url_to_postid($act['img']);
                                $card_img_url = $img_id ? esc_url(wp_get_attachment_image_url($img_id, 'format-carte')) : esc_url($act['img']);
                            } else {
                                $card_img_url = '';
                            }

                            // J'associe une couleur Hexa métier à chaque index, mais je ne l'injecte plus en style CSS !
                            // Je la passe uniquement dans "data-color" pour que mon JS puisse la lire pour le conteneur parent.
                            $color = '#27AE60';
                            if ($index === 1) $color = '#E2B93B';
                            if ($index === 2) $color = '#2F80ED';
                            if ($index === 3) $color = '#9B51E0';
                            ?>

                            <div class="carousel-card-immersive" id="card-act-<?php echo $index; ?>" data-index="<?php echo $index; ?>" data-color="<?php echo $color; ?>" data-bg="<?php echo $card_img_url; ?>">
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

                                    <button class="btn-card-action js-open-modal" data-target="modal-act-<?php echo $index; ?>" aria-label="En savoir plus sur l'activité : <?php echo esc_attr($act['titre']); ?>">
                                        En savoir plus
                                    </button>
                                </div>
                            </div>

                            <div id="modal-act-<?php echo $index; ?>" class="modal-bottom-sheet" data-index="<?php echo $index; ?>">
                                <div class="modal-overlay-close js-close-modal"></div>
                                <div class="modal-content">
                                    <button class="modal-close-btn js-close-modal" aria-label="Fermer">&times;</button>

                                    <?php if($act['img']) : ?>
                                        <div class="modal-header-image">
                                            <img src="<?php echo $card_img_url; ?>" alt="">
                                            <div class="modal-image-gradient"></div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="modal-body">

                                        <div class="modal-content-area">
                                            <?php if ($act['badge']) : ?>
                                                <span class="badge">
                <?php echo esc_html($act['badge']); ?>
            </span>
                                            <?php endif; ?>
                                            <h3><?php echo esc_html($act['titre']); ?></h3>
                                            <?php if ($act['desc_longue']) : ?>
                                                <p class="modal-description"><?php echo esc_html($act['desc_longue']); ?></p>
                                            <?php endif; ?>

                                            <?php if($act['lien']) : ?>
                                                <a href="<?php echo esc_url($act['lien']); ?>" target="_blank" class="btn-modal-link" style="margin-top: 24px; margin-bottom: 8px;">

                                                    <?php echo esc_html($act['texte_bouton']); ?>

                                                </a>
                                            <?php endif; ?>
                                        </div>

                                        <div class="modal-footer-wrapper">
                                            <div class="modal-actions" style="justify-content: center;">

                                                <div class="modal-navigation">
                                                    <button class="modal-arrow prev js-modal-nav" data-target="modal-act-<?php echo $index - 1; ?>" aria-label="Précédent">
                                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                                                    </button>

                                                    <div class="modal-dots">
                                                        <?php
                                                        for($i=0; $i<4; $i++):
                                                            $isActive = ($i === $index) ? 'active' : '';
                                                            ?>
                                                            <span class="dot <?php echo $isActive; ?>"></span>
                                                        <?php endfor; ?>
                                                    </div>

                                                    <button class="modal-arrow next js-modal-nav" data-target="modal-act-<?php echo $index + 1; ?>" aria-label="Suivant">
                                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
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
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                </button>
                <div class="carousel-dots">
                    <span class="dot active"></span><span class="dot"></span><span class="dot"></span><span class="dot"></span>
                </div>
                <button class="carousel-arrow next js-car-next" aria-label="Suivant">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </button>
            </div>
        </div>
    </section>

</main>


<?php
// --- 4. MENU PLEIN ÉCRAN (Burger) ---
?>
<div id="site-menu-overlay" class="menu-full-screen">
    <div class="menu-inner">
        <div class="menu-header">
            <div>
                <span class="menu-subtitle">MENU</span>
                <h2><?php the_title(); ?></h2>
            </div>
            <button class="menu-close-btn js-close-menu" aria-label="Fermer le menu">&times;</button>
        </div>

        <div class="menu-activities-list">
            <?php
            foreach ($activites_carousel as $index => $act) :
                if ($act['titre']) :

                    // Je redéclare la logique de couleur pour le menu
                    $color = '#27AE60'; // Vert par défaut
                    if ($index === 1) $color = '#E2B93B'; // Jaune
                    if ($index === 2) $color = '#2F80ED'; // Bleu
                    if ($index === 3) $color = '#9B51E0'; // Violet
                    ?>

                    <div class="menu-activity-item js-menu-item" data-target="card-act-<?php echo $index; ?>" data-index="<?php echo $index; ?>">

                        <div class="menu-item-color-bar" style="background-color: <?php echo $color; ?>;"></div>

                        <?php if ($act['img']) : ?>
                            <img src="<?php echo esc_url($act['img']); ?>" alt="" class="menu-item-img">
                        <?php endif; ?>

                        <div class="menu-item-text">
                            <h3><?php echo esc_html($act['titre']); ?></h3>
                            <p><?php echo esc_html($act['desc']); ?></p>
                        </div>

                        <button class="menu-item-arrow" aria-label="Voir l'activité" style="background-color: <?php echo $color; ?>;">
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
    // J'attends que le DOM (HTML) soit entièrement chargé avant de lancer mon script pour éviter les erreurs "null"
    document.addEventListener("DOMContentLoaded", function() {

        // --- CENTRAGE DE LA CARTE AU DÉMARRAGE ---
        const container = document.getElementById('carteContainer');
        const pan = document.getElementById('carteScrollPan');
        if(container && pan) {
            // Je calcule la différence entre la grande image et l'écran, et je divise par 2 pour centrer.
            container.scrollLeft = (pan.offsetWidth - container.offsetWidth) / 2;
        }

        // --- VARIABLES GLOBALES ---
        let currentOpenModal = null; // Mémorise la modale actuellement ouverte pour la fermer plus tard
        const menuOverlay = document.getElementById('site-menu-overlay');
        const carouselContainer = document.querySelector('.js-carousel-container');
        const cards = document.querySelectorAll('.carousel-card-immersive');
        const blurBg = document.querySelector('.carousel-blur-bg');
        const dots = document.querySelectorAll('.carousel-dots .dot');

        // --- FONCTION CENTRALE : OPENMODAL ---
        // C'est ma "Source de Vérité Unique". Toute la logique d'ouverture des modales est centralisée ici.
        const openModal = (modalId) => {

            // 1. Si une modale est déjà ouverte (ex: navigation par flèches), je la ferme instantanément.
            if (currentOpenModal) {
                currentOpenModal.classList.remove('is-open');
            }

            const modal = document.getElementById(modalId);
            if (modal) {

                // 2. RÉINITIALISATION DU SCROLL : Je force le retour en haut du texte à chaque ouverture.
                const contentArea = modal.querySelector('.modal-content-area');
                if (contentArea) contentArea.scrollTop = 0;

                // 3. SYNCHRONISATION ARRIÈRE-PLAN :
                // Je cherche la carte (dans le carrousel) qui possède le même index que la modale cliquée.
                const cardId = modalId.replace('modal-', 'card-');
                const relatedCard = document.getElementById(cardId);

                if (relatedCard) {
                    // Le data-color est lu ici uniquement pour modifier les variables globales du carrousel en arrière-plan
                    const themeColor = relatedCard.getAttribute('data-color');

                    if (themeColor) {
                        const wrapper = carouselContainer ? carouselContainer.closest('.carousel-content-wrapper') : null;
                        if (wrapper) wrapper.style.setProperty('--nav-active-color', themeColor);
                    }

                    // L'astuce majeure : Je force le carrousel (invisible sous la modale) à se positionner sur la bonne carte.
                    // Cela déclenche l'IntersectionObserver qui mettra à jour l'image floutée automatiquement !
                    if (carouselContainer) {
                        const scrollPos = relatedCard.offsetLeft - (carouselContainer.offsetWidth / 2) + (relatedCard.offsetWidth / 2);
                        carouselContainer.scrollTo({ left: scrollPos, behavior: 'auto' });
                    }
                }

                // 4. J'affiche physiquement la modale cible et je bloque le défilement du site derrière.
                modal.classList.add('is-open');
                document.body.style.overflow = 'hidden';
                currentOpenModal = modal;
            }
        };

        // --- DÉLÉGATION D'ÉVÉNEMENTS (EVENT DELEGATION) ---
        // Au lieu de greffer 50 EventListeners, j'écoute le "clic" sur tout le corps du site et j'analyse la cible.
        document.body.addEventListener('click', function(e) {

            // Cas A : Clic sur un bouton d'ouverture standard (ex: "En savoir plus")
            const btnOpenModal = e.target.closest('.js-open-modal');
            if (btnOpenModal && !e.target.closest('.js-modal-nav')) {
                openModal(btnOpenModal.getAttribute('data-target'));
                return;
            }

            // Cas B : Clic sur les flèches "Suivant/Précédent" à L'INTÉRIEUR d'une modale
            const btnNavModal = e.target.closest('.js-modal-nav');
            if (btnNavModal) {
                const nextModalId = btnNavModal.getAttribute('data-target');
                if (document.getElementById(nextModalId)) {
                    openModal(nextModalId);
                }
                return;
            }

            // Cas C : Clic sur la croix ou la zone sombre pour FERMER la modale
            if (e.target.closest('.js-close-modal')) {
                if (currentOpenModal) {
                    currentOpenModal.classList.remove('is-open');
                    document.body.style.overflow = ''; // Je réactive le scroll global
                    currentOpenModal = null;
                }
                return;
            }

            // Cas D : GESTION DU MENU BURGER
            if (e.target.closest('.menu-burger')) {
                if (menuOverlay) {
                    menuOverlay.classList.add('is-open');
                    document.body.style.overflow = 'hidden';
                }
                return;
            }
            if (e.target.closest('.js-close-menu')) {
                if (menuOverlay) {
                    menuOverlay.classList.remove('is-open');
                    document.body.style.overflow = '';
                }
                return;
            }

            // Cas E : Clic sur une activité dans la liste du MENU
            const menuItem = e.target.closest('.js-menu-item');
            if (menuItem) {
                const targetCardId = menuItem.getAttribute('data-target');
                const targetCard = document.getElementById(targetCardId);

                // Je déduis l'ID de la modale cible
                const index = targetCardId.replace('card-act-', '');
                const targetModalId = 'modal-act-' + index;

                // 1. Je ferme le menu
                if (menuOverlay) {
                    menuOverlay.classList.remove('is-open');
                    document.body.style.overflow = '';
                }

                // 2. Je scrolle doucement la page pour centrer la carte cible
                if (targetCard) {
                    targetCard.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
                }

                // 3. J'attends que le scroll soit fini (300ms) pour ouvrir la modale
                setTimeout(() => { openModal(targetModalId); }, 300);
                return;
            }

            // Cas F : Navigation manuelle au carrousel sur Desktop (Flèches externes)
            if (e.target.closest('.js-car-prev')) {
                if(carouselContainer) carouselContainer.scrollBy({ left: -320, behavior: 'smooth' });
            }
            if (e.target.closest('.js-car-next')) {
                if(carouselContainer) carouselContainer.scrollBy({ left: 320, behavior: 'smooth' });
            }

            // Cas G : Défilement doux vers la section d'activités (Bouton "DÉCOUVRIR")
            const smoothScrollBtn = e.target.closest('.js-smooth-scroll');
            if (smoothScrollBtn) {
                e.preventDefault();
                const targetSection = document.querySelector(smoothScrollBtn.getAttribute('href'));
                if (targetSection) targetSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });

        // --- INTERSECTION OBSERVER (Performances Visuelles) ---
        // Ce bloc observe quelle carte se trouve au milieu de l'écran (scroll ou swipe mobile).
        if (cards.length > 0 && blurBg && carouselContainer) {
            const observer = new IntersectionObserver(function (entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-active'); // Zoom la carte centrale

                        // Je récupère l'image de fond de la carte et je l'applique floutée à la section entière
                        const newBg = entry.target.getAttribute('data-bg');
                        if (newBg) blurBg.style.backgroundImage = `url('${newBg}')`;

                        // Je récupère la couleur pour les flèches extérieures du carrousel
                        const newColor = entry.target.getAttribute('data-color');
                        const wrapper = carouselContainer.closest('.carousel-content-wrapper');
                        if (wrapper && newColor) wrapper.style.setProperty('--nav-active-color', newColor);

                        // Je mets à jour la classe "active" du petit point correspondant (dot)
                        const cardIndex = Array.from(cards).indexOf(entry.target);
                        dots.forEach((dot, idx) => {
                            dot.classList.toggle('active', idx === cardIndex);
                        });
                    } else {
                        entry.target.classList.remove('is-active'); // Dé-zoom la carte sortante
                    }
                });
            }, { root: carouselContainer, threshold: 0.5 }); // threshold: 0.5 signifie que la carte doit être à 50% visible

            // J'applique l'observateur à chaque carte du DOM
            cards.forEach(card => observer.observe(card));
        }
    });
</script>

<?php
// Fermeture propre du DOM WordPress
get_footer();
?>