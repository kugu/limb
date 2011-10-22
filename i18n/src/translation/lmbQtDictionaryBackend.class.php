<?php
/*
 * Limb PHP Framework
 *
 * @link http://limb-project.com
 * @copyright  Copyright &copy; 2004-2009 BIT(http://bit-creative.com)
 * @license    LGPL http://www.gnu.org/copyleft/lesser.html
 */
lmb_require('limb/i18n/src/translation/lmbI18NDictionary.class.php');
lmb_require('limb/fs/src/exception/lmbFileNotFoundException.class.php');
lmb_require('limb/fs/src/lmbFs.class.php');

/**
 * class lmbQtDictionaryBackend.
 *
 * @package i18n
 * @version $Id$
 */
class lmbQtDictionaryBackend //extends lmbDictionaryBackend ???
{
  protected $search_path = array();
  protected $use_cache = false;
  protected $cache_dir;

  function __construct($search_path = null)
  {
    $this->setSearchPath($search_path);
  }

  function setCacheDir($dir)
  {
    $this->cache_dir = $dir;
  }

  function useCache($flag = true)
  {
    $this->use_cache = $flag;
  }

  function setSearchPath($path)
  {
    $this->search_path = explode(';', $path);
  }

  function create($locale, $domain)
  {
    $file = $this->search_path[0].'/'.$this->_mapToFileName($locale, $domain);
    return $this->saveToFile($file, new lmbI18nDictionary());
  }

  function load($locale, $domain)
  {
    $file = $this->mapToFile($locale, $domain);
    return $this->loadFromFile($file);
  }

  function save($locale, $domain, $dict)
  {
    $file = $this->mapToFile($locale, $domain);
    return $this->saveToFile($file, $dict);
  }

  function loadAll()
  {
    $locator = lmbToolkit :: instance()->getFileLocator($this->search_path, 'i18n');
    $dicts = array();
    $files = $locator->locateAll('*.ts');

    foreach($files as $file)
    {
      list($domain, $locale, ) = explode('.', basename($file));
      $dicts[$locale][$domain] = $this->loadFromFile($file);
    }

    return $dicts;
  }

  function info($locale, $domain)
  {
    $file = $this->mapToFile($locale, $domain);
    return "Qt dictionary contained in '$file', locale '$locale', domain '$domain'";
  }

  function mapToFile($locale, $domain)
  {
    return lmbToolkit :: instance()
      ->findFileByAlias($this->_mapToFileName($locale, $domain), $this->search_path, 'i18n_translations');
  }

  protected function _mapToFileName($locale, $domain = 'default')
  {
    return $domain . '.' . $locale . '.ts';
  }

  function getDOMDocument($dictionary)
  {
    $doc = new DOMDocument('1.0', 'utf-8');
    $doc->formatOutput = true; // pretty printing

    $ts_node = $doc->createElement('TS');
    $doc->appendChild($ts_node);

    $translations = $dictionary->getTranslations();
    $context_node = $doc->createElement('context');

    foreach($translations as $text => $translation)
    {
      $message_node = $doc->createElement('message');
      $text_node = $doc->createElement('source');
      $translation_node = $doc->createElement('translation');

      $text_node->appendChild($doc->createTextNode($text));

      if(empty($translation))
        $translation_node->setAttribute('type', 'unfinished');
      else
        $translation_node->appendChild($doc->createTextNode($translation));

      $message_node->appendChild($text_node);
      $message_node->appendChild($translation_node);

      $context_node->appendChild($message_node);

      $ts_node->appendChild($context_node);
    }
    return $doc;
  }

  function loadFromXML($xml)
  {
    $dictionary = new lmbI18NDictionary();
    $this->_parseXML($dictionary, $xml);
    return $dictionary;
  }

  function loadFromFile($file)
  {
    if(!file_exists($file))
      throw new lmbFileNotFoundException($file, "translations file $file not found");

    $dictionary = new lmbI18nDictionary();

    if(!$this->_loadFromCache($dictionary, $file))
    {
      try
      {
        $this->_parseXML($dictionary, file_get_contents($file));
      }
      catch(lmbException $e)
      {
        throw new lmbException($e->getMessage() . " at file '" . $file . "'");
      }
      $this->_saveToCache($dictionary, $file);
    }
    return $dictionary;
  }

  protected function _parseXML($dictionary, $xml)
  {
    $xml_doc = simplexml_load_string($xml);
    if(false === $xml_doc)
      throw new lmbI18nException('SimpleXML parsing error', array('xml' => $xml));

    foreach($xml_doc->context as $context)
    {
      foreach($context->message as $message)
      {
        if($translation = trim((string)$message->translation))
          $dictionary->add((string)$message->source, $translation);
        else
          $dictionary->add((string)$message->source);
      }
    }
    return true;
  }

  function saveToFile($file, $dictionary)
  {
    $this->getDOMDocument($dictionary)->save($file, LIBXML_NOEMPTYTAG);
  }

  protected function _isFileCachingOn()
  {
    return $this->use_cache && $this->cache_dir;
  }

  protected function _loadFromCache($dictionary, $file)
  {
    if(!$this->_isFileCachingOn())
      return false;

    if(!file_exists($cache = $this->_getCacheFile($file)))
      return false;

    $dictionary->setTranslations(unserialize(file_get_contents($cache)));
    return true;
  }

  protected function _saveToCache($dictionary, $file)
  {
    if(!$this->_isFileCachingOn())
      return;

    $cache = $this->_getCacheFile($file);
    if(!is_dir($dir = dirname($cache)))
      lmbFs::mkdir($dir);
    file_put_contents($this->_getCacheFile($file), serialize($dictionary->getTranslations()), LOCK_EX);
  }

  protected function _getCacheFile($file)
  {
    return $this->cache_dir . '/i18n-qt/' . md5(realpath($file));
  }
}
