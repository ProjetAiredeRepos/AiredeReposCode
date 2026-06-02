// On attend que tout le HTML de la page soit chargé avant d'exécuter le script
document.addEventListener("DOMContentLoaded", function() {

    // --- 1. CENTRAGE DE LA CARTE INTERACTIVE ---
    const container = document.getElementById('carteContainer');
    const pan = document.getElementById('carteScrollPan');

    // Si la carte existe sur la page, on la centre automatiquement au milieu du conteneur
    if(container && pan) {
        container.scrollLeft = (pan.offsetWidth - container.offsetWidth) / 2;
    }

    // --- 2. SÉLECTION DES ÉLÉMENTS DU DOM ---
    const pins = document.querySelectorAll('.pin-button'); // Les points sur la carte
    const carouselButtons = document.querySelectorAll('.btn-card-action'); // Les boutons dans les cartes du carrousel
    const closeButtons = document.querySelectorAll('.modal-close-btn, .modal-overlay-close'); // Les boutons pour fermer les modales
    const burgerBtn = document.querySelector('.menu-burger'); // Le bouton du menu hamburger
    const menuOverlay = document.getElementById('site-menu-overlay'); // Le fond du menu plein écran
    const menuCloseBtn = document.querySelector('.menu-close-btn'); // La croix pour fermer le menu
    const menuItems = document.querySelectorAll('.menu-activity-item'); // Les activités listées dans le menu

    // --- 3. GESTION DES MODALES ---
    // Fonction générique pour ouvrir une modale spécifique et bloquer le défilement de la page en arrière-plan
    function openModal(targetId) {
        const modal = document.getElementById(targetId);
        if(modal) {
            modal.classList.add('is-open');
            document.body.style.overflow = 'hidden'; // Empêche le scroll du site derrière la modale
        }
    }

    // --- 4. GESTION DU MENU BURGER ---
    // Ouvrir le menu au clic sur le burger
    if(burgerBtn && menuOverlay) {
        burgerBtn.addEventListener('click', function() {
            menuOverlay.classList.add('is-open');
            document.body.style.overflow = 'hidden'; // Bloque le scroll derrière le menu
        });
    }

    // Fermer le menu au clic sur la croix
    if(menuCloseBtn && menuOverlay) {
        menuCloseBtn.addEventListener('click', function() {
            menuOverlay.classList.remove('is-open');
            document.body.style.overflow = ''; // Réactive le scroll de la page
        });
    }

    // Comportement au clic sur une activité depuis le menu
    menuItems.forEach(item => {
        item.addEventListener('click', function() {
            const targetCardId = this.getAttribute('data-target'); // Récupère l'ID de la carte correspondante
            const targetCard = document.getElementById(targetCardId);

            // Déduit l'ID de la modale associée (ex: 'modal-act-1')
            const index = targetCardId.replace('card-act-', '');
            const targetModalId = 'modal-act-' + index;

            // 1. Ferme le menu
            if(menuOverlay) {
                menuOverlay.classList.remove('is-open');
                document.body.style.overflow = '';
            }

            // 2. Scrolle la page de manière fluide pour centrer la carte cible à l'écran
            if(targetCard) {
                targetCard.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center',
                    inline: 'center'
                });
            }

            // 3. Ouvre la modale correspondante après un léger délai (le temps du scroll)
            setTimeout(() => {
                openModal(targetModalId);
            }, 300);
        });
    });

    // --- 5. OUVERTURE DES MODALES VIA LES BOUTONS ---
    // Clic sur les "pins" de la carte interactive
    pins.forEach(pin => {
        pin.addEventListener('click', function() {
            const modalId = this.getAttribute('data-target');
            openModal(modalId);
        });
    });

    // Clic sur les boutons "Découvrir" dans les cartes du carrousel
    carouselButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const modalId = this.getAttribute('data-target');
            openModal(modalId);
        });
    });

    // Fermeture de n'importe quelle modale (via la croix ou l'overlay sombre)
    closeButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const activeModal = this.closest('.modal-bottom-sheet');
            if(activeModal) {
                activeModal.classList.remove('is-open');
                document.body.style.overflow = ''; // Réactive le scroll de la page
            }
        });
    });

    // --- 6. GESTION DU SCROLL DOUX (BOUTON HERO) ---
    const btnDecouvrir = document.querySelector('.btn-cta-decouvrir, .btn-scroll');

    // Clic sur la flèche de l'accueil pour descendre vers la section suivante
    if(btnDecouvrir) {
        btnDecouvrir.addEventListener('click', function(e) {
            e.preventDefault(); // Annule le comportement de base du lien

            const targetId = this.getAttribute('href');
            const targetSection = document.querySelector(targetId);

            // Scrolle doucement vers le début de la section ciblée
            if(targetSection) {
                targetSection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    }

    // --- 7. CARROUSEL IMMERSIF (Scroll, Flèches et Couleurs dynamiques) ---
    const cards = document.querySelectorAll('.carousel-card-immersive');
    const blurBg = document.querySelector('.carousel-blur-bg');
    const carouselContainer = document.querySelector('.carousel-container');
    const prevArrow = document.querySelector('.carousel-arrow.prev');
    const nextArrow = document.querySelector('.carousel-arrow.next');
    const dots = document.querySelectorAll('.carousel-dots .dot');

    // Gestion de la navigation manuelle via les flèches (Desktop)
    if (carouselContainer && prevArrow && nextArrow) {
        // Calcule dynamiquement la largeur d'une carte pour savoir de combien de pixels il faut scroller
        function getScrollStep() {
            const firstCard = carouselContainer.querySelector('.carousel-card-immersive');
            return firstCard ? firstCard.offsetWidth + 20 : 300; // 20px correspond au gap
        }

        // Flèche Gauche
        prevArrow.addEventListener('click', function() {
            carouselContainer.scrollBy({ left: -getScrollStep(), behavior: 'smooth' });
        });

        // Flèche Droite
        nextArrow.addEventListener('click', function() {
            carouselContainer.scrollBy({ left: getScrollStep(), behavior: 'smooth' });
        });
    }

    // Gestion de l'état "Actif" des cartes et des couleurs dynamiques (Scroll/Swipe)
    if (cards.length > 0 && blurBg) {
        const wrapper = carouselContainer.closest('.carousel-content-wrapper');

        // Fonction centralisée qui met à jour toute l'interface visuelle (fond, flèches, points)
        // selon la carte qui se trouve au centre de l'écran
        function updateCarouselUI(card) {
            card.classList.add('is-active'); // Agrandit la carte active

            // Change l'image floutée en arrière-plan
            const newBg = card.getAttribute('data-bg');
            if (newBg) blurBg.style.backgroundImage = `url('${newBg}')`;

            // Change la couleur thématique des flèches/boutons via une variable CSS
            const newColor = card.getAttribute('data-color');
            if (wrapper && newColor) {
                wrapper.style.setProperty('--nav-active-color', newColor);
            }

            // Met à jour l'indicateur actif (le petit point qui s'allonge)
            const cardIndex = Array.from(cards).indexOf(card);
            dots.forEach((dot, idx) => {
                if (idx === cardIndex) {
                    dot.classList.add('active');
                } else {
                    dot.classList.remove('active');
                }
            });
        }

        // INITIALISATION : Évite le bug de la couleur par défaut au rechargement de la page.
        // On calcule quelle carte est physiquement visible dans la moitié gauche de l'écran au lancement.
        let initialCard = cards[0];
        cards.forEach(card => {
            const rect = card.getBoundingClientRect();
            if (rect.left >= 0 && rect.left < window.innerWidth / 2) {
                initialCard = card;
            }
        });
        updateCarouselUI(initialCard); // Applique immédiatement les bonnes couleurs

        // OBSERVATEUR : Lance une détection à chaque fois que l'utilisateur scrolle le carrousel
        const observerOptions = {
            root: carouselContainer,
            threshold: 0.6 // La carte doit être à 60% visible pour déclencher le changement
        };

        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    // Si la carte rentre dans la zone cible, on met à jour l'interface
                    updateCarouselUI(entry.target);
                } else {
                    // Si elle sort de la zone, on retire la classe pour la réduire (effet de zoom arrière)
                    entry.target.classList.remove('is-active');
                }
            });
        }, observerOptions);

        // On applique l'observateur à toutes les cartes du carrousel
        cards.forEach(card => {
            observer.observe(card);
        });
    }
});