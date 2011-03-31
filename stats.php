<?
	#
	# $Id: $
	#

	include('include/init.php');

	loadlib('stats');

	$stats = stats_fetch_for_day($_GET['date']);

	$smarty->assign('day',date('D jS',$stats['time']['start']));
	
	$smarty->assign('month',date('F',$stats['time']['start']));
	
	$smarty->assign('month_url',sprintf("%d/%02d/",$stats['time']['y'],$stats['time']['m']));
	
	$smarty->assign('stats',$stats);

	$smarty->display('page_stats.txt');

?>