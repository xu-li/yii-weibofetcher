<?php
/**
 * The social weibo VO
 */
class SocialWBVO
{
  /**
   * Which platform, e.g. "sina", "tencent", etc
   */
  public $platform;

  /**
   * Weibo Id, platform-dependent
   */
  public $weibo_id;

  /**
   * Keyword, e.g. "@hello", "#topic", etc
   */
  public $keyword;

  /**
   * Author id, platform-dependent
   */
  public $author_id;

  /**
   * Author name
   */
  public $author_name;

  /**
   * Author profile image
   */
  public $author_profile_image;

  /**
   * Weibo Content, the main body
   */
  public $content;

  /**
   * Thumbnail in the weibo
   */
  public $thumb;

  /**
   * Original image in the weibo
   */
  public $original_image;

  /**
   * Video link in the weibo
   */
  public $video;

  /**
   * Source link, e.g. http://apps.example.com
   */
  public $source_link;

  /**
   * Source text, e.g. "from MyPhone"
   */
  public $source_text;

  /**
   * How many forwards
   */
  public $forwards;

  /**
   * How many comments
   */
  public $comments;

  /**
   * When created
   */
  public $created_at;

  /**
   * When fetched
   */
  public $fetched_at;

  /**
   * Constructor
   *
   * @param array $values The value to be mapped
   * @param array $mapping The mapping table, keys are the attributes of this vo
   */
  public function __construct($values, $mapping = '')
  {
    if (!empty($mapping) && is_array($mapping))
    {
      $loop = $mapping;
      $using_mapping = TRUE;
    }
    else
    {
      $loop = $values;
      $using_mapping = FALSE;
    }

    foreach ($loop as $key => $value)
    {
      if (property_exists($this, $key))
      {
        $value = $using_mapping ? $this->getValueFromArray($value, $values) : $value;
        $this->$key = $value;
      }
    }
  }

  /**
   * Get the value from an array, use '.' for nested array
   *
   * @param string $key
   * @param array $context
   * @return mixed
   */
  protected function getValueFromArray($key, $context)
  {
    $pieces = explode('.', $key);
    foreach ($pieces as $piece)
    {
        if (!is_array($context) || !array_key_exists($piece, $context))
        {
            return '';
        }
        $context = $context[$piece];
    }
    return $context;
  }
}
