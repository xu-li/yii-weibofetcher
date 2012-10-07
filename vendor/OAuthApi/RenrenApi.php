<?php
include_once(dirname(__FILE__) . '/OAuthApi.php');

/**
 * Renren Api Class
 * 
 * @link http://wiki.dev.renren.com/wiki/%E9%A6%96%E9%A1%B5
 */
class RenrenApi extends OAuthApi
{
  public $user;

  /**
   * Constructor
   */
  public function __construct($sns_options)
  {
    parent::__construct($sns_options);

    $this->setAuthorizationEndpoint("https://graph.renren.com/oauth/authorize");
    $this->setTokenEndpoint("https://graph.renren.com/oauth/token");
    $this->setApiEndpoint("http://api.renren.com/restserver.do");
  }

  /**
   * @inheritDoc
   */
  protected function afterGetAccessToken($response)
  {
    parent::afterGetAccessToken($response);

    $this->user = $response['user'];
  }

  /**
   * @inheritDoc
   */
  public function api($api, $params = array(), $method = 'GET', $headers = array())
  {
    $method = 'POST';
    $params['method'] = ltrim($api, "/");
    $params['format'] = 'JSON';
    $params['v'] = '1.0';

    return parent::api('', $params, $method, $headers);
  }

  /**
   * @inheritDoc
   * @link http://dev.xiaonei.com/wiki/Calculate_signature
   */
  protected function beforeApiCall(&$params)
  {
    $str_arr = array();
    foreach ($params as $k => $v)
    {
      $str_arr[] = $k . '=' . $v;
    }
    sort($str_arr, SORT_STRING);

    $params['sig'] = md5(implode('', $str_arr) . $this->client_secret);
  }

  /**
   * @inheritDoc
   */
  protected function sendRequest($url, $params = array(), $method = 'GET', $headers = array())
  {

    $response = parent::sendRequest($url, $params, $method, $headers);

    if (isset($response['error_code']) && $response['error_code'] != 0)
    {
      throw new OAuthApiException($url, $response, $response['error_msg'], $response['error_code']);
    }

    return $response;
  }

}
