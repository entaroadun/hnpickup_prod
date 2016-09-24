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
      if ( $field == 'news_pickup_ratio' ) {
	$value = $row->newest_max-$row->news_min;
	$value_sign = ($value>=0) ? 1 : -1;
	$log_value = round(log(abs($value)+1,2),3)*$value_sign;
	$data_rows[] = ['etime'=>($row->etime)*1,'ratio'=>$log_value,'offset'=>$offset+$i-1];
      } else if ( $field == 'news_summary' ) {
	$data_rows[] = ['etime'=>($row->etime)*1,'news_min'=>log($row->news_min+1,2),'news_average'=>log($row->news_ave+1,2),'news_max'=>log($row->news_max+1,2),'offset'=>$offset+$i-1];
      } else if ( $field == 'newest_summary' ) {
	$data_rows[] = ['etime'=>($row->etime)*1,'newest_min'=>log($row->newest_min+1,2),'newest_average'=>log($row->newest_ave+1,2),'newest_max'=>log($row->newest_max+1,2),'offset'=>$offset+$i-1];
      } else {
	$data_rows[] = ['error'];
	break;
      }
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
  return($app->redirect('/report/three'));
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

$APP->get('/report/three', function ( Application $app, Request $request ) {
  $params = $request->query->all();
  if ( !isset($params['line_values']) || is_array($params['line_values']) ) {
    $params['line_values'] = 'news_pickup_ratio';
  }
  if ( !isset($params['data_size']) ) {
    $params['data_size'] = 50;
  }
  if ( !isset($params['table_values']) || !is_array($params['table_values']) ) {
    $params['table_values'] = ['news','newest'];
  }
  return($app['twig']->render('three.html',['report_name'=>'Hacker Comparisons','line_values'=>$params['line_values'],'data_size'=>$params['data_size'],'table_values'=>$params['table_values']]));
});

// =========================

$APP->get('/report/two', function ( Application $app, Request $request) {
  $params = $request->query->all();
  if ( !isset($params['line_values']) || is_array($params['line_values']) ) {
    $params['line_values'] = 'news_summary';
  }
  if ( !isset($params['data_size']) ) {
    $params['data_size'] = 50;
  }
  if ( !isset($params['table_values']) || is_array($params['table_values']) ) {
    $params['table_values'] = 'news';
  }
  return($app['twig']->render('two.html',['report_name'=>'Hacker Rewind Tables','line_values'=>$params['line_values'],'data_size'=>$params['data_size'],'table_values'=>$params['table_values']]));
});

// =========================

$APP->get('/report/one', function ( Application $app, Request $request) {
  $params = $request->query->all();
  if ( !isset($params['line_values']) || !is_array($params['line_values']) ) {
    $params['line_values'] = ['news_summary','newest_summary'];
  }
  if ( !isset($params['data_size']) ) {
    $params['data_size'] = 50;
  }
  if ( !isset($params['table_values']) || is_array($params['table_values']) ) {
    $params['table_values'] = 'news';
  }
  return($app['twig']->render('one.html',['report_name'=>'Hacker Line Charts','line_values'=>$params['line_values'],'data_size'=>$params['data_size'],'table_values'=>$params['table_values']]));
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
