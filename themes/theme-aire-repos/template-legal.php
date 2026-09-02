<?php
/*
Template Name: Page Légale (Mentions & Confidentialité)
*/
get_header();

?>

<main class="legal-page-wrapper">
    <div class="legal-container">

        <div class="legal-back">
            <a  href="/" class="btn-retour" id="back-button">
                &larr; Retour au site
            </a>

        </div>

        <h1 class="legal-title">
            <?php echo esc_html( get_field('titre_de_la_page')); ?>
        </h1>

        <div class="legal-content">
            <?php echo wp_kses_post( get_field('contenu_legal')); ?>
        </div>

    </div>
</main>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const btnRetour = document.getElementById('back-button');

        const lastAire = localStorage.getItem('cyclo_last_aire');

        if (btnRetour && lastAire) {
            btnRetour.href = lastAire;
        }
    });
</script>

<?php
get_footer();
?>
