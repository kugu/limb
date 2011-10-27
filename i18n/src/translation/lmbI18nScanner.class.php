<?php
/*
 * Limb PHP Framework
 *
 * @link http://limb-project.com
 * @copyright  Copyright &copy; 2004-2009 BIT(http://bit-creative.com)
 * @license    LGPL http://www.gnu.org/copyleft/lesser.html
 */
lmb_require('limb/fs/src/lmbFs.class.php');

/**
 * class lmbI18nScanner.
 *
 * Scan i18n macro tags and lmb_i18n functions call in specified directory
 *
 * @package i18n
 * @version $Id: lmbI18nScanner.class.php 7994 2009-09-21 13:01:14Z idler $
 */
class lmbI18nScanner
{
  protected $found_files = array();
  protected $messages = array();

  function scan($dir)
  {
    $this->scanForFiles($dir);
    foreach($this->found_files as $file)
      $this->searchMessages($file);
  }

  function getFoundFiles()
  {
    return $this->found_files;
  }

  protected function scanForFiles($dir)
  {
     $result = lmbFs :: findRecursive($dir, $types = 'f', $include_regex = '#.(php|html|phtml|js)$#is');
     foreach($result as $name)
       $this->found_files[] = $name;
  }

  function searchMessages($file)
  {
    $content = file_get_contents($file);
    $this->_searchMacroTags($content);
    $this->_searchFunctionCalls($content);
  }

  protected function _searchMacroTags($content)
  {
    $pattern = '#\{\{(__|i18n)[^}]+text=[\'|"]([^\'^"]+)[\'|"]([^}]+domain=[\'|"]([^\'^"]+)[\'|"])?#is';
    preg_match_all($pattern, $content, $matches);
    $texts = $matches[2];
    $domains = $matches[5];
    for($match_num = 0; $match_num < count($texts); $match_num++)
    {
      $domain = $domains[$match_num] ? $domains[$match_num] : 'default';
      if(!isset($this->messages[$domain]))
        $this->messages[$domain] = array();
      $this->messages[$domain][] = $texts[$match_num];
    }
  }

  protected function _searchFunctionCalls($content)
  {
    $pattern = '#lmb_i18n\s*\(\s*([\'|"])(.*?[^\\\\])\1.*?(([\'|"])(.*?)\4)?\)#is';
    preg_match_all($pattern, $content, $matches);
    $texts = $matches[2];
    $domains = $matches[5];
    for($match_num = 0; $match_num < count($texts); $match_num++)
    {
      $domain = $domains[$match_num] ? $domains[$match_num] : 'default';
      if(!isset($this->messages[$domain]))
        $this->messages[$domain] = array();
      $this->messages[$domain][] = $texts[$match_num];
    }
  }

  /**
   * @param string $domain
   * @return lmbI18nDictionary
   */
  function getDictionary($domain = 'default')
  {
    $dict = new lmbI18nDictionary();
    if(!isset($this->messages[$domain]))
      return $dict;
    foreach($this->messages[$domain] as $message)
      $dict->add($message);
    return $dict;
  }

}