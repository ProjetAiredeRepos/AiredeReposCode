// Fichier : ui.js
// Gère l'interactivité de l'interface (Menu, Modales, Sliders)

document.addEventListener('DOMContentLoaded', () => {

    // 1. MENU BURGER
    const openMenuBtn = document.getElementById('openMenuBtn');
    const closeMenuBtn = document.getElementById('closeMenuBtn');
    const mainMenu = document.getElementById('mainMenu');

    openMenuBtn?.addEventListener('click', () => mainMenu.classList.add('is-active'));
    closeMenuBtn?.addEventListener('click', () => mainMenu.classList.remove('is-active'));

    document.querySelectorAll('.menu-item').forEach(item => {
        item.addEventListener('click', (e) => {
            mainMenu.classList.remove('is-active');
            const targetId = e.currentTarget.getAttribute('data-target');
            document.getElementById(targetId)?.classList.add('is-open');
            document.body.classList.add('modal-open');
        });
    });

    // 2. SLIDER HORIZONTAL ET FOND IMMERSIF
    const slider = document.getElementById('mainSlider');
    const immersiveBg = document.getElementById('immersiveBg');
    const sliderDots = document.querySelectorAll('.slider-dots .dot');
    const cards = document.querySelectorAll('.slider-card');

    if (slider && cards.length > 0 && immersiveBg) {

        const updateImmersiveBackground = (index) => {
            if(!cards[index]) return;
            const imageUrl = cards[index].getAttribute('data-src');
            if(imageUrl) {
                immersiveBg.style.backgroundImage = `url('${imageUrl}')`;
            }
            // Le CSS se charge de colorer les boutons via cet attribut !
            const currentTheme = cards[index].getAttribute('data-theme');
            const sliderSection = document.querySelector('.slider-section');
            if(sliderSection) sliderSection.setAttribute('data-active-theme', currentTheme);
        }

        sliderDots[0]?.classList.add('active');
        updateImmersiveBackground(0);

        slider.addEventListener('scroll', () => {
            let scrollLeft = slider.scrollLeft;
            let cardWidth = slider.clientWidth * 0.75;
            let index = Math.round(scrollLeft / cardWidth);

            sliderDots.forEach(dot => dot.classList.remove('active'));
            if(sliderDots[index]) sliderDots[index].classList.add('active');

            updateImmersiveBackground(index);
        });

        document.querySelector('.next-btn')?.addEventListener('click', () => {
            slider.scrollBy({ left: slider.clientWidth * 0.75, behavior: 'smooth' });
        });
        document.querySelector('.prev-btn')?.addEventListener('click', () => {
            slider.scrollBy({ left: -(slider.clientWidth * 0.75), behavior: 'smooth' });
        });
    }

    // 3. OUVRIR / FERMER LES MODALES
    document.querySelectorAll('.btn-savoir-plus').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const targetId = e.currentTarget.getAttribute('data-target');
            document.getElementById(targetId)?.classList.add('is-open');
            document.body.classList.add('modal-open');
        });
    });

    document.querySelectorAll('.close-modal-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const modal = e.currentTarget.closest('.modal-details');
            if(modal) {
                modal.classList.remove('is-open');
                document.body.classList.remove('modal-open');
                // Sécurité : on remet la carte droite pour la prochaine fois
                const card = modal.querySelector('.modal-card');
                if(card) card.style.transform = '';
            }
        });
    });

    // 4. NAVIGATION ENTRE MODALES (GLISSEMENT HORIZONTAL)
    document.querySelectorAll('.next-modal-btn, .prev-modal-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const isNext = e.currentTarget.classList.contains('next-modal-btn');
            const currentModal = e.currentTarget.closest('.modal-details');
            const currentIndex = parseInt(e.currentTarget.getAttribute('data-current'));
            let targetIndex = isNext ? currentIndex + 1 : currentIndex - 1;
            const targetModal = document.getElementById('modal-' + targetIndex);

            if(targetModal) {
                const currentCard = currentModal.querySelector('.modal-card');
                const targetCard = targetModal.querySelector('.modal-card');

                // 1. On place la nouvelle modale hors écran sans qu'elle bouge
                targetModal.classList.add('no-transition');
                targetModal.classList.add('is-open');
                targetCard.style.transform = isNext ? 'translateX(100vw)' : 'translateX(-100vw)';

                // 2. On force le navigateur à enregistrer cette position
                void targetModal.offsetWidth;

                // 3. On réactive les animations
                targetModal.classList.remove('no-transition');

                // 4. Action ! La nouvelle arrive au centre, l'ancienne s'en va sur le côté
                targetCard.style.transform = 'translate(0, 0)';
                currentCard.style.transform = isNext ? 'translateX(-100vw)' : 'translateX(100vw)';

                // 5. On nettoie l'ancienne modale quand l'animation est finie (400ms)
                setTimeout(() => {
                    currentModal.classList.remove('is-open');
                    currentCard.style.transform = ''; // Nettoyage CSS
                    targetCard.style.transform = ''; // Laisse le CSS de base gérer
                }, 400);
            } else {
                // Fermeture classique si c'est la dernière
                currentModal.classList.remove('is-open');
                document.body.classList.remove('modal-open');
            }
        });
    });

    // 5. BOUTON DÉCOUVRIR (Scroll fluide vers la section)
    const scrollDownBtn = document.getElementById('scrollDownBtn');
    const sectionSurprendre = document.getElementById('section-surprendre');

    if (scrollDownBtn && sectionSurprendre) {
        scrollDownBtn.addEventListener('click', () => {
            // Fait défiler la page jusqu'à la section de manière douce
            sectionSurprendre.scrollIntoView({ behavior: 'smooth' });
        });
    }
});