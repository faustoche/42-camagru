<?php

## Chargement des 3 classes nécéssaires
require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Auth.php';

## Démarrage de la session
Session::start();


## On instancie le routeur
$router = new Router();

## Définition de la route 
$router->get('/', 'HomeController', 'index');
$router->get('/register', 'RegisterController', 'showForm');
$router->post('/register', 'RegisterController', 'processRegistration');
$router->post('/forgot-password', 'PasswordController', 'processNewPassword');
$router->get('/reset', 'PasswordController', 'showResetForm');
$router->post('/reset', 'PasswordController', 'processReset');
$router->get('/login', 'LoginController', 'showLoginForm');
$router->post('/login', 'LoginController', 'processLogin');
$router->get('/logout', 'LogoutController', 'processLogout');
$router->get('/studio', 'StudioController', 'showStudio');
$router->post('/studio/capture', 'StudioController', 'processCapture');
$router->post('/studio/delete', 'StudioController', 'deleteCapture');
$router->post('/studio/publish', 'StudioController', 'publishCapture');

$router->post('/home/details', 'HomeController', 'getImageDetails');


## Lancement du programme/site 
$router->resolve();