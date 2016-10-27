<?php

// =========================

require_once(__DIR__.'/../vendor/autoload.php');

// =========================

function create_connection ( $datastore_name ) {

  $obj_store = NULL;
  // -----------------------
  $obj_store = new GDS\Store($datastore_name);
  // -----------------------
  return($obj_store);

}

// =========================

function get_dom_from_url ( $url ) {

  $dom = NULL;
  // -----------------------
  $ch = curl_init(); 
  if ( !is_null($ch) ) {
    // -- this is convert to "url fetch" by GAE
    curl_setopt($ch,CURLOPT_URL,$url); 
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true); 
    curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
    curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
    $html = iconv('UTF-8','ASCII//TRANSLIT',curl_exec($ch)); 
    curl_close($ch); 
    // -- convert text to DOM structure
    if ( strlen($html) > 0 ) {
      $dom = new domDocument; 
      $internalErrors = libxml_use_internal_errors(true);
      $dom->loadHTML($html);
      libxml_use_internal_errors($internalErrors);
    }
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
	if ( $element->nodeType == XML_ELEMENT_NODE ) {
	  // -- basic HN post information
	  $postid = $element->getAttribute('id');
	  $rank = preg_replace('/\.$/','',$element->childNodes->item(0)->childNodes->item(0)->textContent);
	  $title = $element->childNodes->item(3)->childNodes->item(0)->textContent;
	  if ( $element->childNodes->item(3)->childNodes->item(0) == XML_ELEMENT_NODE ) {
	    $url = $element->childNodes->item(3)->childNodes->item(0)->getAttribute('href');
	  } else {
	    throw new Exception("Page element (url not a node): ".$dom->saveHTML($element));
	  }
	  $points = preg_replace('/ points?$/','',$element->nextSibling->childNodes->item(1)->childNodes->item(1)->textContent);
	  $user = $element->nextSibling->childNodes->item(1)->childNodes->item(3)->textContent;
	  // -- is this a hire post
	  if ( $element->nextSibling->childNodes->item(1)->childNodes->length > 5 ) {
	    $posttime = preg_replace('/ ago$/','',$element->nextSibling->childNodes->item(1)->childNodes->item(5)->textContent);
	  } else {
	    $posttime = '';
	  }
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
	  $posts[$postid] = array('etime'=>$etime,'page'=>$page,'rank'=>(int)$rank,'postid'=>$postid,'title'=>$title,'url'=>$url,'points'=>(int)$points,'user'=>$user,'posttime'=>$posttime,'compare'=>'0');
	} else {
	  throw new Exception("Page element (element not a node): ".$dom->saveHTML($element));
	}
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
  if ( !is_null($datastore) ) {
    if ( count($posts) && count($datastore) ) {
      foreach ( $posts as $key => $post ) {
	$data_objs[] = $datastore->createEntity($post);
      }
      $datastore->upsert($data_objs);
    }
  }
  // -----------------------
  return($data_objs);

}

// =========================

function create_posts_summary ( $name, $posts, $query ) {

  $result = array();
  // -----------------------
  $min_points = 0;
  $max_points = 0;
  $ave_points = 0;
  $n_points = 0;
  $max_story_postid = 0;
  $max_story_title = '';
  $max_story_url = '';
  foreach ( $posts as $key => $post ) {
    if ( $query($post) ) {
      // -- do not include hire posts
      if ( $post['points'] > 0 ) {
	$ave_points += $post['points'];
	$n_points ++;
	if ( $min_points == 0 || $min_points > $post['points'] ) {
	  $min_points = $post['points'];
	}
	if ( $max_points == 0 || $max_points < $post['points'] ) {
	  $max_points = $post['points'];
	  $max_story_postid = $post['postid'];
	  $max_story_title = $post['title'];
	  $max_story_url = $post['url'];
	}
      }
    }
  }
  if ( $n_points > 0 ) {
    $ave_points /= $n_points;
  }
  $result[$name.'_min'] = $min_points;
  $result[$name.'_ave'] = $ave_points;
  $result[$name.'_max'] = $max_points;
  $result[$name.'_postid'] = $max_story_postid;
  $result[$name.'_title'] = $max_story_title;
  $result[$name.'_url'] = $max_story_url;
  // -----------------------
  return($result);
}

// =========================

function count_etimes_entires ( $hnetimes_ds, $hnposts_summary_ds, $etime ) {

  $entry_counts = 0;
  // -----------------------
  // -- get last entry
  $hnetimes_ds->query('SELECT * FROM HNETIMES ORDER BY etime DESC');
  $latest_etime = $hnetimes_ds->fetchOne(); 
  $last_etime = $latest_etime->etime;
  $last_etimeid = $latest_etime->etimeid;
  if ( is_null($last_etime) || is_null($last_etimeid) ) {
    $last_etimeid = 0;
    $last_etime = 0;
  }
  // -- check what is missing
  $hnposts_summary_ds->query('SELECT * FROM HNPOSTS_SUMMARY WHERE etime > @last_etime ORDER BY etime ASC',['last_etime'=>intval($last_etime)]);
  $missing_etime = array();
  $missing_etimeid = array();
  while ( $rows = $hnposts_summary_ds->fetchPage(50) ) {
    foreach ( $rows as $row ) {
      $missing_etime[] = intval($row->etime);
    }
  }
  if ( count($missing_etime) ) {
    $missing_etimes = sort($missing_etime);
    $last_etimeid = intval($last_etimeid);
    foreach ( $missing_etime as $this_etime ) {
      $last_etimeid ++;
      $missing_etimeid[] = $last_etimeid;
    }
    $missing_data = array();
    for ( $i = 0; $i < count($missing_etimeid); $i++ ) {
      $missing_data[] = $hnetimes_ds->createEntity(['etime'=>$missing_etime[$i],'etimeid'=>$missing_etimeid[$i]]);
    }
    $hnetimes_ds->upsert($missing_data);
    $entry_counts = $missing_etimeid[(count($missing_etimeid)-1)];
  } else {
    $entry_counts = $last_etimeid;
  }
  // -----------------------
  return($entry_counts);

}

// =========================

$etime = time();
syslog(LOG_INFO,"Starting at ".$etime);
$hnposts_ds = create_connection('HNPOSTS');
$hnposts_summary_ds = create_connection('HNPOSTS_SUMMARY');
$hnetimes_ds = create_connection('HNETIMES');

// - - - - - - - - -

$news_dom = get_dom_from_url('https://news.ycombinator.com/news');
$news_posts = get_posts_from_dom($news_dom,'news',$etime);
$newest_dom = get_dom_from_url('https://news.ycombinator.com/newest');
$newest_posts = get_posts_from_dom($newest_dom,'newest',$etime);
$the_same = compare_posts($news_posts,$newest_posts);
echo "Parsed ".count($news_posts)." news and ".count($newest_posts)." newest posts where ".$the_same." is/are the same at the time ".$etime." ...\n";
syslog(LOG_INFO,"Parsed ".count($news_posts)." news and ".count($newest_posts)." newest posts where ".$the_same." is/are the same at the time ".$etime." ...\n");

// - - - - - - - - -

$newest_summary = create_posts_summary('newest',$newest_posts,function($post){return(1);});
$both_summary = create_posts_summary('both',$newest_posts,function($post){return($post['compare']==1);});
$news_summary = create_posts_summary('news',$news_posts,function($post){return(1);});
$all_summary = [['etime'=>$etime]+$newest_summary+$both_summary+$news_summary];
$news_inserted = insert_posts_into_datastore($news_posts,$hnposts_ds);
$newest_inserted = insert_posts_into_datastore($newest_posts,$hnposts_ds);
$summary_inserted = insert_posts_into_datastore($all_summary,$hnposts_summary_ds);
echo "Inserted ".count($news_inserted)." news and ".count($newest_inserted)." newest posts with ".count($summary_inserted)." summary at time ".time()."...\n";
syslog(LOG_INFO,"Inserted ".count($news_inserted)." news and ".count($newest_inserted)." newest posts with ".count($summary_inserted)." summary at time ".time()."...\n");
$entry_counts = count_etimes_entires($hnetimes_ds,$hnposts_summary_ds,$etime);
echo "Total entires $entry_counts ...\n";
syslog(LOG_INFO,"Total entires $entry_counts ...\n");

// - - - - - - - - -

?>
