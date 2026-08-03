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
    // Routes pour les paramètres (GET = lecture, POST = écriture)
    register_rest_route( 'cyclo-data/v1', '/settings', array( 'methods' => 'GET', 'callback' => 'cyclo_get_settings', 'permission_callback' => $dashboard_auth ) );
    register_rest_route( 'cyclo-data/v1', '/settings', array( 'methods' => 'POST', 'callback' => 'cyclo_update_settings', 'permission_callback' => $dashboard_auth ) );
    // Gestion des permissions (Lecture et Écriture)
    register_rest_route( 'cyclo-data/v1', '/permissions/(?P<role>[a-zA-Z0-9_-]+)', array( 'methods' => 'GET', 'callback' => 'cyclo_get_permissions', 'permission_callback' => $dashboard_auth ) );
    register_rest_route( 'cyclo-data/v1', '/permissions/(?P<role>[a-zA-Z0-9_-]+)', array( 'methods' => 'POST', 'callback' => 'cyclo_update_permissions', 'permission_callback' => $dashboard_auth ) );
}

// =========================================================================
// FONCTIONS FRONT-END (Découverte & Questionnaire)
// =========================================================================

function aire_repos_log_qr_scan( WP_REST_Request $request ) {
    global $wpdb;
    $id_aire = absint( $request->get_param('id_aire') );
    $lang = sanitize_text_field( $request->get_param('code_langue') );
    $type = sanitize_text_field( $request->get_param('type_visite') ?: 'discovery' );

    if (empty($id_aire)) return new WP_Error('invalid', 'Invalid data', ['status'=>400]);

    // Auto-réparation de la BDD : on ajoute la colonne 'type_visite' si elle n'existe pas
    $col_exists = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}visite_site LIKE 'type_visite'");
    if (empty($col_exists)) {
        $wpdb->query("ALTER TABLE {$wpdb->prefix}visite_site ADD COLUMN type_visite varchar(20) DEFAULT 'discovery'");
    }

    $wpdb->insert( $wpdb->prefix.'visite_site', [
        'date_visite' => current_time('mysql'),
        'id_aire' => $id_aire,
        'code_langue' => strtoupper($lang),
        'temps_total' => 0,
        'type_visite' => $type
    ], ['%s','%d','%s','%d','%s'] );

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

    // --- NOUVELLE LOGIQUE DE PROFILAGE ---
    // On récupère les valeurs envoyées par le nouveau formulaire
    $country_input = sanitize_text_field( $request->get_param('user_country') );
    $country_other = sanitize_text_field( $request->get_param('user_country_other') );
    $region_fr     = sanitize_text_field( $request->get_param('user_region_fr') );

    $profil_origine = '';
    $profil_pays    = '';
    $profil_region  = '';

    if ($country_input === 'France') {
        $profil_origine = 'fr';
        $profil_pays    = 'France';
        $profil_region  = $region_fr; // Ex: Loir-et-Cher (41)
    } elseif ($country_input === 'Autre') {
        $profil_origine = 'etranger';
        $profil_pays    = $country_other; // Le pays tapé à la main
        $profil_region  = '';
    } elseif (!empty($country_input)) {
        $profil_origine = 'etranger';
        $profil_pays    = $country_input; // Le pays sélectionné dans la liste
        $profil_region  = '';
    }

    $wpdb->insert( $wpdb->prefix.'reponse_questionnaire', [
        'id_aire' => $id_aire,
        'q1_satisfaction_aire' => absint($request->get_param('q1_satisfaction_aire')),
        'q2_satisfaction_securite' => absint($request->get_param('q2_satisfaction_securite')),
        'q3_satisfaction_services' => absint($request->get_param('q3_satisfaction_services')),

        // --- INJECTION DES 4 NOUVELLES QUESTIONS ---
        'q6_satisfaction_signaletique' => absint($request->get_param('q6_satisfaction_signaletique')),
        'q7_recommandation' => absint($request->get_param('q7_recommandation')),
        'q8_duree_sejour' => absint($request->get_param('q8_duree_sejour')),
        'q9_distance_sejour' => absint($request->get_param('q9_distance_sejour')),

        'q4_connaissance' => $q4_clean,

        // On injecte nos variables traitées
        'profil_origine' => $profil_origine,
        'profil_region' => $profil_region,
        'profil_pays' => $profil_pays,
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

    // --- CORRECTION : Auto-réparation de la BDD au chargement du dashboard ---
    $col_exists = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}visite_site LIKE 'type_visite'");
    if (empty($col_exists)) {
        $wpdb->query("ALTER TABLE {$wpdb->prefix}visite_site ADD COLUMN type_visite varchar(20) DEFAULT 'discovery'");
    }
    // -------------------------------------------------------------------------

    $params = [];
    // 1. On crée la clause de base (uniquement les dates et l'ID)
    $where_base = build_dashboard_where_clause($request, 'date_visite', $params);

    // 2. On génère la clause spécifique pour la table visite_site (qui a la colonne type_visite)
    $where = $where_base . " AND (type_visite = 'discovery' OR type_visite IS NULL)";
    $filter_type = sanitize_text_field( $request->get_param('filter_type') ?: 'annee' );
    $compare = sanitize_text_field( $request->get_param('compare') ) === '1';

    if ($filter_type === 'jour') { $group_sql = "HOUR(date_visite) as label"; $group_by = "HOUR(date_visite)"; }
    elseif ($filter_type === 'semaine') { $group_sql = "WEEKDAY(date_visite) as label"; $group_by = "WEEKDAY(date_visite)"; }
    elseif ($filter_type === 'mois') { $group_sql = "DAY(date_visite) as label"; $group_by = "DAY(date_visite)"; }
    else { $group_sql = "MONTH(date_visite) as label"; $group_by = "MONTH(date_visite)"; }

    $total = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(id_visite) FROM {$wpdb->prefix}visite_site {$where}", ...$params) );
    $avg_time = $wpdb->get_var( $wpdb->prepare("SELECT AVG(temps_total) FROM {$wpdb->prefix}visite_site {$where}", ...$params) );

    $timeline = $wpdb->get_results( $wpdb->prepare("SELECT {$group_sql}, COUNT(id_visite) as total FROM {$wpdb->prefix}visite_site {$where} GROUP BY {$group_by} ORDER BY label ASC", ...$params) );

    // Comparaison
    // NOUVEAU : On récupère les dates exactes voulues par l'utilisateur
    $compare_start = sanitize_text_field( $request->get_param('compare_start') );
    $compare_end   = sanitize_text_field( $request->get_param('compare_end') );

    $timeline_compare = [];
    if ( $compare && $compare_start && $compare_end ) {
        $compare_params = $params; // On copie les paramètres (notamment id_aire etc.)
        // On écrase les dates de début et de fin (qui sont aux index 0 et 1)
        $compare_params[0] = $compare_start . ' 00:00:00';
        $compare_params[1] = $compare_end . ' 23:59:59';

        // Exécution de la requête avec les nouvelles dates
        // (Attention : assure-toi d'adapter la table et le groupe selon la fonction dans laquelle tu colles ce code)
        // Pour discovery_overview : {$wpdb->prefix}visite_site
        // Pour survey_overview : {$table_survey}
        $table_name = isset($table_survey) ? $table_survey : "{$wpdb->prefix}visite_site";

        $timeline_compare = $wpdb->get_results( $wpdb->prepare("SELECT {$group_sql}, COUNT(id_visite) as total FROM {$table_name} {$where} GROUP BY {$group_by} ORDER BY label ASC", ...$compare_params) );
        // Note: Remplace "id_visite_ou_reponse" par "id_visite" dans discovery_overview et "id_reponse" dans survey_overview.
    }

    $hourly = $wpdb->get_results( $wpdb->prepare("SELECT HOUR(date_visite) as heure, COUNT(id_visite) as total FROM {$wpdb->prefix}visite_site {$where} GROUP BY HOUR(date_visite)", ...$params) );
    $geo = $wpdb->get_results( $wpdb->prepare("SELECT code_langue, COUNT(id_visite) as total FROM {$wpdb->prefix}visite_site {$where} GROUP BY code_langue ORDER BY total DESC LIMIT 5", ...$params) );

    // CORRECTION : On utilise $where_base pour ne pas injecter 'type_visite' dans la table des clics
    $where_clics = str_replace('date_visite', 'date_clic', $where_base);
    $links = $wpdb->get_results( $wpdb->prepare("SELECT texte_lien, COUNT(id_clic) as total FROM {$wpdb->prefix}clic_lien {$where_clics} GROUP BY texte_lien ORDER BY total DESC LIMIT 6", ...$params) );

    return rest_ensure_response( [
        'status'=>'success',
        'data'=>[
            'total_consultations' => (int)$total,
            'temps_moyen' => (int)$avg_time,
            'timeline' => $timeline,
            'timeline_compare' => $timeline_compare,
            'hourly_stats' => $hourly,
            'geo_stats' => $geo,
            'top_links' => $links
        ]
    ]);
}

function cyclo_get_survey_overview( WP_REST_Request $request ) {
    global $wpdb;

    // --- CORRECTION : Auto-réparation de la BDD au chargement du dashboard ---
    $col_exists = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}visite_site LIKE 'type_visite'");
    if (empty($col_exists)) {
        $wpdb->query("ALTER TABLE {$wpdb->prefix}visite_site ADD COLUMN type_visite varchar(20) DEFAULT 'discovery'");
    }

    // NOUVEAU BLOC : Ajout sécurisé des 4 nouvelles questions
    $table_survey = $wpdb->prefix . 'reponse_questionnaire';
    $q6_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_survey} LIKE 'q6_satisfaction_signaletique'");

    if (empty($q6_exists)) {
        // Règle du CTO : Remplace "metrique_x" par les vrais sujets du client (ex: q6_proprete, q7_bruit)
        $wpdb->query("ALTER TABLE {$table_survey} 
            ADD COLUMN q6_satisfaction_signaletique int(1) DEFAULT 0,
            ADD COLUMN q7_recommandation int(1) DEFAULT 0,
            ADD COLUMN q8_duree_sejour int(1) DEFAULT 0,
            ADD COLUMN q9_distance_jour int(1) DEFAULT 0
        ");
    }

    $params = [];
    // Utilise la fonction générique pour générer le WHERE de base
    $where = build_dashboard_where_clause($request, 'date_soumission', $params);
    // NOUVEAU : Récupération des visites spécifiques au questionnaire
    $params_visits = [];
    $where_visits = build_dashboard_where_clause($request, 'date_visite', $params_visits);
    $where_visits .= " AND type_visite = 'survey'";
    $total_consultations_survey = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(id_visite) FROM {$wpdb->prefix}visite_site {$where_visits}", ...$params_visits) );
    $filter_type= sanitize_text_field( $request->get_param('filter_type') ?: 'annee' );
    $compare = sanitize_text_field( $request->get_param('compare') ) === '1';

    $table_survey = $wpdb->prefix . 'reponse_questionnaire';

    if ($filter_type === 'jour') { $group_sql = "HOUR(date_soumission) as label"; $group_by = "HOUR(date_soumission)"; }
    elseif ($filter_type === 'semaine') { $group_sql = "WEEKDAY(date_soumission) as label"; $group_by = "WEEKDAY(date_soumission)"; }
    elseif ($filter_type === 'mois') { $group_sql = "DAY(date_soumission) as label"; $group_by = "DAY(date_soumission)"; }
    else { $group_sql = "MONTH(date_soumission) as label"; $group_by = "MONTH(date_soumission)"; }

    $kpi = $wpdb->get_row( $wpdb->prepare("SELECT COUNT(id_reponse) as total, AVG(q1_satisfaction_aire) as a1, AVG(q2_satisfaction_securite) as a2, AVG(q3_satisfaction_services) as a3 FROM {$table_survey} {$where}", ...$params) );
    $timeline = $wpdb->get_results( $wpdb->prepare("SELECT {$group_sql}, COUNT(id_reponse) as total FROM {$table_survey} {$where} GROUP BY {$group_by} ORDER BY label ASC", ...$params) );

    // Comparaison
    // NOUVEAU : On récupère les dates exactes voulues par l'utilisateur
    $compare_start = sanitize_text_field( $request->get_param('compare_start') );
    $compare_end   = sanitize_text_field( $request->get_param('compare_end') );

    $timeline_compare = [];
    if ( $compare && $compare_start && $compare_end ) {
        $compare_params = $params; // On copie les paramètres (notamment id_aire etc.)
        // On écrase les dates de début et de fin (qui sont aux index 0 et 1)
        $compare_params[0] = $compare_start . ' 00:00:00';
        $compare_params[1] = $compare_end . ' 23:59:59';

        // Exécution de la requête avec les nouvelles dates
        // (Attention : assure-toi d'adapter la table et le groupe selon la fonction dans laquelle tu colles ce code)
        // Pour discovery_overview : {$wpdb->prefix}visite_site
        // Pour survey_overview : {$table_survey}
        $table_name = isset($table_survey) ? $table_survey : "{$wpdb->prefix}visite_site";

        $timeline_compare = $wpdb->get_results( $wpdb->prepare("SELECT {$group_sql}, COUNT(id_reponse) as total FROM {$table_name} {$where} GROUP BY {$group_by} ORDER BY label ASC", ...$compare_params) );
        // Note: Remplace "id_visite_ou_reponse" par "id_visite" dans discovery_overview et "id_reponse" dans survey_overview.
    }

    $q1_dist = $wpdb->get_results( $wpdb->prepare("SELECT q1_satisfaction_aire as note, COUNT(id_reponse) as total FROM {$table_survey} {$where} GROUP BY note", ...$params) );
    $q2_dist = $wpdb->get_results( $wpdb->prepare("SELECT q2_satisfaction_securite as note, COUNT(id_reponse) as total FROM {$table_survey} {$where} GROUP BY note", ...$params) );
    $q3_dist = $wpdb->get_results( $wpdb->prepare("SELECT q3_satisfaction_services as note, COUNT(id_reponse) as total FROM {$table_survey} {$where} GROUP BY note", ...$params) );
    $q4_raw = $wpdb->get_results( $wpdb->prepare("SELECT q4_connaissance FROM {$table_survey} {$where}", ...$params) );

    $origine = $wpdb->get_results( $wpdb->prepare("SELECT profil_origine, COUNT(id_reponse) as total FROM {$table_survey} {$where} AND profil_origine != '' GROUP BY profil_origine", ...$params) );
    // NOUVELLE REQUÊTE : Extraction de la répartition des régions pour les Français
    $region_dist = $wpdb->get_results( $wpdb->prepare("SELECT profil_region, COUNT(id_reponse) as total FROM {$table_survey} {$where} AND profil_origine = 'fr' AND profil_region != '' GROUP BY profil_region", ...$params) );
    $accomp = $wpdb->get_results( $wpdb->prepare("SELECT profil_accompagnement, COUNT(id_reponse) as total FROM {$table_survey} {$where} AND profil_accompagnement != '' GROUP BY profil_accompagnement", ...$params) );
    $pays = $wpdb->get_results( $wpdb->prepare("SELECT profil_pays, COUNT(id_reponse) as total FROM {$table_survey} {$where} AND profil_pays != '' GROUP BY profil_pays ORDER BY total DESC", ...$params) );

    $total = isset($kpi->total) ? (int) $kpi->total : 0;
    $radar = [0,0,0]; $indice = 0;

    if ($total > 0) {
        $indice = ((($kpi->a1/4)*5) + (($kpi->a2/4)*5) + (($kpi->a3/4)*5)) / 3;
        $radar = [ round(($kpi->a1/4)*100), round(($kpi->a2/4)*100), round(($kpi->a3/4)*100) ];
    }

    return rest_ensure_response( [
        'status'=>'success',
        'data'=>[
            'total_reponses' => $total,
            'total_consultations_survey' => (int) $total_consultations_survey, // <-- NOUVELLE LIGNE
            'indice_satisfaction' => round($indice, 1),
            'radar_scores' => $radar,
            'averages' => [
                'aire' => isset($kpi->a1) ? round($kpi->a1, 1) : 0,
                'securite' => isset($kpi->a2) ? round($kpi->a2, 1) : 0,
                'services' => isset($kpi->a3) ? round($kpi->a3, 1) : 0
            ],
            'timeline' => $timeline,
            'timeline_compare' => $timeline_compare,
            'q1_dist' => $q1_dist, 'q2_dist' => $q2_dist, 'q3_dist' => $q3_dist,
            'q4_raw' => $q4_raw,
            'origine' => $origine,
            'region_dist' => $region_dist,  // NOUVELLE LIGNE TRANSMISSE AU JS
            'accompagnement' => $accomp,
            'pays' => $pays
        ]
    ]);
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
        // CORRECTION 1 : L'en-tête de la colonne devient plus clair
        fputcsv($output, ['ID Visite', 'Date (Formatée)', 'ID Aire', 'Langue', 'Temps passé (HH:MM:SS)'], ';');

        $results = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}visite_site {$where} ORDER BY date_visite DESC", ...$params), ARRAY_A );

        if($results) { foreach ( $results as $row ) {
            $date_excel = date('d/m/Y H:i', strtotime($row['date_visite']));

            // CORRECTION 2 : Conversion des secondes en format HH:MM:SS lisible
            $temps_secondes = (int) $row['temps_total'];
            $heures = floor($temps_secondes / 3600);
            $minutes = floor(($temps_secondes / 60) % 60);
            $secondes = $temps_secondes % 60;
            $temps_formate = sprintf('%02d:%02d:%02d', $heures, $minutes, $secondes);

            fputcsv($output, [$row['id_visite'], $date_excel, $row['id_aire'], strtoupper($row['code_langue']), $temps_formate], ';');
        }}
    } else {
        fputcsv($output, ['ID Reponse', 'Date Formatee', 'ID Aire', 'Note Aire', 'Note Securite', 'Note Services', 'Origine', 'Pays'], ';');
        $results = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}reponse_questionnaire {$where} ORDER BY date_soumission DESC", ...$params), ARRAY_A );
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
// GESTION DES UTILISATEURS
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

// =========================================================================
// GESTION DES PARAMÈTRES (OPTIONS ET USER META)
// =========================================================================

function cyclo_get_settings( WP_REST_Request $request ) {
    $user_id = get_current_user_id();
    $is_admin = current_user_can('administrator');

    // Récupération des préférences utilisateur
    $user_settings = [
        'accent_color' => get_user_meta($user_id, 'cyclo_accent_color', true) ?: '#1A9E66',
        'density'      => get_user_meta($user_id, 'cyclo_display_density', true) ?: 'confortable',
        'locale'       => get_user_meta($user_id, 'locale', true) ?: get_locale(),
    ];

    // Récupération des options globales (visibles/modifiables que par l'admin)
    $global_settings = [];
    if ( $is_admin ) {
        $global_settings = [
            'alert_email'    => get_option('cyclo_alert_email', '0'),
            'weekly_report'  => get_option('cyclo_weekly_report', '0'),
            'retention'      => get_option('cyclo_data_retention', '24'),
            'export_format'  => get_option('cyclo_export_format', 'csv'),
        ];
    }

    return rest_ensure_response([
        'status' => 'success',
        'data' => [
            'is_admin' => $is_admin,
            'user'     => $user_settings,
            'global'   => $global_settings
        ]
    ]);
}

function cyclo_update_settings( WP_REST_Request $request ) {
    $user_id = get_current_user_id();
    if ( !$user_id ) return new WP_Error( 'unauthorized', 'Non connecté.', array( 'status' => 401 ) );

    $params = $request->get_json_params();

    // 1. Sauvegarde des préférences Utilisateur
    if ( isset($params['accent_color']) ) update_user_meta( $user_id, 'cyclo_accent_color', sanitize_hex_color($params['accent_color']) );
    if ( isset($params['density']) ) update_user_meta( $user_id, 'cyclo_display_density', sanitize_text_field($params['density']) );

    // 2. Sauvegarde des Options Globales (Si Admin)
    if ( current_user_can('administrator') ) {
        if ( isset($params['alert_email']) ) update_option( 'cyclo_alert_email', sanitize_text_field($params['alert_email']) );
        if ( isset($params['weekly_report']) ) update_option( 'cyclo_weekly_report', sanitize_text_field($params['weekly_report']) );
        if ( isset($params['retention']) ) update_option( 'cyclo_data_retention', sanitize_text_field($params['retention']) );
        if ( isset($params['export_format']) ) update_option( 'cyclo_export_format', sanitize_text_field($params['export_format']) );
    }

    return rest_ensure_response( ['status' => 'success'] );
}

// =========================================================================
// GESTION DES PERMISSIONS (CAPABILITIES)
// =========================================================================

// Liste exacte de nos permissions personnalisées (calquée sur tes maquettes)
function cyclo_get_custom_caps() {
    return [
        'cyclo_view_permissions', 'cyclo_edit_permissions',
        'cyclo_view_users', 'cyclo_create_users', 'cyclo_edit_users', 'cyclo_delete_users',
        'cyclo_view_stats', 'cyclo_export_stats', 'cyclo_compare_stats',
        'cyclo_view_surveys', 'cyclo_manage_surveys'
    ];
}

function cyclo_get_permissions( WP_REST_Request $request ) {
    $role_name = sanitize_text_field( $request->get_param('role') ); // ex: 'cyclo_user', 'cyclo_editeur'
    $role_obj = get_role( $role_name );

    if ( !$role_obj ) {
        return new WP_Error( 'not_found', 'Rôle introuvable.', array( 'status' => 404 ) );
    }

    $all_caps = cyclo_get_custom_caps();
    $active_caps = [];

    // On vérifie quelles permissions le rôle possède actuellement
    foreach ( $all_caps as $cap ) {
        $active_caps[$cap] = isset( $role_obj->capabilities[$cap] ) && $role_obj->capabilities[$cap] === true;
    }

    // Protection : L'administrateur a toujours tout
    if ( $role_name === 'administrator' ) {
        foreach ( $all_caps as $cap ) { $active_caps[$cap] = true; }
    }

    return rest_ensure_response( [ 'status' => 'success', 'data' => $active_caps ] );
}

function cyclo_update_permissions( WP_REST_Request $request ) {
    $current_user = wp_get_current_user();

    // Sécurité : Seul un Admin peut modifier les droits
    if ( !in_array('administrator', (array) $current_user->roles) ) {
        return new WP_Error( 'forbidden', 'Droits insuffisants.', array( 'status' => 403 ) );
    }

    $role_name = sanitize_text_field( $request->get_param('role') );

    // Garde-fou critique : On ne touche JAMAIS aux droits de l'administrateur système via l'UI
    if ( $role_name === 'administrator' ) {
        return new WP_Error( 'forbidden', 'Les permissions Administrateur sont verrouillées.', array( 'status' => 403 ) );
    }

    $role_obj = get_role( $role_name );
    if ( !$role_obj ) return new WP_Error( 'not_found', 'Rôle introuvable.', array( 'status' => 404 ) );

    $permissions = $request->get_json_params(); // Récupère le tableau des cases cochées/décochées
    $all_caps = cyclo_get_custom_caps();

    foreach ( $all_caps as $cap ) {
        if ( isset($permissions[$cap]) && $permissions[$cap] === true ) {
            $role_obj->add_cap( $cap );
        } else {
            $role_obj->remove_cap( $cap );
        }
    }

    return rest_ensure_response( [ 'status' => 'success', 'message' => 'Permissions mises à jour.' ] );
}