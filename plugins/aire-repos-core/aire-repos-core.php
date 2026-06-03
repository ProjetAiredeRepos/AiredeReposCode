<?php
/**
 * Plugin Name: Aire de Repos - Logique Métier & Tracking
 * Description: API REST et architecture de base de données sur mesure pour les statistiques de scans QR Code.
 * Version: 1.0.0
 * Author: Équipe Technique
 *
 * Chemin du fichier : /wp-content/plugins/aire-repos-core/aire-repos-core.php
 */

if (! defined( 'ABSPATH' ) ) {
    exit; // Mesure de sécurité : blocage de l'accès direct au fichier
}

/**
 * Fonction d'installation déclenchée exclusivement à l'activation du plugin.
 * Responsable de la création et de la mise à jour du schéma de base de données.
 */
function aire_repos_install_database_schema() {
    global $wpdb;

    // Récupération automatique de la collation définie dans wp-config.php (ex: utf8mb4_unicode_ci)
    $charset_collate = $wpdb->get_charset_collate();

    // Préfixage des tables pour garantir la sécurité et la compatibilité multisite
    $table_visite = $wpdb->prefix. 'visite_site';
    $table_vue    = $wpdb->prefix. 'vue_page';
    // Définition de la Table 3 : reponse_questionnaire
    $table_questionnaire = $wpdb->prefix . 'reponse_questionnaire';

    // Inclusion du fichier Core de WordPress contenant la définition de dbDelta()
    require_once( ABSPATH. 'wp-admin/includes/upgrade.php' );

    /*
     * Définition de la Table 1 : visite_site (Tracking Global)
     * Attention : Respect absolu des règles d'espacement et de casse de dbDelta.
     */
    $sql_visite = "CREATE TABLE $table_visite (
        id_visite int(11) NOT NULL AUTO_INCREMENT,
        date_visite datetime NOT NULL,
        temps_total int(11) DEFAULT 0,
        id_aire int(11) NOT NULL,
        code_langue varchar(2) NOT NULL,
        PRIMARY KEY  (id_visite),
        KEY id_aire (id_aire),
        KEY code_langue (code_langue)
    ) $charset_collate;";

    dbDelta( $sql_visite );

    /*
     * Définition de la Table 2 : vue_page (Tracking comportemental détaillé)
     * Note technique : dbDelta gère mal les déclarations FOREIGN KEY natives.
     * Il est stratégiquement préférable de définir des index (KEY) sur les clés
     * relationnelles et de gérer l'intégrité référentielle et les suppressions
     * en cascade côté applicatif (en PHP).
     */
    $sql_vue = "CREATE TABLE $table_vue (
        id_vue int(11) NOT NULL AUTO_INCREMENT,
        nom_page varchar(100) NOT NULL,
        temps_passe int(11) NOT NULL,
        id_visite int(11) NOT NULL,
        PRIMARY KEY  (id_vue),
        KEY id_visite (id_visite)
    ) $charset_collate;";

    dbDelta( $sql_vue );

    $sql_questionnaire = "CREATE TABLE $table_questionnaire (
        id_reponse int(11) NOT NULL AUTO_INCREMENT,
        id_aire int(11) NOT NULL,
        q1_satisfaction_aire int(1) NOT NULL,
        q2_satisfaction_securite int(1) NOT NULL,
        q3_satisfaction_services int(1) NOT NULL,
        q4_connaissance varchar(255) NOT NULL,
        q5_suggestions text DEFAULT NULL,
        profil_origine varchar(10) DEFAULT NULL,
        profil_region varchar(100) DEFAULT NULL,
        profil_pays varchar(100) DEFAULT NULL,
        profil_accompagnement varchar(50) DEFAULT NULL,
        date_soumission datetime NOT NULL,
        PRIMARY KEY  (id_reponse),
        KEY id_aire (id_aire)
    ) $charset_collate;";

    dbDelta( $sql_questionnaire );

    // Sauvegarde de la version de la base pour permettre des migrations
    // conditionnelles lors des futures mises à jour du plugin.
    add_option( 'aire_repos_db_version', '1.1' );
}

// Enregistrement du hook d'activation ciblant la fonction ci-dessus
register_activation_hook( __FILE__, 'aire_repos_install_database_schema' );

// Inclusion des routes de l'API REST
require_once plugin_dir_path(__FILE__) . 'api-endpoints.php';

// Enregistrement de l'URL Endpoint pour le questionnaire
add_action( 'init', 'aire_repos_add_questionnaire_endpoint' );
function aire_repos_add_questionnaire_endpoint() {
    // Crée la route /questionnaire/ à la fin des permaliens
    add_rewrite_endpoint( 'questionnaire', EP_PERMALINK );
}

// Redirection vers le template spécifique du thème
add_filter( 'template_include', 'aire_repos_questionnaire_template' );
function aire_repos_questionnaire_template( $template ) {
    global $wp_query;

    // Si on est sur une page "Aire de Repos" ET que l'endpoint "questionnaire est appelé
    if ( is_singular( 'aire_repos' ) && isset( $wp_query->query_vars['questionnaire'] ) ) {
        // On charge un fichier de template distinct depuis le thème
        $new_template = locate_template( array( 'single-aire_repos-questionnaire.php' ) );
        if ( !empty( $new_template ) ) {
            return $new_template;
        }
    }
    return $template;
}

// CUSTOM POST TYPE (CPT) : POINTS D'INTÉRÊT (POI)
add_action( 'init', 'aire_repos_register_poi_cpt');

function aire_repos_register_poi_cpt() {
    $labels = array(
        'name' => 'Points d\'Intérêt',
        'singular_name' => 'Point d\'Intérêt',
        'menu_name'             => 'Points d\'Intérêt',
        'add_new'               => 'Ajouter un point',
        'add_new_item'          => 'Ajouter un nouveau Point d\'Intérêt',
        'edit_item'             => 'Modifier le Point d\'Intérêt',
        'all_items'             => 'Tous les points',
        'search_items'          => 'Rechercher un point',
        'not_found'             => 'Aucun point d\'intérêt trouvé.',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'menu_position'      => 20, // S'affiche juste en dessous du menu "Pages"
        'menu_icon'          => 'dashicons-location', // Icône de punaise (très visuel)
        'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'has_archive'        => false, // Pas besoin de page d'archive pour une application web
        'show_in_rest'       => true, // Indispensable pour profiter de l'éditeur moderne de WordPress
    );

    register_post_type( 'poi', $args );

}