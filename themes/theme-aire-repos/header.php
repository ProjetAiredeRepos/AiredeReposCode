<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
          content="Découvrez l'aire de repos immersive de <?php the_title(); ?>. Explorez la richesse de notre milieu naturel et laissez-vous surprendre par nos activités locales.">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<header class="site-header">
    <div class="site-branding">
        <?php
        // On récupère l'ID du logo uploadé dans l'administration
        $custom_logo_id = get_theme_mod('custom_logo');

        if ($custom_logo_id) {
            // On récupère l'URL de l'image du logo
            $logo_url = wp_get_attachment_image_url($custom_logo_id, 'full');

            echo '<img src="' . esc_url($logo_url) . '" alt="Logo ' . get_bloginfo('name') . '">';
            echo '</a>';

        } else {
            // Fallback texte s'il n'y a pas encore de logo uploadé
            echo '<a href="#accueil" class="site-title js-smooth-scroll">' . get_bloginfo('name') . '</a>';
        }
        ?>
    </div>
</header>