<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../core/Session.php';

/**
 * Login controller
 * display and processing of user authentication
 */
class LoginController {

    /**
     * Displays the login form view
     */
    public function showLoginForm(array $tab = []) {
        // Make sure only non-authenticated users can access the login page
        Auth::requireGuest();
        
        ob_start();
        require_once __DIR__ . '/../views/login.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../views/layout.php';
    }

    /**
     * Process the submitted login form data
     */
    public function processLogin() {

        Auth::requireGuest();

        // Validates the CSRF token to protect against attacks
        if (!isset($_POST['csrf_token']) || !Session::validateCsrfToken($_POST['csrf_token'])) {
            die("Erreur de sécurité CSRF : requête invalide.1");
        }
        $user = new Users();
        $errors = [];

        if (isset($_POST['password']) && isset($_POST['email'])) {
            $password = $_POST['password'];
            $email = $_POST['email'];

            // retrieve the user by their email address
            $userData = $user->getUserByEmail($email);

            if ($userData) {
                // Check if the user has validated their account via email link
                if (!$userData['confirmed']) {
                    $errors['not-confirmed'] = "Please confirm your email before logging in.";
                } 

                // Verify that the entered password matches the stored hash
                elseif (password_verify($password, $userData['password'])) {
                    // Aif sucessecfull store user ID in session and redirect to home
                    Session::set('user_id', $userData['id']);
                    header('Location: /');
                    exit();
                } else {
                    $errors['invalid-password'] = "Password is invalid";
                }
            } else {
                $errors['invalid-email'] = "Email is invalid";
            }
        }
        // If errors, reload the form and pass the error array to the view
        $this->showLoginForm($errors);
    }
}