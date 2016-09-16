<?php

echo "Work in progress ... stayed tuned ...\n";

// =========================

function get_dom_from_url ( $url ) {

  $dom = null;
  // -----------------------
  $ch = curl_init(); 
  curl_setopt($ch,CURLOPT_URL,$url); 
  curl_setopt($ch,CURLOPT_RETURNTRANSFER,1); 
  $html = curl_exec($ch); 
  curl_close($ch); 
  if ( strlen($html) > 0 ) {
    $dom = new domDocument; 
    $internalErrors = libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_use_internal_errors($internalErrors);
  }
  // -----------------------
  return($dom);

}

// =========================

function get_posts_from_dom ( $dom ) {

  $posts = array();
  // -----------------------
  if ( !is_null($dom) ) {
    $finder = new DomXPath($dom);
    $elements = $finder->query("//tr[@class='athing']");
    if ( !is_null($elements) ) {
      foreach ( $elements as $element ) {
	$id = $element->getAttribute('id');
	$rank = preg_replace('/\.$/','',$element->childNodes->item(0)->childNodes->item(0)->textContent);
	$title = $element->childNodes->item(3)->childNodes->item(0)->textContent;
	$url = $element->childNodes->item(3)->childNodes->item(0)->getAttribute('href');
	$points = preg_replace('/ points?$/','',$element->nextSibling->childNodes->item(1)->childNodes->item(1)->textContent);
	$user = $element->nextSibling->childNodes->item(1)->childNodes->item(3)->textContent;
	$time = preg_replace('/ ago$/','',$element->nextSibling->childNodes->item(1)->childNodes->item(5)->textContent);
	if ( strlen($time) == 0 ) {
	  $time = $points;
	  $points = '0';
	  $user = '';
	}
	$posts[$id] = array('rank'=>$rank,'id'=>$id,'title'=>$title,'url'=>$url,'points'=>$points,'user'=>$user,'time'=>$time,'compare'=>'0');
      }
    }
  }
  // -----------------------
  return($posts);
       
}

// =========================

function compare_posts ( &$posts_ref_1, &$posts_ref_2 ) {

  // -----------------------
  if ( count($posts_ref_1) && count($posts_ref_2) ) {
    foreach ( $posts_ref_1 as $key => $post ) {
      if ( array_key_exists($key,$posts_ref_2) ) {
	$posts_ref_1[$key]['compare'] = 1;
	$posts_ref_2[$key]['compare'] = 1;
      }
    }
  }
  // -----------------------

}

// =========================

function insert_posts_into_datastore ( $posts ) {
}

// =========================

$news_dom = get_dom_from_url('https://news.ycombinator.com/news');
$news_posts = get_posts_from_dom($news_dom);
$newest_dom = get_dom_from_url('https://news.ycombinator.com/newest');
$newest_posts = get_posts_from_dom($newest_dom);
compare_posts($news_posts,$newest_posts);

?>
