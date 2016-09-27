<?php

// =========================

function convert_offset_to_etime ( $etimes, $offset ) {

  $this_etime = 0;
  // -----------------------
  if ( !is_null($etimes) && !is_null($offset) && is_numeric($offset) ) {
    // -- get the right etime (offset -> etimeid -> etime)
    $etimes->query('SELECT * FROM HNETIMES ORDER BY etime DESC');
    $row = $etimes->fetchOne();
    $latest_etimeid = $row->etimeid;
    $latest_etime = $row->etime;
    $needed_etimeid = max($latest_etimeid-$offset+1,1);
    $etimes->query('SELECT * FROM HNETIMES WHERE etimeid = @needed_etimeid',['needed_etimeid'=>intval($needed_etimeid)]);
    $row = $etimes->fetchOne();
    $this_etime = intval($row->etime);
  }
  // -----------------------
  return($this_etime);

}

// =========================

function get_hnposts ( $etimes, $hnposts, $offset, $page ) {

  $table_rows = array();
  // -----------------------
  if ( !is_null($hnposts) && is_numeric($offset) && ( $page == 'news' || $page == 'newest' ) ) {
    $this_etime = convert_offset_to_etime($etimes,$offset);
    $hnposts->query('SELECT * FROM HNPOSTS WHERE page = @page AND etime = @this_etime',['page'=>$page,'this_etime'=>$this_etime]);
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

function get_summary ( $etimes, $hnposts, $offset, $limit, $field ) {

  $data_rows = array();
  // -----------------------
  if ( !is_null($hnposts) && is_numeric($offset) && is_numeric($limit) ) {
    $start_etime = convert_offset_to_etime($etimes,$offset);
    $end_etime = convert_offset_to_etime($etimes,$offset+$limit-1);
    $hnposts->query('SELECT * FROM HNPOSTS_SUMMARY WHERE etime <= @start_etime AND etime >= @end_etime ORDER BY etime DESC',['start_etime'=>intval($start_etime),'end_etime'=>intval($end_etime)]);
    $all_rows = array();
    while ( $rows = $hnposts->fetchPage(50) ) {
      $all_rows = array_merge($all_rows,$rows);
    }
    $all_rows = array_reverse($all_rows);
    $i = min($limit,count($all_rows));
    foreach ( $all_rows as $row ) {
      if ( $field == 'news_pickup_ratio' ) {
	$value = $row->newest_max-$row->news_min;
	$value_sign = ($value>=0) ? 1 : -1;
	$log_value = round(log(abs($value)+1,2),3)*$value_sign;
	$data_rows[] = ['etime'=>($row->etime)*1,'ratio'=>$log_value,'offset'=>$offset+$i-1];
      } else if ( $field == 'news_summary' ) {
	$data_rows[] = ['etime'=>($row->etime)*1,'news_min'=>log($row->news_min+1,1.1),'news_average'=>log($row->news_ave+1,1.1),'news_max'=>log($row->news_max+1,1.1),'offset'=>$offset+$i-1];
      } else if ( $field == 'newest_summary' ) {
	$data_rows[] = ['etime'=>($row->etime)*1,'newest_min'=>log($row->newest_min+1,1.1),'newest_average'=>log($row->newest_ave+1,1.1),'newest_max'=>log($row->newest_max+1,1.1),'offset'=>$offset+$i-1];
      } else if ( $field == 'all_summary' ) {
	$data_rows[] = ['etime'=>($row->etime)*1,'newest_min'=>$row->newest_min,'newest_average'=>$row->newest_ave,'newest_max'=>$row->newest_max,'news_min'=>$row->news_min,'news_average'=>$row->news_ave,'news_max'=>$row->news_max,     'both_min'=>$row->both_min,'both_average'=>$row->both_ave,'both_max'=>$row->both_max,'offset'=>$offset+$i-1];
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

function hn_params_validation  ( $params, $line_default, $table_default ) {

  // -----------------------
  if ( !isset($params['line_values']) ) {
    $params['line_values'] = $line_default;
  }
  if ( !is_array($params['line_values']) ) {
    $params['line_values'] = [$params['line_values']];
  }
  // - - - - - - - - -
  if ( !isset($params['table_values']) ) {
    $params['table_values'] = $table_default;
  }
  if ( !is_array($params['table_values']) ) {
    $params['table_values'] = [$params['table_values']];
  }
  // - - - - - - - - -
  if ( !isset($params['data_size']) ) {
    $params['data_size'] = 48;
  }
  // - - - - - - - - -
  if ( !isset($params['offset']) ) {
    $params['offset'] = 1;
  }
  // -----------------------
  return($params);

}

// =========================

?>
