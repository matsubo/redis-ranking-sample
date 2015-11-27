<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>AKB勝手に総選挙</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="Yuki Matsukura">

    <!-- Le styles -->
    <link href="./components/bootstrap/docs/assets/css/bootstrap.css" rel="stylesheet">
    <style>
      body {
        padding-top: 60px; /* 60px to make the container go all the way to the bottom of the topbar */
      }
    </style>
    <link href="./components/bootstrap/docs/assets/css/bootstrap-responsive.css" rel="stylesheet">

    <!-- HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      <script src="components/bootstrap/docs/assets/js/html5shiv.js"></script>
    <![endif]-->

    <!-- Fav and touch icons -->
    <link rel="apple-touch-icon-precomposed" sizes="144x144" href="components/bootstrap/docs/assets/ico/apple-touch-icon-144-precomposed.png">
    <link rel="apple-touch-icon-precomposed" sizes="114x114" href="components/bootstrap/docs/assets/ico/apple-touch-icon-114-precomposed.png">
    <link rel="apple-touch-icon-precomposed" sizes="72x72" href="components/bootstrap/docs/assets/ico/apple-touch-icon-72-precomposed.png">
    <link rel="apple-touch-icon-precomposed" href="components/bootstrap/docs/assets/ico/apple-touch-icon-57-precomposed.png">
    <link rel="shortcut icon" href="components/bootstrap/docs/assets/ico/favicon.png">
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

    <div class="navbar navbar-inverse navbar-fixed-top">
      <div class="navbar-inner">
        <div class="container">
          <button type="button" class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="brand" href="?">AKB勝手に総選挙</a>
          <div class="nav-collapse collapse">
            <ul class="nav">
              <li class="active"><a href="?">Home</a></li>
            </ul>
          </div><!--/.nav-collapse -->
        </div>
      </div>
    </div>

    <div class="container">

      <h1>AKB勝手にランキング</h1>

<p>
応援したい人に投票しよう！ 
</p>

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


/* initialize code.
$member_text = file_get_contents('member.txt');
$member_array = explode("\n", $member_text);
foreach ($member_array as $member) {
    $member = trim($member);
    if (!$member) {
        continue;
    }
    if ($ranking->getScore($member) !== false) {
        continue;
    }
    $ranking->setUserScore($member, 0);
}
 */
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


<h2>メンバー追加方法</h2>
<p>
こちらのファイルに対してpull requestを送って欲しいです。
<br />
<a href="https://github.com/matsubo/redis-ranking-sample/blob/master/member.txt">https://github.com/matsubo/redis-ranking-sample/blob/master/member.txt</a>
</p>

<!--
<form method="post" action="?mode=add">
  <fieldset>
    <legend>追加</legend>
    <label>名前</label>
    <input name="text" type="text" placeholder="前田敦子">
    <button type="submit" class="btn">追加</button>
  </fieldset>
</form>
-->


<h2>メモ</h2>
<a href="http://matsu.teraren.com/blog/2013/04/28/redis-ranking-api/">Redis-Ranking</a>を使って構築しています。

<br />
<br />
<br />
<br />
<br />


    </div> <!-- /container -->

    <!-- Le javascript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="components/bootstrap/docs/assets/js/jquery.js"></script>

  </body>
</html>


