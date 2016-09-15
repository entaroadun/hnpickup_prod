<?php

echo "Work in progress ... stayed tuned ...";

// GET "HACKER NEWS" LATEST NEWS
$curl = curl_init();
curl_setopt_array($curl,array(CURLOPT_RETURNTRANSFER=>1,CURLOPT_URL=>'https://news.ycombinator.com/news', CURLOPT_USERAGENT=>'HNPICKUP SERVICE'));
$news = curl_exec($curl);
curl_close($curl);

// GET "HACKER NEWS" NEWEST POSTS
curl_setopt_array($curl,array(CURLOPT_RETURNTRANSFER=>1,CURLOPT_URL=>'https://news.ycombinator.com/newest',CURLOPT_USERAGENT=>'HNPICKUP SERVICE'));
$newest = curl_exec($curl);
curl_close($curl);

// PARSE RESULTS
//<tr class='athing' id='12502991'> <td align="right" valign="top" class="title"><span class="rank">18.</span></td>      <td valign="top" class="votelinks"><center><a id='up_12502991' onclick='return vote(this, "up")' href='vote?id=12502991&amp;how=up&amp;auth=be91e9da3a1bfca76e28874478701cd716f713a6&amp;goto=newest'><div class='votearrow' title='upvote'></div></a></center></td><td class="title"><a href="https://github.com/kaienkira/acme-client" class="storylink" rel="nofollow">A small PHP script to get and renew TLS certs from Let's Encrypt</a><span class="sitebit comhead"> (<a href="from?site=github.com"><span class="sitestr">github.com</span></a>)</span></td></tr><tr><td colspan="2"></td><td class="subtext"> <span class="score" id="score_12502991">1 point</span> by <a href="user?id=kaienkira" class="hnuser"><font color="#3c963c">kaienkira</font></a> <span class="age"><a href="item?id=12502991">1 hour ago</a></span> <span id="unv_12502991"></span> | <a href="flag?id=12502991&amp;auth=be91e9da3a1bfca76e28874478701cd716f713a6&amp;goto=newest">flag</a> | <a href="hide?id=12502991&amp;goto=newest&amp;auth=be91e9da3a1bfca76e28874478701cd716f713a6" onclick="return hidestory(this, 12502991)">hide</a> | <a href="https://hn.algolia.com/?query=A%20small%20PHP%20script%20to%20get%20and%20renew%20TLS%20certs%20from%20Let's%20Encrypt&sort=byDate&dateRange=all&type=story&storyText=false&prefix&page=0" class="hnpast">past</a> | <a href="https://www.google.com/search?q=A%20small%20PHP%20script%20to%20get%20and%20renew%20TLS%20certs%20from%20Let's%20Encrypt">web</a> | <a href="item?id=12502991">discuss</a>              </td></tr>

$NEWS_ARR = array();

function get_matches_in_array ( $matches ) {
  array_push($NEWS_ARR,$matches[1]);

}

$string = preg_replace_callback('/(\d+) points?<\/span> by','get_matches_in_array',$news);


echo " ".strlen($news)." ".strlen($newest)." ".$NEWS_ARR[0]."|";

?>
