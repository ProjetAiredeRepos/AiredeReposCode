<?php get_header(); ?>

<main class="site-dynamique">

    <?php
    //On récupère l'URL de l'image stockée dans ACF
    $image_accueil = get_field('image_fond_accueil');
    ?>

    <section id="accueil" class="hero-section" style="background-image: url('<?php echo esc_url($image_accueil); ?>');">
        <div class="overlay-sombre"></div>

        <button class="menu-burger" aria-label="Ouvrir le menu">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
        </button>

        <div class="hero-content">
            <h1>Bienvenue à<br><?php the_title(); ?></h1>
            <p>Prenez le temps de découvrir ce qui vous entoure</p>
        </div>

        <div class="hero-bottom">
            <span class="decouvrir-text">DÉCOUVRIR</span>
            <a href="#carte" class="btn-scroll">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </a>
        </div>
    </section>

</main>

<?php get_footer(); ?>