<?php
if (!function_exists('curl_init'))
{
  throw new Exception('CURL PHP extension is required.');
}

/**
 * A curl request sender implementation
 */
class OAuthRequestSender implements IOAuthRequestSender
{
  /**
   * @inheritDoc
   */
  public function sendRequest($url, $params = array(), $method = 'POST', $headers = array())
  {
    $opts = array();

    // disable the 'Expect: 100-continue' behaviour. This causes CURL to wait
    // for 2 seconds if the server does not support this header.
    $headers[] = 'Expect:';
    $opts[CURLOPT_HTTPHEADER] = $headers;

    if ($method != 'GET')
    {
      $opts[CURLOPT_POST] = TRUE;

      // check if there is a '@'
      $has_file = FALSE;
      foreach ($params as $k => $value)
      {
        $file_name = substr($value, 0, 1);
        if (substr($value, 0, 1) === '@' && file_exists($file_name))
        {
          $has_file = TRUE;
          break;
        }
      }

      $opts[CURLOPT_POSTFIELDS] = $has_file ? $params : http_build_query($params);
    }
    else
    {
      $url .= (strpos($url, '?') === FALSE ? '?' : '&') . http_build_query($params);
    }

    $opts[CURLOPT_CONNECTTIMEOUT] = 10;
    $opts[CURLOPT_RETURNTRANSFER] = TRUE;
    $opts[CURLOPT_TIMEOUT] = 60;
    $opts[CURLOPT_USERAGENT] = 'OAuthApi';
    $opts[CURLOPT_URL] = $url;
    $opts[CURLOPT_SSL_VERIFYPEER] = FALSE;

    // send request
    $ch = curl_init();
    curl_setopt_array($ch, $opts);
    $result = curl_exec($ch);

    // get the information of the request if no error
    if (!curl_errno($ch))
    {
      $result = array(curl_getinfo($ch, CURLINFO_HTTP_CODE), $result);
    }
    else
    {
      $result = array(0, curl_error($ch));
    }

    // close it
    curl_close($ch);

    return $result;
  }
}
