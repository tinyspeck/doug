<?
	#
	# $Id$
	#

	include('include/init.php');


	#
	# get list of bugs
	#

	if ($_GET['assigned_to']){
		$where = "status != 'closed' AND assigned_user='" . addslashes($_GET['assigned_to']) ."'";
		$title = 'Open Issues Assigned to ' . $_GET['assigned_to'];
	}
	elseif ($_GET['opened_by']){
		$where = "status != 'closed' AND opened_user='" . addslashes($_GET['opened_by']) ."'";
		$title = 'Opened Issues from ' . $_GET['opened_by'];
	}
	elseif ($_GET[all]){
		$where = '1';
		$title = 'All Issues';
	}else{
		$where = "status != 'closed'";
		$title = 'All Open Issues';
	}

	$count = db_fetch_single("SELECT COUNT(*) FROM bugs WHERE $where");
	$bugs = db_fetch_all("SELECT * FROM bugs WHERE $where ORDER BY date_modified DESC LIMIT 100");

	foreach ($bugs as $k => $v){

		$bugs[$k][age] = ceil((time() - $v[date_create]) / (24 * 60 * 60));
	}

	$smarty->assign('count', $count);
	$smarty->assign_by_ref('bugs', $bugs);
	$smarty->assign('title', $title);


	#
	# output
	#

	$smarty->display('page_index.txt');
?>