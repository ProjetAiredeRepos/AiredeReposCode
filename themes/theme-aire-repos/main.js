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
        // Ajout du paramètre "direction" (par défaut sur "next")
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

            // --- MISE À JOUR DU SCROLL ET DES COULEURS (Pour la nouvelle modale) ---
            const modalContentArea = targetModal.querySelector('.modal-content');
            if (modalContentArea) {
                modalContentArea.scrollTop = 0;
            }

            const cardId = modalId.replace('modal-', 'card-');
            const relatedCard = document.getElementById(cardId);

            if (relatedCard) {
                const themeColor = relatedCard.getAttribute('data-color');
                if (themeColor) {
                    targetModal.style.setProperty('--modal-theme-color', themeColor);
                    const arrows = targetModal.querySelectorAll('.modal-arrow');
                    arrows.forEach(arrow => arrow.style.color = themeColor);
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
            // On vérifie que ce n'est PAS un bouton de navigation interne à la modale
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
                // Détecte si on a cliqué sur la flèche de gauche (.prev) ou droite
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

            // E. Navigation Carrousel Desktop
            if (e.target.closest('.carousel-arrow.prev') || e.target.closest('.js-car-prev')) {
                if(carouselContainer) carouselContainer.scrollBy({ left: -320, behavior: 'smooth' });
                return;
            }
            if (e.target.closest('.carousel-arrow.next') || e.target.closest('.js-car-next')) {
                if(carouselContainer) carouselContainer.scrollBy({ left: 320, behavior: 'smooth' });
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
            }, { root: carouselContainer, threshold: 0.5 });

            cards.forEach(card => observer.observe(card));
        }
    });
})();