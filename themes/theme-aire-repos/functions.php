<?php

// 1. CONFIGURATION DE BASE DU THEME
function cyclo_theme_setup() {
    // Demande à Wordpress de gérer la balise <title> tout seul
    add_theme_support( 'title-tag' );
    // Active le support des images à la une
    add_theme_support('post-thumbnails');
    // Active le changement de logo depuis l'administration
    add_theme_support('custom-logo', array(
        'height' => 80,
        'width' => 250,
        'flex-height' => true,
        'flex-width' => true,
    ));
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

// --- 5. OPTION LOGO DU FOOTER ---
function cyclo_customizer_settings( $wp_customize ) {
    // Crée la case dans la base de données pour stocker le logo du footer
    $wp_customize->add_setting( 'footer_logo', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ) );

    // Ajoute le bouton d'envoi d'image dans l'onglet "Identité du site" de l'admin
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'footer_logo', array(
        'label'       => 'Logo du Footer (Optionnel)',
        'description' => 'Importez une version alternative de votre logo pour le pied de page.',
        'section'     => 'title_tagline',
        'settings'    => 'footer_logo',
    ) ) );
}
// Enregistre l'option dans l'outil de personnalisation WordPress
add_action( 'customize_register', 'cyclo_customizer_settings' );


// --- 6. FONCTIONS OUTILS POUR LES IMAGES ACF ---

/**
 * Récupère l'adresse URL d'une image, peu importe son format dans ACF (ID, Tableau ou Lien direct)
 */
function cyclo_get_image_url($field_value, $size = 'large') {
    if (empty($field_value)) return '';

    // Cas 1 : Le champ ACF renvoie un ID numérique
    if (is_numeric($field_value)) {
        $url = wp_get_attachment_image_url($field_value, $size);
        return $url ? $url : wp_get_attachment_url($field_value);
    }
    // Cas 2 : Le champ ACF renvoie un Tableau complet (Array)
    if (is_array($field_value)) {
        return isset($field_value['sizes'][$size]) ? $field_value['sizes'][$size] : $field_value['url'];
    }
    // Cas 3 : Le champ ACF renvoie déjà une URL brute (String)
    if (is_string($field_value)) {
        return $field_value;
    }
    return '';
}

/**
 * Génère la balise HTML <img> optimisée ou utilise un lien classique en secours
 */
function cyclo_get_image_tag($field_value, $size = 'large', $classes = '') {
    if (empty($field_value)) return '';

    // Si la valeur est un ID, on utilise le générateur automatique de WordPress (gère le srcset pour mobile)
    if (is_numeric($field_value)) {
        $html = wp_get_attachment_image($field_value, $size, false, array('class' => $classes, 'alt' => ''));
        if (!empty($html)) return $html;
    }

    // Si ce n'est pas un ID, on crée une balise img classique avec l'URL de secours
    $url = cyclo_get_image_url($field_value, $size);
    if (!empty($url)) {
        return '<img src="' . esc_url($url) . '" class="' . esc_attr($classes) . '" alt="">';
    }
    return '';
}

// --- 7. SAUVEGARDE AUTOMATIQUE DES CHAMPS ACF (JSON) ---

/**
 * Configure un point de sauvegarde automatique pour ACF.
 * Dès que l'on enregistre un groupe de champs dans le tableau de bord,
 * un fichier JSON est créé ou mis à jour dans le dossier /acf-json/ de votre thème.
 */
add_filter('acf/settings/save_json', function( $path ) {
    // On indique le chemin vers notre dossier personnalisé
    $path = get_template_directory() . '/acf-json';
    return $path;
});

/**
 * Configure un point de lecture automatique pour ACF.
 * Si on change de serveur, WordPress lira ce dossier pour recréer
 * instantanément les formulaires dans l'administration.
 */
add_filter('acf/settings/load_json', function( $paths ) {
    // On retire le chemin par défaut pour éviter les conflits
    unset($paths[0]);
    // On ajoute notre dossier de thème
    $paths[] = get_template_directory() . '/acf-json';
    return $paths;
});