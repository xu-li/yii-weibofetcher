<?php
Yii::import("application.modules.weibofetcher.fetchers.ISocialWBFetcher");
Yii::import("application.modules.weibofetcher.models.SocialWBVO");

abstract class BaseSocialWBFetcher implements ISocialWBFetcher
{
  /**
   * @inheritDoc
   */
  public function fetch($keyword = '@', $limit = 30, $last_id = 0, $last_weibo_time = '')
  {

    // defaults to use '@'
    $keyword = empty($keyword) ? '@' : $keyword;

    // check if it's for hashtag or mentions
    $first_char = substr($keyword, 0, 1);

    try
    {
      if ($first_char === '#')
      {
        $data = $this->fetchByTopic(trim($keyword, '#'), $limit, $last_id, $last_weibo_time);
      }
      else
      {
        // get the name of mentioned if it's not passed in
        $keyword = $keyword === '@' ? '@' . $this->getNameOfMentioned() : $keyword;

        // sanitize the keyword
        $keyword = $first_char === '@' ? $keyword : '@' . $keyword;

        $data = $this->fetchByMention($limit, $last_id, $last_weibo_time);
      }

      $result = array();
      if (!empty($data))
      {
        foreach ($data as $row)
        {
          $vo = $this->convertToWeiboVO($row);
          $vo->keyword = $keyword;
          $vo->fetched_at = time();
          $result[] = $vo;
        }
      }

      return $result;
    }
    catch (Exception $err)
    {
      Yii::log($err, 'info', 'ext.SocialWeiboFetcher.SinaWBFetcher');
      return FALSE;
    }
  }

  /**
   * Get the name of the current mentioned user
   *
   * @return string
   */
  abstract protected function getNameOfMentioned();

  /**
   * Get the weibos mentioned to current user, identified by access_token
   *
   * @param int $limit
   * @param string $last_id
   * @param int $last_weibo_time
   * @return array
   */
  abstract protected function fetchByMention($limit = 30, $last_id = 0, $last_weibo_time = '');

  /**
   * Get the weibos by searching the hashtag
   *
   * @param string $topic
   * @param int $limit
   * @param string $last_id
   * @param int $last_weibo_time
   * @return array
   */
  abstract protected function fetchByTopic($topic, $limit = 30, $last_id = 0, $last_weibo_time = '');

  /**
   * Convert a raw weibo to a WeiboVO
   *
   * @param array $row
   * @return SocialWBVO
   */
  abstract protected function convertToWeiboVO($row);
}
