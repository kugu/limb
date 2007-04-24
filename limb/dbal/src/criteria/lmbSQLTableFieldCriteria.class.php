<?php
/**
 * Limb Web Application Framework
 *
 * @link http://limb-project.com
 *
 * @copyright  Copyright &copy; 2004-2007 BIT
 * @license    LGPL http://www.gnu.org/copyleft/lesser.html
 * @version    $Id: lmbSQLTableFieldCriteria.class.php 5767 2007-04-24 12:02:11Z pachanga $
 * @package    dbal
 */
lmb_require('limb/dbal/src/criteria/lmbSQLFieldCriteria.class.php');

class lmbSQLTableFieldCriteria extends lmbSQLFieldCriteria
{
  protected $table;

  function __construct($raw_column, $value, $comparison = null)
  {
    @list($table, $column) = explode('.', $raw_column);

    if($column === null)
      throw new lmbException('Column must contain table name', array('column' => $raw_column));

    parent :: __construct($raw_column, $value, $comparison);

    $this->table = $table;
  }

  function getTable()
  {
    return $this->table;
  }
}

?>
