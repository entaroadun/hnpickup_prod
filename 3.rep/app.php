<?php

// =========================

require_once(__DIR__.'/../vendor/autoload.php');
use Silex\Application;
use Silex\Provider\TwigServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
putenv('GOOGLE_APPLICATION_CREDENTIALS=../0.conf/hnpickup_key.json');
require_once(__DIR__.'/libs/app_helpers.php');

// =========================

$APP = new Silex\Application();
$MD = new Parsedown();
$APP->register(new Silex\Provider\TwigServiceProvider());
$APP['md'] = $MD;
$APP['twig.path'] = [ __DIR__.'/templates' ];
$APP['hnposts'] = ( $_SERVER['SERVER_NAME'] === 'localhost' ? new \GDS\Store('HNPOSTS',new \GDS\Gateway\RESTv1('hnpickup')) : new \GDS\Store('HNPOSTS') );
$APP['summary'] = ( $_SERVER['SERVER_NAME'] === 'localhost' ? new \GDS\Store('HNPOSTS_SUMMARY',new \GDS\Gateway\RESTv1('hnpickup')) : new \GDS\Store('HNPOSTS_SUMMARY') );
$APP['etimes'] = ( $_SERVER['SERVER_NAME'] === 'localhost' ? new \GDS\Store('HNETIMES',new \GDS\Gateway\RESTv1('hnpickup')) : new \GDS\Store('HNETIMES') );

// =========================

$APP->get('/', function (Application $app, Request $request) {
  return($app->redirect('/report/intro'));
});

// =========================

$APP->get('/hnposts/{offset}/{page}.json', function ( $offset, $page, Application $app, Request $request ) {
  $memcache = new Memcached;
  // - - - - - - - - -
  if ( !($table_rows = $memcache->get("hnposts/$offset/$page")) ) {
    $hnposts = $app['hnposts'];
    $etimes = $app['etimes'];
    $table_rows = get_hnposts($etimes,$hnposts,$offset,$page);
    $memcache->set("hnposts/$offset/$page",$table_rows,time()+15*60);
  }
  // - - - - - - - - -
  return(json_encode($table_rows));
});

// =========================

$APP->get('/summary/{offset}/{limit}/{field}.json', function ( $offset, $limit, $field, Application $app, Request $request ) {
  $memcache = new Memcached;
  // - - - - - - - - -
  if ( !($table_rows = $memcache->get("summary/$offset/$limit/$field")) ) {
    $summary = $app['summary'];
    $etimes = $app['etimes'];
    $table_rows = get_summary($etimes,$summary,$offset,$limit,$field);
    $memcache->set("summary/$offset/$limit/$field",$table_rows,time()+15*60);
  }
  // - - - - - - - - -
  return(json_encode($table_rows));
});

// =========================

$APP->get('/report/three', function ( Application $app, Request $request ) {
  $params = $request->query->all();
  $params = hn_params_validation($params,['news_pickup_ratio'],['news','newest']);
  return($app['twig']->render('three.html',array_merge(['report_name'=>'Hacker News Comparisons'],$params)));
});

// =========================

$APP->get('/report/two', function ( Application $app, Request $request) {
  $params = $request->query->all();
  $params = hn_params_validation($params,['news_summary'],['news']);
  return($app['twig']->render('two.html',array_merge(['report_name'=>'Hacker Rewind Tables'],$params)));
});

// =========================

$APP->get('/report/one', function ( Application $app, Request $request) {
  $params = $request->query->all();
  $params = hn_params_validation($params,['news_summary','newest_summary'],[]);
  return($app['twig']->render('one.html',array_merge(['report_name'=>'Hacker Line Charts'],$params)));
});

// =========================

$APP->get('/report/intro', function ( Application $app, Request $request) {
  $memcache = new Memcached;
  // - - - - - - - - -
  if ( !($readme = $memcache->get('readme')) ) {
    $readme = file_get_contents('../README.md');
    $readme = $app['md']->text($readme);
    $memcache->set('readme',$readme);
  }
  return($app['twig']->render('intro.html',['report_name'=>'Introduction','readme'=>$readme]));
});

// =========================

$APP->error(function (Exception $e, Request $request, $code) use ($APP) {
  switch ( $code ) {
    case 404:
      $message = 'The requested page could not be found.';
      break;
    default:
      $message = 'We are sorry, but something went terribly wrong. '.$e;
  }
  return($APP['twig']->render('error.html',array('report_name'=>'Error','message'=>$message)));
});

// =========================

$APP['debug'] = true;
$APP->run();

?>
