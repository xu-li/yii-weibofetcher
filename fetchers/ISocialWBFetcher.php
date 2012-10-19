<?php

/**
 * Interface ISocialWBFetcher
 */
interface ISocialWBFetcher
{
  /**
   * Fetch the weibos
   *
   * @param string $keyword A string starts with "@" or "#".
   * @param int $limit How many to fetch, use negtive values for fetching older weibos.
   * @param string $last_id From which weibo, should we start
   * @param int $last_weibo_time From when, should we start
   * @return array|FALSE FALSE if something went wrong
   */
  function fetch($keyword = '@', $limit = 50, $last_id = 0, $last_weibo_time = '');
}
