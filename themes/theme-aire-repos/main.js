/**
 * main.js
 * Architecture par Délégation d'Événements (Event Delegation)
 */
(() => {
    'use strict'; // Mode strict pour sécuriser le code

    document.addEventListener("DOMContentLoaded", () => {
        // --- 1. SÉLECTION DES ÉLÉMENTS GLOBAUX ---
        const menuOverlay = document.getElementById('site-menu-overlay');
        const carouselContainer = document.querySelector('.js-carousel-container') || document.querySelector('.carousel-container');
        const blurBg = document.querySelector('.carousel-blur-bg');
        const cards = document.querySelectorAll('.carousel-card-immersive');

        let currentOpenModal = null;

        // --- 2. CENTRAGE DE LA CARTE ---
        const container = document.getElementById('carteContainer');
        const pan = document.getElementById('carteScrollPan');
        if(container && pan) {
            container.scrollLeft = (pan.offsetWidth - container.offsetWidth) / 2;
        }

        // --- 3. GESTIONNAIRE D'ÉTAT DES MODALES ---
        const openModal = (modalId, direction = 'next') => {
            const targetModal = document.getElementById(modalId);
            if (!targetModal) return;

            // S'il y a DÉJÀ une modale d'ouverte
            if (currentOpenModal && currentOpenModal.id !== modalId) {

                // Détermine les bonnes classes CSS selon la direction (gauche ou droite)
                const outClass = direction === 'next' ? 'is-switching-out' : 'is-switching-out-reverse';
                const inClass = direction === 'next' ? 'is-switching-in' : 'is-switching-in-reverse';

                // Active les animations
                currentOpenModal.classList.add(outClass);
                targetModal.classList.add(inClass);

                currentOpenModal.classList.remove('is-open');
                targetModal.classList.add('is-open');

                const previousModal = currentOpenModal;
                currentOpenModal = targetModal;

                // Nettoie après 300ms
                setTimeout(() => {
                    previousModal.classList.remove(outClass);
                    targetModal.classList.remove(inClass);
                }, 300);

            } else {
                // Ouverture classique (La fenêtre monte du bas)
                targetModal.classList.add('is-open');
                document.body.style.overflow = 'hidden';
                currentOpenModal = targetModal;
            }

            /* Réinitialisation du défilement de l'en-tête et de la zone textuelle */
            const mainContent = targetModal.querySelector('.modal-content');
            if (mainContent) mainContent.scrollTop = 0;

            const contentArea = targetModal.querySelector('.modal-content-area');
            if (contentArea) contentArea.scrollTop = 0;

            /* Récupération de la couleur thématique par ID ou par attribut data-target */
            const cardId = modalId.replace('modal-', 'card-');
            let relatedCard = document.getElementById(cardId);

            if (!relatedCard) {
                relatedCard = document.querySelector(`.carousel-card-immersive[data-target="${modalId}"]`);
            }

            if (relatedCard) {
                const themeColor = relatedCard.getAttribute('data-color');
                if (themeColor) {
                    /* Application de la variable CSS au niveau global du document et local de la modale */
                    document.documentElement.style.setProperty('--modal-theme-color', themeColor);
                    targetModal.style.setProperty('--modal-theme-color', themeColor);

                    /* Coloration de toutes les flèches de navigation via leurs classes fonctionnelles */
                    const arrows = document.querySelectorAll('.js-modal-nav, .modal-arrow');
                    arrows.forEach(arrow => arrow.style.color = themeColor);

                    /* Application de la couleur uniquement sur le bouton d'action principal */
                    const actionBtn = targetModal.querySelector('.btn-cta-decouvrir');
                    if (actionBtn) {
                        actionBtn.style.backgroundColor = themeColor;
                    }
                }
            }
        };

        const closeModal = () => {
            if (currentOpenModal) {
                currentOpenModal.classList.remove('is-open');
                document.body.style.overflow = '';
                currentOpenModal = null;
            }
        };

        // --- 4. DÉLÉGATION D'ÉVÉNEMENTS (Cœur du système) ---
        document.body.addEventListener('click', (e) => {

            // A. Ouvrir une modale (via un bouton contenant data-target)
            const openBtn = e.target.closest('[data-target]');
            if (openBtn && !e.target.closest('.js-modal-nav')) {
                const targetId = openBtn.getAttribute('data-target');
                openModal(targetId);
                return;
            }

            // B. Navigation Inter-Modales (Flèches Suivant / Précédent)
            const navBtn = e.target.closest('.js-modal-nav');
            if (navBtn) {
                const nextModalId = navBtn.getAttribute('data-target');
                const direction = navBtn.classList.contains('prev') ? 'prev' : 'next';

                if (document.getElementById(nextModalId)) {
                    openModal(nextModalId, direction);
                }
                return;
            }

            // C. Fermer une modale
            if (e.target.closest('.js-close-modal') || e.target.closest('.modal-close-btn') || e.target.closest('.modal-overlay-close')) {
                closeModal();
                return;
            }

            // D. Menu Burger (Ouverture/Fermeture)
            if (e.target.closest('.menu-burger')) {
                if (menuOverlay) {
                    menuOverlay.classList.add('is-open');
                    document.body.style.overflow = 'hidden';
                }
                return;
            }
            if (e.target.closest('.menu-close-btn') || e.target.closest('.js-close-menu')) {
                if (menuOverlay) {
                    menuOverlay.classList.remove('is-open');
                    document.body.style.overflow = '';
                }
                return;
            }

            // E. Navigation Carrousel Mobile & Desktop (LE CORRECTIF ULTIME ANTI-SAUT)
            const btnPrev = e.target.closest('.carousel-arrow.prev') || e.target.closest('.js-car-prev');
            const btnNext = e.target.closest('.carousel-arrow.next') || e.target.closest('.js-car-next');

            if (btnPrev || btnNext) {
                e.preventDefault();

                if (carouselContainer) {
                    // Verrou anti-spam (empêche les doubles clics accidentels)
                    if (carouselContainer.dataset.isLocked === 'true') return;
                    carouselContainer.dataset.isLocked = 'true';

                    // Calcul mathématique de la carte centrale
                    const cardsArray = Array.from(carouselContainer.querySelectorAll('.carousel-card-immersive'));
                    const containerCenter = carouselContainer.scrollLeft + (carouselContainer.offsetWidth / 2);

                    let currentIndex = 0;
                    let minDistance = Infinity;

                    cardsArray.forEach((card, index) => {
                        const cardCenter = card.offsetLeft + (card.offsetWidth / 2);
                        const distance = Math.abs(cardCenter - containerCenter);
                        if (distance < minDistance) {
                            minDistance = distance;
                            currentIndex = index;
                        }
                    });

                    // Cible
                    let targetIndex = currentIndex;
                    if (btnNext && currentIndex < cardsArray.length - 1) {
                        targetIndex++;
                    } else if (btnPrev && currentIndex > 0) {
                        targetIndex--;
                    }

                    if (targetIndex !== currentIndex) {
                        const targetCard = cardsArray[targetIndex];
                        const scrollPos = targetCard.offsetLeft - (carouselContainer.offsetWidth / 2) + (targetCard.offsetWidth / 2);

                        // Désactivation temporaire de l'aimant CSS pour éviter la "catapulte"
                        carouselContainer.style.scrollSnapType = 'none';
                        carouselContainer.scrollTo({ left: scrollPos, behavior: 'smooth' });

                        // Réactivation de l'aimant après le défilement (500ms)
                        setTimeout(() => {
                            carouselContainer.style.scrollSnapType = 'x mandatory';
                            carouselContainer.dataset.isLocked = 'false';
                        }, 500);
                    } else {
                        // Si on est à la fin, on déverrouille direct
                        carouselContainer.dataset.isLocked = 'false';
                    }
                }
                return;
            }

            // F. Scroll Doux (Bouton Hero)
            const smoothScrollBtn = e.target.closest('.btn-scroll') || e.target.closest('.btn-cta-decouvrir');
            if (smoothScrollBtn) {
                e.preventDefault();
                const targetId = smoothScrollBtn.getAttribute('href');
                if (targetId) {
                    const targetSection = document.querySelector(targetId);
                    if (targetSection) targetSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        });

        // --- 5. INTERSECTION OBSERVER (Carrousel : Flou & Couleurs) ---
        if (cards.length > 0 && blurBg && carouselContainer) {
            const dots = document.querySelectorAll('.carousel-dots .dot');
            const wrapper = carouselContainer.closest('.carousel-content-wrapper');

            const firstCard = cards[0];
            const initialBg = firstCard.getAttribute('data-bg');
            const initialColor = firstCard.getAttribute('data-color');

            if (initialBg) blurBg.style.backgroundImage = `url('${initialBg}')`;
            if (wrapper && initialColor) wrapper.style.setProperty('--nav-active-color', initialColor);

            firstCard.classList.add('is-active');
            if (dots.length > 0) dots[0].classList.add('active');

            const observerOptions = {
                root: carouselContainer,
                rootMargin: '0px -40% 0px -40%',
                threshold: 0
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-active');

                        const newBg = entry.target.getAttribute('data-bg');
                        if (newBg) blurBg.style.backgroundImage = `url('${newBg}')`;

                        const newColor = entry.target.getAttribute('data-color');
                        if (wrapper && newColor) wrapper.style.setProperty('--nav-active-color', newColor);

                        const cardIndex = Array.from(cards).indexOf(entry.target);
                        dots.forEach((dot, idx) => {
                            dot.classList.toggle('active', idx === cardIndex);
                        });
                    } else {
                        entry.target.classList.remove('is-active');
                    }
                });
            }, observerOptions);

            cards.forEach(card => observer.observe(card));
        }
    });
})();