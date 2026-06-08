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

    // 1. Validation temporelle stricte via REGEX (YYYY-MM-DD)
    $start_date = sanitize_text_field( $request->get_param('start_date') ?: date('Y-01-01') );
    $end_date   = sanitize_text_field( $request->get_param('end_date') ?: date('Y-12-31') );

    if ( ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date) ) {
        return new WP_Error( 'invalid_date', 'Format de date invalide.', array( 'status' => 400 ) );
    }

    $table_visite = $wpdb->prefix . 'visite_site';

    // 2. Requête Préparée : Protection absolue contre l'injection SQL
    // On utilise %s pour les chaînes (dates) et %d pour les entiers
    $query_total = $wpdb->prepare(
        "SELECT COUNT(id_visite) FROM {$table_visite} WHERE date_visite >= %s AND date_visite <= %s",
        $start_date . ' 00:00:00',
        $end_date . ' 23:59:59'
    );

    $total_visits = $wpdb->get_var( $query_total );

    // Requête préparée pour le graphique d'évolution mensuelle
    $query_monthly = $wpdb->prepare(
        "SELECT MONTH(date_visite) as mois, COUNT(id_visite) as total 
         FROM {$table_visite} 
         WHERE date_visite >= %s AND date_visite <= %s 
         GROUP BY MONTH(date_visite) 
         ORDER BY mois ASC",
        $start_date . ' 00:00:00',
        $end_date . ' 23:59:59'
    );

    $monthly_stats = $wpdb->get_results( $query_monthly );

    // --- 3. Requête : Répartition par tranche horaire ---
    $query_hourly = $wpdb->prepare(
        "SELECT HOUR(date_visite) as heure, COUNT(id_visite) as total 
         FROM {$table_visite} 
         WHERE date_visite >= %s AND date_visite <= %s 
         GROUP BY HOUR(date_visite) 
         ORDER BY heure ASC",
        $start_date . ' 00:00:00',
        $end_date . ' 23:59:59'
    );
    $hourly_stats = $wpdb->get_results( $query_hourly );

    // --- 4. Requête : Origine géographique (basée sur le code_langue) ---
    $query_geo = $wpdb->prepare(
        "SELECT code_langue, COUNT(id_visite) as total 
         FROM {$table_visite} 
         WHERE date_visite >= %s AND date_visite <= %s 
         GROUP BY code_langue 
         ORDER BY total DESC 
         LIMIT 5", // On prend le Top 5 pour le graphique Donut
        $start_date . ' 00:00:00',
        $end_date . ' 23:59:59'
    );
    $geo_stats = $wpdb->get_results( $query_geo );

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

    $start_date = sanitize_text_field( $request->get_param('start_date') ?: date('Y-01-01') );
    $end_date = sanitize_text_field( $request->get_param('end_date') ?: date('Y-12-31') );

    $table_survey = $wpdb->prefix . 'response_questionnaire';

    // 1. KPIs (Key Performance Indicator) globaux (Total et Moyenne pondérée)
    $query_kpi = $wpdb->prepare(
        "SELECT COUNT(id_response) as total,
                       AVG(q1_satisfaction_aire) as avg_aire,
                       AVG(q2_satisfaction_securite) as avg_securite,
                       AVG(q3_satisfaction_services) as avg_services
               FROM {table_survey}
               WHERE date_soumission >= %s AND date_soumission <= %s",
        $start_date . '00:00:00',
        $end_date . '23:59:59'
    );
    $kpi_data = $wpdb->get_row( $query_kpi );

    // Calcul de l'indice de satisfaction global (Moyenne des 3 moyennes, ramenée sur 5)
    // Nos notes sont sur 4 (Très satisfaisant = 4, etc.). On ramène sur 5 pour l'affichage maquette.
    $avg_aire_5 = ($kpi_data->avg_aire / 4) * 5;
    $avg_sec_5 = ($kpi_data->avg_securite / 4) * 5;
    $avg_srv_5 = ($kpi_data->avg_services / 4) * 5;

    $indice_global = ($avg_aire_5 + $avg_sec_5 + $avg_srv_5) / 3;

    // 2. Évolution mensuelle des réponses
    $query_monthly = $wpdb->prepare(
        "SELECT MONTH(date_soumission) as mois, COUNT(id_response) as total
        FROM {table_survey}
        WHERE date_soumission >= %s AND date_soumission <= %s
        GROUP BY MONTH(date_soumission)
        ORDER BY mois ASC",
        $start_date . '00:00:00',
        $end_date . '23:59:59'
    );
    $monthly_stats = $wpdb->get_results( $query_monthly );

    return rest_ensure_response( array(
        'status' => 'success',
        'data' => array(
            'total_responses' => (int) $kpi_data->total,
            'indice_satisfaction' => round($indice_global, 1),
            'radar_scores' => [
                round(($kpi_data->avg_aire /4) * 100), // Score sur 100%
                round(($kpi_data->avg_securite /4) * 100),
                round(($kpi_data->avg_services /4) *100)
            ],
            'monthly_evolution' => $monthly_stats
        )
    ));
}