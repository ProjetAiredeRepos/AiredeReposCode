<?php
/**
 * Template Name: Cyclo-Data Login
 */

// Si l'utilisateur est déjà connecté, on le redirige vers le dashboard
if ( is_user_logged_in() ) {
    wp_redirect( home_url('/dashboard/') );
    exit;
}

// Gestion de la soumission du formulaire
$login_error = '';
if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['cyclo_login_nonce'] ) ) {
    // 1. Protection CSRF (Cross-Site Request Forgery)
    if ( wp_verify_nonce( $_POST['cyclo_login_nonce'], 'cyclo_login_action' ) ) {

        // 2. Nettoyage et Validation (OWASP)
        $raw_email = isset($_POST['log']) ? trim($_POST['log']) : '';
        $password = isset($_POST['pwd']) ? $_POST['pwd'] : '';

        // REGEX stricte pour la validation de l'email
        $email_pattern = '/^[a-zA-Z0-9._%+-]+@[a-z-A-Z0-9.-]+\.[a-zA-Z]{2,}$/';

        if (empty($raw_email) || empty($password)) {
            $login_error = "Veuillez remplir tous les champs.";
        } elseif (!preg_match($email_pattern, $raw_email)) {
            $login_error = "Le format de l'adresse e-mail est invalide.";
        } else {
            // L'email est validé par le Regex, on le sécurise pour WordPress
            $safe_email = sanitize_email($raw_email);

            $creds = array(
                    'user_login' => $safe_email,
                'user_password' => $password,
                'remember' => isset($_POST['rememberme'])
            );

            // 3. Authentification
            $user = wp_signon($creds, false);

            if (is_wp_error($user)) {
                // Délai artificiel pour ralentir les attaques par force brute (Timing Attack Mitigation)
                sleep(1);
                // Message d'erreur générique pour ne pas révéler si l'email existe ou non (OWASP)
                $login_error = "Identifiants incorrects. Veuillez réesayer.";
            } else {
                wp_safe_redirect(home_url('/dashboard/'));
                exit;
            }
        }
    } else {
        $login_error = "Session expirée. Veuillez recharger la page.";
    }
}

get_header('dashboard');  // On utilise un header allégé
?>

<div class="cyclo-login-layout">
    <div class="login-cover-image" style="background-image: url('<?php echo get_template_directory_uri(); ?>/assets/images/loire-cover.jpg');">
        <div class="cover-overlay">
            <div class="cover-brand">
                <span class="brand-tag">Loir-et-Cher Cyclotourisme</span>
                <h1>Cyclo-Data 41</h1>
                <p>Plateforme d'administration des statistiques d'usage des itinéraires cyclables du département.</p>
            </div>
        </div>
    </div>

    <div class="login-form-container">
        <div class="login-form-inner">
            <div class="login-header">
                <h2>Heureux de vous retrouver !</h2>
                <p>Connectez-vous pour accéder à votre espace.</p>
            </div>

            <?php if ( $login_error ) : ?>
                <div class="login-alert error"><?php echo esc_html( wp_strip_all_tags( $login_error ) ); ?></div>
            <?php endif; ?>

            <form method="post" action="" class="cyclo-form">
                <?php wp_nonce_field( 'cyclo_login_action', 'cyclo_login_nonce' ); ?>

                <div class="form-group">
                    <label for="user_login">Adresse e-mail</label>
                    <input type="email" name="log" id="user_login" required autocomplete="email" placeholder="admin@example.com">
                </div>

                <div class="form-group">
                    <label for="user_pass">Mot de passe</label>
                    <input type="password" name="pwd" id="user_pass" required autocomplete="current-password" placeholder="Votre mot de passe">
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary-login">Connexion</button>
                    <a href="<?php echo wp_lostpassword_url(); ?>" class="link-forgot">Mot de passe oublié ?</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php get_footer('dashboard'); ?>
