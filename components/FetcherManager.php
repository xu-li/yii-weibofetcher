<?php
/**
 * The fetcher manager component
 *
 * How to use
 * 1. configuration
 * $configs = array(
 *   'PLATFORM_KEY' => array(
 *     'oauth' => Used to initialize the api class for FETCHER
 *     'fetcher' => Fetcher Class, used to fetch the Weibos from different platform
 *     'router' => Router Class, used to process the weibos, e.g. saving to db
 *     'keyword' => The term for seaching, e.g. "#hello" for hashtag, "" for mentions
 *     'limit' => How many weibos should we fetch
 *   )
 * )
 *
 * 2. Usage
 * $manger->fetch('PLATFORM_KEY')
 */
class FetcherManager extends CApplicationComponent
{
  public $configs = array(
    'sina' => array(
      'oauth' => array(
        'class'=> 'application.modules.weibofetcher.vendor.OAuthApi.SinaWBApi',
        'client_id' => '51885333',
        'client_secret' => 'c1b238a2f5ed43c177014fd6bcc76ee4',
        'access_token' => '2.00oaUhFD0NkhVD216236264dKJQr3E'
      ),
      'fetcher' => 'application.modules.weibofetcher.fetchers.SinaWBFetcher',
      'router' => array(
      
      ),
      'keyword' => '', // empty for fetching "@" statuses
      'limit' => 100
    ),
    'tencent' => array(
      'oauth' => array(
        'class'=> 'application.modules.weibofetcher.vendor.OAuthApi.TencentWBApi',
        'client_id' => '801245460',
        'client_secret' => 'd12b828fc77692f9440bb09e77a455fe',
        'access_token' => '4bfe6b3859567565ce28c27040d1e301',
        'openid' => '608AAE7A96E502100BCB768D26DD284F'
      ),
      'fetcher' => 'application.modules.weibofetcher.fetchers.TencentWBFetcher',
      'router' => array(
        'class' => 'application.modules.weibofetcher.routers.SimpleDBWBRouter',
        'db' => 'db',
        'fields' => array(
          'keyword' => 'KEYWORD',
          'sns' => 'PLATFORM',
          'sns_id' => 'id',
          'source' => 'source.text',
          'fetched_at' => 'NOW'
        ),
        'db_table' => 'feeds'
      ),
      'keyword' => '#hello', // empty for fetching "@" statuses
      'limit' => 10
    ),
  );

  /**
   * Do the fetch
   *
   * @param string $platform
   */
  public function fetch($platform)
  {
    if (!isset($this->configs[$platform]))
    {
      Yii::log($platform . " not suppored yet.", "info");
      return ;
    }

    $config = $this->configs[$platform];

    // setup api
    $api = Yii::import($config['oauth']['class']);
    $api = new $api(array(
      'client_id' => $config['oauth']['client_id'],
      'client_secret' => $config['oauth']['client_secret']
    ));
    $api->setAccessToken($config['oauth']['access_token']);
    if ($platform == 'tencent')
    {
      $api->setOpenId($config['oauth']['openid']);
    }

    // setup fetcher
    $fetcher = Yii::import($config['fetcher']);
    $fetcher = new $fetcher($api);
    $data = $fetcher->fetch($config['keyword'], $config['limit']);

    if (empty($config['router']))
    {
      return $data;
    }

    // setup router
    return YiiBase::createComponent($config['router'])->save($platform, $data);
  }
}
