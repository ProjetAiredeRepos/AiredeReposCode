<?php

// 1. CONFIGURATION DE BASE DU THEME
function cyclo_theme_setup() {
    // Demande à Wordpress de gérer la balise <title> tout seul
    add_theme_support( 'title-tag' );
    // Active le support des images à la une
    add_theme_support('post-thumbnails');
}
// On exécute cette fonction au moment de l'initialisation du thème
add_action( 'after_setup_theme', 'cyclo_theme_setup' );

// 2. CHARGEMENT DES STYLES ET SCRIPTS
function cyclo_theme_assets() {
    // On charge Outfit (du poids 100 au poids 900) et DM Sans depuis Google Fonts
    wp_enqueue_style('cyclo-google-fonts', 'https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Outfit:wght@400;600;700;900&display=swap', array(), null);

    // Charger le fichier style.css principal
    wp_enqueue_style('cyclo-style', get_stylesheet_uri(), array(), '1.0');

    // On déclare notre script de tracking
    wp_enqueue_script(
        'aire-repos-tracking', // Nom de code unique
        get_template_directory_uri() . '/tracking.js', // Chemin vers le fichier
        array(), // Pas de dépendances (comme jQuery)
        '1.0', // Version
        true // true = charger dans le footer pour ne pas bloquer l'affichage
    );

    // AJOUT : Injection sécurisée de l'ID de l'aire pour le tracking
    if ( is_singular('aire_repos') ) {
        wp_localize_script('aire-repos-tracking', 'cycloData', array(
            'aire_id' => get_the_ID()
        ));
    }

    // Charger le fichier main.js dans le footer
    wp_enqueue_script('cyclo-main-script',  get_template_directory_uri() . '/main.js', array(), '1.0', true);

    // Chargement conditionnel du script du questionnaire
    global $wp_query;
    if ( is_singular( 'aire_repos' ) && isset( $wp_query->query_vars['questionnaire'] ) ) {
        wp_enqueue_script(
            'cyclo-questionnaire-script', // Nom de code unique
            get_template_directory_uri() . '/scripts/questionnaire.js', // Chemin vers le nouveau fichier
            array(), // Pas de dépendances
            '1.0',
            true // true = charger dans le footer (bonne pratique performance)
        );
    }
}
// On exécute cette fonction au moment de charger les scripts
add_action('wp_enqueue_scripts', 'cyclo_theme_assets');

add_filter('show_admin_bar', '__return_false');

// 3. TAILLES D'IMAGES OPTIMISÉES (Lighthouse)

// AJOUT ICI : Le format pour le fond d'écran Hero du fichier single PHP !
add_image_size('hero-mobile', 600, 900, true);

// On crée une taille "carte" (400px de large pour le carrousel et les modales)
add_image_size('format-carte', 400, 600, true);

// On crée une taille "mini" pour les petits aperçus ou les pins (150px)
add_image_size('format-mini', 150, 150, true);


// 4. FORCER UNE EXCELLENTE COMPRESSION SUR LES IMAGES (Score Performance)
// Par défaut, WordPress compresse à 82%. On baisse à 70% pour diviser le poids par deux sans perte visible sur mobile.
add_filter('jpeg_quality', function($quality) { return 70; });
add_filter('wp_editor_set_quality', function($quality) { return 70; });