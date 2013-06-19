<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty const modifier plugin
 */
function smarty_modifier_const($object, $constName) {
	$class	= new ReflectionClass($object); 
	return	$class->getConstant($constName); 
} 

/* vim: set expandtab: */

?>
