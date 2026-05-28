<?php
// Chargement des scripts et styles du thème
function aire_repos_enqueue_scripts() {

    // 1. Déclarer la feuille de style principale (style.css)
    wp_enqueue_style(
        'aire-repos-style',
        get_stylesheet_uri(),
        array(),
        filemtime( get_stylesheet_directory() . '/style.css' ) // Gère le cache automatiquement
    );

    // 2. On déclare notre script de tracking
    wp_enqueue_script(
        'aire-repos-tracking', // Nom de code unique
    get_template_directory_uri() . 'js/tracking.js', // Chemin vers le fichier
        array(), // Pas de dépendances (comme jQuery)
    '1.0', // Version
    true // true = charger dans le footer pour ne pas bloquer l'affichage
    );

    // 3. Déclarer le nouveau script pour l'interface (Menu Burger, Modales)
    wp_enqueue_script(
        'aire-repos-ui',
        get_template_directory_uri() . '/js/ui.js',
        array(),
        '1.0',
        true
    );
}

// On accroche notre fonction à l'action native WordPress
add_action( 'wp_enqueue_scripts', 'aire_repos_enqueue_scripts' );