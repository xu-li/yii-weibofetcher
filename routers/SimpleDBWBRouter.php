<?php
/**
 * A simple DB weibo Router to save the weibos to db
 *
 * Configuration: 
 * $configs = array(
 *   'db' => string|array|CDbConnection, if it's a string, it will use Yii::app()->STRING,
 *                                       if it's an array, it should be compatible with CDbConnection
 *   'db_table' => string, which table to store the weibos
 *   'fields' => array, mapping from db fields to weibo data, use a dot syntax.e.g. array('name' => 'user.nick')
 *   'batch_size' => int, how many records should we use in one insert command
 * )
 * @see FetcherManager
 */
class SimpleDBWBRouter extends CComponent
{
  /**
   * CDbConnection
   */
  public $db;

  /**
   * Which table should we use
   */
  public $db_table = 'feeds';

  /**
   * Mapping from db fields to weibo keys
   */
  public $fields;

  /**
   * How many records in one insert
   */
  public $batch_size = 30;

  protected $initialized = FALSE;

  /**
   * @inheritDoc
   */
  public function init()
  {
    if ($this->initialized || empty($this->db))
    {
      return ;
    }

    $this->initialized = TRUE;

    if (is_string($this->db))
    {
      // it's an application component attached to CApplication
      $this->db = Yii::app()->{$this->db};
    }
    else if (is_array($this->db))
    {
      // it's a db config array
      $this->db = YiiBase::createComponent($this->db);
    }
  }

  /**
   * Save the data into db
   *
   * @param string $platform
   * @param array $data
   */
  public function save($platform, $data)
  {
    $this->init();

    if (empty($this->db) || empty($this->db_table) || empty($this->fields)
      || empty($data) || empty($data['data']))
    {
      return ;
    }

    $db = $this->db;
    $keyword = $db->quoteValue($data['keyword']);
    $platform = $db->quoteValue($platform);
    $fields = $this->fields;
    $columns = array_keys($fields);
    $sql_prefix = "INSERT IGNORE INTO %s (%s) VALUES ";
    $sql_prefix = sprintf($sql_prefix, $this->db_table, implode(',', $columns));
    $chunks = array_chunk($data['data'], $this->batch_size);

    foreach ($chunks as $chunk)
    {
      $sql = '';
      foreach ($chunk as $row)
      {
        $values = array();
        foreach ($fields as $data_field)
        {
          $upper = strtoupper($data_field);
          if ($upper === 'PLATFORM')
          {
            $value = $platform;
          }
          else if ($upper === 'KEYWORD')
          {
            $value = $keyword;
          }
          else if ($upper === 'NOW')
          {
            $value = time();
          }
          else
          {
            $value = $this->getNestedVar($row, $data_field);
            if ($value === NULL)
            {
              $value = 'NULL';
            }
            else if (is_string($value))
            {
              $value = $db->quoteValue($value);
            }
          }

          $values[] = $value;
        }
        $sql .= "(" . implode(',', $values) . "),";
      }

      if (!empty($sql))
      {
        $db->createCommand($sql_prefix . substr($sql, 0, -1))->execute();
      }
    }
  }

  /**
   * Get the nexted var using a dot syntax
   *
   * @param array $context
   * @param string $name
   * @param mixed $default
   */
  protected function getNestedVar($context, $name, $default = NULL)
  {
    $pieces = explode('.', $name);
    foreach ($pieces as $piece)
    {
      if (!is_array($context) || !array_key_exists($piece, $context))
      {
        return $default;
      }

      $context = &$context[$piece];
    }

    return $context;
  }

}
