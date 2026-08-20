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
$q_profil_q2 = get_field('q_profil_q2') ?: "Sélectionnez votre pays...";
$q_profil_q3 = get_field('q_profil_q3') ?: "Sélectionnez votre région...";
$q_profil_q4 = get_field('q_profil_q4') ?: "Sélectionnez votre département...";
$q_profil_q5 = get_field('q_profil_q5') ?: "Comment voyagez-vous ?";
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

                    <!-- Étape 9 : Question Facultative A (Origine) -->
                    <!-- L'attribut data-no-auto indique au JS de ne pas passer à la suite tout de suite -->
                    <div class="q-step" role="group" data-no-auto="true">

                        <div class="q-profilage-header">
                            <h3>Aidez-nous à mieux vous connaître <span>(facultatif)</span></h3>
                            <!-- NOUVEAU : Texte de réassurance -->
                            <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem; color: rgba(255,255,255,0.9);">Vos réponses sont 100% anonymes.</p>
                        </div>

                        <div class="q-question-header">
                            <div class="q-number" style="background-color: #4A5568; color: white;">a</div>
                            <div class="q-question-text"><?php echo esc_html($q_profil_q1); ?></div>
                        </div>

                        <div class="q-options-container" style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                            <label class="q-radio-card" style="flex: 1;"><input type="radio" name="user_origine" value="France"> France</label>
                            <label class="q-radio-card" style="flex: 1;"><input type="radio" name="user_origine" value="Etranger"> Étranger</label>
                        </div>

                        <!-- Sous-menu France -->
                        <div id="group-france" style="display: none; padding: 1.5rem; background: #F7FAFC; border-left: 4px solid var(--q-primary); border-radius: 4px; margin-bottom: 1.5rem;">
                            <div class="form-group" style="margin-bottom: 1rem;">
                                <label for="user_region_fr" style="display: block; font-weight: 600; margin-bottom: 8px;">Quelle région ?</label>
                                <select id="user_region_fr" name="user_region_fr" style="width: 100%; padding: 12px; border: 1px solid #E2E8F0; border-radius: 6px;">
                                    <option value="">Sélectionnez votre région...</option>
                                    <option value="Auvergne-Rhône-Alpes">Auvergne-Rhône-Alpes</option>
                                    <option value="Bourgogne-Franche-Comté">Bourgogne-Franche-Comté</option>
                                    <option value="Bretagne">Bretagne</option>
                                    <option value="Centre-Val de Loire">Centre-Val de Loire</option>
                                    <option value="Corse">Corse</option>
                                    <option value="Grand Est">Grand Est</option>
                                    <option value="Hauts-de-France">Hauts-de-France</option>
                                    <option value="Île-de-France">Île-de-France</option>
                                    <option value="Normandie">Normandie</option>
                                    <option value="Nouvelle-Aquitaine">Nouvelle-Aquitaine</option>
                                    <option value="Occitanie">Occitanie</option>
                                    <option value="Pays de la Loire">Pays de la Loire</option>
                                    <option value="Provence-Alpes-Côte d'Azur">Provence-Alpes-Côte d'Azur</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="user_dep_fr" style="display: block; font-weight: 600; margin-bottom: 8px;">Quel département ?</label>
                                <select id="user_dep_fr" name="user_dep_fr" style="width: 100%; padding: 12px; border: 1px solid #E2E8F0; border-radius: 6px;">
                                    <option value="">Sélectionnez votre département...</option>
                                    <?php
                                    // Le CTO : Génération dynamique des 96 départements métropolitains + DOM-TOM
                                    $departements = [
                                            '01 - Ain', '02 - Aisne', '03 - Allier', '04 - Alpes-de-Haute-Provence', '05 - Hautes-Alpes', '06 - Alpes-Maritimes', '07 - Ardèche', '08 - Ardennes', '09 - Ariège', '10 - Aube', '11 - Aude', '12 - Aveyron', '13 - Bouches-du-Rhône', '14 - Calvados', '15 - Cantal', '16 - Charente', '17 - Charente-Maritime', '18 - Cher', '19 - Corrèze', '2A - Corse-du-Sud', '2B - Haute-Corse', '21 - Côte-d\'Or', '22 - Côtes-d\'Armor', '23 - Creuse', '24 - Dordogne', '25 - Doubs', '26 - Drôme', '27 - Eure', '28 - Eure-et-Loir', '29 - Finistère', '30 - Gard', '31 - Haute-Garonne', '32 - Gers', '33 - Gironde', '34 - Hérault', '35 - Ille-et-Vilaine', '36 - Indre', '37 - Indre-et-Loire', '38 - Isère', '39 - Jura', '40 - Landes', '41 - Loir-et-Cher', '42 - Loire', '43 - Haute-Loire', '44 - Loire-Atlantique', '45 - Loiret', '46 - Lot', '47 - Lot-et-Garonne', '48 - Lozère', '49 - Maine-et-Loire', '50 - Manche', '51 - Marne', '52 - Haute-Marne', '53 - Mayenne', '54 - Meurthe-et-Moselle', '55 - Meuse', '56 - Morbihan', '57 - Moselle', '58 - Nièvre', '59 - Nord', '60 - Oise', '61 - Orne', '62 - Pas-de-Calais', '63 - Puy-de-Dôme', '64 - Pyrénées-Atlantiques', '65 - Hautes-Pyrénées', '66 - Pyrénées-Orientales', '67 - Bas-Rhin', '68 - Haut-Rhin', '69 - Rhône', '70 - Haute-Saône', '71 - Saône-et-Loire', '72 - Sarthe', '73 - Savoie', '74 - Haute-Savoie', '75 - Paris', '76 - Seine-Maritime', '77 - Seine-et-Marne', '78 - Yvelines', '79 - Deux-Sèvres', '80 - Somme', '81 - Tarn', '82 - Tarn-et-Garonne', '83 - Var', '84 - Vaucluse', '85 - Vendée', '86 - Vienne', '87 - Haute-Vienne', '88 - Vosges', '89 - Yonne', '90 - Territoire de Belfort', '91 - Essonne', '92 - Hauts-de-Seine', '93 - Seine-Saint-Denis', '94 - Val-de-Marne', '95 - Val-d\'Oise', '971 - Guadeloupe', '972 - Martinique', '973 - Guyane', '974 - La Réunion', '976 - Mayotte'
                                    ];
                                    foreach ($departements as $dep) {
                                        echo '<option value="' . esc_attr($dep) . '">' . esc_html($dep) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <!-- Sous-menu Étranger -->
                        <div id="group-etranger" style="display: none; padding: 1.5rem; background: #F7FAFC; border-left: 4px solid var(--q-primary); border-radius: 4px; margin-bottom: 1.5rem;">
                            <div class="form-group">
                                <label for="user_pays_etranger" style="display: block; font-weight: 600; margin-bottom: 8px;">Quel pays ?</label>
                                <select id="user_pays_etranger" name="user_pays_etranger" style="width: 100%; padding: 12px; border: 1px solid #E2E8F0; border-radius: 6px;">
                                    <option value=""><?php echo esc_html($q_profil_q2); ?></option>
                                    <option value="Allemagne">Allemagne</option>
                                    <option value="Belgique">Belgique</option>
                                    <option value="Espagne">Espagne</option>
                                    <option value="Italie">Italie</option>
                                    <option value="Pays-Bas">Pays-Bas</option>
                                    <option value="Royaume-Uni">Royaume-Uni</option>
                                    <option value="Autres pays européens">Autres pays européens</option>
                                    <option value="Autre">Autre</option>
                                </select>
                            </div>

                            <!-- NOUVEAU : Champ texte caché pour saisir son propre pays -->
                            <div class="form-group" id="group-pays-autre" style="display: none; margin-top: 1rem;">
                                <label for="user_pays_autre" style="display: block; font-weight: 600; margin-bottom: 8px;">Précisez votre pays :</label>
                                <input type="text" id="user_pays_autre" name="user_pays_autre" placeholder="Ex: Canada, Suisse..." style="width: 100%; padding: 12px; border: 1px solid #E2E8F0; border-radius: 6px;">
                            </div>
                        </div>

                        <!-- Bouton manuel pour passer à l'étape suivante -->
                        <button type="button" class="btn-next-step btn-profil-next">Continuer</button>
                    </div>

                    <!-- Étape 10 : Question Facultative B (Accompagnement) -->
                    <div class="q-step" role="group">
                        <div class="q-question-header">
                            <div class="q-number" style="background-color: #4A5568; color: white;">b</div>
                            <div class="q-question-text"><?php echo esc_html($q_profil_q5); ?></div>
                        </div>
                        <div class="q-options-container">
                            <label class="q-radio-card"><input type="radio" name="profil_accompagnement" value="Seul(e)"> Seul(e)</label>
                            <label class="q-radio-card"><input type="radio" name="profil_accompagnement" value="En famille"> En famille</label>
                            <label class="q-radio-card"><input type="radio" name="profil_accompagnement" value="Entre amis"> Entre amis</label>
                            <label class="q-radio-card"><input type="radio" name="profil_accompagnement" value="Autre"> Autre</label>
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