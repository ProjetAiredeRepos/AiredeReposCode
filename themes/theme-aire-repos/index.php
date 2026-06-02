<?php
/**
 * Fichier de secours obligatoire pour WordPress.
 * Le design principal de notre application se trouve dans front-page.php.
 */

get_header(); ?>

    <main>
        <?php
        while ( have_posts() ) : the_post();
            // C'est ici que s'affichera le contenu de l'éditeur classique si besoin
            the_content();
        endwhile;
        ?>
    </main>

<?php get_footer(); ?>
