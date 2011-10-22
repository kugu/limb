<?php
/*
 * Limb PHP Framework
 *
 * @link http://limb-project.com
 * @copyright  Copyright &copy; 2004-2009 BIT(http://bit-creative.com)
 * @license    LGPL http://www.gnu.org/copyleft/lesser.html
 */
lmb_require('limb/toolkit/src/lmbAbstractTools.class.php');
lmb_require('limb/i18n/src/locale/lmbLocale.class.php');
lmb_require('limb/i18n/src/translation/lmbI18nDictionary.class.php');
lmb_require('limb/i18n/src/translation/lmbQtDictionaryBackend.class.php');

lmb_env_setor('LIMB_LOCALE_INCLUDE_PATH', 'i18n/locale;limb/i18n/i18n/locale');
lmb_env_setor('LIMB_TRANSLATIONS_INCLUDE_PATH', 'i18n/translations;limb/*/i18n/translations');

/**
 * class lmbI18NTools.
 *
 * @package i18n
 * @version $Id: lmbI18NTools.class.php 8150 2010-03-26 07:58:18Z Forumsky $
 */
class lmbI18nTools extends lmbAbstractTools
{
  protected $current_locale;
  protected $locale_objects = array();
  protected $dictionaries = array();
  protected $dict_backend;

  function getDictionaryBackend()
  {
    if(!is_object($this->dict_backend))
    {
      $paths = lmb_env_get('LIMB_TRANSLATIONS_INCLUDE_PATH');
      $this->dict_backend = lmbToolkit::instance()->createDictionaryBackend($paths);
    }

    return $this->dict_backend;
  }

  function setDictionaryBackend($backend)
  {
    $this->dict_backend = $backend;
  }

  function createDictionaryBackend($search_parh)
  {
    $dict_backend = new lmbQtDictionaryBackend($search_parh);
    if(lmb_env_get('LIMB_VAR_DIR') && LIMB_APP_PRODUCTION == lmb_app_mode())
    {
      $dict_backend->setCacheDir(lmb_env_get('LIMB_VAR_DIR'));
      $dict_backend->useCache();
    }
    return $dict_backend;
  }

  function getLocale()
  {
    if(!$this->current_locale)
      $this->current_locale = 'en_US';

    return $this->current_locale;
  }

  function setLocale($locale)
  {
    $this->current_locale = $locale;
  }

  function getLocaleObject($locale = null)
  {
    if(!$locale)
      $locale = $this->toolkit->getLocale();

    if(!isset($this->locale_objects[$locale]))
      $this->locale_objects[$locale] = $this->toolkit->createLocaleObject($locale);

    return $this->locale_objects[$locale];
  }

  function addLocaleObject($obj, $locale = null)
  {
    if(!$locale)
      $locale = $obj->getLocaleString();

    $this->locale_objects[$locale] = $obj;
  }

  function createLocaleObject($locale)
  {
    $file = $this->toolkit->findFileByAlias($locale . '.ini', lmb_env_get('LIMB_LOCALE_INCLUDE_PATH'), 'i18n_locale');

    if(lmb_env_has('LIMB_VAR_DIR'))
      return new lmbLocale($locale, new lmbCachedIni($file, lmb_env_get('LIMB_VAR_DIR') . '/locale/'));
    else
      return new lmbLocale($locale, new lmbIni($file));
  }

  function getDictionary($locale, $domain)
  {
    if(!isset($this->dictionaries[$locale . '@' . $domain]))
    {
      $backend = $this->toolkit->getDictionaryBackend();
      $this->dictionaries[$locale . '@' . $domain] = $backend->load($locale, $domain);
    }

    return $this->dictionaries[$locale . '@' . $domain];
  }

  function setDictionary($locale, $domain, $dict)
  {
    $this->dictionaries[$locale . '@' . $domain] = $dict;
  }

  /**
   * Translate text
   *
   * @example $toolkit->translate(“Hello”, “domain”)
   * @example $toolkit->translate(“Hello {arg}”, array('arg' => 'Bob'), “domain”)
   */
  function translate($text, $args_or_domain, $domain)
  {
    $locale = $this->toolkit->getLocale();

    $attributes = null;

    if(is_array($args_or_domain))
      $attributes = $args_or_domain;
    elseif(is_string($args_or_domain))
      $domain = $args_or_domain;

    if($dict = $this->toolkit->getDictionary($locale, $domain))
      return $dict->translate($text, $attributes);
    else
      return $text;
  }
}
