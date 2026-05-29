<?php

// 1. CONFIGURATION DE BASE DU THEME
function cyclo_theme_setup() {
    // Demande à Wordpress de gérer la balise <title> tout seul
    add_theme_support( 'title-tag' );
}
// On exécute cette fonction au moment de l'initialisation du thème
add_action( 'after_setup_theme', 'cyclo_theme_setup' );

// 2. CHARGEMENT DES STYLES ET SCRIPTS
function cyclo_theme_assets() {
    // On charge Outfit (du poids 100 au poids 900) et DM Sans depuis Google Fonts
    wp_enqueue_style('cyclo-google-fonts', 'https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Outfit:wght@400;600;700;900&display=swap', array(), null);

    // Charger le fichier style.css principal
    wp_enqueue_style('cyclo-style', get_stylesheet_uri(), array(), '1.0');

    // Charger le fichier main.js dans le footer
    wp_enqueue_script('cyclo-main-script',  get_template_directory_uri() . '/main.js', array(), '1.0', true);
}
// On exécute cette fonction au moment de charger les scripts
add_action('wp_enqueue_scripts', 'cyclo_theme_assets');