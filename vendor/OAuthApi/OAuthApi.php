<?php
if (!function_exists('json_decode'))
{
  throw new Exception('JSON PHP extension is required.');
}

/**
 * The OAuth Api Class 2.0
 *
 * @author lixu<AthenaLightenedMyPath@gmail.com>
 * @version 0.1
 * @link http://tools.ietf.org/html/draft-ietf-oauth-v2-31
 */
class OAuthApi
{
  /**
   * OAuth client id
   */
  protected $client_id;

  /**
   * OAuth client secret
   */
  protected $client_secret;

  /**
   * OAuth redirect uri
   */
  protected $redirect_uri;

  /**
   * OAuth access token
   */
  protected $access_token;

  /**
   * OAuth refresh token
   * @unimplemented
   */
  protected $refresh_token;

  /**
   * OAuth expires in
   * @unimplemented
   */
  protected $expires_in;

  /**
   * Authorization endpoint
   *
   * @link http://tools.ietf.org/html/draft-ietf-oauth-v2-31#section-3.1
   */
  protected $authorization_endpoint;

  /**
   * Token endpoint
   *
   * @link http://tools.ietf.org/html/draft-ietf-oauth-v2-31#section-3.2
   */
  protected $token_endpoint;

  /**
   * Api endpoint
   */
  protected $api_endpoint;

  /**
   * The Request sender
   */
  protected $request_sender;

  /**
   * Constructor
   *
   * <code>
   * new CLASS(array('client_id' => YOUR_CLIENT_ID, 'client_secret' => YOUR_CLIENT_SECRET));
   * </code>
   * @param array $oauth_options An array of key-value pairs
   * @param IOAuthRequestSender $request_sender
   */
  public function __construct($oauth_options, $request_sender = NULL)
  {
    if (!isset($oauth_options['client_id']))
    {
      throw new Exception("Client id is required.");
    }

    if (!isset($oauth_options['client_secret']))
    {
      throw new Exception("Client secret is required.");
    }

    $this->client_id = $oauth_options['client_id'];
    $this->client_secret = $oauth_options['client_secret'];
    $this->redirect_uri = empty($oauth_options['redirect_uri']) ? '' : $oauth_options['redirect_uri'];

    if ($request_sender == NULL)
    {
      include_once(dirname(__FILE__) . '/OAuthRequestSender.php');
      $this->request_sender = new OAuthRequestSender();
    }
    else
    {
      $this->request_sender = $request_sender;
    }
  }

  /**
   * Get the login url
   *
   * @param string $scope The permission scope
   * @param string $state 
   * @param string $redirect_uri The redirect url after authentication
   * @return string
   */
  public function getLoginUrl($scope = '', $state = '', $redirect_uri = '')
  {
    $redirect_uri = empty($redirect_uri) ? $this->redirect_uri : $redirect_uri;

    $params = array();
    $params['client_id'] = $this->client_id;
    $params['response_type'] = 'code';
    $params['redirect_uri'] = $redirect_uri;

    if (!empty($state))
    {
      $params['state'] = $state;
    }

    if (!empty($scope))
    {
      $params['scope'] = $scope;
    }

    return $this->authorization_endpoint . "?" . http_build_query($params);
  }

  //////////////////////////////////////////////////////////////////////////
  // Access Tokens
  //////////////////////////////////////////////////////////////////////////

  /**
   * Get the access token using a code from authorization endpoint
   *
   * @param string $code
   * @param string $redirect_uri The uri used in the login url
   * @return string
   */
  public function getAccessToken($code = '', $redirect_uri = '')
  {
    if (!empty($this->access_token) || $code == '')
    {
      return $this->access_token;
    }

    $redirect_uri = empty($redirect_uri) ? $this->redirect_uri : $redirect_uri;

    $params = array();
    $params['client_id'] = $this->client_id;
    $params['client_secret'] = $this->client_secret;
    $params['redirect_uri'] = $redirect_uri;
    $params['grant_type'] = 'authorization_code';
    $params['code'] = $code;

    $response = $this->sendRequest($this->getTokenEndpoint(), $params, 'POST');
    $this->afterGetAccessToken($response);

    return $this->access_token;
  }

  /**
   * Get the access token using name/password pair
   *
   * @link http://tools.ietf.org/html/draft-ietf-oauth-v2-31#section-4.3.2
   * @param string $scope The permission scope
   * @param string $username
   * @param string $password
   * @return string
   */
  public function getAccessTokenAsResourceOwner($scope = '', $username, $password)
  {
    if (!empty($this->access_token) || $code == '')
    {
      // @todo check the expiration time
      return $this->access_token;
    }

    $params = array();
    $params['client_id'] = $this->client_id;
    $params['client_secret'] = $this->client_secret;
    $params['grant_type'] = 'password';
    $params['username'] = $username;
    $params['password'] = $password;

    $response = $this->sendRequest($this->getTokenEndpoint(), $params, 'POST');
    $this->afterGetAccessToken($response);

    return $this->access_token;
  }

  /**
   * Refresh the access token using the refresh_token
   * 
   * @param string $refresh_token 
   * @return string
   */
  public function refreshAccessToken($refresh_token = '')
  {
    $refresh_token = empty($refresh_token) ? $this->refresh_token : $refresh_token;

    $params = array();
    $params['client_id'] = $this->client_id;
    $params['client_secret'] = $this->client_secret;
    $params['grant_type'] = 'refresh_token';
    $params['refresh_token'] = $refresh_token;

    $response = $this->sendRequest($this->getTokenEndpoint(), $params, 'POST');
    $this->afterGetAccessToken($response);

    return $this->access_token;
  }

  /**
   * Send access token request
   *
   * @param array $response
   * @return string
   */
  protected function afterGetAccessToken($response)
  {
    $this->access_token = isset($response['access_token']) ? $response['access_token'] : '';
    $this->refresh_token = isset($response['refresh_token']) ? $response['refresh_token'] : '';
    $this->expires_in = isset($response['expires_in']) ? $response['expires_in'] : -1;

    return $this->access_token;
  }



  /**
   * Set the access token directly
   *
   * @param string $access_token
   */
  public function setAccessToken($access_token)
  {
    $this->access_token = $access_token;
  }

  //////////////////////////////////////////////////////////////////////////
  // Api Call
  //////////////////////////////////////////////////////////////////////////

  /**
   * Call the api
   *
   * @return mixed
   */
  public function api($api, $params = array(), $method = 'GET', $headers = array())
  {
    $access_token = $this->getAccessToken();

    $api = ltrim($api, "/");
    $params['client_id'] = $this->client_id;
    $params['access_token'] = $access_token;

    $this->beforeApiCall($params);

    return $this->sendRequest($this->getApiEndpoint() . $api, $params, $method, $headers);
  }

  /**
   * Before sending the api request, 
   * some implementations may modify the parameters, e.g. calculate signature
   *
   * @param array $params The parameters sent to the api
   */
  protected function beforeApiCall(&$params)
  {
    // do nothing
  }


  //////////////////////////////////////////////////////////////////////////
  // Getter & Setter
  //////////////////////////////////////////////////////////////////////////

  /**
   * Get authorization endpoint
   *
   * @return string
   */
  public function getAuthorizationEndpoint()
  {
    return $this->authorization_endpoint;
  }

  /**
   * Set authorization endpoint
   *
   * @param string $uri
   */
  public function setAuthorizationEndpoint($uri)
  {
    $this->authorization_endpoint = $uri;
  }

  /**
   * Get token endpoint
   *
   * @return string
   */
  public function getTokenEndpoint()
  {
    return $this->token_endpoint;
  }

  /**
   * Set token endpoint
   *
   * @param string $uri
   */
  public function setTokenEndpoint($uri)
  {
    $this->token_endpoint = $uri;
  }

  /**
   * Get api endpoint
   *
   * @return string
   */
  public function getApiEndpoint()
  {
    return $this->api_endpoint;
  }

  /**
   * Set api endpoint
   *
   * @param string $uri
   */
  public function setApiEndpoint($uri)
  {
    $this->api_endpoint = rtrim($uri, "/") . "/";
  }

  /**
   * Set request sender
   *
   * @param IOAuthRequestSender $sender
   */
  public function setRequestSender($sender)
  {
    $this->request_sender = $sender;
  }

  //////////////////////////////////////////////////////////////////////////
  // Requests
  //////////////////////////////////////////////////////////////////////////

  /**
   * Send request
   *
   * @param string $url
   * @param array $params
   * @param string $method
   * @param array $headers
   * @return mixed
   */
  protected function sendRequest($url, $params = array(), $method = 'GET', $headers = array())
  {
    list($code, $response) = $this->request_sender->sendRequest($url, $params, $method, $headers);

    // server error
    if ($code == 0)
    {
      throw new OAuthApiException($url, FALSE, $response);
    }
    else if ($code != 200)
    {
      throw new OAuthApiException($url, $response, "HTTP Error: " . $code);
    }
    else
    {
      // sometimes it's json string, and sometimes it's a pure string
      $decoded = $this->decodeJSONOrQueryString($response);

      // if it fails
      // @link http://tools.ietf.org/html/draft-ietf-oauth-v2-31#section-5.2
      if (isset($decoded['error']))
      {
        if (is_array($decoded['error']))
        {
          $message = $this->getErrorMessage($decoded['error']);
        }
        else
        {
          $message = sprintf("[%s]%s", $decoded['error'], isset($decoded['error_description']) ? $decoded['error_description'] : '');
        }

        throw new OAuthApiException($url ,$response, $message);
      }

      return $decoded;
    }

    return $response;
  }

  //////////////////////////////////////////////////////////////////////////
  // Helpers
  //////////////////////////////////////////////////////////////////////////
  /**
   * Get an array from a json string or query string
   *
   * @param string $str
   * @return array
   */
  protected function decodeJSONOrQueryString($str)
  {
    $decoded = json_decode($str, TRUE);
    if ((version_compare(PHP_VERSION, '5.3.0') >= 0 && json_last_error() !== JSON_ERROR_NONE)
      || $decoded === NULL)
    {
      if (strpos($str, '=') !== FALSE)
      {
        $decoded = array();
        parse_str($str, $decoded);
      }
      else
      {
        // it's just an error message
        $decoded = array('error' => $str);
      }
    }
    return $decoded;
  }

  /**
   * Get the error message from array when it's not a standard OAuth error response
   *
   * @param array $error
   * @return string
   */
  protected function getErrorMessage($error)
  {
    return json_encode($error);
  }
}

/**
 * Interface for the request sender
 */
interface IOAuthRequestSender
{
  /**
   * Send a http request
   *
   * @param string $url
   * @param array $params
   * @param string $method
   * @param array $headers
   * @return array(HTTP_CODE, HTTP_BODY) on Success, array(0, FAIL_REASON) on Failure
   */
  public function sendRequest($url, $params = array(), $method = 'POST', $headers = array());
}

/**
 * The OAuth Exception
 */
class OAuthApiException extends Exception
{
  // The reqeust url
  public $request;

  // The response 
  public $response;

  /**
   * Constructor
   *
   * @param string $request
   * @param mixed $response
   * @param string $message
   * @param int $code
   */
  public function __construct($request, $response, $message = '', $code = 0)
  {
    parent::__construct($message, $code);

    $this->request = $request;
    $this->response = $response;
  }

  /**
   * @inheritDoc
   */
  public function __toString()
  {
    return sprintf("Exception: Failed to request \"%s\", error: %s.", $this->request, $this->getMessage());
  }
}
