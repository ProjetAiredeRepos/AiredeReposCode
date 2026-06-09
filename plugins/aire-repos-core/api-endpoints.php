<?php
// Fichier : wp-content/plugins/aire-repos-core/api-endpoints.php

// Sécurité : Empêcher l'accès direct
if ( !defined('ABSPATH')) {
    exit;
}

add_action( 'rest_api_init', 'aire_repos_register_api_endpoints' );

// Enregistrement des routes personnalisées de l'API REST
function aire_repos_register_api_endpoints() {

    // Endpoint 1 : Enregistrement de la visite initiale
    register_rest_route( 'aire-repos/v1', '/scan', array(
        'methods' => WP_REST_Server::CREATABLE, // Constante pour 'POST'
        'callback' => 'aire_repos_log_qr_scan',
        'permission_callback' => '__return_true', // Autorise l'accès public (Cookieless)
    ));

    // Endpoint 2 : Mise à jour du temps de visie à la fermeture
    register_rest_route( 'aire-repos/v1', '/update-time', array(
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'aire_repos_update_visit_time',
        'permission_callback' => '__return_true',
    ));

    // Endpoint 3 : Soumission du questionnaire
    register_rest_route( 'aire-repos/v1', '/submit-survey', array(
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'aire_repos_submit_survey',
        'permission_callback' => '__return_true', // Soumission publique
    ));

    // Endpoint Dashboard : Vue d'ensemble Découverte
    register_rest_route( 'cyclo-data/v1', '/discovery-overview', array(
        'methods' => WP_REST_Server::READABLE,  //GET
        'callback' => 'cyclo_get_discovery_overview',
        'permission_callback' => function () {
            // SÉCURITÉ : Vérifie i l'utilisateur est connecté et possède un de nos rôles
            $user = wp_get_current_user();

            // On définit la liste stricte des rôles autorisés (ajout de 'administrator')
            $allowed_roles = ['administrator', 'cyclo_admin', 'cyclo_editeur', 'cyclo_user'];

            // Vérification de l'intersection entre les rôles de l'utilisateur et les rôles autorisés
            return array_intersect( $allowed_roles, (array) $user->roles ) ? true : false;
        }
    ));

    // Endpoint Dashboard : Vue d'ensemble Questionnaires
    register_rest_route( 'cyclo-data/v1', '/survey-overview', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'cyclo_get_survey_overview',
        'permission_callback' => function () {
            $user = wp_get_current_user();
            return array_intersect(['administrator', 'cyclo_admin', 'cyclo_editeur'], (array) $user->roles) ? true : false;
        }
    ));

    // ROUTE POUR L'EXPORT CSV
    register_rest_route( 'cyclo-data/v1', '/export-csv', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'cyclo_export_csv_data',
        'permission_callback' => function () {
            $user = wp_get_current_user();
            return array_intersect(['administrator', 'cyclo_admin', 'cyclo_editeur'], (array) $user->roles) ? true : false;
        }
    ));
}

// Logique de traitement pour l'endpoint /scan
function aire_repos_log_qr_scan( WP_REST_Request $request ) {
    global $wpdb;

    // 1. Validation et Nettoyage (Sanitization)
    $id_aire = absint( $request->get_param( 'id_aire' ) );
    $code_langue = sanitize_text_field( $request->get_param( 'code_langue' ) );

    // Contrôle d'intégrité
    if ( empty($id_aire) || empty($code_langue) || strlen($code_langue) > 2) {
        return new WP_Error( 'invalid_data', 'Paramètres de scan invalides.', array( 'status' => 400 ) );
    }

    // 2. Préparation des variables
    $table_visite = $wpdb->prefix . 'visite_site';
    $current_time = current_time( 'mysql' );

    // 3. Insertion sécurisée
    $inserted = $wpdb->insert(
        $table_visite,
        array(
            'date_visite' => $current_time,
            'id_aire' => $id_aire,
            'code_langue' => strtoupper( $code_langue ),
            'temps_total' => 0
        ),
        array( '%s', '%d', '%s', '%d' )
    );

    if ( false === $inserted ) {
        return new WP_Error( 'db_error', 'Erreur interne de journalisation.', array( 'status' => 500 ) );
    }

    // 4. Réponse JSON formatée
    return rest_ensure_response( array(
        'status' => 'success',
        'message' => 'Scan enregistré de manière anonyme.',
        'id_visite' => $wpdb->insert_id
    ));
}

// Logique de traitement pour l'endpoint /update-time
function aire_repos_update_visit_time( WP_REST_Request $request ) {
    global $wpdb;

    $id_visite = absint( $request->get_param( 'id_visite'));
    $temps_total = absint( $request->get_param('temps_total'));

    if ( empty($id_visite) || !isset($_REQUEST['temps_total']) ) {
        return new WP_Error( 'invalid_data', 'Données de mise à jour invalides.', array( 'status' => 400 ) );
    }

    $table_visite = $wpdb->prefix . 'visite_site';

    // Sécurité : mise à jour préparée
    $wpdb->update(
        $table_visite,
        array( 'temps_total' => $temps_total ),
        array( 'id_visite' => $id_visite ),
        array( '%d'),
        array( '%d')
    );

    return rest_ensure_response( array( 'status' => 'success' ) );
}

// Logique de traitement pour l'endpoint /submit-survey
function aire_repos_submit_survey( WP_REST_Request $request ) {
    global $wpdb;

    // Nettoyage rigoureux des données entrantes (Sanitization)
    $id_aire = absint( $request->get_param( 'id_aire' ) );
    $q1 = absint( $request->get_param( 'q1_satisfaction_aire' ) );
    $q2 = absint( $request->get_param( 'q2_satisfaction_securite' ) );
    $q3 = absint( $request->get_param( 'q3_satisfaction_services' ) );

    // Les cases à cocher renvoient un tableau, on le transforme en chaîne séparée par des virgules
    $q4_array = (array) $request->get_param( 'q4_connaissance' );
    $q4_clean = sanitize_text_field( implode( ',', array_map( 'sanitize_text_field', $q4_array ) ) );

    $q5 = sanitize_textarea_field( $request->get_param( 'q5_suggestions' ) );

    // Données de profilage (Optionnelles)
    $origine = sanitize_text_field( $request->get_param( 'profil_origine' ) );
    $region = sanitize_text_field( $request->get_param( 'profil_region' ) );
    $pays = sanitize_text_field( $request->get_param( 'profil_pays' ) );
    if ( $pays === 'autre' ) {
        $pays = sanitize_text_field( $request->get_param( 'profil_pays_autre' ) ); // Capture le champ texte libre
    }
    $accompagnement = sanitize_text_field( $request->get_param( 'profil_accompagnement' ) );

    // Validation métier : Les questions 1 à 4 sont obligatoires
    if ( empty($id_aire) || empty($q1) || empty($q2) || empty($q3) || empty($q4_clean) ) {
        return new WP_Error( 'invalid_data', 'Veuillez remplir toutes les questions obligatoires.', array( 'status' => 400 ) );
    }

    $table_name = $wpdb->prefix . 'reponse_questionnaire';
    $inserted = $wpdb->insert(
        $table_name,
        array(
            'id_aire' => $id_aire,
            'q1_satisfaction_aire' => $q1,
            'q2_satisfaction_securite' => $q2,
            'q3_satisfaction_services' => $q3,
            'q4_connaissance' => $q4_clean,
            'q5_suggestions' => $q5,
            'profil_origine' => $origine,
            'profil_region' => $region,
            'profil_pays' => $pays,
            'profil_accompagnement' => $accompagnement,
            'date_soumission' => current_time( 'mysql' )
        ),
        array( '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
    );

    if ( false === $inserted ) {
        return new WP_Error( 'db_error', 'Erreur serveur lors de l\'enregistrement.', array( 'status' => 500 ) );
    }

    return rest_ensure_response( array(
        'status' => 'success',
        'message' => 'Merci pour votre participation !'
    ));
}

// Logique récupération des données, Endpoint Dashboard (Outil de découverte)
function cyclo_get_discovery_overview( WP_REST_Request $request ) {
    global $wpdb;

    // 1. Récupération des filtres envoyés par le JS
    $start_date = sanitize_text_field( $request->get_param('start_date') ?: date('Y-01-01') );
    $end_date   = sanitize_text_field( $request->get_param('end_date') ?: date('Y-12-31') );
    $level      = sanitize_text_field( $request->get_param('level') ?: 'global' );
    $id         = sanitize_text_field( $request->get_param('id') ?: 'all' );

    $table_visite = $wpdb->prefix . 'visite_site';

    // 2. Construction dynamique et sécurisée de la requête SQL
    $where = "WHERE date_visite >= %s AND date_visite <= %s";
    $params = [ $start_date . ' 00:00:00', $end_date . ' 23:59:59' ];

    // Si on clique sur une Aire spécifique
    if ( $level === 'aire' && $id !== 'all' ) {
        $where .= " AND id_aire = %d";
        $params[] = intval($id);
    }
    // Si on clique sur un Itinéraire (la catégorie parente)
    elseif ( $level === 'route' && is_numeric($id) ) {
        $aires = get_posts(array(
            'post_type' => 'aire_repos', 'numberposts' => -1, 'fields' => 'ids',
            'tax_query' => array( array('taxonomy' => 'itineraire', 'field' => 'term_id', 'terms' => intval($id)) )
        ));
        if (!empty($aires)) {
            $placeholders = implode(',', array_fill(0, count($aires), '%d'));
            $where .= " AND id_aire IN ($placeholders)";
            $params = array_merge($params, $aires);
        } else {
            $where .= " AND id_aire = 0"; // Force 0 si l'itinéraire est vide
        }
    }

    // 3. Exécution des requêtes avec les filtres appliqués
    $total_visits = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(id_visite) FROM {$table_visite} {$where}", $params) );
    $monthly_stats = $wpdb->get_results( $wpdb->prepare("SELECT MONTH(date_visite) as mois, COUNT(id_visite) as total FROM {$table_visite} {$where} GROUP BY MONTH(date_visite) ORDER BY mois ASC", $params) );
    $hourly_stats = $wpdb->get_results( $wpdb->prepare("SELECT HOUR(date_visite) as heure, COUNT(id_visite) as total FROM {$table_visite} {$where} GROUP BY HOUR(date_visite) ORDER BY heure ASC", $params) );
    $geo_stats = $wpdb->get_results( $wpdb->prepare("SELECT code_langue, COUNT(id_visite) as total FROM {$table_visite} {$where} GROUP BY code_langue ORDER BY total DESC LIMIT 5", $params) );

    return rest_ensure_response( array(
        'status' => 'success',
        'data' => array(
            'total_consultations' => (int) $total_visits,
            'monthly_evolution'   => $monthly_stats,
            'hourly_stats'        => $hourly_stats,
            'geo_stats'           => $geo_stats
        )
    ));
}

// Fonction de traitement avec requêtes préparées (OWASP) pour calcul des moyennes : Endpoint Dashboard (questionnaires)
function cyclo_get_survey_overview( WP_REST_Request $request ) {
    global $wpdb;

    // 1. Récupération des filtres
    $start_date = sanitize_text_field( $request->get_param('start_date') ?: date('Y-01-01') );
    $end_date   = sanitize_text_field( $request->get_param('end_date') ?: date('Y-12-31') );
    $level      = sanitize_text_field( $request->get_param('level') ?: 'global' );
    $id         = sanitize_text_field( $request->get_param('id') ?: 'all' );

    $table_survey = $wpdb->prefix . 'reponse_questionnaire';

    $where = "WHERE date_soumission >= %s AND date_soumission <= %s";
    $params = [ $start_date . ' 00:00:00', $end_date . ' 23:59:59' ];

    if ( $level === 'aire' && $id !== 'all' ) {
        $where .= " AND id_aire = %d";
        $params[] = intval($id);
    } elseif ( $level === 'route' && is_numeric($id) ) {
        $aires = get_posts(array(
            'post_type' => 'aire_repos', 'numberposts' => -1, 'fields' => 'ids',
            'tax_query' => array( array('taxonomy' => 'itineraire', 'field' => 'term_id', 'terms' => intval($id)) )
        ));
        if (!empty($aires)) {
            $placeholders = implode(',', array_fill(0, count($aires), '%d'));
            $where .= " AND id_aire IN ($placeholders)";
            $params = array_merge($params, $aires);
        } else {
            $where .= " AND id_aire = 0";
        }
    }

    // 2. Requêtes
    $kpi_data = $wpdb->get_row( $wpdb->prepare(
        "SELECT COUNT(id_reponse) as total, AVG(q1_satisfaction_aire) as avg_aire, AVG(q2_satisfaction_securite) as avg_securite, AVG(q3_satisfaction_services) as avg_services FROM {$table_survey} {$where}", $params
    ));

    $monthly_stats = $wpdb->get_results( $wpdb->prepare(
        "SELECT MONTH(date_soumission) as mois, COUNT(id_reponse) as total FROM {$table_survey} {$where} GROUP BY MONTH(date_soumission) ORDER BY mois ASC", $params
    ));

    // 3. Calculs Sécurisés (Anti division par 0)
    $total = isset($kpi_data->total) ? (int) $kpi_data->total : 0;
    $radar = [0, 0, 0];
    $indice_global = 0;

    if ($total > 0) {
        $avg_aire_5 = ($kpi_data->avg_aire / 4) * 5;
        $avg_sec_5 = ($kpi_data->avg_securite / 4) * 5;
        $avg_srv_5 = ($kpi_data->avg_services / 4) * 5;
        $indice_global = ($avg_aire_5 + $avg_sec_5 + $avg_srv_5) / 3;

        $radar = [
            round(($kpi_data->avg_aire / 4) * 100),
            round(($kpi_data->avg_securite / 4) * 100),
            round(($kpi_data->avg_services / 4) * 100)
        ];
    }

    return rest_ensure_response( array(
        'status' => 'success',
        'data' => array(
            'total_reponses' => $total,
            'indice_satisfaction' => round($indice_global, 1),
            'radar_scores' => $radar,
            'monthly_evolution' => $monthly_stats
        )
    ));
}

// Logique d'export de données en CSV, Endpoint route pour l'export CSV
function cyclo_export_csv_data( WP_REST_Request $request ) {
    global $wpdb;

    // 1. Récupération des filtres
    $module = sanitize_text_field( $request->get_param('module') ?: 'discovery');
    $start_date = sanitize_text_field( $request->get_param('start_date') ?: date('Y-01-01') );
    $end_date = sanitize_text_field( $request->get_param('end_date') ?: date('Y-12-31') );
    $level = sanitize_text_field( $request->get_param('level') ?: 'global' );
    $id = sanitize_text_field( $request->get_param('id') ?: 'all' );

    // 2. Construction de la condition WHERE (comme pour l'API Dashboard)
    $where = "WHERE date >= %s AND date <= %s";
    $params = [ $start_date . '00:00:00', $end_date . '23:59:59' ];

    if ( $level === 'aire' && $id !== 'all' ) {
        $where .= " AND id_aire = %d";
        $params[] = intval($id);
    } elseif ( $level === 'route' && is_numeric($id) ) {
        $aires = get_posts(array(
            'post_type' => 'aire_repos', 'numberposts' => -1, 'fields' => 'ids',
            'tax_query' => array( array('taxonomy' => 'itineraire', 'field' => 'term_id', 'terms' => intval($id)) )
        ));
        if (!empty($aires)) {
            $placeholders = implode(',', array_fill(0, count($aires), '%d'));
            $where .= " AND id_aire IN ($placeholders)";
            $params = array_merge($params, $aires);
        } else {
            $where .= " AND id_aire = 0";
        }
    }

    // 3. Configuration des Headers pour forcer le téléchargement du CSV
    $filename = "export_{$module}_" . date('Ymd') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    // Ouvre le flux de sortie PHP
    $output = fopen('php://output', 'w');
    // Ajoute le BOM UTF-8 pour qu'Excel lise correctement les accents (très important !)
    fputs($output, $bom =(chr(0xEF) . chr(0xBB) . chr(0xBF)));

    // 4. Extraction et formatage selon le module
    if ( $module === 'discovery' ) {
        $table = $wpdb->prefix . 'visit_site';
        // Modification dynamique du WHERE pour matcher les noms de colonnes de cette table
        $where_disco = str_replace('date', 'date_visite', $where);

        fputcsv($output, ['ID Visite', 'Date', 'ID Aire', 'Langue', 'Temps passé (s)'], ';');

        $results = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$table} {$where_disco} ORDER BY date_visite DESC", $params), ARRAY_A );
        foreach ( $results as $row ) {
            fputcsv($output, [$row['id_visite'], $row['date_visite'], $row['id_aire'], strtoupper($row['code_langue']), $row['temps_total']], ';');
        }
    } else {
        $table = $wpdb->prefix . 'response_questionnaire';
        $where_survey = str_replace('date', 'date_soumission', $where);

        fputcsv($output, ['ID Response', 'Date', 'ID Aire', 'Note Aire', 'Note Securite', 'Note Services', 'Origine', 'Pays'], ';');

        $results = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$table} {$where_survey} ORDER BY date_soumission DESC", $params), ARRAY_A );
        foreach ( $results as $row ) {
            fputcsv($output, [$row['id_response'], $row['date_soumission'], $row['q1_satisfaction_aire'], $row['q2_satisfaction_securite'], $row['q3_satisfaction_services'], $row['profil_origine'], $row['profil_pays']], ';');
        }
    }

    fclose($output);
    exit; // Coupe l'exécution de WP pour envoyer un fichier pur, sans HTML autour.
}