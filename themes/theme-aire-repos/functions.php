<?php
// Chargement des scripts et styles du thème
function aire_repos_enqueue_scripts() {

    // On déclare notre script de tracking
    wp_enqueue_script(
        'aire-repos-tracking', // Nom de code unique
    get_template_directory_uri() . 'js/tracking.js', // Chemin vers le fichier
        array(), // Pas de dépendances (comme jQuery)
    '1.0', // Version
    true // true = charger dans le footer pour ne pas bloquer l'affichage
    );
}

// On accroche notre fonction à l'action native WordPress
add_action( 'wp_enqueue_scripts', 'aire_repos_enqueue_scripts' );