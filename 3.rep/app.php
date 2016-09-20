<?php

// =========================

require_once(__DIR__.'/../vendor/autoload.php');
use Silex\Application;
use Silex\Provider\TwigServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// =========================

// create the Silex application
$APP = new Silex\Application();
$APP->register(new Silex\Provider\TwigServiceProvider());
$APP['twig.path'] = [ __DIR__.'/templates' ];

// =========================

$APP->get('/', function (Application $app, Request $request) {
  return("Main page");
});

$APP->get('/{name}', function ($name, Application $app, Request $request) {
  return("page:".$name);
});

// =========================

$APP['debug'] = true;
$APP->run();

?>
