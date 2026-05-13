<?php
/*
 Plugin Name: Aire Repos Core
Description: Plugin métier gérant la logique des tables sur mesure pour les statistiques de scan (Cookieless).
Version: 1.0
Author: Samuel et Semih étudiants à la fabrique numérique 41
 */

// Sécurité : Empêcher l'accès direct au fichier
if (! defined('ABSPATH' )) {
    exit;
}

// Déclaration de la fonction à lancer lors de l'activation du plugin
register_activation_hook(__FILE__, 'aire_repos_creer_tables_statistiques' );

function aire_repos_creer_tables_statistiques() {
    global $wpdb;

    // 1. Récupération de l'interclassement (Collation) défini par WordPress
    // (Correspond généralement à utf8mb4_unicode_520_ci comme demandé)
    $charset_collate = $wpdb->get_charset_collate();

    // Inclusion du fichier natif de WordPress requis pour utiliser la fonction dbDelta()
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    // TABLE 1 : visite_site (Enregistre le scan initial du QR Code)
    // Rescpect des consignes : Type INT pour l'ID (pas BIGINT) et moteur InnoDB
    $nom_table_visite = $wpdb->prefix . 'visite_site';
    $sql_visite = "CREATE TABLE $nom_table_visite (
        id_visite INT NOT NULL AUTO_INCREMENT,
        id_aire_repos INT NOT NULL,
        id_langue INT NOT NULL,
        date_scan DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
        user_agent VARCHAR(255) DEFAULT '' NOT NULL,
        PRIMARY KEY (id_visite)
    ) $charset_collate ENGINE=InnoDB;";

    // Exécution sécurisée de la requête SQL via dbDelta
    dbDelta($sql_visite);

    // TABLE 2 : vue_page (Enregistre le temps passé par visite)
    // Respect des consignes : Type INT, Moteur InnoDB
    $nom_table_vue = $wpdb->prefix . 'vue_page';

    $sql_vue = "CREATE TABLE $nom_table_vue (
        id_vue INT NOT NULL AUTO_INCREMENT,
        id_visite INT NOT NULL,
        page_vue VARCHAR(100) NOT NULL,
        temps_passe_secondes INT NOT NULL,
        PRIMARY KEY (id_vue),
        KEY id_visite (id_visite)
    ) $charset_collate ENGINE=InnoDB;";

    dbDelta( $sql_vue );
}

// ENREGISTRER DES SCANS
// On "accroche" la fonction au chargement des pages du site (template_redirect)
add_action( 'template_redirect', 'aire_repos_enregistrer_nouveau_scan' );

function aire_repos_enregistrer_nouveau_scan() {

    // 1. On vérifie si l'URL contient le paramètre "?action=scan-qr"
    if ( isset($_GET['action']) && $_GET['aire_id'] === 'scan_qr' ) {

        // 2. On récupère de manière sécurisée (intval) les numéros dans l'URL
        $id_aire = isset($_GET['aire_id']) ? intval($_GET['aire_id']) : 0;
        $id_langue = isset($_GET['langue_id']) ? intval($_GET['langue_id']) : 1;

        // 3. On récupère le navigateur du téléphone (Safari, Chrome...) pour les stats
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '';

        // 4. Si l'ID de l'aire est valide, on déclenche ce code
        if ( $id_aire > 0 ) {
            global $wpdb;

            $wpdb->insert(
                    $wpdb->prefix . 'visite_site', // La table sur mesure
                    array(
                            'id_aire_repos' => $id_aire,
                        'id_langue' => $id_langue,
                        'user_agent' => $user_agent
                    ),
                    array( '%d', '%d', '%s' ) // Précisition des formats : entier, entier, chaine de caractère
            );
        }
    }
}
