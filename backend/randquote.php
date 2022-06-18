<?php

  /**
   * Return a random quote scraped from http://www.brainyquote.com
   */

  include './lib/simple_html_dom-1.5.php';

  function hwrng($min,$max) {
    // Read 4 bytes from /dev/hwrng, format as unsigned 4 byte integer. Range: [0, 2^32-1]
    $n = shell_exec("sudo od /dev/hwrng --address-radix=n --read-bytes=4 --format=u4");
    // Range: [0,1) (real)
    $n = $n / 4294967296; // 2^32
    // Range: [$min,$max], keep integer part
    $n = intval($n*($max-$min+1) + $min); // +1 to include $max in range
    return $n;
  }

  $response = array(
    "quote" => "",
    "author" => "",
  );
  $url = 'http://www.brainyquote.com/quotes/'.chr(hwrng(97,122)).'.html';
  $html = file_get_html($url);

  if (is_null($total_pages = $html->find('/html/body/div[4]/div[2]/div[1]/div[2]/div/ul/li[1]/div/ul/li',-2))) {
    $total_pages = 1;
  } else {
    $total_pages = $total_pages->plaintext;
    $url = str_replace('.html',hwrng(1,$total_pages).'.html',$url);
    $html = file_get_html($url);
  }

  $authors = $html->find('table[class=table table-hover table-bordered] tbody tr');
  $total_authors = count($authors);

  $author = $authors[hwrng(0,$total_authors-1)];
  $url = 'http://www.brainyquote.com'.$author->find('a',0)->href.'?vm=l';
  $html = file_get_html($url);

  if (is_null($total_pages = $html->find('/html/body/div[4]/div/div/div[1]/div[2]/div/ul[2]/li[1]/div/ul/li',-2))) {
    $total_pages = 1;
  } else {
    $total_pages = $total_pages->plaintext;
    $url = str_replace('.html','_'.hwrng(1,$total_pages).'.html',$url);
    $html = file_get_html($url);
  }

  $quotes = $html->find('span[class=bqQuoteLink] a');
  $total_quotes = count($quotes);
  $quote = $quotes[hwrng(0,$total_quotes-1)];
  $url = 'http://www.brainyquote.com'.$quote->href;
  $html = file_get_html($url);

  $response["quote"] = $html->find('//*[@id="quoteContent"]/div/p[1]',0)->plaintext;
  $response["author"] = trim($html->find('//*[@id="quoteContent"]/div/p[2]/a',0)->plaintext);
  
  header('Content-Type: application/json');
  echo json_encode($response);
?>
