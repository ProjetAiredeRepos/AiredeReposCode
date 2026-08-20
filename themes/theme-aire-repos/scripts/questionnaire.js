// Fichier : wp-content/themes/theme-aire-repos/scripts/questionnaire.js

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('form-questionnaire');
    const successScreen = document.getElementById('q-success-screen');
    const errorAlert = document.getElementById('q-error-message');
    const submitBtn = document.querySelector('.q-submit-btn');

    // Éléments de progression
    const progressFill = document.getElementById('progress-fill');
    const progressText = document.getElementById('progress-text');

    if (!form) return; // Sécurité

    // Les 8 champs obligatoires avec leur VRAI nom en base de données
    const requiredSteps = [
        'input[name="q2_satisfaction_securite"]',
        'input[name="q3_satisfaction_services"]',
        'input[name="q6_satisfaction_signaletique"]',
        'input[name="q7_recommandation"]',
        'input[name="q1_satisfaction_aire"]',
        'input[name="q4_connaissance[]"]',
        'input[name="q8_duree_sejour"]',
        'input[name="q9_distance_sejour"]'
    ];

    // ==========================================================================
    // 1. MOTEUR DU WIZARD (Assistant étape par étape)
    // ==========================================================================

    // On rassemble les questions obligatoires ET la zone de profilage dans le parcours
    const steps = Array.from(document.querySelectorAll('.q-step'));
    let currentStepIndex = 0;
    const totalQuestions = 8; // On ne compte que les 8 obligatoires pour la barre

    // Fonction maîtresse : Affiche l'étape active
    function showStep(index) {
        errorAlert.classList.add('hidden'); // Cache les erreurs

        steps.forEach((step, i) => {
            if (i === index) {
                step.classList.add('is-active');
                window.scrollTo({ top: 0, behavior: 'smooth' }); // Remonte en haut
            } else {
                step.classList.remove('is-active');
            }
        });

        // On affiche le bouton "Envoyer" dès qu'on arrive aux questions facultatives (index >= 8)
        if (index >= 8) {
            submitBtn.classList.add('is-active');
            // Optionnel : On peut changer le texte pour encourager l'envoi immédiat
            // submitBtn.textContent = 'Envoyer le questionnaire';
        } else {
            submitBtn.classList.remove('is-active');
        }

        // Mise à jour de la barre de progression (bloquée à 8 maximum)
        let displayIndex = Math.min(index + 1, totalQuestions);
        if (index >= totalQuestions) displayIndex = totalQuestions;

        if (progressText && progressFill) {
            progressText.innerText = `${displayIndex}/${totalQuestions}`;
            progressFill.style.width = `${(displayIndex / totalQuestions) * 100}%`;
        }
    }

    // Écouteurs pour l'auto-avancement sur chaque étape
    steps.forEach((step, index) => {
        const radios = step.querySelectorAll('input[type="radio"]');
        const checkboxes = step.querySelectorAll('input[type="checkbox"]');

        // A. Boutons radio : Avancement automatique (SAUF si data-no-auto est présent)
        if (radios.length > 0 && !step.hasAttribute('data-no-auto')) {
            radios.forEach(radio => {
                radio.addEventListener('change', () => {
                    setTimeout(() => {
                        if (currentStepIndex < steps.length - 1) {
                            currentStepIndex++;
                            showStep(currentStepIndex);
                        }
                    }, 400);
                });
            });
        }

        // Gestion du bouton "Continuer" spécifique à l'étape A du profilage (Origine/Pays)
        const btnProfilNext = step.querySelector('.btn-profil-next');
        if (btnProfilNext) {
            btnProfilNext.addEventListener('click', () => {
                currentStepIndex++;
                showStep(currentStepIndex);
            });
        }

        // B. Checkboxes (Choix multiples Q6) : Ajout d'un bouton de validation manuel
        if (checkboxes.length > 0 && index < steps.length - 1) {
            const nextBtn = document.createElement('button');
            nextBtn.type = 'button';
            nextBtn.className = 'btn-next-step';
            nextBtn.innerText = 'Valider et continuer';

            nextBtn.addEventListener('click', () => {
                const isChecked = Array.from(checkboxes).some(cb => cb.checked);
                if (!isChecked) {
                    errorAlert.innerText = "Veuillez sélectionner au moins une option pour continuer.";
                    errorAlert.classList.remove('hidden');
                    return;
                }
                currentStepIndex++;
                showStep(currentStepIndex);
            });

            step.querySelector('.q-options-container').appendChild(nextBtn);
        }
    });

    // ==========================================================================
    // 2. LOGIQUE CONDITIONNELLE (Nouvelles questions facultatives : Origine et Pays)
    // ==========================================================================

    const origines = document.querySelectorAll('input[name="user_origine"]');
    const groupFrance = document.getElementById('group-france');
    const groupEtranger = document.getElementById('group-etranger');
    const inputRegionFr = document.getElementById('user_region_fr');
    const inputDepFr = document.getElementById('user_dep_fr');

    // NOUVEAU : Gestion des champs "Pays"
    const inputPaysEtranger = document.getElementById('user_pays_etranger');
    const groupPaysAutre = document.getElementById('group-pays-autre');
    const inputPaysAutre = document.getElementById('user_pays_autre');

    if (origines.length > 0) {
        origines.forEach(radio => {
            radio.addEventListener('change', function() {
                // Nettoyage des champs
                if (inputRegionFr) inputRegionFr.value = '';
                if (inputDepFr) inputDepFr.value = '';
                if (inputPaysEtranger) inputPaysEtranger.value = '';
                if (inputPaysAutre) inputPaysAutre.value = '';
                if (groupPaysAutre) groupPaysAutre.style.display = 'none';

                // Affichage conditionnel France/Étranger
                if (this.value === 'France') {
                    if (groupFrance) groupFrance.style.display = 'block';
                    if (groupEtranger) groupEtranger.style.display = 'none';
                } else if (this.value === 'Etranger') {
                    if (groupFrance) groupFrance.style.display = 'none';
                    if (groupEtranger) groupEtranger.style.display = 'block';
                }
            });
        });
    }

    // Écouteur pour afficher le champ texte si "Autre" est sélectionné
    if (inputPaysEtranger) {
        inputPaysEtranger.addEventListener('change', function() {
            if (this.value === 'Autre' || this.value === 'Autres pays européens') {
                if (groupPaysAutre) groupPaysAutre.style.display = 'block';
            } else {
                if (groupPaysAutre) groupPaysAutre.style.display = 'none';
                if (inputPaysAutre) inputPaysAutre.value = '';
            }
        });
    }

    // ==========================================================================
    // 3. SOUMISSION AJAX
    // ==========================================================================

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        errorAlert.classList.add('hidden');

        // Validation stricte finale (Vérifie que les 8 obligatoires sont bien remplies)
        let isValid = true;
        requiredSteps.forEach(selector => {
            const inputs = document.querySelectorAll(selector);
            if (inputs.length > 0) {
                const isChecked = Array.from(inputs).some(input => input.checked);
                if (!isChecked) isValid = false;
            }
        });

        if (!isValid) {
            errorAlert.innerText = "Veuillez répondre à toutes les questions obligatoires.";
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
                // Masquage de l'interface
                form.style.display = 'none';

                const sidebar = document.querySelector('.q-sidebar');
                if (sidebar) sidebar.style.display = 'none';

                const header = document.querySelector('.q-header');
                if (header) header.style.display = 'none';

                const qLayout = document.querySelector('.q-layout');
                if (qLayout) {
                    qLayout.style.display = 'flex';
                    qLayout.style.justifyContent = 'center';
                    qLayout.style.alignItems = 'center';
                    qLayout.style.minHeight = '90vh';
                    qLayout.style.width = '100%';
                }

                const qContent = document.querySelector('.q-content');
                if (qContent) {
                    qContent.style.display = 'flex';
                    qContent.style.justifyContent = 'center';
                    qContent.style.width = '100%';
                    qContent.style.margin = '0';
                    qContent.style.padding = '0';
                }

                // Affichage UI de succès
                successScreen.classList.remove('hidden');
                successScreen.style.display = 'flex';
                successScreen.style.flexDirection = 'column';
                successScreen.style.alignItems = 'center';
                successScreen.style.justifyContent = 'center';
                successScreen.style.textAlign = 'center';
                successScreen.style.backgroundColor = '#ffffff';
                successScreen.style.padding = '3rem 2rem';
                successScreen.style.borderRadius = '16px';
                successScreen.style.boxShadow = '0 10px 30px rgba(0,0,0,0.08)';
                successScreen.style.maxWidth = '500px';
                successScreen.style.width = '90%';

                const icon = successScreen.querySelector('.q-success-icon');
                if (icon) {
                    icon.style.fontSize = '4rem';
                    icon.style.color = '#1A9E66';
                    icon.style.marginBottom = '20px';
                }
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

    // Lancement de l'assistant (Affiche la première question)
    showStep(currentStepIndex);
});