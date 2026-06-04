// Fichier : wp-content/themes/theme-aire-repos/scripts/questionnaire.js

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('form-questionnaire');
    const successScreen = document.getElementById('q-success-screen');
    const errorAlert = document.getElementById('q-error-message');
    const submitBtn = document.querySelector('.q-submit-btn');

    // Éléments de progression
    const progressFill = document.getElementById('progress-fill');
    const progressText = document.getElementById('progress-text');
    const requiredSteps = [
        'input[name="q1_satisfaction_aire"]',
        'input[name="q2_satisfaction_securite"]',
        'input[name="q3_satisfaction_services"]',
        'input[name="q4_connaissance[]"]',
        'textarea[name="q5_suggestions"]'
    ];

    // Logique Conditionnelle du Profilage
    const radiosOrigine = document.querySelectorAll('input[name="profil_origine"]');
    const blocRegion = document.getElementById('bloc-region');
    const blocPays = document.getElementById('bloc-pays');
    const selectPays = document.getElementById('select-pays');
    const inputPaysAutre = document.getElementById('input-pays-autre');

    // --- ACCORDÉON PROFILAGE ---
    const profilageTrigger = document.getElementById('profilage-trigger');

    if (profilageTrigger) {
        profilageTrigger.addEventListener('click', () => {
            // Lit l'état actuel (ouvert ou fermé)
            const isExpanded = profilageTrigger.getAttribute('aria-expanded') === 'true';
            // Inverse l'état (ce qui déclenche l'animation CSS Grid et fait tourner le chevron)
            profilageTrigger.setAttribute('aria-expanded', !isExpanded);
        });
    }

    if (radiosOrigine.length > 0) {
        radiosOrigine.forEach(radio => {
            radio.addEventListener('change', (e) => {
                if (e.target.value === 'fr') {
                    blocRegion.classList.remove('hidden');
                    blocPays.classList.add('hidden');
                } else {
                    blocRegion.classList.add('hidden');
                    blocPays.classList.remove('hidden');
                }
            });
        });
    }

    if (selectPays) {
        selectPays.addEventListener('change', (e) => {
            if (e.target.value === 'autre') {
                inputPaysAutre.classList.remove('hidden');
            } else {
                inputPaysAutre.classList.add('hidden');
            }
        });
    }

    // Mise à jour de la barre de progression
    if (form) {
        form.addEventListener('change', () => {
            let completed = 0;
            requiredSteps.forEach((selector) => {
                const inputs = document.querySelectorAll(selector);
                if (inputs.length > 0 && (inputs[0].type === 'radio' || inputs[0].type === 'checkbox')) {
                    const isChecked = Array.from(inputs).some(input => input.checked);
                    if (isChecked) completed++;
                } else if (inputs.length > 0 && inputs[0].value.trim() !== '') {
                    completed++;
                }
            });

            // Limite à 5 (les 5 questions principales)
            completed = Math.min(completed, 5);
            if (progressText && progressFill) {
                progressText.innerText = `${completed}/5`;
                progressFill.style.width = `${(completed / 5) * 100}%`;
            }
        });

        // Soumission AJAX
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            errorAlert.classList.add('hidden');

            // Vérification personnalisée pour les checkbox Q4 (au moins une requise)
            const q4Checked = document.querySelectorAll('input[name="q4_connaissance[]"]:checked').length > 0;
            if (!q4Checked) {
                errorAlert.classList.remove('hidden');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.textContent = 'Envoi en cours...';

            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            data['q4_connaissance'] = formData.getAll('q4_connaissance[]');
            data['id_aire'] = form.getAttribute('data-aire');

            try {
                const response = await fetch('/wp-json/aire-repos/v1/submit-survey', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (response.ok && result.status === 'success') {
                    form.style.display = 'none';
                    successScreen.classList.remove('hidden');
                    const sidebar = document.querySelector('.q-sidebar');
                    if (sidebar) sidebar.style.display = 'none';
                } else {
                    throw new Error(result.message || "Erreur serveur");
                }
            } catch (error) {
                errorAlert.innerText = error.message;
                errorAlert.classList.remove('hidden');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Envoyer le questionnaire';
            }
        });
    }
});