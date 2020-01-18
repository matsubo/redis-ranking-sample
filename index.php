<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>AKB勝手に総選挙</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="Yuki Matsukura">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">

<style type="text/css">
body {
margin-top: 60px;
}
div.banner {
margin-top: 1em;
margin-bottom: 1em;
}
</style>

    <!-- HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      <script src="assets/js/html5shiv.js"></script>
    <![endif]-->

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

ini_set('display_errors', 1);
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

<footer>
<div class="row">
<div class="col-md-12 text-right">
<a href="https://matsu.teraren.com/blog/">&copy Yuki Matsukura</a>
</div>
</footer>

    </div> <!-- /container -->

<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>


  </body>
</html>


