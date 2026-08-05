<?php
// Fichier : wp-content/themes/theme-aire-repos/single-aire_repos-questionnaire.php
get_header('dashboard');
$aire_id = get_the_ID();

// Récupération des champs ACF (avec des fallbacks sécurisés si le client oublie de remplir un champ)
$q_titre = get_field('q_titre') ?: 'Questionnaire de satisfaction';
$q_sous_titre = get_field('q_sous_titre') ?: 'Aidez-nous à améliorer votre expérience.';
$q_label_1 = get_field('q_label_1') ?: "Comment jugez-vous la sécurité des aménagements de la Loire à vélo ? *";
$q_label_2 = get_field('q_label_2') ?: "Comment jugez-vous les équipements et services le long de la Loire à vélo (points d'eau, sanitaires, aires de repos...) ?   *";
$q_label_3 = get_field('q_label_3') ?: "Comment jugez-vous la qualité de la signalétique le long du parcours ?  *";
$q_label_4 = get_field('q_label_4') ?: "Recommanderiez-vous l'itinéraire de la Loire à vélo à vos proches ? *";

// NOUVEAU : Récupération des 4 nouveaux champs ACF (à créer dans ton interface WordPress)
$q_label_5 = get_field('q_label_5') ?: "Evaluez votre niveau de satisfaction de l'aire de repos de Chaumont-sur-Loire *";
$q_label_6 = get_field('q_label_6') ?: "Comment avez-vous connu la Loire à vélo ?  *";
$q_label_7 = get_field('q_label_7') ?: "Combien de jours dure votre séjour sur la Loire à vélo ?  *";
$q_label_8 = get_field('q_label_8') ?: "Quelle distance moyenne parcourez-vous par jour ? *";

$q_bg = get_field('q_image_fond') ? 'background-image: url(' . esc_url(get_field('q_image_fond')) . ');' : '';

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
                    <!-- CORRECTION : On passe à 8 questions -->
                    <span class="q-progress-text" id="progress-text">0/8</span>
                </div>
            </aside>

            <section class="q-content">
                <header class="q-header">
                    <h1 class="q-title"><?php echo esc_html($q_titre); ?></h1>
                    <div class="q-subtitle"><?php echo wp_kses_post($q_sous_titre); ?></div>
                </header>

                <form id="form-questionnaire" data-aire="<?php echo esc_attr($aire_id); ?>">

                    <!-- VISUEL Q1 : Sécurité (Ancienne q2) -->
                    <div class="q-step" role="group">
                        <div class="q-question-header">
                            <div class="q-number">1</div>
                            <div class="q-question-text"><?php echo esc_html(str_replace('*', '', $q_label_1)); ?> <span class="q-required">*</span></div>
                        </div>
                        <div class="q-options-container">
                            <label class="q-radio-card"><input type="radio" name="q2_satisfaction_securite" value="4"> Très satisfaisante</label>
                            <label class="q-radio-card"><input type="radio" name="q2_satisfaction_securite" value="3"> Satisfaisante</label>
                            <label class="q-radio-card"><input type="radio" name="q2_satisfaction_securite" value="2"> Peu satisfaisante</label>
                            <label class="q-radio-card"><input type="radio" name="q2_satisfaction_securite" value="1"> Insatisfaisante</label>
                        </div>
                    </div>

                    <!-- VISUEL Q2 : Équipements (Ancienne q3) -->
                    <div class="q-step" role="group">
                        <div class="q-question-header">
                            <div class="q-number">2</div>
                            <div class="q-question-text"><?php echo esc_html(str_replace('*', '', $q_label_2)); ?> <span class="q-required">*</span></div>
                        </div>
                        <div class="q-options-container">
                            <label class="q-radio-card"><input type="radio" name="q3_satisfaction_services" value="4"> Très satisfaisant</label>
                            <label class="q-radio-card"><input type="radio" name="q3_satisfaction_services" value="3"> Satisfaisant</label>
                            <label class="q-radio-card"><input type="radio" name="q3_satisfaction_services" value="2"> Peu satisfaisant</label>
                            <label class="q-radio-card"><input type="radio" name="q3_satisfaction_services" value="1"> Insatisfaisant</label>
                        </div>
                    </div>

                    <!-- VISUEL Q3 : Signalétique (NOUVELLE q6) -->
                    <div class="q-step" role="group">
                        <div class="q-question-header">
                            <div class="q-number">3</div>
                            <div class="q-question-text"><?php echo esc_html(str_replace('*', '', $q_label_3)); ?> <span class="q-required">*</span></div>
                        </div>
                        <div class="q-options-container">
                            <label class="q-radio-card"><input type="radio" name="q6_satisfaction_signaletique" value="4"> Très satisfaisante</label>
                            <label class="q-radio-card"><input type="radio" name="q6_satisfaction_signaletique" value="3"> Satisfaisante</label>
                            <label class="q-radio-card"><input type="radio" name="q6_satisfaction_signaletique" value="2"> Peu satisfaisante</label>
                            <label class="q-radio-card"><input type="radio" name="q6_satisfaction_signaletique" value="1"> Insatisfaisante</label>
                        </div>
                    </div>

                    <!-- VISUEL Q4 : Recommandation (NOUVELLE q7) -->
                    <div class="q-step" role="group">
                        <div class="q-question-header">
                            <div class="q-number">4</div>
                            <div class="q-question-text"><?php echo esc_html(str_replace('*', '', $q_label_4)); ?> <span class="q-required">*</span></div>
                        </div>
                        <div class="q-options-container">
                            <label class="q-radio-card"><input type="radio" name="q7_recommandation" value="4"> Oui tout à fait</label>
                            <label class="q-radio-card"><input type="radio" name="q7_recommandation" value="3"> Oui probablement</label>
                            <label class="q-radio-card"><input type="radio" name="q7_recommandation" value="2"> Non probablement pas</label>
                            <label class="q-radio-card"><input type="radio" name="q7_recommandation" value="1"> Non pas du tout</label>
                        </div>
                    </div>

                    <!-- VISUEL Q5 : Aire de repos (Ancienne q1) -->
                    <div class="q-step" role="group">
                        <div class="q-question-header">
                            <div class="q-number">5</div>
                            <div class="q-question-text"><?php echo esc_html(str_replace('*', '', $q_label_5)); ?> <span class="q-required">*</span></div>
                        </div>
                        <div class="q-options-container">
                            <label class="q-radio-card"><input type="radio" name="q1_satisfaction_aire" value="4"> Très satisfaisante</label>
                            <label class="q-radio-card"><input type="radio" name="q1_satisfaction_aire" value="3"> Satisfaisante</label>
                            <label class="q-radio-card"><input type="radio" name="q1_satisfaction_aire" value="2"> Peu satisfaisante</label>
                            <label class="q-radio-card"><input type="radio" name="q1_satisfaction_aire" value="1"> Insatisfaisante</label>
                        </div>
                    </div>

                    <!-- VISUEL Q6 : Connaissance (Ancienne q4) -->
                    <div class="q-step" role="group">
                        <div class="q-question-header">
                            <div class="q-number">6</div>
                            <div class="q-question-text">
                                <?php echo esc_html(str_replace('*', '', $q_label_6)); ?> <span class="q-required">*</span>
                                <div class="q-help-text">Plusieurs réponses possibles</div>
                            </div>
                        </div>
                        <div class="q-options-container">
                            <label class="q-checkbox-card"><input type="checkbox" name="q4_connaissance[]" value="Site web"> Site web</label>
                            <label class="q-checkbox-card"><input type="checkbox" name="q4_connaissance[]" value="Réseaux sociaux"> Réseaux sociaux</label>
                            <label class="q-checkbox-card"><input type="checkbox" name="q4_connaissance[]" value="Reportage"> Reportage (presse écrite, télévision...)</label>
                            <label class="q-checkbox-card"><input type="checkbox" name="q4_connaissance[]" value="Guides de voyage"> Guides de voyage</label>
                            <label class="q-checkbox-card"><input type="checkbox" name="q4_connaissance[]" value="Office de tourisme"> Office de tourisme</label>
                            <label class="q-checkbox-card"><input type="checkbox" name="q4_connaissance[]" value="Recommandations"> Recommandations</label>
                            <label class="q-checkbox-card"><input type="checkbox" name="q4_connaissance[]" value="Autre"> Autre</label>
                        </div>
                    </div>

                    <!-- VISUEL Q7 : Durée de séjour (NOUVELLE q8) -->
                    <div class="q-step" role="group">
                        <div class="q-question-header">
                            <div class="q-number">7</div>
                            <div class="q-question-text"><?php echo esc_html(str_replace('*', '', $q_label_7)); ?> <span class="q-required">*</span></div>
                        </div>
                        <div class="q-options-container">
                            <label class="q-radio-card"><input type="radio" name="q8_duree_sejour" value="1"> 1 journée (ou demi-journée)</label>
                            <label class="q-radio-card"><input type="radio" name="q8_duree_sejour" value="2"> 2 à 3 jours</label>
                            <label class="q-radio-card"><input type="radio" name="q8_duree_sejour" value="3"> 4 à 7 jours</label>
                            <label class="q-radio-card"><input type="radio" name="q8_duree_sejour" value="4"> Plus d'une semaine</label>
                        </div>
                    </div>

                    <!-- VISUEL Q8 : Distance moyenne (NOUVELLE q9) -->
                    <div class="q-step" role="group">
                        <div class="q-question-header">
                            <div class="q-number">8</div>
                            <div class="q-question-text"><?php echo esc_html(str_replace('*', '', $q_label_8)); ?> <span class="q-required">*</span></div>
                        </div>
                        <div class="q-options-container">
                            <label class="q-radio-card"><input type="radio" name="q9_distance_sejour" value="1"> Moins de 25 km</label>
                            <label class="q-radio-card"><input type="radio" name="q9_distance_sejour" value="2"> Entre 25 et 50 km</label>
                            <label class="q-radio-card"><input type="radio" name="q9_distance_sejour" value="3"> Entre 50 et 75 km</label>
                            <label class="q-radio-card"><input type="radio" name="q9_distance_sejour" value="4"> Plus de 75 km</label>
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
                        </div>
                    </div>

                    <div id="q-error-message" class="q-alert hidden">Veuillez sélectionner au moins une option pour continuer.</div>

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