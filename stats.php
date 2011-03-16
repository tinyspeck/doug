<?
	#
	# $Id$
	#

	include('include/init.php');


	#
	# stats!
	#

	$open_bugs = db_fetch_all("SELECT COUNT(id) AS num, assigned_user AS user FROM bugs WHERE status='open'     GROUP BY user ORDER BY num DESC");
	$reso_bugs = db_fetch_all("SELECT COUNT(id) AS num, opened_user   AS user FROM bugs WHERE status='resolved' GROUP BY user ORDER BY num DESC");
	$fixs_bugs = db_fetch_all("SELECT COUNT(id) as num, resolved_user AS user FROM bugs WHERE (status='closed' OR status='resolved') GROUP BY user ORDER BY num DESC");
	$file_bugs = db_fetch_all("SELECT COUNT(id) as num, opened_user   AS user FROM bugs GROUP BY user ORDER BY num DESC");

	totalize($open_bugs);
	totalize($reso_bugs);
	totalize($fixs_bugs);
	totalize($file_bugs);

	function totalize(&$bugs){
		$t = 0;
		foreach ($bugs as $b){ $t += $b[num]; }
		$bugs[] = array(
			'user' => 'Total',
			'num' => $t,
		);
	}

	$smarty->assign('open_bugs', $open_bugs);
	$smarty->assign('reso_bugs', $reso_bugs);
	$smarty->assign('fixs_bugs', $fixs_bugs);
	$smarty->assign('file_bugs', $file_bugs);


	#
	# output
	#

	$smarty->display('page_stats.txt');
?>