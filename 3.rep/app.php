<?php

// =========================

require_once(__DIR__.'/../vendor/autoload.php');
use Silex\Application;
use Silex\Provider\TwigServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
putenv('GOOGLE_APPLICATION_CREDENTIALS=../0.conf/hnpickup_key.json');

// =========================

function get_hnposts ( $hnposts, $offset, $page ) {

  $table_rows = array();
  // -----------------------
  if ( !is_null($hnposts) && is_numeric($offset) && ( $page == 'news' || $page == 'newest' ) ) {
    $hnposts->query('SELECT * FROM HNPOSTS WHERE page = @page ORDER BY etime DESC LIMIT @limit OFFSET @offset',['limit'=>30,'offset'=>(($offset-1)*30),'page'=>$page]);
    $rows = $hnposts->fetchAll();
    usort($rows,function($a,$b){return(($a->rank<$b->rank)?-1:1);});
    foreach ( $rows as $row ) {
      $table_rows[] = ['rank'=>$row->rank,'title'=>$row->title,'url'=>$row->url,'points'=>$row->points,'postid'=>$row->postid,'compare'=>$row->compare,'etime'=>$row->etime];
    }
  }
  // -----------------------
  return($table_rows);

}

// =========================

function get_summary ( $hnposts, $offset, $limit, $field ) {

  $data_rows = array();
  // -----------------------
  if ( !is_null($hnposts) && is_numeric($offset) && is_numeric($limit) ) {
    $hnposts->query('SELECT * FROM HNPOSTS_SUMMARY ORDER BY etime DESC LIMIT @limit OFFSET @offset',['limit'=>$limit*1,'offset'=>($offset-1)]);
    $rows = array_reverse($hnposts->fetchAll());
    $i = min($limit,count($rows));
    foreach ( $rows as $row ) {
      $value = $row->newest_max-$row->news_min;
      $value_sign = ($value>=0) ? 1 : -1;
      $log_value = round(log(abs($value)+1,2),3)*$value_sign;
      $data_rows[] = ['etime'=>($row->etime)*1,'ratio'=>$log_value,'offset'=>$offset+$i-1];
      $i --;
    }
  }
  // -----------------------
  return($data_rows);

}

// =========================

// create the Silex application
$APP = new Silex\Application();
$APP->register(new Silex\Provider\TwigServiceProvider());
$APP['twig.path'] = [ __DIR__.'/templates' ];
$APP['hnposts'] = new \GDS\Store('HNPOSTS');#,new \GDS\Gateway\RESTv1('hnpickup'));
$APP['summary'] = new \GDS\Store('HNPOSTS_SUMMARY');#,new \GDS\Gateway\RESTv1('hnpickup'));
date_default_timezone_set('America/Los_Angeles');

// =========================

$APP->get('/', function (Application $app, Request $request) {
  return($app->redirect('/report/dashboard'));
});

// =========================

$APP->get('/hnposts/{offset}/{page}.json', function ( $offset, $page, Application $app, Request $request ) {
  $memcache = new Memcached;
  // - - - - - - - - -
  if ( !($table_rows = $memcache->get("hnposts/$offset/$page")) ) {
    $hnposts = $app['hnposts'];
    $table_rows = get_hnposts($hnposts,$offset,$page);
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
    $table_rows = get_summary($summary,$offset,$limit,$field);
    $memcache->set("summary/$offset/$limit/$field",$table_rows,time()+15*60);
  }
  // - - - - - - - - -
  return(json_encode($table_rows));
});

// =========================

$APP->get('/report/dashboard', function ( Application $app, Request $request ) {
  return($app['twig']->render('dashboard.html',array('report_name'=>'Full Dashboard')));
});

// =========================

$APP->get('/report/news', function ( Application $app, Request $request) {
  return($app['twig']->render('news.html',array('report_name'=>'Hacker News Page Rewind')));
});

// =========================

$APP->get('/report/newest', function ( Application $app, Request $request ) {
  return($app['twig']->render('newest.html',array('report_name'=>'Hacker Newest Page Rewind')));
});

// =========================

$APP->get('/report/pickup', function ( Application $app, Request $request ) {
  return($app['twig']->render('pickup.html',array('report_name'=>'Hacker News Pickup Ratio')));
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
