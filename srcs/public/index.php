<?php

/**
 * Application entry point and front controller
 * All users requests arrive here
 */

## Calling basic classes
require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Auth.php';

## Starting session and initializing router
Session::start();
$router = new Router();

/**
 * ROUTE DEFINITION (mapping URL -> controller -> method)
 * Router associate URL (/login) with HTTP method (get/post) to a specific controller
 */

// Public routes: gallery, auth, password
$router->get('/', 'HomeController', 'index');
$router->get('/login', 'LoginController', 'showLoginForm');
$router->post('/login', 'LoginController', 'processLogin');
$router->get('/register', 'RegisterController', 'showForm');
$router->post('/register', 'RegisterController', 'processRegistration');
$router->post('/forgot-password', 'PasswordController', 'processNewPassword');
$router->get('/reset', 'PasswordController', 'showResetForm');
$router->post('/reset', 'PasswordController', 'processReset');
$router->get('/logout', 'LogoutController', 'processLogout');
$router->get('/404', 'NotFoundController', 'processNotFound');

// Account verification
$router->get('/confirm', 'RegisterController', 'confirmAccount');
$router->get('/verify-notice', 'RegisterController', 'showVerifyNotice');
$router->post('/resend-verification', 'RegisterController', 'resendVerificationEmail');

// Studio routes
$router->get('/studio', 'StudioController', 'showStudio');
$router->post('/studio/capture', 'StudioController', 'processCapture');
$router->post('/studio/delete', 'StudioController', 'deleteCapture');
$router->post('/studio/publish', 'StudioController', 'publishCapture');
$router->post('/studio/unpublish', 'StudioController', 'unpublishCapture');

// Interaction routes: ajax calls from the gallery
$router->post('/home/details', 'HomeController', 'getImageDetails');
$router->post('/home/toggle-like', 'HomeController', 'toggleLike');
$router->post('/home/add-comment', 'HomeController', 'addComment');
$router->post('/home/load-more', 'HomeController', 'loadImageGallery');

// Verification notice and resend routes
$router->get('/profile', 'ProfileController', 'showProfile');
$router->post('/profile', 'ProfileController', 'updateProfile');

// Program execution
// Resolve() parse the URI and HTTP method, and do the dispatch
$router->resolve();