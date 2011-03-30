<?
	#
	# $Id: $
	#

	include('include/init.php');

	loadlib('stats');

	$smarty->assign('stats',stats_fetch_for_day($_GET['date']));

	$smarty->display('page_stats.txt');

?>