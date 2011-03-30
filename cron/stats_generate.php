<?
	#
	# $Id: $
	#

	include('../include/init.php');

	loadlib('stats');

	$time = stats_get_time_window($date);
	$bugs = stats_generate_daily_bugs($time);
	stats_db_write_daily($time,$bugs);
?>