<?php
/**
 * Template Name: Cyclo-Data Login
 */

$login_error = '';
$require_password_change = false;

// 1. Vérification si l'utilisateur est déjà connecté
if ( is_user_logged_in() ) {
    // A-t-il l'obligation de changer son mot de passe ?
    $force_change = get_user_meta(get_current_user_id(), '_cyclo_force_pwd_change', true);
    if ( $force_change === '1' ) {
        $require_password_change = true;
    } else {
        wp_redirect( home_url('/dashboard/') );
        exit;
    }
}

// 2. Gestion de la soumission des formulaires
if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {

    // CAS A : Soumission du NOUVEAU mot de passe (Changement forcé)
    if ( isset($_POST['cyclo_new_password_nonce']) && wp_verify_nonce($_POST['cyclo_new_password_nonce'], 'cyclo_new_pwd_action') ) {
        $user_id = get_current_user_id();
        $new_pwd = $_POST['new_pwd'];
        $confirm_pwd = $_POST['confirm_pwd'];

        if ( $new_pwd !== $confirm_pwd ) {
            $login_error = "Les mots de passe ne correspondent pas.";
            $require_password_change = true; // On maintient le formulaire affiché
        } elseif ( strlen($new_pwd) < 8 ) {
            $login_error = "Le mot de passe doit contenir au moins 8 caractères.";
            $require_password_change = true;
        } else {
            // Modification du mot de passe
            wp_set_password( $new_pwd, $user_id );
            // On supprime le marqueur de changement obligatoire
            delete_user_meta( $user_id, '_cyclo_force_pwd_change' );

            // On reconnecte l'utilisateur sans qu'il ait besoin de se reloguer
            wp_clear_auth_cookie();
            wp_set_current_user( $user_id );
            wp_set_auth_cookie( $user_id );

            wp_safe_redirect(home_url('/dashboard/'));
            exit;
        }
    }

    // CAS B : Connexion classique
    elseif ( isset( $_POST['cyclo_login_nonce'] ) && wp_verify_nonce( $_POST['cyclo_login_nonce'], 'cyclo_login_action' ) ) {
        $raw_email = isset($_POST['log']) ? trim($_POST['log']) : '';
        $password = isset($_POST['pwd']) ? $_POST['pwd'] : '';

        // Validation OWASP
        $email_pattern = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';

        if (empty($raw_email) || empty($password)) {
            $login_error = "Veuillez remplir tous les champs.";
        } elseif (!preg_match($email_pattern, $raw_email)) {
            $login_error = "Le format de l'adresse e-mail est invalide.";
        } else {
            $creds = array(
                    'user_login'    => sanitize_email($raw_email),
                    'user_password' => $password,
                    'remember'      => isset($_POST['rememberme'])
            );

            // Authentification
            $user = wp_signon($creds, false);

            if (is_wp_error($user)) {
                sleep(1); // Brute Force Mitigation
                $login_error = "Identifiants incorrects. Veuillez réessayer.";
            } else {
                // Succès de connexion ! On vérifie s'il doit changer son mot de passe
                if ( get_user_meta($user->ID, '_cyclo_force_pwd_change', true) === '1' ) {
                    $require_password_change = true; // Déclenche l'affichage du second formulaire
                } else {
                    wp_safe_redirect(home_url('/dashboard/'));
                    exit;
                }
            }
        }
    } else {
        $login_error = "Session expirée. Veuillez recharger la page.";
    }
}

$site_name = get_bloginfo('name') ?: 'Cyclo-Data 41';
$site_desc = get_bloginfo('description') ?: "Plateforme d'administration des statistiques d'usage.";
$bg_image_url = get_the_post_thumbnail_url(get_the_ID(), 'full') ?: get_template_directory_uri() . '/assets/images/loire-cover.jpg';

get_header('dashboard');
?>

<div class="cyclo-login-layout">
    <div class="login-cover-image" style="background-image: url('<?php echo esc_url($bg_image_url); ?>');">
        <div class="cover-overlay">
            <div class="cover-brand">
                <span class="brand-tag">Administration Sécurisée</span>
                <h1><?php echo esc_html($site_name); ?></h1>
                <p><?php echo esc_html($site_desc); ?></p>
            </div>
        </div>
    </div>

    <div class="login-form-container">
        <div class="login-form-inner">

            <?php if ( $login_error ) : ?>
                <div class="login-alert error"><?php echo esc_html( $login_error ); ?></div>
            <?php endif; ?>

            <?php if ( $require_password_change ) : ?>
                <div class="login-header">
                    <h2>Sécurisation de votre compte</h2>
                    <p>Pour des raisons de sécurité, vous devez définir un nouveau mot de passe personnel avant d'accéder au tableau de bord.</p>
                </div>

                <form method="post" action="" class="cyclo-form">
                    <?php wp_nonce_field( 'cyclo_new_pwd_action', 'cyclo_new_password_nonce' ); ?>

                    <div class="form-group">
                        <label for="new_pwd">Nouveau mot de passe</label>
                        <div style="position: relative; display: flex; align-items: center;">
                            <input type="password" name="new_pwd" id="new_pwd" required placeholder="8 caractères minimum" style="width: 100%; padding-right: 40px;">
                            <button type="button" class="toggle-password-btn" aria-label="Afficher le mot de passe" style="position: absolute; right: 10px; background: none; border: none; cursor: pointer; color: #718096; display: flex; padding: 4px;">
                                <svg class="icon-eye" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_pwd">Confirmer le mot de passe</label>
                        <div style="position: relative; display: flex; align-items: center;">
                            <input type="password" name="confirm_pwd" id="confirm_pwd" required placeholder="Confirmez votre saisie" style="width: 100%; padding-right: 40px;">
                            <button type="button" class="toggle-password-btn" aria-label="Afficher le mot de passe" style="position: absolute; right: 10px; background: none; border: none; cursor: pointer; color: #718096; display: flex; padding: 4px;">
                                <svg class="icon-eye" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </button>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary-login">Mettre à jour et accéder</button>
                    </div>
                </form>

            <?php else : ?>
                <div class="login-header">
                    <h2>Heureux de vous retrouver !</h2>
                    <p>Connectez-vous pour accéder à votre espace.</p>
                </div>

                <form method="post" action="" class="cyclo-form">
                    <?php wp_nonce_field( 'cyclo_login_action', 'cyclo_login_nonce' ); ?>

                    <div class="form-group">
                        <label for="user_login">Adresse e-mail</label>
                        <input type="email" name="log" id="user_login" required autocomplete="email" placeholder="admin@example.com">
                    </div>

                    <div class="form-group">
                        <label for="user_pass">Mot de passe</label>
                        <div style="position: relative; display: flex; align-items: center;">
                            <input type="password" name="pwd" id="user_pass" required autocomplete="current-password" placeholder="Votre mot de passe" style="width: 100%; padding-right: 40px;">
                            <button type="button" class="toggle-password-btn" aria-label="Afficher le mot de passe" style="position: absolute; right: 10px; background: none; border: none; cursor: pointer; color: #718096; display: flex; padding: 4px;">
                                <svg class="icon-eye" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </button>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary-login">Connexion</button>
                        <a href="<?php echo esc_url(wp_lostpassword_url()); ?>" class="link-forgot">Mot de passe oublié ?</a>
                    </div>
                </form>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- Script pour écouter le clic sur les boutons pour pouvoir voir les mots de passe -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleButtons = document.querySelectorAll('.toggle-password-btn');

        // Les deux icônes SVG en chaîne de caractères
        const iconEye = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
        const iconEyeOff = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>';

        toggleButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Cible l'input qui se trouve juste avant le bouton dans le DOM
                const input = this.previousElementSibling;

                if (input.getAttribute('type') === 'password') {
                    input.setAttribute('type', 'text');
                    this.innerHTML = iconEyeOff;
                    this.style.color = '#1A9E66'; // Couleur primaire quand activé
                } else {
                    input.setAttribute('type', 'password');
                    this.innerHTML = iconEye;
                    this.style.color = '#718096'; // Couleur neutre par défaut
                }
            });
        });
    });
</script>

<?php get_footer('dashboard'); ?>
