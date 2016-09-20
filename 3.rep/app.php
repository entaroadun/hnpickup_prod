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
  return($app->redirect('/report/dashboard'));
});

$APP->get('/report/dashboard', function (Application $app, Request $request) {
  return($app['twig']->render('dashboard.html',array('report_name'=>'Full Dashboard')));
});

$APP->get('/report/news', function (Application $app, Request $request) {
  return($app['twig']->render('news.html',array('report_name'=>'Hacker News Page Rewind')));
});

$APP->get('/report/newest', function (Application $app, Request $request) {
  return($app['twig']->render('newest.html',array('report_name'=>'Hacker Newest Page Rewind')));
});

$APP->get('/report/pickup', function (Application $app, Request $request) {
  return($app['twig']->render('pickup.html',array('report_name'=>'Hacker News Pickup Ratio')));
});

$APP->error(function (Exception $e, Request $request, $code) use ($APP) {
  switch ( $code ) {
    case 404:
      $message = 'The requested page could not be found.';
      break;
    default:
      $message = 'We are sorry, but something went terribly wrong.';
  }
  return($APP['twig']->render('error.html',array('report_name'=>'Error','message'=>$message)));
});

// =========================

$APP['debug'] = true;
$APP->run();

?>
