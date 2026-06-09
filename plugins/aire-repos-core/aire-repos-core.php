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
    // Définition de la table 4
    $table_clics = $wpdb->prefix . 'clic_lien';

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

    // Table 4 : Tracking des clics sur les liens sortants
    $sql_clics = "CREATE TABLE $table_clics (
        id_clic int(11) NOT NULL AUTO_INCREMENT,
        id_aire int(11) NOT NULL,
        id_visite int(11) DEFAULT NULL,
        texte_lien varchar(255) NOT NULL,
        url_lien text NOT NULL,
        date_clic datetime NOT NULL,
        PRIMARY KEY  (id_clic),
        KEY id_aire (id_aire)
    ) $charset_collate;";

    dbDelta( $sql_clics );

    // GESTION DES RÔLES CYCLO-DATA
    // On nettoie les rôles s'ils existent déjà pour pouvoir les mettre à jour
    remove_role( 'cyclo_admin');
    remove_role( 'cyclo_editeur');
    remove_role('cyclo_user');

    // Rôle : Administrateur (Accès total)
    add_role( 'cyclo_admin', 'Admin Cyclo-Data', array(
        'read' => true,
        'manage_cyclo_users' => true,
        'view_all_stats' => true,
        'manage_settings' => true
    ));

    // Rôle : Éditeur (Accès statistiques globales + getion formulaires)
    add_role( 'cyclo_editeur', 'Éditeur Cyclo-Data', array(
        'read' => true,
        'view_all_stats' => true,
        'manage_forms' => true
    ));

    // Rôle : Utilisateur (Accès restreint à ses propres aires)
    add_role( 'cyclo_user', 'Utilisateur Cyclo-Data', array(
        'read' => true,
        'view_restricted_stats' => true
    ));


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


add_action( 'init', 'cyclo_register_itineraire_taxonomy' );

function cyclo_register_itineraire_taxonomy() {
    $labels = array(
        'name'              => 'Itinéraires',
        'singular_name'     => 'Itinéraire',
        'search_items'      => 'Rechercher un itinéraire',
        'all_items'         => 'Tous les itinéraires',
        'parent_item'       => 'Itinéraire parent',
        'parent_item_colon' => 'Itinéraire parent :',
        'edit_item'         => 'Modifier l\'itinéraire',
        'update_item'       => 'Mettre à jour l\'itinéraire',
        'add_new_item'      => 'Ajouter un nouvel itinéraire',
        'new_item_name'     => 'Nom du nouvel itinéraire',
        'menu_name'         => 'Itinéraires',
    );

    $args = array(
        'hierarchical'      => true, // true = se comporte comme des catégories (avec cases à cocher)
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true, // Affiche la colonne dans la liste des aires de repos
        'query_var'         => true,
        'show_in_rest'      => true, // Indispensable pour l'éditeur Gutenberg et notre API REST
        'rewrite'           => array( 'slug' => 'itineraire' ),
    );

    // On attache cette taxonomie au CPT 'aire_repos'
    register_taxonomy( 'itineraire', array( 'aire_repos' ), $args );
}

