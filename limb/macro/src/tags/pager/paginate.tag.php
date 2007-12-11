<?php
/*
 * Limb PHP Framework
 *
 * @link http://limb-project.com 
 * @copyright  Copyright &copy; 2004-2007 BIT(http://bit-creative.com)
 * @license    LGPL http://www.gnu.org/copyleft/lesser.html 
 */

/**
 * Applies pager to iterator (so called "pagination")
 * @tag paginate
 * @req_attributes iterator
 * @package macro
 * @version $Id$
 */
class lmbMacroPaginateTag extends lmbMacroTag
{
  protected function _generateContent($code)
  {
    $iterator = $this->get('iterator');
    
    if($this->has('pager'))
    {
      if(!$pager_tag = $this->parent->findUpChild($this->get('pager')))
        $this->raise('Can\'t find pager by "pager" attribute in {{paginate}} tag');
      
      $pager = $pager_tag->getPagerVar();
      
      if($this->has('limit'))
        $code->writePhp("{$pager}->setItemsPerPage({$this->get('limit')});\n");
      
      $code->writePhp("{$pager}->setTotalItems({$iterator}->count());\n");
      $code->writePhp("{$pager}->prepare();\n");
      $offset = $code->generateVar();
      $code->writePhp("{$offset} = {$pager}->getCurrentPageBeginItem();\n");
      $code->writePhp("if({$offset} > 0) {$offset} = {$offset} - 1;\n");
      $code->writePhp("{$iterator}->paginate({$offset}, {$pager}->getItemsPerPage());\n");
      return;
    }
    elseif($this->has('offset'))
    {
      if(!$this->has('limit'))
        $this->raise('"limit" attribute for {{paginate}} is required if "offset" is given');
      
      $code->writePhp("{$iterator}->paginate({$this->get('offset')},{$this->get('limit')});\n");
      return;
    }
    elseif($this->has('limit'))
    {
      $code->writePhp("{$iterator}->paginate(0,{$this->get('limit')});\n");
      return;
    }
  }
}


