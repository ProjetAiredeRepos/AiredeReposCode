// Fichier : ui.js
// Gèrel'interactivité de l'interface (Menu, Modales, Sliders)
document.addEventListener('DOMContentLoaded', () => {
    // 1. Gestion du menu burger
    const openMenuBtn = document.getElementById('openMenuBtn');
    const closeMenuBtn = document.getElementById('closeMenuBtn');
    const mainMenu = document.getElementById('mainMenu');

    if (openMenuBtn && mainMenu) {
        openMenuBtn.addEventListener('click', () => {
            mainMenu.classList.add('is-active');
        });
    }

    if (closeMenuBtn && mainMenu) {
        closeMenuBtn.addEventListener('click', () => {
            mainMenu.classList.remove('is-active');
        });
    }

    // 2. Gestion des modales (En savoir plus)
    const btnSavoirPlus = document.querySelectorAll('.btn-savoir-plus');
    const closeModals = document.querySelectorAll('.close-modal-btn');

    // Ouvrir la bonne modale
    btnSavoirPlus.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const targetId = e.target.getAttribute('data-target');
            const targetModal = document.getElementById(targetId);
            if (targetModal) {
                targetModal.classList.add('is-open');
            }
        });
    });

    // Fermer les modales
    closeModals.forEach(btn => {
        btn.addEventListener('click', (e) => {
            // Trouve le parent de type .modal-details et lui retire la classe
            const modal = e.target.closest('.modal-details');
            if (modal) {
                modal.classList.remove('is-open');
            }
        });
    });
});

