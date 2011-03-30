<?
	#
	# $Id: $
	#

	include('../include/init.php');

	loadlib('stats');

	$date = '2010-11-08';
	
	$ts = 0;
	
	while ($ts < 1301468400){
		$time = stats_get_time_window($date);
		$bugs = stats_generate_daily_bugs($time);
		$counts = stats_generate_daily_counts($bugs);
		echo "$date,";
		foreach (array_keys($counts) as $key){
			echo "$key:$counts[$key],";
		}
		echo "\n";
		stats_db_write_daily($time,$bugs);
		$ts=$time['end']+1;
		$date = date('Y-m-d',$ts);
	}
?>