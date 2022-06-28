<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>AKB勝手に総選挙</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="Yuki Matsukura">


<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.3/css/bootstrap.min.css" integrity="sha512-GQGU0fMMi238uA+a/bdWJfpUGKUkBdgfFdgBm72SUQ6BeyWjoY/ton0tEjH+OSH9iP4Dfh+7HM0I9f5eR0L/4w==" crossorigin="anonymous" referrerpolicy="no-referrer" />

<style type="text/css">
body {
margin-top: 60px;
}
div.banner {
margin-top: 1em;
margin-bottom: 1em;
}

div.footer {
background-color: #f5f5f5;
height: 60px;
}

</style>

    <!-- Fav and touch icons -->
    <link rel="apple-touch-icon-precomposed" sizes="144x144" href="assets/ico/apple-touch-icon-144-precomposed.png">
    <link rel="apple-touch-icon-precomposed" sizes="114x114" href="assets/ico/apple-touch-icon-114-precomposed.png">
    <link rel="apple-touch-icon-precomposed" sizes="72x72" href="assets/ico/apple-touch-icon-72-precomposed.png">
    <link rel="apple-touch-icon-precomposed" href="assets/ico/apple-touch-icon-57-precomposed.png">
    <link rel="shortcut icon" href="assets/ico/favicon.png">
  </head>

  <body>
<!-- Google Tag Manager -->
<noscript><iframe src="//www.googletagmanager.com/ns.html?id=GTM-WNH3R2"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'//www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-WNH3R2');</script>
<!-- End Google Tag Manager -->


<nav class="navbar navbar-expand-md navbar-dark bg-dark fixed-top">

  <div class="container-fluid">
    <!-- Brand and toggle get grouped for better mobile display -->
    <div class="navbar-header">
      <a class="navbar-brand" href="?">AKB勝手に総選挙</a>
    </div>

  </div><!-- /.container-fluid -->
</nav>




    <div class="container">

      <h1>AKB勝手にランキング</h1>

<p>
応援したい人に投票しよう！ 
</p>

<div class="row banner">
  <div class="col-md-12">
    <?php include './ad.inc.php'; ?>
  </div>
</div>

<table class="table table-striped">
<tr>
<th>順位</th><th>名前</th><th>得票数</th><th>&nbsp;</th>
</tr>


<?php

require_once __DIR__ . '/vendor/autoload.php';

use Matsubo\Redis\Ranking;

$redis = new \Redis;
$redis->connect('127.0.0.1', 6379);

$ranking = new Ranking($key = 'akb', $redis);

$text = isset($_REQUEST['text']) ? $_REQUEST['text'] : '';
if (isset($_REQUEST['mode']) && $_REQUEST['mode']  == 'increment') {
    if ($ranking->getScore($text) !== false) {
        $ranking->incrementScore($text, -1);
    }
}

$i = 1;
foreach ($ranking->getRange(0, -1, true) as $name => $value) {
    print sprintf('<tr><td>%d 位</td><td>%s</td><td>%d<td><form method="post" action="?mode=increment"><input type="hidden" name="text" value="%s"><button type="submit" class="btn btn-primary">投票</button></form></td></tr>', $i++, $name, abs($value), $name);
    print "\n";
}


?>
</table>



<div class="row banner">
  <div class="col-md-12">
    <?php include './ad.inc.php'; ?>
  </div>
</div>


<h2>メンバー追加方法</h2>
<p>
こちらのファイルに対してpull requestを送って欲しいです。
<br />
<a href="https://github.com/matsubo/redis-ranking-sample/blob/master/member.txt">https://github.com/matsubo/redis-ranking-sample/blob/master/member.txt</a>
</p>


<h2>メモ</h2>
<a href="https://matsu.teraren.com/blog/2013/04/28/redis-ranking-api/">Redis-Ranking</a>を使って構築しています。

<br />
<br />
<br />



    </div> <!-- /container -->


<div class="footer">
  <div class="container">
    <div class="row">
      <div class="col-md-12 text-right mt-1">
        <p class="muted credit">
          <a href="https://twitter.com/matsubokkuri">@matsubokkuri</a>
        </p>
      </div>
    </div>
  </div>
</div>


<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js" referrerpolicy="no-referrer"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.5/umd/popper.min.js" integrity="sha512-8cU710tp3iH9RniUh6fq5zJsGnjLzOWLWdZqBMLtqaoZUA6AWIE34lwMB3ipUNiTBP5jEZKY95SfbNnQ8cCKvA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.3/js/bootstrap.min.js" integrity="sha512-OvBgP9A2JBgiRad/mM36mkzXSXaJE9BEIENnVEmeZdITvwT09xnxLtT4twkCa8m/loMbPHsvPl0T8lRGVBwjlQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>



  </body>
</html>


