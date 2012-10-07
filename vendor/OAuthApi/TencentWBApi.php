<?php
include_once(dirname(__FILE__) . '/OAuthApi.php');

/**
 * Tencent Weibo Api Class
 * 
 * @link http://wiki.open.t.qq.com/index.php/%E9%A6%96%E9%A1%B5
 */
class TencentWBApi extends OAuthApi
{
  /**
   * Tencent openid
   */
  protected $openid;

  /**
   * Tencent openkey
   */
  protected $openkey;

  /**
   * The screen name
   */
  public $name;

  /**
   * The nick name
   */
  public $nick;

  /**
   * Constructor
   */
  public function __construct($sns_options)
  {
    parent::__construct($sns_options);

    $this->setAuthorizationEndpoint("https://open.t.qq.com/cgi-bin/oauth2/authorize");
    $this->setTokenEndpoint("https://open.t.qq.com/cgi-bin/oauth2/access_token");
    $this->setApiEndpoint("http://open.t.qq.com/api");
  }

  /**
   * @inheritDoc
   */
  protected function beforeApiCall(&$params)
  {
    if (empty($this->openid))
    {
      throw new Exception("Openid is required.");
    }

    $params['openid'] = $this->openid;
    $params['format'] = 'json';
    $params['oauth_consumer_key'] = $this->client_id;
    $params['oauth_version'] = '2.a';
    $params['scope'] = 'all';
    unset($params['client_id']);
  }

  /**
   * @inheritDoc
   */
  protected function sendRequest($url, $params = array(), $method = 'GET', $headers = array())
  {

    $response = parent::sendRequest($url, $params, $method, $headers);

    if (isset($response['errcode']) && $response['errcode'] != 0)
    {
      throw new OAuthApiException($url, $response, $response['msg'], $response['errcode']);
    }

    return $response;
  }

  /**
   * @inheritDoc
   */
  protected function afterGetAccessToken($response)
  {
    parent::afterGetAccessToken($response);

    $this->name = $response['name'];
    $this->nick = $response['nick'];
  }

  //////////////////////////////////////////////////////////////////////////
  // Getter & Setter
  //////////////////////////////////////////////////////////////////////////

  /**
   * Set openid and openkey
   *
   * @param string $openid
   * @param string $openkey
   * @link http://wiki.open.t.qq.com/index.php/OAuth2.0%E9%89%B4%E6%9D%83
   */
  public function setOpenIdAndKey($openid, $openkey)
  {
    $this->openid = $openid;
    $this->openkey = $openkey;
  }

  /**
   * Get openid
   *
   * @return string
   */
  public function getOpenId()
  {
    return $this->openid;
  }

  /**
   * Get openkey
   *
   * @return string
   */
  public function getOpenKey()
  {
    return $this->openkey;
  }
}
