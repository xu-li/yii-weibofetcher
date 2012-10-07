<?php
include_once(dirname(__FILE__) . '/OAuthApi.php');

/**
 * Sina Weibo Api Class
 * 
 * @link http://open.weibo.com/wiki/%E9%A6%96%E9%A1%B5
 */
class SinaWBApi extends OAuthApi
{
  protected $uid = '';

  /**
   * Constructor
   */
  public function __construct($sns_options)
  {
    parent::__construct($sns_options);

    $this->setAuthorizationEndpoint("https://api.weibo.com/oauth2/authorize");
    $this->setTokenEndpoint("https://api.weibo.com/oauth2/access_token");
    $this->setApiEndpoint("https://api.weibo.com/2");
  }

  /**
   * Get the uid
   *
   * @return string 
   */
  public function getUid()
  {
    return $this->uid;
  }

  /**
   * @inheritDoc
   */
  protected function afterGetAccessToken($response)
  {
    parent::afterGetAccessToken($response);

    $this->uid = $response['uid'];
  }
}
