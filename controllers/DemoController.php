<?php
Yii::import("application.modules.weibofetcher.fetchers.SinaWBFetcher");
Yii::import("application.modules.weibofetcher.fetchers.TencentWBFetcher");
define('REDIRECTION_URI', 'http://oauth-api-tester.appspot.com');
class DemoController extends CController
{
  public function actionIndex()
  {
    $manager = Yii::import("application.modules.weibofetcher.components.FetcherManager");

    $manager = new $manager();
    var_dump($manager->fetch('tencent'));

    return ;
    /*
    $fetcher = new SinaWBFetcher(array(
      'client_id' => '51885333',
      'client_secret' => 'c1b238a2f5ed43c177014fd6bcc76ee4',
      'redirect_uri' => REDIRECTION_URI,
      'access_token' => '2.00oaUhFD0NkhVD22804b46c3oBPdbE'
    ));

    */
    $fetcher = new TencentWBFetcher(array(
      'client_id' => '801245460',
      'client_secret' => 'd12b828fc77692f9440bb09e77a455fe',
      'redirect_uri' => REDIRECTION_URI,
      'access_token' => '22e060247791b8095f92158e2eb923d2',
      'open_id' => '608AAE7A96E502100BCB768D26DD284F'
    ));

    // var_dump($fetcher->fetch('@adfadsf', -2, '136683052170622', 1347438987));
    var_dump($fetcher->fetch('#hello', 5));
  }
}
