/**
 * main.js
 * Architecture par Délégation d'Événements (Event Delegation)
 * CTO Fix : Zero Warnings, Anti-Vibration & Performance (No Layout Thrashing)
 */
(() => {
    'use strict';

    document.addEventListener("DOMContentLoaded", () => {
        const menuOverlay = document.getElementById('site-menu-overlay');
        const carouselContainer = document.querySelector('.js-carousel-container') || document.querySelector('.carousel-container');
        const blurBg = document.querySelector('.carousel-blur-bg');
        const cards = document.querySelectorAll('.carousel-card-immersive');

        let currentOpenModal = null;

        // ==========================================================================
        // --- CTO FIX : KILLSWITCH DES FLÈCHES SUR DESKTOP ---
        // ==========================================================================
        if (window.matchMedia("(min-width: 1024px)").matches) {
            const arrows = document.querySelectorAll('.carousel-arrow, .js-car-prev, .js-car-next');
            arrows.forEach(arrow => arrow.remove());
        }

        // --- MOTEUR D'ANIMATION CENTRALISÉ (Soulage le processeur) ---
        const smoothAnimate = (start, distance, duration, type, onUpdate, onComplete) => {
            let startTime = null;
            const animation = (currentTime) => {
                if (startTime === null) startTime = currentTime;
                const timeElapsed = currentTime - startTime;
                let t = timeElapsed / (duration / 2);
                let run;

                if (type === 'cubic') {
                    if (t < 1) run = (distance / 2) * t * t * t + start;
                    else { t -= 2; run = (distance / 2) * (t * t * t + 2) + start; }
                } else {
                    if (t < 1) run = (distance / 2) * t * t + start;
                    else { t--; run = -(distance / 2) * (t * (t - 2) - 1) + start; }
                }

                if (timeElapsed < duration) {
                    onUpdate(run);
                    requestAnimationFrame(animation);
                } else {
                    onUpdate(start + distance);
                    if (onComplete) onComplete();
                }
            };
            requestAnimationFrame(animation);
        };

        // --- CENTRAGE CARTE ---
        const container = document.getElementById('carteContainer');
        const pan = document.getElementById('carteScrollPan');
        const carteImage = document.querySelector('.carte-img-landscape');

        if (container && pan && carteImage) {
            const centerMap = () => {
                container.scrollLeft = (pan.scrollWidth - container.clientWidth) / 2;
            };
            if (carteImage.complete) requestAnimationFrame(centerMap);
            else carteImage.addEventListener('load', () => requestAnimationFrame(centerMap));
            window.addEventListener('resize', () => requestAnimationFrame(centerMap));
        }

        // --- GESTION MODALES ---
        const openModal = (modalId, direction = 'next') => {
            const targetModal = document.getElementById(modalId);
            if (!targetModal) return;

            if (currentOpenModal && currentOpenModal.id !== modalId) {
                const outClass = direction === 'next' ? 'is-switching-out' : 'is-switching-out-reverse';
                const inClass = direction === 'next' ? 'is-switching-in' : 'is-switching-in-reverse';

                currentOpenModal.classList.add(outClass);
                targetModal.classList.add(inClass);
                currentOpenModal.classList.remove('is-open');
                targetModal.classList.add('is-open');

                const previousModal = currentOpenModal;
                currentOpenModal = targetModal;

                setTimeout(() => {
                    previousModal.classList.remove(outClass);
                    targetModal.classList.remove(inClass);
                }, 300);
            } else {
                targetModal.classList.add('is-open');
                document.body.style.overflow = 'hidden';
                currentOpenModal = targetModal;
            }

            const mainContent = targetModal.querySelector('.modal-content');
            if (mainContent) mainContent.scrollTop = 0;
            const contentArea = targetModal.querySelector('.modal-content-area');
            if (contentArea) contentArea.scrollTop = 0;

            const cardId = modalId.replace('modal-', 'card-');
            const relatedCard = document.getElementById(cardId) || document.querySelector(`.carousel-card-immersive[data-target="${modalId}"]`);

            if (relatedCard) {
                const themeColor = relatedCard.getAttribute('data-color');
                if (themeColor) {
                    document.documentElement.style.setProperty('--modal-theme-color', themeColor);
                    targetModal.style.setProperty('--modal-theme-color', themeColor);
                    targetModal.querySelectorAll('.js-modal-nav, .modal-arrow').forEach(arrow => arrow.style.color = themeColor);
                    const actionBtn = targetModal.querySelector('.btn-cta-decouvrir');
                    if (actionBtn) actionBtn.style.backgroundColor = themeColor;
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

        const closeMenu = () => {
            if (menuOverlay) {
                menuOverlay.classList.remove('is-open');
                document.body.style.overflow = '';
            }
        };

        // --- DÉLÉGATION D'ÉVÉNEMENTS ---
        document.body.addEventListener('click', (e) => {
            const menuItem = e.target.closest('.js-menu-item');
            if (menuItem) {
                e.preventDefault();
                closeMenu();
                const section = document.getElementById('section-activites');
                if (section) section.scrollIntoView({behavior: 'smooth'});

                const index = menuItem.getAttribute('data-index');
                const targetCard = document.getElementById('card-act-' + index);

                if (targetCard && carouselContainer) {
                    // FIX REFLOW : On lit d'abord, on écrit ensuite
                    const scrollPos = targetCard.offsetLeft - (carouselContainer.offsetWidth / 2) + (targetCard.offsetWidth / 2);
                    requestAnimationFrame(() => {
                        carouselContainer.style.scrollSnapType = 'none';
                        carouselContainer.scrollTo({left: scrollPos, behavior: 'auto'});
                        setTimeout(() => carouselContainer.style.scrollSnapType = 'x mandatory', 100);
                    });
                }

                if (index !== null) setTimeout(() => openModal('modal-act-' + index), 400);
                return;
            }

            const openBtn = e.target.closest('[data-target]');
            if (openBtn && !e.target.closest('.js-modal-nav') && !e.target.closest('.js-menu-item')) {
                openModal(openBtn.getAttribute('data-target'));
                return;
            }

            const navBtn = e.target.closest('.js-modal-nav');
            if (navBtn) {
                const nextModalId = navBtn.getAttribute('data-target');
                if (document.getElementById(nextModalId)) openModal(nextModalId, navBtn.classList.contains('prev') ? 'prev' : 'next');
                return;
            }

            if (e.target.closest('.js-close-modal') || e.target.closest('.modal-close-btn') || e.target.closest('.modal-overlay-close')) {
                closeModal();
                return;
            }

            if (e.target.closest('.menu-burger')) {
                if (menuOverlay) { menuOverlay.classList.add('is-open'); document.body.style.overflow = 'hidden'; }
                return;
            }
            if (e.target.closest('.menu-close-btn') || e.target.closest('.js-close-menu')) {
                closeMenu();
                return;
            }

            const btnPrev = e.target.closest('.carousel-arrow.prev') || e.target.closest('.js-car-prev');
            const btnNext = e.target.closest('.carousel-arrow.next') || e.target.closest('.js-car-next');

            if (btnPrev || btnNext) {
                e.preventDefault();
                if (carouselContainer) {
                    if (carouselContainer.dataset.isLocked === 'true') return;
                    carouselContainer.dataset.isLocked = 'true';

                    const cardsArray = Array.from(carouselContainer.querySelectorAll('.carousel-card-immersive'));

                    // FIX REFLOW : Lecture DOM groupée
                    const containerCenter = carouselContainer.scrollLeft + (carouselContainer.offsetWidth / 2);
                    let currentIndex = 0;
                    let minDistance = Infinity;

                    cardsArray.forEach((card, index) => {
                        const distance = Math.abs((card.offsetLeft + (card.offsetWidth / 2)) - containerCenter);
                        if (distance < minDistance) { minDistance = distance; currentIndex = index; }
                    });

                    let targetIndex = currentIndex;
                    if (btnNext && currentIndex < cardsArray.length - 1) targetIndex++;
                    else if (btnPrev && currentIndex > 0) targetIndex--;

                    if (targetIndex !== currentIndex) {
                        const targetCard = cardsArray[targetIndex];
                        let scrollPos = targetCard.offsetLeft - (carouselContainer.offsetWidth / 2) + (targetCard.offsetWidth / 2);
                        const maxScroll = carouselContainer.scrollWidth - carouselContainer.clientWidth;
                        scrollPos = Math.max(0, Math.min(scrollPos, maxScroll));

                        const startPos = carouselContainer.scrollLeft;

                        // Écriture DOM dans un frame
                        requestAnimationFrame(() => {
                            carouselContainer.style.scrollSnapType = 'none';
                            smoothAnimate(startPos, scrollPos - startPos, 650, 'cubic',
                                (val) => carouselContainer.scrollLeft = val,
                                () => {
                                    carouselContainer.style.scrollSnapType = 'x mandatory';
                                    carouselContainer.dataset.isLocked = 'false';
                                }
                            );
                        });
                    } else {
                        carouselContainer.dataset.isLocked = 'false';
                    }
                }
                return;
            }

            const smoothScrollBtn = e.target.closest('.btn-scroll') || e.target.closest('.btn-cta-decouvrir');
            if (smoothScrollBtn) {
                e.preventDefault();
                const targetId = smoothScrollBtn.getAttribute('href');
                if (targetId && targetId !== '#') {
                    const targetSection = document.querySelector(targetId);
                    if (targetSection) {
                        const startPosition = window.scrollY;
                        const targetPosition = targetSection.getBoundingClientRect().top + startPosition;
                        smoothAnimate(startPosition, targetPosition - startPosition, 500, 'quad', (val) => window.scrollTo(0, val));
                    }
                }
            }
        });

        const heroBottomBlock = document.querySelector('.js-hero-routing');
        if (heroBottomBlock) {
            const scrollBtn = heroBottomBlock.querySelector('.js-smooth-scroll');
            if (document.querySelector('#carte')) scrollBtn.setAttribute('href', '#carte');
            else if (document.querySelector('#section-activites')) scrollBtn.setAttribute('href', '#section-activites');
            else heroBottomBlock.style.display = 'none';
        }

        // --- OBSERVER ---
        if (cards.length > 0 && blurBg && carouselContainer) {
            const dots = document.querySelectorAll('.carousel-dots .dot');
            const wrapper = carouselContainer.closest('.carousel-content-wrapper');
            const firstCard = cards[0];

            if (firstCard.getAttribute('data-bg')) blurBg.style.backgroundImage = `url('${firstCard.getAttribute('data-bg')}')`;
            if (wrapper && firstCard.getAttribute('data-color')) wrapper.style.setProperty('--nav-active-color', firstCard.getAttribute('data-color'));

            firstCard.classList.add('is-active');
            if (dots.length > 0) dots[0].classList.add('active');

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-active');
                        if (entry.target.getAttribute('data-bg')) blurBg.style.backgroundImage = `url('${entry.target.getAttribute('data-bg')}')`;
                        if (wrapper && entry.target.getAttribute('data-color')) wrapper.style.setProperty('--nav-active-color', entry.target.getAttribute('data-color'));

                        const cardIndex = Array.from(cards).indexOf(entry.target);
                        dots.forEach((dot, idx) => dot.classList.toggle('active', idx === cardIndex));
                    } else {
                        entry.target.classList.remove('is-active');
                    }
                });
            }, { root: carouselContainer, rootMargin: '0px -40% 0px -40%', threshold: 0 });

            cards.forEach(card => observer.observe(card));
        }
    });
})();