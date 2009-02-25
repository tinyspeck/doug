<?
	#
	# $Id$
	#

	include('include/init.php');


	#
	# get list of bugs
	#

	$where = '1';

	$count = db_fetch_single("SELECT COUNT(*) FROM bugs WHERE $where");
	$bugs = db_fetch_all("SELECT * FROM bugs WHERE $where ORDER BY date_modified DESC LIMIT 100");

	foreach ($bugs as $k => $v){

		$bugs[$k][age] = ceil((time() - $v[date_create]) / (24 * 60 * 60));
	}

	$smarty->assign('count', $count);
	$smarty->assign_by_ref('bugs', $bugs);


	#
	# output
	#

	$smarty->display('page_index.txt');
?>