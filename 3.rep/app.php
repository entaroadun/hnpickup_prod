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
$APP['hnposts'] = new GDS\Store('HNPOSTS');
$APP['hnposts_summary'] = new GDS\Store('HNPOSTS_SUMMARY');
date_default_timezone_set('America/Los_Angeles');

// =========================

$APP->get('/', function (Application $app, Request $request) {
  return($app->redirect('/report/dashboard/news/1'));
});

$APP->get('/report/dashboard/{page}/{number}', function ($page, $number, Application $app, Request $request) {
  $memcache = new Memcached;
  $number = 1*$number;
  if ( !$memcache->get('hnposts'.$number.$page) ) {
    $table_rows = array();
    $table_name = '';
    $table_next = '';
    $table_prev = '';
    $hnposts = $app['hnposts'];
    $hnposts->query('SELECT * FROM HNPOSTS ORDER BY etime DESC LIMIT @end OFFSET @start',['end'=>60,'start'=>(($number-1)*60)]);
    $rows = $hnposts->fetchAll();
    usort($rows,function($a,$b){return(($a->rank<$b->rank)?-1:1);});
    foreach ( $rows as $row ) {
      if ( $row->page == $page ) {
        $table_rows[] = ['rank'=>$row->rank,'title'=>$row->title,'url'=>$row->url,'points'=>$row->points,'user'=>$row->user,'compare'=>$row->compare];
        if ( strcmp($table_name,'') === 0 ) {
          $table_name = date('Y-m-d H:i T',(int)$row->etime);
        }
      }
    }
    $table_name = 'Hacker '.ucfirst($page).' Page at '.$table_name;
    $table_next = "<a href='/report/dashboard/$page/".($number-1)."'>&gt;</a>";
    $table_prev = "<a href='/report/dashboard/$page/".($number+1)."'>&lt;</a>";
    $table = ['table_rows'=>$table_rows,'table_prev'=>$table_prev,'table_next'=>$table_next,'table_name'=>$table_name];
    $memcache->set('hnposts'.$number.$page,$table);
  } else {
    $table = $memcache->get('hnposts'.$number.$page);
  }
  $table_name = $table['table_name'];
  $table_prev = $table['table_prev'];
  $table_next = $table['table_next'];
  $table_rows = $table['table_rows'];
  return($app['twig']->render('dashboard.html',array('report_name'=>'Full Dashboard','table_rows'=>$table_rows,'table_name'=>$table_name,'table_prev'=>$table_prev,'table_next'=>$table_next)));
});

$APP->get('/report/news/{number}', function ($number, Application $app, Request $request) {
  return($app['twig']->render('news.html',array('report_name'=>'Hacker News Page Rewind')));
});

$APP->get('/report/newest/{number}', function ($number, Application $app, Request $request) {
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
