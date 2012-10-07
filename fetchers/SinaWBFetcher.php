<?php
Yii::import("application.modules.weibofetcher.fetchers.BaseSocialWBFetcher");
Yii::import("application.modules.weibofetcher.vendor.OAuthApi.SinaWBApi");

/**
 * Sina Weibo Fetcher
 */
class SinaWBFetcher extends BaseSocialWBFetcher
{
  /**
   * The api object
   */
  protected $api;

  protected $statusToVOMapping = array(
    'weibo_id' => 'idstr',
    'author_id' => 'user.idstr',
    'author_name' => 'user.name',
    'author_profile_image' => 'user.profile_image_url',
    'content' => 'text',
    'thumb' => 'thumbnail_pic',
    'original_image' => 'original_pic',
    'forwards' => 'reposts_count',
    'comments' => 'comments_count',
    'created_at' => 'created_at'
  );

  /**
   * Constructor
   */
  public function __construct($oauth_configs)
  {
    $this->api = new SinaWBApi($oauth_configs);
    if (!empty($oauth_configs['access_token']))
    {
      $this->api->setAccessToken($oauth_configs['access_token']);
    }
  }

  /**
   * @inheritDoc
   */
  protected function getNameOfMentioned()
  {
    // get the user id
    $response = $this->api->api('account/get_uid.json');
    $uid = isset($response['uid']) ? $response['uid'] : 0;

    // get the name
    $response = $this->api->api('users/show.json', array('uid' => $uid));
    return isset($response['name']) ? $response['name'] : '';
  }

  /**
   * @inheritDoc
   */
  protected function fetchByMention($limit = 30, $last_id = 0, $last_weibo_time = '')
  {
    $result = array();
    $is_looking_forward = $limit > 0;
    $left = abs($limit);

    $params = array();
    if ($last_id != 0)
    {
      if ($is_looking_forward)
      {
        $params['since_id'] = $last_id;
      }
      else
      {
        $params['max_id'] = $last_id;
      }
    }

    $page = 1;
    do
    {
      $params['page'] = $page;
      $params['count'] = $left > 200 ? 200 : $left;

      $response = $this->api->api('statuses/mentions.json', $params, 'GET');

      // no more weibos
      if (empty($response['statuses']))
      {
        break;
      }

      foreach ($response['statuses'] as $status)
      {
        if (!empty($status) && !empty($status['user']) && !empty($status['user']['id']))
        {
          $result[] = $status;
          $left--;
        }
      }

      $page++;
    } while ($left > 0);

    return $result;
  }

  /**
   * @inheritDoc
   */
  protected function fetchByTopic($topic, $limit = 30, $last_id = 0, $last_weibo_time = '')
  {
    throw new Exception("Not implemented");
  }

  /**
   * @inheritDoc
   */
  protected function convertToWeiboVO($row)
  {
    $vo = new SocialWBVO($row, $this->statusToVOMapping);
    $vo->platform = 'sina';
    if (isset($row['source']))
    {
      $vo->source_link = preg_replace('!.*href=[\'"]?([^\'" ]*).*!', '$1', $row['source']);
      $vo->source_text = strip_tags($row['source']);
    }
    $vo->created_at = strtotime($vo->created_at);

    return $vo;
  }
}
