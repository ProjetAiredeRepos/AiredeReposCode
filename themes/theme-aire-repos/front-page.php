<?php
// Modèle de la page d'accueil (Mobile First)
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
    <button class="burger-btn" id="openMenuBtn">☰</button>

    <div class="menu-overlay" id="mainMenu">
        <button class="modal-close-btn" id="closeMenuBtn">✕</button>
        <h2 style="color: white; margin-bottom: 30px;">MENU</h2>
        <div style="color: white;">
            <p>Un fleuve sauvage protégé</p>
            <p>Un site marqué par l'histoire</p>
            <p>La Loire à Vélo</p>
        </div>
    </div>

    <section class="hero-section">
        <div class="hero-content">
            <h1>Bienvenue à Chaumont-sur-Loire</h1>
            <p>Prenez le temps de découvrir ce qui vous entoure</p>
        </div>
    </section>

    <section class="map-section">
        <h2>Découverte du milieu</h2>
        <p>Touchez un point sur la carte pour explorer.</p>
        <div class="map-container">
            <div class="map-pin pin-nature"></div>
            <div class="map-pin pin-histoire"></div>
        </div>
    </section>

    <section class="slider-section">
        <h2 style="padding: 0 16px; color: #333;">Laissez-vous surprendre...</h2>
        <div class="cards-slider">

            <div class="slider-card" data-theme="nature">
                <span class="badge">À découvrir</span>
                <h3>Un site marqué par l'histoire</h3>
                <p>Histoire, du bac médieval au pont...</p>
                <button class="btn-savoir-plus" data-target="modal-1">En savoir plus ↗</button>
            </div>

            <div class="slider-card" data-theme="histoire">
                <span class="badge">À découvrir</span>
                <h3>Un site marqué par l'histoire</h3>
                <p>Histoire, du bac médiéval au pont...</p>
                <button class="btn-savoir-plus" data-target="modal-2">En savoir plus ↗</button>
            </div>

        </div>
    </section>

    <div class="modal-details" id="modal-1">
        <div class="modal-header-image">
            <button class="modal-close-btn close-modal-btn">✕</button>
        </div>
        <div class="modal-content">
            <span class="badge" style="background: var(--theme-nature); color: white; padding: 4px 8px; border-radius: 20px; font-size: 12px;">À DÉCOUVRIR</span>
            <h2 style="margin-top: 10px;">Un fleuve sauvage protégé</h2>
            <p style="margin-top: 20px;">La Loire est le dernier fleuve sauvage d'Europe. En Loir-et-Cher, le Val de Loire est inscrit au Patrimoine Mondial de l'UNESCO...</p>
        </div>
    </div>

    <?php wp_footer(); ?>
</body>
</html>