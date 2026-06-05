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
get_header('dashboard');
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="cyclo-app-layout" data-nonce="<?php echo esc_attr($api_nonce); ?>">

    <aside class="cyclo-sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">🚲</div>
            <div>
                <strong>Cyclo-Data 41</strong>
                <span>Administration</span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section">
                <span class="nav-title">OUTILS DE DÉCOUVERTE</span>
                <button class="nav-item active" data-view="discovery-overview">
                    <span class="icon">🧭</span> Tout voir
                </button>
            </div>

            <div class="nav-section">
                <span class="nav-title">QUESTIONNAIRES</span>
                <button class="nav-item" data-view="survey-overview">
                    <span class="icon">📊</span> Tout voir
                </button>
            </div>
        </nav>

        <div class="sidebar-footer">
            <button class="nav-item" data-view="settings">⚙️ Paramètres</button>
            <div class="user-profile">
                <div class="avatar"><?php echo esc_html(strtoupper(substr($user->user_firstname, 0, 1) . substr($user->user_lastname, 0, 1))); ?></div>
                <div class="user-info">
                    <strong><?php echo esc_html($user->display_name); ?></strong>
                    <span><?php echo esc_html($user->user_email); ?></span>
                </div>
                <a href="<?php echo wp_logout_url( home_url('/connexion/') ); ?>" class="logout-btn" title="Se déconnecter">🚪</a>
            </div>
        </div>
    </aside>

    <main class="cyclo-main-content">
        <header class="content-header">
            <div class="breadcrumbs">Administration > Outils de découverte > <strong>Vue d'ensemble</strong></div>
            <div class="header-actions">
                <div class="date-filters">
                    <button class="filter-btn">Jour</button>
                    <button class="filter-btn">Semaine</button>
                    <button class="filter-btn">Mois</button>
                    <button class="filter-btn active">Année</button>
                </div>
            </div>
        </header>

        <div id="dashboard-root" class="dashboard-grid">
            <div class="loading-state">Chargement des données...</div>
        </div>
    </main>

</div>

<script src="<?php echo get_template_directory_uri(); ?>/scripts/dashboard-app.js"></script>

<?php get_footer('dashboard'); ?>
