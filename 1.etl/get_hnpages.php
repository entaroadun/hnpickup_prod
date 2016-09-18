<?php

// =========================

require_once('./vendor/autoload.php');
putenv('GOOGLE_APPLICATION_CREDENTIALS=./hnpickup-sa.json');
$HNPOSTS = new GDS\Store('HNPOSTS',new GDS\Gateway\RESTv1('hnpickup'));

// =========================

// =========================

function get_dom_from_url ( $url ) {

  $dom = null;
  // -----------------------
  $ch = curl_init(); 
  curl_setopt($ch,CURLOPT_URL,$url); 
  curl_setopt($ch,CURLOPT_RETURNTRANSFER,1); 
  $html = curl_exec($ch); 
  curl_close($ch); 
  // -- convert text to DOM structure
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

function get_posts_from_dom ( $dom, $page, $etime ) {

  $posts = array();
  // -----------------------
  if ( !is_null($dom) ) {
    $finder = new DomXPath($dom);
    $elements = $finder->query("//tr[@class='athing']");
    if ( !is_null($elements) ) {
      foreach ( $elements as $element ) {
	// -- basic post information
	$postid = $element->getAttribute('id');
	$rank = preg_replace('/\.$/','',$element->childNodes->item(0)->childNodes->item(0)->textContent);
	$title = $element->childNodes->item(3)->childNodes->item(0)->textContent;
	$url = $element->childNodes->item(3)->childNodes->item(0)->getAttribute('href');
	$points = preg_replace('/ points?$/','',$element->nextSibling->childNodes->item(1)->childNodes->item(1)->textContent);
	$user = $element->nextSibling->childNodes->item(1)->childNodes->item(3)->textContent;
	$posttime = preg_replace('/ ago$/','',$element->nextSibling->childNodes->item(1)->childNodes->item(5)->textContent);
	// -- probably a hire post by ycombinator
	if ( strlen($posttime) == 0 ) {
	  $posttime = $points;
	  $points = '0';
	  $user = '';
	}
	// -- convert time to minutes
	if ( preg_match('/minute/',$posttime) ) {
	  $posttime = preg_replace('/ minutes?/','',$posttime)*1;
	} elseif ( preg_match('/hour/',$posttime) ) {
	  $posttime = preg_replace('/ hours?/','',$posttime)*1*60;
	} elseif ( preg_match('/day/',$posttime) ) {
	  $posttime = preg_replace('/ days?/','',$posttime)*1*60*24;
	}
	// -- this should match datastore entities
	$posts[$postid] = array('etime'=>$etime,'page'=>$page,'rank'=>$rank,'postid'=>$postid,'title'=>$title,'url'=>$url,'points'=>$points,'user'=>$user,'posttime'=>$posttime,'compare'=>'0');
      }
    }
  }
  // -----------------------
  return($posts);
       
}

// =========================

function compare_posts ( &$posts_ref_1, &$posts_ref_2 ) {

  $the_same = 0;
  // -----------------------
  if ( count($posts_ref_1) && count($posts_ref_2) ) {
    foreach ( $posts_ref_1 as $key => $post ) {
      if ( array_key_exists($key,$posts_ref_2) ) {
	$posts_ref_1[$key]['compare'] = 1;
	$posts_ref_2[$key]['compare'] = 1;
	$the_same ++;
      }
    }
  }
  // -----------------------
  return($the_same);

}

// =========================

function insert_posts_into_datastore ( $posts, $datastore ) {

  $data_objs = array();
  // -----------------------
  if ( count($posts) && count($datastore) ) {
    foreach ( $posts as $key => $post ) {
      $data_objs[] = $datastore->createEntity($post);
    }
    $datastore->upsert($data_objs);
  }
  // -----------------------
  return($data_objs);

}

// =========================

$etime = time();
$news_dom = get_dom_from_url('https://news.ycombinator.com/news');
$news_posts = get_posts_from_dom($news_dom,'news',$etime);
$newest_dom = get_dom_from_url('https://news.ycombinator.com/newest');
$newest_posts = get_posts_from_dom($newest_dom,'newest',$etime);
$the_same = compare_posts($news_posts,$newest_posts);
echo "Parsed ".count($news_posts)." news and ".count($newest_posts)." newest posts where ".$the_same." is/are the same at the time ".$etime." ...\n";
$news_inserted = insert_posts_into_datastore($news_posts,$HNPOSTS);
$newest_inserted = insert_posts_into_datastore($newest_posts,$HNPOSTS);
echo "Inserted ".count($news_inserted)." news and ".count($newest_inserted)." newest posts ...\n";

?>
