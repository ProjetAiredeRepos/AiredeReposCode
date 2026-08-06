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

    // CORRECTION : On déclare les 8 champs obligatoires avec leur VRAI nom en base de données
    const requiredSteps = [
        'input[name="q2_satisfaction_securite"]', // Visuel Q1
        'input[name="q3_satisfaction_services"]', // Visuel Q2
        'input[name="q6_satisfaction_signaletique"]', // Visuel Q3
        'input[name="q7_recommandation"]', // Visuel Q4
        'input[name="q1_satisfaction_aire"]', // Visuel Q5
        'input[name="q4_connaissance[]"]', // Visuel Q6
        'input[name="q8_duree_sejour"]', // Visuel Q7
        'input[name="q9_distance_sejour"]'  // Visuel Q8
    ];

    // MOTEUR DU WIZARD (Assistant étape par étape)
    const steps = Array.from(document.querySelectorAll('.q-step'));
    const profilageZone = document.querySelector('.q-profilage-zone');
    if (profilageZone) steps.push(profilageZone);

    let currentStepIndex = 0;
    const totalQuestions = 8;

    // Fonction qui affiche l'étape active et masque les autres
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

        // Affiche le bouton Envoyer uniquement à la toute fin
        if (index === steps.length - 1) {
            submitBtn.classList.add('is-active');
        } else {
            submitBtn.classList.remove('is-active');
        }

        // Mise à jour de la barre de progression
        let displayIndex = Math.min(index + 1, totalQuestions);
        if (index === steps.length - 1) displayIndex = totalQuestions; // Force 8/8 sur le profilage

        if (progressText && progressFill) {
            progressText.innerText = `${displayIndex}/${totalQuestions}`;
            progressFill.style.width = `${(displayIndex / totalQuestions) * 100}%`;
        }
    }

    // Écouteurs pour l'auto-avancement
    steps.forEach((step, index) => {
        const radios = step.querySelectorAll('input[type="radio"]');
        const checkboxes = step.querySelectorAll('input[type="checkbox"]');

        // A. Boutons radio : Avancement automatique après 400ms
        if (radios.length > 0) {
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

        // B. Checkboxes (Choix multiples) : Ajout d'un bouton de validation manuel
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

    // --- ACCORDÉON PROFILAGE ---
    const profilageTrigger = document.getElementById('profilage-trigger');
    const profilageContent = document.getElementById('profilage-content');

    if (profilageContent) {
        // CORRECTION : On force le masquage physique au chargement
        profilageContent.style.display = 'none';
    }

    if (profilageTrigger && profilageContent) {
        profilageTrigger.addEventListener('click', () => {
            const isExpanded = profilageTrigger.getAttribute('aria-expanded') === 'true';
            profilageTrigger.setAttribute('aria-expanded', !isExpanded);
            // On bascule l'affichage en même temps que la flèche
            profilageContent.style.display = isExpanded ? 'none' : 'block';
        });
    }

    // --- LOGIQUE CONDITIONNELLE : PAYS ET RÉGIONS ---
    const countrySelect = document.getElementById('user_country');
    const groupOther = document.getElementById('group-country-other');
    const groupRegion = document.getElementById('group-region-fr');

    if (countrySelect) {
        countrySelect.addEventListener('change', function() {
            const selectedValue = this.value;

            groupOther.style.display = 'none';
            groupRegion.style.display = 'none';

            document.getElementById('user_country_other').value = '';
            document.getElementById('user_region_fr').value = '';

            if (selectedValue === 'France') {
                groupRegion.style.display = 'block';
            } else if (selectedValue === 'Autre') {
                groupOther.style.display = 'block';
            }
        });
    }

    // --- MISE À JOUR DE LA BARRE DE PROGRESSION ---
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

            // CORRECTION : Calcul sur 8 questions
            completed = Math.min(completed, 8);
            if (progressText && progressFill) {
                progressText.innerText = `${completed}/8`;
                progressFill.style.width = `${(completed / 8) * 100}%`;
            }
        });

        // --- SOUMISSION AJAX ---
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            errorAlert.classList.add('hidden');

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
                    // 1. On cache les anciens éléments (Formulaire, Sidebar, Header)
                    form.style.display = 'none';

                    const sidebar = document.querySelector('.q-sidebar');
                    if (sidebar) sidebar.style.display = 'none';

                    const header = document.querySelector('.q-header');
                    if (header) header.style.display = 'none'; // Cache le titre pour recentrer la vue

                    // 2. On force les conteneurs parents à se centrer
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

                    // 3. On stylise la carte de succès pour qu'elle soit belle et large
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

                    // 4. L'icône de succès
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
    }
    // LANCEMENT DU WIZARD
    showStep(currentStepIndex);
});