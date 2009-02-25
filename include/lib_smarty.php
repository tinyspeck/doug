<?
	#
	# $Id$
	#

	define('SMARTY_DIR', INCLUDE_DIR.'/smarty-2.6.19/');

	require_once(SMARTY_DIR . 'Smarty.class.php');

	$GLOBALS[smarty] = new Smarty();

	$GLOBALS[smarty]->template_dir = APP_DIR.'/templates/';
	$GLOBALS[smarty]->compile_dir  = APP_DIR.'/templates_c/';
	$GLOBALS[smarty]->compile_check = 1;
	$GLOBALS[smarty]->force_compile = 1;

	$GLOBALS[smarty]->assign_by_ref('cfg', $GLOBALS[cfg]);

	function dateify($d){
		return date('Y-m-d H:i:s', $d);
	}

	$GLOBALS[smarty]->register_modifier('dateify', 'dateify');
?>