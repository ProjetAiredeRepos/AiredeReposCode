<?php
/**
 * Template Name: Cyclo-Data Dashboard
 */

// Sécurité absolue : Redirection si non connecté
if ( !is_user_logged_in() ) {
    wp_redirect( home_url('connexion'));
    exit;
}

$user = wp_get_current_user();
$api_nonce = wp_create_nonce( 'wp_rest' ); // Nonce pour sécuriser les appels fetch()
// 1. Récupération dynamique des itinéraires (taxonomie)
$itineraires = get_terms( array(
        'taxonomy'   => 'itineraire',
        'hide_empty' => false, // On affiche même ceux qui n'ont pas encore d'aires
) );

// Fonction Helper pour générer l'arbre des menus dynamiquement
function render_dynamic_sidebar_menu($itineraires, $module_name) {
    if ( empty($itineraires) || is_wp_error($itineraires) ) return;

    foreach ($itineraires as $itineraire) {
        $term_id = $itineraire->term_id;
        $unique_target = 'sub-' . $module_name . '-' . $term_id;

        // Requête sécurisée pour récupérer les aires liées à cet itinéraire
        $aires = get_posts(array(
                'post_type'      => 'aire_repos',
                'numberposts'    => -1, // On les prend toutes
                'tax_query'      => array(
                        array(
                                'taxonomy' => 'itineraire',
                                'field'    => 'term_id',
                                'terms'    => $term_id
                        )
                )
        ));

        // Affichage du bouton Itinéraire parent
        echo '<div class="nav-group">';
        // Bouton Itinéraire (Ajout de data-name)
        echo '<button class="nav-item has-submenu" data-action="route-and-toggle" data-module="' . esc_attr($module_name) . '" data-level="route" data-id="' . esc_attr($term_id) . '" data-name="' . esc_attr($itineraire->name) . '" data-target="' . esc_attr($unique_target) . '">';
        echo '<svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s-8-4.5-8-11.8A8 8 0 0 1 12 2a8 8 0 0 1 8 8.2c0 7.3-8 11.8-8 11.8z"></path><circle cx="12" cy="10" r="3"></circle></svg>';
        echo esc_html($itineraire->name);
        if (!empty($aires)) {
            echo '<svg class="chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-left:auto;"><polyline points="6 9 12 15 18 9"></polyline></svg>';
        }
        echo '</button>';

        // S'il y a des aires, on crée le sous-menu
        if (!empty($aires)) {
            echo '<div class="submenu" id="' . esc_attr($unique_target) . '" style="display: none; padding-left: 1.5rem;">';
            foreach ($aires as $aire) {
                // Bouton Aire de repos (Ajout de data-name)
                echo '<button class="nav-item sub-item" data-action="route" data-module="' . esc_attr($module_name) . '" data-level="aire" data-id="' . esc_attr($aire->ID) . '" data-name="' . esc_attr($aire->post_title) . '">';
                echo '<span class="dot"></span> ' . esc_html($aire->post_title);
                echo '</button>';
            }
            echo '</div>';
        }
        echo '</div>';
    }
}

get_header('dashboard');
?>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <div class="cyclo-app-layout" data-nonce="<?php echo esc_attr($api_nonce); ?>">

        <aside class="cyclo-sidebar">
            <div class="sidebar-brand">
                <div class="brand-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="5.5" cy="17.5" r="3.5"></circle><circle cx="18.5" cy="17.5" r="3.5"></circle><path d="M15 6a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm-3 11.5V14l-3-3 4-3 2 3h2"></path></svg>
                </div>
                <div>
                    <strong><?php echo esc_html(get_bloginfo('name') ?: 'Cyclo-Data 41'); ?></strong>
                    <span>Administration</span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <span class="nav-title">OUTILS DE DÉCOUVERTE</span>

                    <button class="nav-item active" data-action="route" data-module="discovery" data-level="global" data-id="all">
                        <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                        Tout voir
                    </button>

                    <?php render_dynamic_sidebar_menu($itineraires, 'discovery'); ?>

                </div>

                <div class="nav-section" style="margin-top: 2rem;">
                    <span class="nav-title">QUESTIONNAIRES</span>
                    <button class="nav-item" data-action="route" data-module="survey" data-level="global" data-id="all">
                        <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        Tout voir
                    </button>

                    <?php render_dynamic_sidebar_menu($itineraires, 'survey'); ?>
                </div>

                <div class="nav-section" style="margin-top: 2rem;">
                    <span class="nav-title">UTILISATEURS</span>
                    <button class="nav-item" data-view="users-list">
                        <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        Liste des utilisateurs
                    </button>
                    <button class="nav-item" data-view="users-permissions">
                        <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        Gestion des permissions
                    </button>
                </div>
            </nav>

            <div class="sidebar-footer">
                <button class="nav-item" data-view="settings">
                    <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                    Paramètres
                </button>
                <div class="user-profile">
                    <div class="avatar"><?php echo esc_html(strtoupper(substr($user->user_firstname, 0, 1) . substr($user->user_lastname, 0, 1))); ?></div>
                    <div class="user-info">
                        <strong><?php echo esc_html($user->display_name); ?></strong>
                        <span><?php echo esc_html($user->user_email); ?></span>
                    </div>
                    <a href="<?php echo esc_url(wp_logout_url( home_url('/connexion/') )); ?>" class="logout-btn" aria-label="Se déconnecter">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                    </a>
                </div>
            </div>
        </aside>

        <main class="cyclo-main-content">
            <header class="content-header">
                <!-- NOUVELLE STRUCTURE : Flex-direction Column pour forcer le titre en dessous -->
                <div class="header-titles">
                    <div class="breadcrumbs-container">
                        <button id="mobile-menu-btn" class="mobile-nav-toggle" aria-label="Ouvrir le menu">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                        </button>
                        <div class="breadcrumbs">Administration <span class="sep">></span> Chargement...</div>
                    </div>
                    <!-- Le titre est maintenant en dur, le JS changera juste son texte -->
                    <h1 id="dash-main-title" class="dash-main-title">Chargement...</h1>
                </div>

                <div class="header-actions">
                    <div class="date-filters">
                        <button class="filter-btn">Jour</button>
                        <button class="filter-btn">Semaine</button>
                        <button class="filter-btn">Mois</button>
                        <button class="filter-btn active">Année</button>
                    </div>

                    <button class="btn-action outline" id="export-csv-btn" title="Télécharger les données brutes pour Excel">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        Données CSV
                    </button>

                    <button class="btn-action outline" id="export-pdf-btn" title="Télécharger le rapport graphique">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        Rapport PDF
                    </button>
                </div>
            </header>

            <!-- L'overlay sombre pour fermer le menu sur mobile (UX propre) -->
            <div id="sidebar-overlay" class="sidebar-overlay"></div>

            <div id="dashboard-root" class="dashboard-grid">
            </div>
        </main>

    </div>

    <script src="<?php echo get_template_directory_uri(); ?>/scripts/dashboard-app.js"></script>

<?php get_footer('dashboard'); ?>