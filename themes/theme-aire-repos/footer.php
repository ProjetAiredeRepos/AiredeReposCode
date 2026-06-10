<?php wp_footer(); ?>

<footer class="site-footer">
    <div class="footer-container">

        <div class="footer-top-row">
            <div class="footer-logo">
                <?php
                // On récupère le logo du footer ET le logo principal
                $footer_logo_url = get_theme_mod( 'footer_logo' );
                $main_logo_id    = get_theme_mod( 'custom_logo' );

                if ( $footer_logo_url ) {
                    // CAS 1 : L'utilisateur a mis un logo spécifique pour le footer
                    echo '<a href="#accueil" class="js-smooth-scroll" aria-label="Remonter en haut de la page">';
                    echo '<img src="' . esc_url( $footer_logo_url ) . '" alt="Logo Footer ' . get_bloginfo( 'name' ) . '">';
                    echo '</a>';

                } elseif ( $main_logo_id ) {
                    // CAS 2 : Pas de logo footer, on met le logo du header à la place
                    $main_logo_url = wp_get_attachment_image_url( $main_logo_id, 'full' );
                    echo '<a href="#accueil" class="js-smooth-scroll" aria-label="Remonter en haut de la page">';
                    echo '<img src="' . esc_url( $main_logo_url ) . '" alt="Logo ' . get_bloginfo( 'name' ) . '">';
                    echo '</a>';

                } else {
                    // CAS 3 : Aucun logo n'est envoyé, on affiche le texte
                    echo '<a href="#accueil" class="site-title js-smooth-scroll">' . get_bloginfo( 'name' ) . '</a>';
                }
                ?>
            </div>

            <nav class="footer-nav">
                <a href="<?php echo esc_url(site_url('/mentions-legales/')); ?>" class="footer-link">Mentions Légales</a>
                <a href="<?php echo esc_url(site_url('/politique-de-confidentialite/')); ?>" class="footer-link">Confidentialité</a>
            </nav>
        </div>

        <div class="footer-bottom-row">
            <p class="footer-copyright">
                &copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. Tous droits réservés.
            </p>
        </div>

    </div>
</footer>

<?php
wp_footer();
?>

</body>
</html>

