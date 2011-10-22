<?php
/*
 * Limb PHP Framework
 *
 * @link http://limb-project.com
 * @copyright  Copyright &copy; 2004-2012 BIT(http://bit-creative.com)
 * @license    LGPL http://www.gnu.org/copyleft/lesser.html
 */
lmb_require('limb/i18n/src/translation/lmbI18nScanner.class.php');

class lmbI18nScannerTest extends UnitTestCase
{
  function setUp()
  {
    lmbFs :: mkdir(lmb_var_dir());
  }

  function tearDown()
  {
    lmbFs :: rm(lmb_var_dir());
  }

  function testGetFoundFiles()
  {
    lmbFs :: safeWrite(lmb_var_dir().'file.html', '');
    lmbFs :: safeWrite(lmb_var_dir().'file.php', '');
    lmbFs :: safeWrite(lmb_var_dir().'file.phtml', '');
    lmbFs :: safeWrite(lmb_var_dir().'file.wrong', '');

    $scanner = new lmbI18nScanner();
    $scanner->scan(lmb_var_dir());

    $files = $scanner->getFoundFiles();
    $this->assertEqual(3, count($files));
    $this->assertTrue(in_array(lmb_var_dir().'file.html', $files));
    $this->assertTrue(in_array(lmb_var_dir().'file.php', $files));
    $this->assertTrue(in_array(lmb_var_dir().'file.phtml', $files));
  }

  function testGetMessages_macro_tag()
  {
    lmbFs :: safeWrite(lmb_var_dir().'file.phtml', '{{i18n text="foo"}}{{i18n text="bar" domain=\'domain1\'}}{{i18n text=\'baz\' domain="domain2"}}');
    $scanner = new lmbI18nScanner();
    $scanner->scan(lmb_var_dir());

    $this->assertEqual(new lmbI18nDictionary(array('foo' => '')), $scanner->getDictionary());
    $this->assertEqual(new lmbI18nDictionary(array('bar' => '')), $scanner->getDictionary('domain1'));
    $this->assertEqual(new lmbI18nDictionary(array('baz' => '')), $scanner->getDictionary('domain2'));
  }

  function testGetMessages_function()
  {
    $content =<<<EOD
lmb_i18n("foo")
lmb_i18n("bar","domain1")
lmb_i18n("baz {arg1}", \$var1)
lmb_i18n("giz {arg2}", \$var2, "domain2")
EOD;
    lmbFs :: safeWrite(lmb_var_dir().'file.phtml', $content);
    $scanner = new lmbI18nScanner();
    $scanner->scan(lmb_var_dir());

    $this->assertEqual(new lmbI18nDictionary(array('foo' => '', 'baz {arg1}' => '')), $scanner->getDictionary());
    $this->assertEqual(new lmbI18nDictionary(array('bar' => '')), $scanner->getDictionary('domain1'));
    $this->assertEqual(new lmbI18nDictionary(array('giz {arg2}' => '')), $scanner->getDictionary('domain2'));
  }
}