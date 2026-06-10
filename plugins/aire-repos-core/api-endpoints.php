<?php
// Fichier : wp-content/plugins/aire-repos-core/api-endpoints.php

// Sécurité : Empêcher l'accès direct
if ( !defined('ABSPATH')) exit;

add_action( 'rest_api_init', 'cyclo_register_all_endpoints' );

function cyclo_register_all_endpoints() {
    // --- 1. ENDPOINTS FRONT-END (Public) ---
    register_rest_route( 'aire-repos/v1', '/scan', array( 'methods' => 'POST', 'callback' => 'aire_repos_log_qr_scan', 'permission_callback' => '__return_true' ) );
    register_rest_route( 'aire-repos/v1', '/update-time', array( 'methods' => 'POST', 'callback' => 'aire_repos_update_visit_time', 'permission_callback' => '__return_true' ) );
    register_rest_route( 'aire-repos/v1', '/submit-survey', array( 'methods' => 'POST', 'callback' => 'aire_repos_submit_survey', 'permission_callback' => '__return_true' ) );
    register_rest_route( 'aire-repos/v1', '/log-click', array( 'methods' => 'POST', 'callback' => 'aire_repos_log_click', 'permission_callback' => '__return_true' ) );

    // --- 2. ENDPOINTS DASHBOARD (Sécurisés) ---
    $dashboard_auth = function () {
        $user = wp_get_current_user();
        return array_intersect( ['administrator', 'cyclo_admin', 'cyclo_editeur', 'cyclo_user'], (array) $user->roles ) ? true : false;
    };
    register_rest_route( 'cyclo-data/v1', '/discovery-overview', array( 'methods' => 'GET', 'callback' => 'cyclo_get_discovery_overview', 'permission_callback' => $dashboard_auth ) );
    register_rest_route( 'cyclo-data/v1', '/survey-overview', array( 'methods' => 'GET', 'callback' => 'cyclo_get_survey_overview', 'permission_callback' => $dashboard_auth ) );
    register_rest_route( 'cyclo-data/v1', '/export-csv', array( 'methods' => 'GET', 'callback' => 'cyclo_export_csv_data', 'permission_callback' => $dashboard_auth ) );
    // Route pour récupérer les utilisateurs
    register_rest_route( 'cyclo-data/v1', '/users', array( 'methods' => 'GET', 'callback' => 'cyclo_get_users_list', 'permission_callback' => $dashboard_auth ) );
    // Création d'un utilisateur
    register_rest_route( 'cyclo-data/v1', '/users/create', array( 'methods' => 'POST', 'callback' => 'cyclo_create_user', 'permission_callback' => $dashboard_auth ) );
    // Mise à jour des paramètres (Mot de passe, Profil)
    register_rest_route( 'cyclo-data/v1', '/settings/update', array( 'methods' => 'POST', 'callback' => 'cyclo_update_settings', 'permission_callback' => $dashboard_auth ) );
}

// =========================================================================
// FONCTIONS FRONT-END (Découverte & Questionnaire)
// =========================================================================

function aire_repos_log_qr_scan( WP_REST_Request $request ) {
    global $wpdb;
    $id_aire = absint( $request->get_param('id_aire') );
    $lang = sanitize_text_field( $request->get_param('code_langue') );
    if (empty($id_aire)) return new WP_Error('invalid', 'Invalid data', ['status'=>400]);
    $wpdb->insert( $wpdb->prefix.'visite_site', ['date_visite'=>current_time('mysql'), 'id_aire'=>$id_aire, 'code_langue'=>strtoupper($lang), 'temps_total'=>0], ['%s','%d','%s','%d'] );
    return rest_ensure_response( ['status'=>'success', 'id_visite'=>$wpdb->insert_id] );
}

function aire_repos_update_visit_time( WP_REST_Request $request ) {
    global $wpdb;
    $id_visite = absint( $request->get_param('id_visite') );
    $temps = absint( $request->get_param('temps_total') );
    if($id_visite) $wpdb->update( $wpdb->prefix.'visite_site', ['temps_total'=>$temps], ['id_visite'=>$id_visite], ['%d'], ['%d'] );
    return rest_ensure_response( ['status'=>'success'] );
}

function aire_repos_log_click( WP_REST_Request $request ) {
    global $wpdb;
    $id_aire = absint( $request->get_param('id_aire') );
    $texte = sanitize_text_field( $request->get_param('texte_lien') );
    $url = esc_url_raw( $request->get_param('url_lien') );
    if($id_aire && $texte) {
        $wpdb->insert( $wpdb->prefix.'clic_lien', ['id_aire'=>$id_aire, 'texte_lien'=>$texte, 'url_lien'=>$url, 'date_clic'=>current_time('mysql')], ['%d','%s','%s','%s'] );
    }
    return rest_ensure_response( ['status'=>'success'] );
}

function aire_repos_submit_survey( WP_REST_Request $request ) {
    global $wpdb;
    $id_aire = absint( $request->get_param('id_aire') );
    $q4_array = (array) $request->get_param('q4_connaissance');
    $q4_clean = sanitize_text_field( implode(',', array_map('sanitize_text_field', $q4_array)) );

    if(empty($id_aire)) return new WP_Error('error', 'Missing data', ['status'=>400]);

    $wpdb->insert( $wpdb->prefix.'reponse_questionnaire', [
        'id_aire' => $id_aire,
        'q1_satisfaction_aire' => absint($request->get_param('q1_satisfaction_aire')),
        'q2_satisfaction_securite' => absint($request->get_param('q2_satisfaction_securite')),
        'q3_satisfaction_services' => absint($request->get_param('q3_satisfaction_services')),
        'q4_connaissance' => $q4_clean,
        'q5_suggestions' => sanitize_textarea_field($request->get_param('q5_suggestions')),
        'profil_origine' => sanitize_text_field($request->get_param('profil_origine')),
        'profil_region' => sanitize_text_field($request->get_param('profil_region')),
        'profil_pays' => sanitize_text_field($request->get_param('profil_pays')),
        'profil_accompagnement' => sanitize_text_field($request->get_param('profil_accompagnement')),
        'date_soumission' => current_time('mysql')
    ]);
    return rest_ensure_response( ['status'=>'success'] );
}

// =========================================================================
// FONCTIONS DASHBOARD & EXPORT
// =========================================================================

function build_dashboard_where_clause($request, $date_col, &$params) {
    $start_date = sanitize_text_field( $request->get_param('start_date') ?: date('Y-01-01') );
    $end_date   = sanitize_text_field( $request->get_param('end_date') ?: date('Y-12-31') );
    $level      = sanitize_text_field( $request->get_param('level') ?: 'global' );
    $id         = sanitize_text_field( $request->get_param('id') ?: 'all' );

    $where = "WHERE {$date_col} >= %s AND {$date_col} <= %s";
    $params = [ $start_date . ' 00:00:00', $end_date . ' 23:59:59' ];

    if ( $level === 'aire' && $id !== 'all' ) {
        $where .= " AND id_aire = %d"; $params[] = intval($id);
    } elseif ( $level === 'route' && is_numeric($id) ) {
        $aires = get_posts(['post_type'=>'aire_repos', 'numberposts'=>-1, 'fields'=>'ids', 'tax_query'=>[['taxonomy'=>'itineraire', 'field'=>'term_id', 'terms'=>intval($id)]]]);
        if (!empty($aires)) {
            $placeholders = implode(',', array_fill(0, count($aires), '%d'));
            $where .= " AND id_aire IN ($placeholders)";
            $params = array_merge($params, $aires);
        } else { $where .= " AND id_aire = 0"; }
    }
    return $where;
}

function cyclo_get_discovery_overview( WP_REST_Request $request ) {
    global $wpdb;
    $params = [];
    $where = build_dashboard_where_clause($request, 'date_visite', $params);
    $filter_type = sanitize_text_field( $request->get_param('filter_type') ?: 'annee' );

    // Grouping Temporel Réparé (Semaine = WEEKDAY de 0 à 6)
    if ($filter_type === 'jour') { $group_sql = "HOUR(date_visite) as label"; $group_by = "HOUR(date_visite)"; }
    elseif ($filter_type === 'semaine') { $group_sql = "WEEKDAY(date_visite) as label"; $group_by = "WEEKDAY(date_visite)"; }
    elseif ($filter_type === 'mois') { $group_sql = "DAY(date_visite) as label"; $group_by = "DAY(date_visite)"; }
    else { $group_sql = "MONTH(date_visite) as label"; $group_by = "MONTH(date_visite)"; }

    $total = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(id_visite) FROM {$wpdb->prefix}visite_site {$where}", $params) );
    $timeline = $wpdb->get_results( $wpdb->prepare("SELECT {$group_sql}, COUNT(id_visite) as total FROM {$wpdb->prefix}visite_site {$where} GROUP BY {$group_by} ORDER BY label ASC", $params) );
    $hourly = $wpdb->get_results( $wpdb->prepare("SELECT HOUR(date_visite) as heure, COUNT(id_visite) as total FROM {$wpdb->prefix}visite_site {$where} GROUP BY HOUR(date_visite)", $params) );
    $geo = $wpdb->get_results( $wpdb->prepare("SELECT code_langue, COUNT(id_visite) as total FROM {$wpdb->prefix}visite_site {$where} GROUP BY code_langue ORDER BY total DESC LIMIT 5", $params) );

    // NOUVEAU : Extraction des vrais liens cliqués
    $where_clics = str_replace('date_visite', 'date_clic', $where);
    $links = $wpdb->get_results( $wpdb->prepare("SELECT texte_lien, COUNT(id_clic) as total FROM {$wpdb->prefix}clic_lien {$where_clics} GROUP BY texte_lien ORDER BY total DESC LIMIT 6", $params) );

    return rest_ensure_response( ['status'=>'success', 'data'=>['total_consultations'=>(int)$total, 'timeline'=>$timeline, 'hourly_stats'=>$hourly, 'geo_stats'=>$geo, 'top_links'=>$links] ]);
}

function cyclo_get_survey_overview( WP_REST_Request $request ) {
    global $wpdb;
    $start_date = sanitize_text_field( $request->get_param('start_date') );
    $end_date   = sanitize_text_field( $request->get_param('end_date') );
    $level      = sanitize_text_field( $request->get_param('level') ?: 'global' );
    $id         = sanitize_text_field( $request->get_param('id') ?: 'all' );
    $filter_type= sanitize_text_field( $request->get_param('filter_type') ?: 'annee' );

    $table_survey = $wpdb->prefix . 'reponse_questionnaire';
    $where = "WHERE date_soumission >= %s AND date_soumission <= %s";
    $params = [ $start_date . ' 00:00:00', $end_date . ' 23:59:59' ];

    if ( $level === 'aire' && $id !== 'all' ) {
        $where .= " AND id_aire = %d"; $params[] = intval($id);
    } elseif ( $level === 'route' && is_numeric($id) ) {
        $aires = get_posts(array('post_type' => 'aire_repos', 'numberposts' => -1, 'fields' => 'ids', 'tax_query' => array( array('taxonomy' => 'itineraire', 'field' => 'term_id', 'terms' => intval($id)) )));
        if (!empty($aires)) {
            $placeholders = implode(',', array_fill(0, count($aires), '%d'));
            $where .= " AND id_aire IN ($placeholders)";
            $params = array_merge($params, $aires);
        } else {
            $where .= " AND id_aire = 0";
        }
    }

    if ($filter_type === 'jour') {
        $group_sql = "HOUR(date_soumission) as label"; $group_by = "HOUR(date_soumission)";
    } elseif ($filter_type === 'semaine') {
        $group_sql = "WEEKDAY(date_soumission) as label"; $group_by = "WEEKDAY(date_soumission)";
    } elseif ($filter_type === 'mois') {
        $group_sql = "DAY(date_soumission) as label"; $group_by = "DAY(date_soumission)";
    } else {
        $group_sql = "MONTH(date_soumission) as label"; $group_by = "MONTH(date_soumission)";
    }

    // FIX : Utilisation stricte de l'opérateur "splat" (...$params)
    $kpi = $wpdb->get_row( $wpdb->prepare("SELECT COUNT(id_reponse) as total, AVG(q1_satisfaction_aire) as a1, AVG(q2_satisfaction_securite) as a2, AVG(q3_satisfaction_services) as a3 FROM {$table_survey} {$where}", ...$params) );
    $timeline = $wpdb->get_results( $wpdb->prepare("SELECT {$group_sql}, COUNT(id_reponse) as total FROM {$table_survey} {$where} GROUP BY {$group_by} ORDER BY label ASC", ...$params) );

    $q1_dist = $wpdb->get_results( $wpdb->prepare("SELECT q1_satisfaction_aire as note, COUNT(id_reponse) as total FROM {$table_survey} {$where} GROUP BY note", ...$params) );
    $q2_dist = $wpdb->get_results( $wpdb->prepare("SELECT q2_satisfaction_securite as note, COUNT(id_reponse) as total FROM {$table_survey} {$where} GROUP BY note", ...$params) );
    $q3_dist = $wpdb->get_results( $wpdb->prepare("SELECT q3_satisfaction_services as note, COUNT(id_reponse) as total FROM {$table_survey} {$where} GROUP BY note", ...$params) );
    $q4_raw = $wpdb->get_results( $wpdb->prepare("SELECT q4_connaissance FROM {$table_survey} {$where}", ...$params) );

    $origine = $wpdb->get_results( $wpdb->prepare("SELECT profil_origine, COUNT(id_reponse) as total FROM {$table_survey} {$where} AND profil_origine != '' GROUP BY profil_origine", ...$params) );
    $accomp = $wpdb->get_results( $wpdb->prepare("SELECT profil_accompagnement, COUNT(id_reponse) as total FROM {$table_survey} {$where} AND profil_accompagnement != '' GROUP BY profil_accompagnement", ...$params) );

    // FIX PAYS : La condition "AND profil_origine = 'et'" bloquait parfois. On prend tous les pays renseignés.
    $pays = $wpdb->get_results( $wpdb->prepare("SELECT profil_pays, COUNT(id_reponse) as total FROM {$table_survey} {$where} AND profil_pays != '' GROUP BY profil_pays ORDER BY total DESC", ...$params) );

    $total = isset($kpi->total) ? (int) $kpi->total : 0;
    $radar = [0,0,0]; $indice = 0;

    if ($total > 0) {
        $indice = ((($kpi->a1/4)*5) + (($kpi->a2/4)*5) + (($kpi->a3/4)*5)) / 3;
        $radar = [ round(($kpi->a1/4)*100), round(($kpi->a2/4)*100), round(($kpi->a3/4)*100) ];
    }

    return rest_ensure_response( ['status'=>'success', 'data'=>['total_reponses'=>$total, 'indice_satisfaction'=>round($indice, 1), 'radar_scores'=>$radar, 'timeline'=>$timeline, 'q1_dist'=>$q1_dist, 'q2_dist'=>$q2_dist, 'q3_dist'=>$q3_dist, 'q4_raw'=>$q4_raw, 'origine'=>$origine, 'accompagnement'=>$accomp, 'pays'=>$pays] ]);
}

function cyclo_export_csv_data( WP_REST_Request $request ) {
    global $wpdb;
    $module = sanitize_text_field( $request->get_param('module') ?: 'discovery' );
    $params = [];
    $where = build_dashboard_where_clause($request, ($module === 'discovery' ? 'date_visite' : 'date_soumission'), $params);

    if (ob_get_length()) ob_clean(); // Sécurité Buffer
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="export_' . $module . '_' . date('Ymd') . '.csv"');
    $output = fopen('php://output', 'w');
    fputs($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM pour Excel

    if ( $module === 'discovery' ) {
        fputcsv($output, ['ID Visite', 'Date (Formatée)', 'ID Aire', 'Langue', 'Temps passé (secondes)'], ';');
        $results = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}visite_site {$where} ORDER BY date_visite DESC", $params), ARRAY_A );
        if($results) { foreach ( $results as $row ) {
            // FIX CSV : Formatage clair de la date pour Excel
            $date_excel = date('d/m/Y H:i', strtotime($row['date_visite']));
            fputcsv($output, [$row['id_visite'], $date_excel, $row['id_aire'], strtoupper($row['code_langue']), $row['temps_total']], ';');
        }}
    } else {
        fputcsv($output, ['ID Reponse', 'Date Formatee', 'ID Aire', 'Note Aire', 'Note Securite', 'Note Services', 'Origine', 'Pays'], ';');
        $results = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}reponse_questionnaire {$where} ORDER BY date_soumission DESC", $params), ARRAY_A );
        if($results) { foreach ( $results as $row ) {
            $date_excel = date('d/m/Y H:i', strtotime($row['date_soumission']));
            fputcsv($output, [$row['id_reponse'], $date_excel, $row['id_aire'], $row['q1_satisfaction_aire'], $row['q2_satisfaction_securite'], $row['q3_satisfaction_services'], $row['profil_origine'], $row['profil_pays']], ';');
        }}
    }
    fclose($output); exit;
}

// Fonction pour avoir la liste des utilisateurs
function cyclo_get_users_list( WP_REST_Request $request ) {
    // Vérification de sécurité : Seul l'Admin ou l'Éditeur peut voir la liste
    $current_user = wp_get_current_user();
    if ( !array_intersect(['administrator', 'cyclo_admin', 'cyclo_editeur'], (array) $current_user->roles) ) {
        return new WP_Error( 'forbidden', 'Vous n\'avez pas les droits pour voir cette page.', array( 'status' => 403 ) );
    }

    $wp_users = get_users();
    $formatted_users = [];

    foreach ( $wp_users as $u ) {
        $roles = (array) $u->roles;
        $main_role = 'Utilisateur';
        $role_color = 'blue';

        if ( in_array('administrator', $roles) || in_array('cyclo_admin', $roles) ) {
            $main_role = 'Admin'; $role_color = 'yellow';
        } elseif ( in_array('cyclo_editeur', $roles) ) {
            $main_role = 'Éditeur'; $role_color = 'purple';
        }

        // Création des initiales pour l'avatar
        $initials = strtoupper(substr($u->first_name, 0, 1) . substr($u->last_name, 0, 1));
        if (empty($initials)) $initials = strtoupper(substr($u->display_name, 0, 2));

        $formatted_users[] = array(
            'id' => $u->ID,
            'name' => $u->display_name,
            'email' => $u->user_email,
            'role' => $main_role,
            'role_color' => $role_color,
            'initials' => $initials,
            'date' => date_i18n('d M Y', strtotime($u->user_registered))
        );
    }

    return rest_ensure_response( array(
        'status' => 'success',
        'data' => array(
            'total' => count($formatted_users),
            'users' => $formatted_users
        )
    ));
}

// =========================================================================
// GESTION DES UTILISATEURS ET PARAMÈTRES
// =========================================================================

function cyclo_create_user( WP_REST_Request $request ) {
    $current_user = wp_get_current_user();

    // Sécurité stricte : Seuls les Admins peuvent créer des comptes
    if ( !in_array('administrator', (array) $current_user->roles) && !in_array('cyclo_admin', (array) $current_user->roles) ) {
        return new WP_Error( 'forbidden', 'Droits insuffisants.', array( 'status' => 403 ) );
    }

    $email      = sanitize_email( $request->get_param('email') );
    $first_name = sanitize_text_field( $request->get_param('first_name') );
    $last_name  = sanitize_text_field( $request->get_param('last_name') );
    $role       = sanitize_text_field( $request->get_param('role') ); // ex: 'cyclo_editeur', 'cyclo_user'

    if ( email_exists($email) ) {
        return new WP_Error( 'exists', 'Cette adresse e-mail est déjà utilisée.', array( 'status' => 400 ) );
    }

    // Génération native d'un mot de passe ultra-sécurisé (12 caractères, lettres, chiffres, symboles)
    $password = wp_generate_password( 12, true, true );

    $user_id = wp_insert_user( array(
        'user_login' => $email, // On utilise l'email comme identifiant
        'user_email' => $email,
        'user_pass'  => $password,
        'first_name' => $first_name,
        'last_name'  => $last_name,
        'role'       => $role
    ) );

    if ( is_wp_error( $user_id ) ) return $user_id;

    // MARQUEUR DE SÉCURITÉ : Force le changement de mot de passe à la première connexion
    update_user_meta( $user_id, '_cyclo_force_pwd_change', '1' );

    return rest_ensure_response( array(
        'status' => 'success',
        'message' => 'Utilisateur créé avec succès.',
        // C'est la SEULE et UNIQUE fois où le mot de passe est retourné en clair pour être affiché dans le Dashboard (conformément à ta maquette)
        'temp_password' => $password
    ) );
}

function cyclo_update_settings( WP_REST_Request $request ) {
    $user_id = get_current_user_id();
    if ( !$user_id ) return new WP_Error( 'unauthorized', 'Non connecté.', array( 'status' => 401 ) );

    $new_password = $request->get_param('new_password');
    $accent_color = sanitize_hex_color( $request->get_param('accent_color') ); // Exemple pour tes maquettes de couleurs

    // Si l'utilisateur change de mot de passe depuis les paramètres
    if ( !empty($new_password) ) {
        wp_set_password( $new_password, $user_id );

        // On supprime le marqueur temporaire au cas où
        delete_user_meta( $user_id, '_cyclo_force_pwd_change' );

        // Reconnexion forcée en arrière-plan car wp_set_password détruit les cookies de session
        clean_user_cache( $user_id );
        wp_clear_auth_cookie();
        wp_set_current_user( $user_id );
        wp_set_auth_cookie( $user_id );
    }

    if ( !empty($accent_color) ) {
        update_user_meta( $user_id, 'cyclo_accent_color', $accent_color );
    }

    return rest_ensure_response( ['status' => 'success'] );
}