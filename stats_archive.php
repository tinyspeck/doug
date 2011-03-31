<?
	#
	# $Id: $
	#

	include('include/init.php');

	loadlib('stats');

	if ($_GET['y'] && $_GET['m']){
		$smarty->assign('days',stats_get_days_for_month($_GET['y'],$_GET['m']));
		
		$time_str = strtotime(sprintf("%d-%02d-01 12:00",$_GET['y'],$_GET['m']));
		$smarty->assign('title_date',date('F Y',$time_str));
		$smarty->assign('month',date('F',$time_str));
	} elseif ($_GET['y']) {
		$smarty->assign('months',stats_get_months_for_year($_GET['y']));
		$smarty->assign('title_date',$_GET['y']);
	} else {
		$smarty->assign('years',stats_get_years());
	}
	
	$smarty->display('page_stats_archive.txt');

?>