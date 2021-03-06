<?php
Yii::import("application.modules.weibofetcher.fetchers.BaseSocialWBFetcher");

/**
 * Tencent Weibo Fetcher
 */
class TencentWBFetcher extends BaseSocialWBFetcher
{
  /**
   * @inheritDoc
   */
  protected function getNameOfMentioned()
  {
    // get the user info
    $response = $this->api->api('user/info');
    return isset($response['data']['name']) ? $response['data']['name'] : '';
  }

  /**
   * @inheritDoc
   */
  protected function fetchByMention($limit = 30, $last_id = 0, $last_weibo_time = '')
  {
    return $this->fetchByTopic('', $limit, $last_id, $last_weibo_time);
  }

  /**
   * @inheritDoc
   */
  protected function fetchByTopic($topic, $limit = 30, $last_id = 0, $last_weibo_time = '')
  {
    $result = array();
    $params = array();

    // check if fetch the '@' or hashtag
    $is_fetching_at = empty($topic);
    $api = $is_fetching_at ? 'statuses/mentions_timeline' : 'statuses/ht_timeline_ext';
    $last_id_key = $is_fetching_at ? 'lastid' : 'tweetid';
    $page_time_key = $is_fetching_at ? 'pagetime' : 'time';

    // add the topic parameter
    if (!$is_fetching_at)
    {
      $params['httext'] = $topic;
    }

    // if last_id is empty, then it will default to looking backward
    // otherwise, it depends on whether limit is negtive or not
    $is_looking_forward = empty($last_id) ? FALSE : ($limit > 0);

    // how many weibos we need
    $left = abs($limit);

    if ($last_id != 0)
    {
      $params['pageflag'] = $is_looking_forward ? 2 : 1;
      $params[$last_id_key] = $last_id;
      $params[$page_time_key] = $last_weibo_time;
    }

    do
    {
      $params['reqnum'] = $left > 70 ? 70 : $left;
      $params['reqnum'] = 1;

      $response = $this->api->api($api, $params, 'GET');

      // no more weibos
      if (empty($response['data']) || empty($response['data']['info']))
      {
        break;
      }

      for ($i = 0, $l = count($response['data']['info']); $i < $l; ++$i)
      {
        // use the first weibo if it's looking forward
        // otherwise use the last weibo
        $status = $response['data']['info'][$i];
        if (($is_looking_forward && $i == 0)
          || (!$is_looking_forward && $i == $l - 1))
        {
          $params[$last_id_key] = $status['id'];
          $params[$page_time_key] = $status['timestamp'];
          $params['pageflag'] = $is_looking_forward ? 2 : 1;
        }

        if (!empty($status) && !empty($status['id']))
        {
          $result[] = $status;
          $left--;
        }
      }

    } while ($left > 0);

    return $result;
  }
}
