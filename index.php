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


<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-193685-1']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>


  </head>

  <body>

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

require_once 'vendor/autoload.php';

use Matsubo\Redis\Ranking;

$redis = new \Redis();
$redis->connect('127.0.0.1', 6379);

$ranking = new Ranking($key = 'akb', $redis);



/*
$ranking->setUserScore('中塚智実',0);
$ranking->setUserScore('松原夏海',0);
$ranking->setUserScore('小森美果',0);
$ranking->setUserScore('鬼頭桃菜',0);
$ranking->setUserScore('佐藤聖羅',0);
$ranking->setUserScore('向田茉夏',0);
$ranking->setUserScore('小柳有沙',0);
$ranking->setUserScore('仲川遥香',0);
$ranking->setUserScore('荻野利沙',0);
$ranking->setUserScore('大脇有紗',0);
$ranking->setUserScore('藤本美月',0);
$ranking->setUserScore('岸野里香',0);
$ranking->setUserScore('山本ひとみ',0);
$ranking->setUserScore('板野友美',0);
$ranking->setUserScore('河西智美',0);
$ranking->setUserScore('仁藤萌乃',0);
$ranking->setUserScore('秋元才加',0);
$ranking->setUserScore('桑原みずき',0);
$ranking->setUserScore('高田志織',0);
$ranking->setUserScore('平松可奈子',0);
$ranking->setUserScore('矢神久美',0);
$ranking->setUserScore('赤枝里々奈',0);
$ranking->setUserScore('小木曽汐莉',0);
$ranking->setUserScore('秦佐和子',0);
$ranking->setUserScore('上野圭澄',0);
$ranking->setUserScore('原望奈美',0);
$ranking->setUserScore('小林絵未梨',0);
$ranking->setUserScore('篠原栞那',0);
$ranking->setUserScore('福本愛菜',0);
$ranking->setUserScore('前田敦子',0);
$ranking->setUserScore('奥真奈美',0);
$ranking->setUserScore('小野恵令奈',0);
$ranking->setUserScore('佐藤夏希',0);
$ranking->setUserScore('増田有華',0);
$ranking->setUserScore('仲谷明香',0);
$ranking->setUserScore('米沢瑠美',0);
$ranking->setUserScore('平田璃香子',0);
 */


if (isset($_REQUEST['mode']) && $_REQUEST['mode']  == 'add') {
    $ranking->setUserScore($_REQUEST['text'], -1);
} elseif (isset($_REQUEST['mode']) && $_REQUEST['mode']  == 'increment') {
    $ranking->incrementScore($_REQUEST['text'], -1);
}

$i = 1;
foreach ($ranking->getRange(0, -1, true) as $name => $value) {
    print sprintf('<tr><td>%d 位</td><td>%s</td><td>%d<td><form method="post" action="?mode=increment"><input type="hidden" name="text" value="%s"><button type="submit" class="btn btn-primary">投票</button></form></td></tr>', $i++, $name, abs($value), $name);
    print "\n";
}


?>
</table>




<form method="post" action="?mode=add">
  <fieldset>
    <legend>追加</legend>
    <label>名前</label>
    <input name="text" type="text" placeholder="前田敦子">
    <button type="submit" class="btn">追加</button>
  </fieldset>
</form>


<h2>メモ</h2>
<a href="http://matsu.teraren.com/blog/2013/04/28/redis-ranking-api/">Redis-Ranking</a>を使って構築しています。


    </div> <!-- /container -->

    <!-- Le javascript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="components/bootstrap/docs/assets/js/jquery.js"></script>
    <script src="components/bootstrap/docs/assets/js/bootstrap-transition.js"></script>
    <script src="components/bootstrap/docs/assets/js/bootstrap-alert.js"></script>
    <script src="components/bootstrap/docs/assets/js/bootstrap-modal.js"></script>
    <script src="components/bootstrap/docs/assets/js/bootstrap-dropdown.js"></script>
    <script src="components/bootstrap/docs/assets/js/bootstrap-scrollspy.js"></script>
    <script src="components/bootstrap/docs/assets/js/bootstrap-tab.js"></script>
    <script src="components/bootstrap/docs/assets/js/bootstrap-tooltip.js"></script>
    <script src="components/bootstrap/docs/assets/js/bootstrap-popover.js"></script>
    <script src="components/bootstrap/docs/assets/js/bootstrap-button.js"></script>
    <script src="components/bootstrap/docs/assets/js/bootstrap-collapse.js"></script>
    <script src="components/bootstrap/docs/assets/js/bootstrap-carousel.js"></script>
    <script src="components/bootstrap/docs/assets/js/bootstrap-typeahead.js"></script>

  </body>
</html>


