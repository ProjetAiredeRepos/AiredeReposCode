<?php
// Fichier : wp-content/themes/theme-aire-repos/single-aire_repos-questionnaire.php
get_header('dashboard');
$aire_id = get_the_ID();

// Récupération des champs ACF (avec des fallbacks sécurisés si le client oublie de remplir un champ)
$q_titre = get_field('q_titre') ?: 'Questionnaire de satisfaction';
$q_sous_titre = get_field('q_sous_titre') ?: 'Aidez-nous à améliorer votre expérience.';
$q_label_1 = get_field('q_label_1') ?: "Évaluez votre niveau de satisfaction de l'aire de repos *";
$q_label_2 = get_field('q_label_2') ?: "Évaluez votre niveau de satisfaction sur la sécurité *";
$q_label_3 = get_field('q_label_3') ?: "Évaluez votre satisfaction sur les services *";
$q_label_4 = get_field('q_label_4') ?: "Comment avez-vous eu connaissance de cet itinéraire ? *";
$q_bg = get_field('q_image_fond') ? 'background-image: url(' . esc_url(get_field('q_image_fond')) . '); background-size: cover; background-position: center;' : '';

$q_profil_titre = get_field('q_profil_titre') ?: "Aidez-nous à mieux vous connaître";
$q_profil_q1 = get_field('q_profil_q1') ?: "D'où venez-vous ?";
$q_profil_q2 = get_field('q_profil_q2') ?: "Précisez votre pays";
$q_profil_q3 = get_field('q_profil_q3') ?: "De quel région venez-vous";
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
                    <span class="q-progress-text" id="progress-text">0/4</span>
                </div>
            </aside>

            <section class="q-content">
                <header class="q-header">
                    <h1 class="q-title"><?php echo esc_html($q_titre); ?></h1>
                    <div class="q-subtitle"><?php echo wp_kses_post($q_sous_titre); ?></div>
                </header>

                <form id="form-questionnaire" data-aire="<?php echo esc_attr($aire_id); ?>">

                    <!-- Q1 -->
                    <div class="q-step" role="group" aria-labelledby="q1-title">
                        <div class="q-question-header">
                            <div class="q-number">1</div>
                            <div class="q-question-text" id="q1-title">
                                <?php echo esc_html(str_replace('*', '', $q_label_1)); ?> <span class="q-required">*</span>
                            </div>
                        </div>
                        <div class="q-options-container">
                            <label class="q-radio-card"><input type="radio" name="q1_satisfaction_aire" value="4"> Très satisfaisant</label>
                            <label class="q-radio-card"><input type="radio" name="q1_satisfaction_aire" value="3"> Satisfaisant</label>
                            <label class="q-radio-card"><input type="radio" name="q1_satisfaction_aire" value="2"> Peu satisfaisant</label>
                            <label class="q-radio-card"><input type="radio" name="q1_satisfaction_aire" value="1"> Pas du tout satisfaisant</label>
                        </div>
                    </div>

                    <!-- Q2 -->
                    <div class="q-step" role="group" aria-labelledby="q2-title">
                        <div class="q-question-header">
                            <div class="q-number">2</div>
                            <div class="q-question-text" id="q2-title">
                                <?php echo esc_html(str_replace('*', '', $q_label_2)); ?> <span class="q-required">*</span>
                            </div>
                        </div>
                        <div class="q-options-container">
                            <label class="q-radio-card"><input type="radio" name="q2_satisfaction_securite" value="4"> Très satisfaisant</label>
                            <label class="q-radio-card"><input type="radio" name="q2_satisfaction_securite" value="3"> Satisfaisant</label>
                            <label class="q-radio-card"><input type="radio" name="q2_satisfaction_securite" value="2"> Peu satisfaisant</label>
                            <label class="q-radio-card"><input type="radio" name="q2_satisfaction_securite" value="1"> Pas du tout satisfaisant</label>
                        </div>
                    </div>

                    <!-- Q3 -->
                    <div class="q-step" role="group" aria-labelledby="q3-title">
                        <div class="q-question-header">
                            <div class="q-number">3</div>
                            <div class="q-question-text" id="q3-title">
                                <?php echo esc_html(str_replace('*', '', $q_label_3)); ?> <span class="q-required">*</span>
                            </div>
                        </div>
                        <div class="q-options-container">
                            <label class="q-radio-card"><input type="radio" name="q3_satisfaction_services" value="4"> Très satisfaisant</label>
                            <label class="q-radio-card"><input type="radio" name="q3_satisfaction_services" value="3"> Satisfaisant</label>
                            <label class="q-radio-card"><input type="radio" name="q3_satisfaction_services" value="2"> Peu satisfaisant</label>
                            <label class="q-radio-card"><input type="radio" name="q3_satisfaction_services" value="1"> Pas du tout satisfaisant</label>
                        </div>
                    </div>

                    <!-- Q4 -->
                    <div class="q-step" role="group" aria-labelledby="q4-title">
                        <div class="q-question-header">
                            <div class="q-number">4</div>
                            <div class="q-question-text" id="q4-title">
                                <?php echo esc_html(str_replace('*', '', $q_label_4)); ?> <span class="q-required">*</span>
                                <div class="q-help-text">Plusieurs réponses possibles</div>
                            </div>
                        </div>
                        <div class="q-options-container">
                            <label class="q-checkbox-card"><input type="checkbox" name="q4_connaissance[]" value="presse"> Presse écrite</label>
                            <label class="q-checkbox-card"><input type="checkbox" name="q4_connaissance[]" value="tv"> Télévision</label>
                            <label class="q-checkbox-card"><input type="checkbox" name="q4_connaissance[]" value="web"> Sites Internet institutionnels</label>
                            <label class="q-checkbox-card"><input type="checkbox" name="q4_connaissance[]" value="guides"> Guides de voyage</label>
                            <label class="q-checkbox-card"><input type="checkbox" name="q4_connaissance[]" value="reseaux"> Réseaux Sociaux</label>
                            <label class="q-checkbox-card"><input type="checkbox" name="q4_connaissance[]" value="recommandation"> Recommandations</label>
                            <label class="q-checkbox-card"><input type="checkbox" name="q4_connaissance[]" value="autre"> Autre</label>
                        </div>
                    </div>

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
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label for="user_country" style="display: block; font-weight: 600; margin-bottom: 8px;"><?php echo esc_html($q_profil_q1); ?> </label>
                                <select id="user_country" name="user_country" style="width: 100%; padding: 12px; border: 1px solid #E2E8F0; border-radius: 6px; background-color: #F7FAFC; color: #4A5568;">
                                    <option value="">Sélectionnez votre pays...</option>
                                    <option value="France">France</option>
                                    <option value="Allemagne">Allemagne</option>
                                    <option value="Belgique">Belgique</option>
                                    <option value="Espagne">Espagne</option>
                                    <option value="Italie">Italie</option>
                                    <option value="Pays-Bas">Pays-Bas</option>
                                    <option value="Royaume-Uni">Royaume-Uni</option>
                                    <option value="Suisse">Suisse</option>
                                    <option value="États-Unis">États-Unis</option>
                                    <option value="Canada">Canada</option>
                                    <option value="Australie">Australie</option>
                                    <option value="Japon">Japon</option>
                                    <option value="Chine">Chine</option>
                                    <option value="Brésil">Brésil</option>
                                    <option value="Autre">Autre (Préciser)...</option>
                                </select>
                            </div>

                            <div class="form-group" id="group-country-other" style="display: none; margin-bottom: 20px; padding-left: 15px; border-left: 3px solid #1A9E66;">
                                <label for="user_country_other" style="display: block; font-weight: 600; margin-bottom: 8px;"><?php echo esc_html($q_profil_q2); ?> </label>
                                <input type="text" id="user_country_other" name="user_country_other" placeholder="Saisissez votre pays" style="width: 100%; padding: 12px; border: 1px solid #E2E8F0; border-radius: 6px;">
                            </div>

                            <div class="form-group" id="group-region-fr" style="display: none; margin-bottom: 20px; padding-left: 15px; border-left: 3px solid #1A9E66;">
                                <label for="user_region_fr" style="display: block; font-weight: 600; margin-bottom: 8px;"><?php echo esc_html($q_profil_q3); ?> </label>
                                <select id="user_region_fr" name="user_region_fr" style="width: 100%; padding: 12px; border: 1px solid #E2E8F0; border-radius: 6px; background-color: #F7FAFC; color: #4A5568;">
                                    <option value="">Sélectionnez votre région...</option>
                                    <option value="Loir-et-Cher (41)">Département du Loir-et-Cher (41)</option>
                                    <option value="Centre-Val de Loire (hors 41)">Région Centre-Val de Loire (hors 41)</option>
                                    <option value="Autre région française">Autre région française</option>
                                </select>
                            </div>

                            <fieldset class="q-sub-step">
                                <legend><div class="legend-content"><?php echo esc_html($q_profil_q4); ?></div></legend>
                                <label class="q-radio-card"><input type="radio" name="profil_accompagnement" value="seul"> Seul(e)</label>
                                <label class="q-radio-card"><input type="radio" name="profil_accompagnement" value="famille"> En famille</label>
                                <label class="q-radio-card"><input type="radio" name="profil_accompagnement" value="amis"> Entre amis</label>
                                <label class="q-radio-card"><input type="radio" name="profil_accompagnement" value="guide"> Avec un guide / agence</label>
                            </fieldset>
                        </div> </div> <div class="q-rgpd-zone">
                        <p class="q-rgpd-title">CONFIDENTIALITÉ (RGPD)</p>
                        <label class="q-checkbox-card">
                            <input type="checkbox" id="rgpd-consent" required>
                            J'accepte que mes réponses soient traitées&nbsp;<strong><u>anonymement</u></strong>&nbsp;à des fins statistiques (RGPD). *
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