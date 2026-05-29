document.addEventListener("DOMContentLoaded", function() {

    // Gestion de la carte
    const container = document.getElementById('carteContainer');
    const pan = document.getElementById('carteScrollPan');
    if(container && pan) {
        container.scrollLeft = (pan.offsetWidth - container.offsetWidth) / 2;
    }

    // --- LOGIQUE DES MODALS ---
    const pins = document.querySelectorAll('.pin-button');
    const closeButtons = document.querySelectorAll('.modal-close-btn, .modal-overlay-close');

    // Ouverture au clic sur un point
    pins.forEach(pin => {
        pin.addEventListener('click', function() {
            const modalId = this.getAttribute('data-target');
            const modal = document.getElementById(modalId);
            if(modal) {
                modal.classList.add('is-open');
                document.body.style.overflow = 'hidden'; /* Bloque le scroll du site en arrière-plan */
            }
        });
    });

    // Fermeture au clic sur la croix ou à côté
    closeButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const activeModal = document.querySelector('.modal-bottom-sheet.is-open');
            if(activeModal) {
                activeModal.classList.remove('is-open');
                document.body.style.overflow = ''; /* Libère le scroll du site */
            }
        });
    });
});

document.addEventListener("DOMContentLoaded", function() {
    const btnDecouvrir = document.querySelector('.btn-cta-decouvrir');

    if(btnDecouvrir) {
        btnDecouvrir.addEventListener('click', function(e) {
            // Empêche le saut brutal par défaut
            e.preventDefault();

            // Récupère la cible (le carrousel)
            const targetId = this.getAttribute('href');
            const targetSection = document.querySelector(targetId);

            if(targetSection) {
                // Fait glisser la page doucement jusqu'à la cible
                targetSection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    }
});