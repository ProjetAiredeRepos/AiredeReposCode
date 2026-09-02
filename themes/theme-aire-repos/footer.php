<footer class="site-footer">
    <div class="footer-container">

        <div class="footer-top-row">

            <!-- CONTENEUR FLEXBOX DES LOGOS -->
            <div class="footer-partners-wrapper">

                <!-- Bloc 1 : Initiative -->
                <div class="initiative-block">
                    <h2 class="title-footer">Une initiative de :</h2>
                    <div class="footer-logo">
                        <?php
                        $footer_logo_url  = get_theme_mod( 'footer_logo' );
                        $main_logo_id     = get_theme_mod( 'custom_logo' );

                        // FIX CTO : On récupère le lien dynamique, ou on met #accueil par défaut
                        $footer_link_raw  = get_theme_mod( 'footer_logo_link' );
                        $footer_link_safe = $footer_link_raw ? esc_url( $footer_link_raw ) : '#accueil';

                        // Gestion de l'attribut target et rel selon si c'est un lien externe ou une ancre interne
                        $is_external = ($footer_link_raw) ? 'target="_blank" rel="noopener noreferrer"' : 'class="js-smooth-scroll" aria-label="Remonter en haut"';

                        if ( $footer_logo_url ) {
                            echo '<a href="' . $footer_link_safe . '" ' . $is_external . '>';
                            echo '<img src="' . esc_url( $footer_logo_url ) . '" alt="Initiative Loir-et-Cher">';
                            echo '</a>';
                        } elseif ( $main_logo_id ) {
                            $main_logo_url = wp_get_attachment_image_url( $main_logo_id, 'full' );
                            echo '<a href="' . $footer_link_safe . '" ' . $is_external . '>';
                            echo '<img src="' . esc_url( $main_logo_url ) . '" alt="Initiative">';
                            echo '</a>';
                        }
                        ?>
                    </div>
                </div>

                <!-- Bloc 2 : Partenaire (Dynamique) -->
                <?php
                $partner_logo_url = get_theme_mod( 'partner_logo' );
                $partner_link_url = get_theme_mod( 'partner_logo_link' ); // Nouveau lien

                if ( $partner_logo_url ) :
                    ?>
                    <div class="initiative-block">
                        <h2 class="title-footer">En partenariat avec :</h2>
                        <div class="footer-logo">
                            <?php if ( $partner_link_url ) : ?>
                                <!-- Lien externe sécurisé -->
                                <a href="<?php echo esc_url( $partner_link_url ); ?>" target="_blank" rel="noopener noreferrer">
                                    <img src="<?php echo esc_url( $partner_logo_url ); ?>" alt="Partenaire">
                                </a>
                            <?php else : ?>
                                <!-- S'il n'y a pas de lien renseigné, on affiche juste l'image -->
                                <img src="<?php echo esc_url( $partner_logo_url ); ?>" alt="Partenaire">
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>

            <!-- NAVIGATION FOOTER -->
            <nav class="footer-nav">
                <a href="<?php echo esc_url(site_url('/mentions-legales/')); ?>" class="footer-link">Mentions Légales</a>
            </nav>

        </div>

        <div class="footer-bottom-row">
            <p class="footer-copyright">
                &copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. Tous droits réservés.
            </p>
        </div>

    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
