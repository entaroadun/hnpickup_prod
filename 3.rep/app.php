<?php

// =========================

require_once(__DIR__.'/../vendor/autoload.php');
use Silex\Application;
use Silex\Provider\TwigServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
putenv('GOOGLE_APPLICATION_CREDENTIALS=../0.conf/hnpickup_key.json');

// =========================

// create the Silex application
$APP = new Silex\Application();
$APP->register(new Silex\Provider\TwigServiceProvider());
$APP['twig.path'] = [ __DIR__.'/templates' ];
$APP['hnposts'] = new \GDS\Store('HNPOSTS',new \GDS\Gateway\RESTv1('hnpickup'));
$APP['hnposts_summary'] = new \GDS\Store('HNPOSTS_SUMMARY',new \GDS\Gateway\RESTv1('hnpickup'));
date_default_timezone_set('America/Los_Angeles');

// =========================

$APP->get('/', function (Application $app, Request $request) {
  return($app->redirect('/report/dashboard/1'));
});

// =========================

$APP->get('/report/dashboard/{number}', function ($number, Application $app, Request $request) {
  $memcache = new Memcached;
  $number = 1*$number;
  if ( !$memcache->get('hnposts.'.$number) ) {
    $table_rows = array();
    $data_rows = array();
    $table_date = '';
    $table_next = '';
    $table_prev = '';
    $hnposts = $app['hnposts'];
    // - - - - - - - - - -
    $hnposts->query('SELECT * FROM HNPOSTS ORDER BY etime DESC LIMIT @end OFFSET @start',['end'=>60,'start'=>(($number-1)*60)]);
    $rows = $hnposts->fetchAll();
    usort($rows,function($a,$b){return(($a->rank<$b->rank)?-1:1);});
    foreach ( $rows as $row ) {
      $table_rows[$row->page][] = ['rank'=>$row->rank,'title'=>$row->title,'url'=>$row->url,'points'=>$row->points,'postid'=>$row->postid,'compare'=>$row->compare];
      if ( strcmp($table_date,'') === 0 ) {
        $table_date = date('Y-m-d H:i T',(int)$row->etime);
      }
    }
    $table_next = "/report/dashboard/".($number-1);
    $table_prev = "/report/dashboard/".($number+1);
    // - - - - - - - - - -
    $hnposts->query('SELECT * FROM HNPOSTS_SUMMARY ORDER BY etime DESC LIMIT @end OFFSET @start',['end'=>60,'start'=>($number-1)]);
    $rows = array_reverse($hnposts->fetchAll());
    foreach ( $rows as $row ) {
      $value = $row->newest_max-$row->news_min;
      $value_sign = ($value>=0) ? 1 : -1;
      $log_value = round(log(abs($value)+1,2),3)*$value_sign;
      $data_rows[] = ['etime'=>$row->etime,'value'=>$log_value];
    }
    // - - - - - - - - - -
    $table = ['table_news'=>$table_rows['news'],'table_newest'=>$table_rows['newest'],'table_prev'=>$table_prev,'table_next'=>$table_next,'table_date'=>$table_date,'etime'=>time(),'dataset'=>$data_rows];
    $memcache->set('hnposts.'.$number,$table);
    $memcache->delete('hnposts.'.$number,600);
  } else {
    $table = $memcache->get('hnposts.'.$number);
  }
  $table_date = $table['table_date'];
  $table_prev = $table['table_prev'];
  $table_next = $table['table_next'];
  $table_news = $table['table_news'];
  $table_newest = $table['table_newest'];
  $dataset = $table['dataset'];
  return($app['twig']->render('dashboard.html',array('report_name'=>'Full Dashboard','table_news'=>$table_news,'table_newest'=>$table_newest,'table_date'=>$table_date,'table_prev'=>$table_prev,'table_next'=>$table_next,'dataset'=>$dataset)));
});

// =========================

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
      $message = 'We are sorry, but something went terribly wrong. '.$e;
  }
  return($APP['twig']->render('error.html',array('report_name'=>'Error','message'=>$message)));
});

// =========================

$APP['debug'] = true;
$APP->run();

?>
