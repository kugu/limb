<?php
/*
 * Limb PHP Framework
 *
 * @link http://limb-project.com
 * @copyright  Copyright &copy; 2004-2012 BIT(http://bit-creative.com)
 * @license    LGPL http://www.gnu.org/copyleft/lesser.html
 */
lmb_require('limb/i18n/src/translation/lmbI18nScanner.class.php');
lmb_require('limb/i18n/src/translation/lmbI18nDictionary.class.php');
lmb_require('limb/fs/src/lmbFs.class.php');

/**
 * @desc Init dictionary in folder PROJECT_DIR/i18n/translations
 * @param locale Locale name
 * @param domain Domain name ('default' by default)
 * @example limb.php i18n_init -D locale=ru_RU [-D domain=default]
 */
function task_i18n_init($args = array())
{
  $locale = taskman_propor('locale', null);
  $domain = taskman_propor('domain', 'default');
  if(!$locale || !$domain)
  {
    taskman_sysmsg("Usage: limb.php i18n_init -D locale=ru_RU [-D domain=default]");
    exit(1);
  }

  $backend = lmbToolkit::instance()->createDictionaryBackend(taskman_prop('PROJECT_DIR').'/i18n/translations');
  $backend->create($locale, $domain);

  taskman_msg("Dictionary created\n");
}

/**
 * @desc Scan specified directory for new messages
 * @alias i18n_ut
 * @param locale Locale name
 * @param domain Domain name ('default' by default)
 * @param scan Directory to scan
 * @example limb.php i18n_init -D locale=ru_RU -D scan=dir/to/scan [-D domain=default]
 */
function task_i18n_scan($args = array())
{
  $locale = taskman_propor('locale', null);
  $domain = taskman_propor('domain', 'default');
  $scan_dir = taskman_propor('scan', null);
  if(!$locale || !$scan_dir)
  {
    taskman_sysmsg("Usage: limb.php i18n_init -D locale=ru_RU -D scan=dir/to/scan [-D domain=default]");
    exit(1);
  }

  if(!$output_dir = realpath(taskman_prop('PROJECT_DIR').'/i18n/translations'))
  {
    taskman_sysmsg('Output directory is not valid');
    exit(1);
  }

  $backend = lmbToolkit::instance()->getDictionaryBackend();
  try
  {
    $old_dictionary = $backend->load($locale, $domain);
  }
  catch(lmbFileNotFoundException $e)
  {
    $backend->create($locale, $domain);
    $old_dictionary = new lmbI18nDictionary();
  }

  $scanner = new lmbI18nScanner();
  $scanner->scan($scan_dir);
  $new_dictionary = $scanner->getDictionary($domain);

  $backend->save($locale, $domain, $new_dictionary->merge($old_dictionary));
}