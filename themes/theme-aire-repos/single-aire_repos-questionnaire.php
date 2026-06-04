<?php
// Fichier : wp-content/themes/theme-aire-repos/single-aire_repos-questionnaire.php
get_header();
$aire_id = get_the_ID();

// Récupération des champs ACF (avec des fallbacks sécurisés si le client oublie de remplir un champ)
$q_titre = get_field('q_titre') ?: 'Questionnaire de satisfaction';
$q_sous_titre = get_field('q_sous_titre') ?: 'Aidez-nous à améliorer votre expérience.';
$q_label_1 = get_field('q_label_1') ?: "Évaluez votre niveau de satisfaction de l'aire de repos *";
$q_label_2 = get_field('q_label_2') ?: "Évaluez votre niveau de satisfaction sur la sécurité *";
$q_label_3 = get_field('q_label_3') ?: "Évaluez votre satisfaction sur les services *";
$q_label_4 = get_field('q_label_4') ?: "Comment avez-vous eu connaissance de cet itinéraire ? *";
$q_label_5 = get_field('q_label_5') ?: "Avez-vous des suggestions ou remarques complémentaires ?";
$q_bg = get_field('q_image_fond') ? 'background-image: url(' . esc_url(get_field('q_image_fond')) . '); background-size: cover; background-position: center;' : '';

$q_profil_titre = get_field('q_profil_titre') ?: "Aidez-nous à mieux vous connaître";
$q_profil_q1 = get_field('q_profil_q1') ?: "D'où venez-vous ?";
$q_profil_q2 = get_field('q_profil_q2') ?: "Vous venez plutôt de...";
$q_profil_q3 = get_field('q_profil_q3') ?: "De quel pays venez-vous ?";
$q_profil_q4 = get_field('q_profil_q4') ?: "Comment êtes-vous venu ?";
?>

    <main class="questionnaire-app" style="<?php echo $q_bg; ?>">
        <div class="q-layout">

            <aside class="q-sidebar">
                <div class="q-progress-box">
                    <span class="q-progress-label">PROGRESSION</span>
                    <div class="q-progress-bar-container">
                        <div class="q-progress-fill" id="progress-fill" style="width: 0%;"></div>
                    </div>
                    <span class="q-progress-text" id="progress-text">0/5</span>
                </div>
            </aside>

            <section class="q-content">
                <header class="q-header">
                    <h1 class="q-title"><?php echo esc_html($q_titre); ?></h1>
                    <p class="q-subtitle"><?php echo esc_html($q_sous_titre); ?></p>
                </header>

                <form id="form-questionnaire" data-aire="<?php echo esc_attr($aire_id); ?>">

                    <!-- Q1 -->
                    <fieldset class="q-step" data-step="1">
                        <legend>
                            <div class="legend-content">
                                <div class="q-number">1</div>
                                <div class="q-question-wrapper">
                                    <span class="q-question-text"><?php echo esc_html($q_label_1); ?></span>
                                    <span class="q-required">*</span>
                                </div>
                            </div>
                        </legend>
                        <div class="q-options-container">
                            <label class="q-radio-card"><input type="radio" name="q1_satisfaction_aire" value="4"> Très satisfaisant</label>
                            <label class="q-radio-card"><input type="radio" name="q1_satisfaction_aire" value="3"> Satisfaisant</label>
                            <label class="q-radio-card"><input type="radio" name="q1_satisfaction_aire" value="2"> Peu satisfaisant</label>
                            <label class="q-radio-card"><input type="radio" name="q1_satisfaction_aire" value="1"> Pas du tout satisfaisant</label>
                        </div>
                    </fieldset>

                    <!-- Q2 -->
                    <fieldset class="q-step" data-step="2">
                        <legend>
                            <div class="legend-content">
                                <div class="q-number">2</div>
                                <div class="q-question-wrapper">
                                    <span class="q-question-text"><?php echo esc_html($q_label_2); ?></span>
                                    <span class="q-required">*</span>
                                </div>
                            </div>
                        </legend>
                        <div class="q-options-container">
                            <label class="q-radio-card"><input type="radio" name="q2_satisfaction_securite" value="4"> Très satisfaisant</label>
                            <label class="q-radio-card"><input type="radio" name="q2_satisfaction_securite" value="3"> Satisfaisant</label>
                            <label class="q-radio-card"><input type="radio" name="q2_satisfaction_securite" value="2"> Peu satisfaisant</label>
                            <label class="q-radio-card"><input type="radio" name="q2_satisfaction_securite" value="1"> Pas du tout satisfaisant</label>
                        </div>
                    </fieldset>

                    <!-- Q3 -->
                    <fieldset class="q-step" data-step="3">
                        <legend>
                            <div class="legend-content">
                                <div class="q-number">3</div>
                                <div class="q-question-wrapper">
                                    <span class="q-question-text"><?php echo esc_html($q_label_3); ?></span>
                                    <span class="q-required">*</span>
                                </div>
                            </div>
                        </legend>
                        <div class="q-options-container">
                            <label class="q-radio-card"><input type="radio" name="q3_satisfaction_services" value="4"> Très satisfaisant</label>
                            <label class="q-radio-card"><input type="radio" name="q3_satisfaction_services" value="3"> Satisfaisant</label>
                            <label class="q-radio-card"><input type="radio" name="q3_satisfaction_services" value="2"> Peu satisfaisant</label>
                            <label class="q-radio-card"><input type="radio" name="q3_satisfaction_services" value="1"> Pas du tout satisfaisant</label>
                        </div>
                    </fieldset>

                    <!-- Q4 -->
                    <fieldset class="q-step" data-step="4">
                        <legend>
                            <div class="legend-content">
                                <div class="q-number">4</div>
                                <div class="q-question-wrapper">
                                    <span class="q-question-text"><?php echo esc_html($q_label_4); ?></span>
                                    <span class="q-required">*</span>
                                    <small class="q-help-text">Plusieurs réponses possibles</small>
                                </div>
                            </div>
                        </legend>
                        <div class="q-options-container">
                            <label class="q-checkbox-card"><input type="checkbox" name="q4_connaissance[]" value="presse"> Presse écrite</label>
                            <label class="q-checkbox-card"><input type="checkbox" name="q4_connaissance[]" value="tv"> Télévision</label>
                            <label class="q-checkbox-card"><input type="checkbox" name="q4_connaissance[]" value="web"> Sites Internet institutionnels</label>
                            <label class="q-checkbox-card"><input type="checkbox" name="q4_connaissance[]" value="guides"> Guides de voyage</label>
                            <label class="q-checkbox-card"><input type="checkbox" name="q4_connaissance[]" value="reseaux"> Réseaux Sociaux</label>
                            <label class="q-checkbox-card"><input type="checkbox" name="q4_connaissance[]" value="recommandation"> Recommandations</label>
                            <label class="q-checkbox-card"><input type="checkbox" name="q4_connaissance[]" value="autre"> Autre</label>
                        </div>
                    </fieldset>

                    <!-- Q5 -->
                    <fieldset class="q-step" data-step="5">
                        <legend>
                            <div class="legend-content">
                                <div class="q-number">5</div>
                                <div class="q-question-wrapper">
                                    <span class="q-question-text"><?php echo esc_html($q_label_5); ?></span>
                                    <span class="q-badge-facultatif">Facultatif</span>
                                </div>
                            </div>
                        </legend>
                        <div class="q-options-container">
                            <textarea name="q5_suggestions" rows="4" placeholder="Écrivez votre message ici..."></textarea>
                        </div>
                    </fieldset>

                    <!-- Zone Profilage Dynamique (Accordéon) -->
                    <div class="q-profilage-zone">
                        <!-- Le bouton déclencheur de l'accordéon -->
                        <button type="button" class="q-accordion-trigger" id="profilage-trigger" aria-expanded="false">
                            <div class="q-profilage-header">
                                <h3><?php echo esc_html($q_profil_titre); ?> <span class="q-badge-facultatif">Facultatif</span></h3>
                                <p>Quelques questions anonymes sur votre profil de visiteur</p>
                            </div>
                            <svg class="q-chevron" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>

                        <!-- Le conteneur masqué -->
                        <div class="q-accordion-content" id="profilage-content">
                            <div class="q-accordion-inner">
                                <fieldset class="q-sub-step">
                                    <legend><div class="legend-content"><?php echo esc_html($q_profil_q1); ?></div></legend>
                                    <label class="q-radio-card"><input type="radio" name="profil_origine" value="fr"> Je suis français(e)</label>
                                    <label class="q-radio-card"><input type="radio" name="profil_origine" value="et"> Je suis étranger(ère)</label>
                                </fieldset>

                                <fieldset class="q-sub-step hidden" id="bloc-region">
                                    <legend><div class="legend-content"><?php echo esc_html($q_profil_q2); ?></div></legend>
                                    <label class="q-radio-card"><input type="radio" name="profil_region" value="dep41"> Du département (41)</label>
                                    <label class="q-radio-card"><input type="radio" name="profil_region" value="centre"> De la région (Centre-Val de Loire)</label>
                                    <label class="q-radio-card"><input type="radio" name="profil_region" value="autre_fr"> D'une autre région</label>
                                </fieldset>

                                <fieldset class="q-sub-step hidden" id="bloc-pays">
                                    <legend><div class="legend-content"><?php echo esc_html($q_profil_q3); ?></div></legend>
                                    <select name="profil_pays" id="select-pays" class="q-select">
                                        <option value="">Sélectionnez...</option>
                                        <option value="allemagne">Allemagne</option>
                                        <option value="belgique">Belgique</option>
                                        <option value="espagne">Espagne</option>
                                        <option value="italie">Italie</option>
                                        <option value="luxembourg">Luxembourg</option>
                                        <option value="pays-bas">Pays-Bas</option>
                                        <option value="portugal">Portugal</option>
                                        <option value="uk">Royaume-Uni</option>
                                        <option value="suisse">Suisse</option>
                                        <option value="etats-unis">États-Unis</option>
                                        <option value="canada">Canada</option>
                                        <option value="autre">Autre pays</option>
                                    </select>
                                    <input type="text" name="profil_pays_autre" id="input-pays-autre" class="q-input hidden" placeholder="Précisez votre pays...">
                                </fieldset>

                                <fieldset class="q-sub-step">
                                    <legend><div class="legend-content"><?php echo esc_html($q_profil_q4); ?></div></legend>
                                    <label class="q-radio-card"><input type="radio" name="profil_accompagnement" value="seul"> Seul(e)</label>
                                    <label class="q-radio-card"><input type="radio" name="profil_accompagnement" value="famille"> En famille</label>
                                    <label class="q-radio-card"><input type="radio" name="profil_accompagnement" value="amis"> Entre amis</label>
                                    <label class="q-radio-card"><input type="radio" name="profil_accompagnement" value="guide"> Avec un guide / agence</label>
                                </fieldset>
                            </div>
                        </div>
                    </div>

                    <div class="q-rgpd-zone">
                        <p class="q-rgpd-title">CONFIDENTIALITÉ (RGPD)</p>
                        <label class="q-checkbox-card">
                            <input type="checkbox" id="rgpd-consent" required>
                            J'accepte que mes réponses soient traitées anonymement à des fins statistiques (RGPD). *
                        </label>
                    </div>

                    <div id="q-error-message" class="q-alert hidden">Veuillez répondre aux questions obligatoires (1 à 4) et accepter les conditions RGPD.</div>

                    <button type="submit" class="q-submit-btn">Envoyer le questionnaire</button>
                </form>

                <div id="q-success-screen" class="hidden">
                    <div class="q-success-icon">✓</div>
                    <h2>Merci pour votre retour !</h2>
                    <p>Votre questionnaire a bien été envoyé. Vos réponses nous aideront à améliorer l'expérience.</p>
                    <p class="q-success-footer">Bonne route !</p>
                </div>

            </section>
        </div>
    </main>

<?php get_footer(); ?>