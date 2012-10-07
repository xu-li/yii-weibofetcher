<?php
/**
 * The fetch command
 *
 * Usage: 
 * yiic COMMAND_NAME --path=PATH_TO_CONFIG_FILE
 * yiic COMMAND_NAME --num=10 // fetch 10 latest weibos
 * yiic COMMAND_NAME --table=weibos // save to weibos table
 */
class FetchCommand extends CConsoleCommand
{
  const LOG_CATEGORY = 'application.modules.weibofetcher.FetchCommand';

  /**
   * Default configuration file path
   */
  public $defaultPath;

  /**
   * default action
   *
   * @param string $path Path of the configuration file
   * @param int $num How many weibos to fetch
   * @param string $table Which db table should we store
   */
  public function actionIndex($path = '', $num = 0, $table = '')
  {
    $path = empty($path) ? $this->defaultPath : $path;

    if (substr($path, 0, -3) != 'php')
    {
      $path = Yii::getPathOfAlias($path) . '.php';
    }

    if (!is_file($path))
    {
      Yii::log("It's not a reqular file at " . $path, 'error', self::LOG_CATEGORY);
      return ;
    }

    // check if it's running
    $status_file = Yii::getPathOfAlias('application.runtime');
    $status_file .= "/" . md5($path) . ".lock";
    if (file_exists($status_file))
    {
      Yii::log("It's running, " . $path, 'info', self::LOG_CATEGORY);
      return ;
    }

    // create the lock file
    if (FALSE === touch($status_file))
    {
      Yii::log("Failed to create lock file at " . $status_file, 'error', self::LOG_CATEGORY);
      return ;
    }

    // get the config
    $config = require_once($path);

    // override
    if (!empty($num))
    {
      $config['num'] = $num;
    }

    if (!empty($table))
    {
      $config['db_table'] = $table;
    }

    // check the config
    if (!$this->isFetchConfigValid($config))
    {
      Yii::log("Config file is not valid, " . $path, 'error', self::LOG_CATEGORY);

      // delete the lock file
      @unlink($status_file);
      return ;
    }

    // initialize the fetcher
    $cls = Yii::import($config['class']);
    $fetcher = new $cls($config['options']);

    // fetch
    $num = !empty($config['num']) ? intval($config['num']) : 100;
    $weibos = $fetcher->fetch($config['keyword'], $num);

    // save it 
    if ($weibos)
    {
      $db = Yii::app()->{$config['db_component']};
      $chunks = array_chunk($weibos, 100);

      $columns = array_keys(get_class_vars('SocialWBVO'));
      $non_quote_columns = array('forwards', 'comments', 'created_at', 'fetched_at');
      $sql_prefix = "INSERT IGNORE INTO %s (%s) VALUES ";
      $sql_prefix = sprintf($sql_prefix, $config['db_table'], implode(',', $columns));
      foreach ($chunks as $chunk)
      {
        $sql = '';
        foreach ($chunk as $row)
        {
          $values = array();
          foreach ($columns as $column)
          {
            if (in_array($column, $non_quote_columns))
            {
              $values[] = empty($row->$column) ? 0 : $row->$column;
            }
            else
            {
              $values[] = empty($row->$column) ? "''" : $db->quoteValue($row->$column);
            }
          }
          $sql .= "(" . implode(',', $values) . "),";
        }

        if (!empty($sql))
        {
          $db->createCommand($sql_prefix . substr($sql, 0, -1))->execute();
        }
      }
    }

    // delete the lock file
    @unlink($status_file);
  }

  /**
   * Check if the config is valid
   *
   * @return bool
   */
  protected function isFetchConfigValid($config)
  {
    if (empty($config))
    {
      return FALSE;
    }

    foreach (array('class', 'platform', 'options', 'keyword', 'db_component', 'db_table') as $field)
    {
      if (!isset($config[$field]))
      {
        return FALSE;
      }
    }

    return TRUE;
  }
}
