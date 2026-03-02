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

## Lancement du programme/site 
$router->resolve();