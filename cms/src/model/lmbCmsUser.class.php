<?php
/*
 * Limb PHP Framework
 *
 * @link http://limb-project.com
 * @copyright  Copyright &copy; 2004-2007 BIT(http://bit-creative.com)
 * @license    LGPL http://www.gnu.org/copyleft/lesser.html
 */

/**
 * class lmbCmsUser.
 *
 * @package cms
 * @version $Id$
 */

lmb_require("limb/cms/src/model/lmbCmsUserRoles.class.php");

class lmbCmsUser extends lmbActiveRecord
{
  protected $_many_belongs_to = array(
    'user' => array(
      'field' => 'user_id',
      'class' => 'User',
    )
  );

  protected $password;
  protected $_is_logged_in = false;

  const ROLE_NAME_ADMIN = 'admin';
  const ROLE_NAME_EDITOR = 'editor';

  /**
   * @return lmbValidator
   */
  protected function _createValidator()
  {
    $validator = new lmbValidator();
    $validator->addRequiredRule('name', 'Поле "Имя" обязательно для заполнения');
    $validator->addRequiredRule('login', 'Поле "Логин" обязательно для заполнения');
    $validator->addRequiredRule('email', 'Поле "E-mail" обязательно для заполнения');

    lmb_require('limb/cms/src/validation/rule/lmbCmsUserUniqueFieldRule.class.php');
    $validator->addRule(new lmbCmsUserUniqueFieldRule('login', $this));
    $validator->addRule(new lmbCmsUserUniqueFieldRule('email', $this));

    lmb_require('limb/validation/src/rule/lmbEmailRule.class.php');
    $validator->addRule(new lmbEmailRule('email', 'Неверный формат поля "E-mail"'));
    return $validator;
  }

  /**
   * @return lmbValidator
   */
  protected function _createInsertValidator()
  {
    $validator = $this->_createValidator();
    $validator->addRequiredRule('password', 'Поле "Пароль" обязательно для заполнения');

    lmb_require('limb/validation/src/rule/lmbMatchRule.class.php');
    $validator->addRule(new lmbMatchRule('password', 'repeat_password', 'Значения полей "Пароль" и "Подтверждение пароля" не совпадают'));

    return $validator;
  }

  protected function _onBeforeSave()
  {
    if($this->password)
      $this->setHashedPassword($this->getCryptedPassword($this->password));

    if (!$this->user_id)
    {
      $user = new User();
      $user->import($this->export());
      $user->save();
      $this->user_id = $user->id;
    }
  }

  function login($login, $password)
  {
    $criteria = new lmbSQLFieldCriteria('login', $login);
    $user = lmbActiveRecord ::findFirst('lmbCmsUser', array('criteria' => $criteria));

    if($user && $user->isPasswordCorrect($password))
    {
      $this->import($user);
      $this->setIsNew(false);
      $this->setLoggedIn(true);
      return true;
    }
    else
    {
      $this->setLoggedIn(false);
      return false;
    }
  }

  function logout()
  {
    $this->reset();
    $this->setLoggedIn(false);
  }

  function isLoggedIn()
  {
    return $this->_is_logged_in;
  }

  function setLoggedIn($logged_in)
  {
    $this->_is_logged_in = $logged_in;
  }

  function getCryptedPassword($password)
  {
    if(!$this->getCtime())	$this->setCtime(time());
    return sha1('.kO/|b@S@.42'.$this->getCtime().sha1($password));
  }

  function isPasswordCorrect($password)
  {
    return $this->getHashedPassword() == $this->getCryptedPassword($password);
  }

  function generatePassword()
  {
    $alphabet = array(
        array('b','c','d','f','g','h','g','k','l','m','n','p','q','r','s','t','v','w','x','z',
              'B','C','D','F','G','H','G','K','L','M','N','P','Q','R','S','T','V','W','X','Z'),
        array('a','e','i','o','u','y','A','E','I','O','U','Y'),
    );

    $new_password = '';
    for($i = 0; $i < 9 ;$i++)
    {
      $j = $i%2;
      $min_value = 0;
      $max_value = count($alphabet[$j]) - 1;
      $key = rand($min_value, $max_value);
      $new_password .= $alphabet[$j][$key];
    }
    return $new_password;
  }

  function getIsAdmin()
  {
    return $this->getRoleType() == lmbCmsUserRoles :: ADMIN;
  }

  static function getRoleTypeList()
  {
    return array(
      self::ROLE_NAME_ADMIN => 'Administrator',
      self::ROLE_NAME_EDITOR => 'Editor'
    );
  }

}

