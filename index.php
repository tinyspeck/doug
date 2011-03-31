<?
	#
	# $Id$
	#

	include('include/init.php');



	#
	# search filter?
	#

	$search_extra = 1;
	$s = '';

	if ($_GET['s']){

		$s = '"'.$_GET['s'].'"';

		$terms = search_split_terms($_GET['s']);
		$terms_db = search_db_escape_terms($terms);

		$parts_bugs = array();
		$parts_notes = array();

		foreach ($terms_db as $term_db){
			$parts_bugs[] = "title RLIKE '$term_db'";
			$parts_notes[] = "note RLIKE '$term_db'";
		}

		$parts_bugs = implode(' AND ', $parts_bugs);
		$parts_notes = implode(' AND ', $parts_notes);

		$ids1 = db_fetch_all("SELECT id FROM bugs WHERE $parts_bugs");
		$ids2 = db_fetch_all("SELECT bug_id FROM notes WHERE $parts_notes");

		$ids = array(0);

		foreach ($ids1 as $row){ $ids[$row['id']] = 1; }
		foreach ($ids2 as $row){ $ids[$row['bug_id']] = 1; }

		$search_extra = " id IN (".implode(',', array_keys($ids)).")";
	}

	function search_split_terms($terms){

		$terms = preg_replace("/\"(.*?)\"/e", "search_transform_term('\$1')", $terms);
		$terms = preg_split("/\s+|,/", $terms);

		$out = array();

		foreach($terms as $term){

			$term = preg_replace("/\{WHITESPACE-([0-9]+)\}/e", "chr(\$1)", $term);
			$term = preg_replace("/\{COMMA\}/", ",", $term);

			$out[] = $term;
		}

		return $out;
	}

	function search_transform_term($term){
		$term = preg_replace("/(\s)/e", "'{WHITESPACE-'.ord('\$1').'}'", $term);
		$term = preg_replace("/,/", "{COMMA}", $term);
		return $term;
	}

	function search_escape_rlike($string){
		return preg_replace("/([.\[\]*^\$])/", '\\\$1', $string);
	}

	function search_db_escape_terms($terms){
		$out = array();
		foreach($terms as $term){
			#$out[] = '[[:<:]]'.AddSlashes(search_escape_rlike($term)).'[[:>:]]';
			$out[] = '[[:<:]]'.AddSlashes(search_escape_rlike($term));
		}
		return $out;
	}

	#
	# get list of bugs
	#

	if ($_GET['all']){
		$where = '1';
		$title = 'All Issues';
		if ($s){
			$title = "Issues matching $s";
		}
	}
	elseif ($_GET['resolved']){
		$where = "status = 'resolved'";
		$title = 'All Resolved Issues';
		if ($s){
			$title = "Resolved Issues matching $s";
		}
	}
	elseif ($_GET['closed']){
		$where = "status = 'closed'";
		$title = 'All Closed Issues';
		if ($s){
			$title = "Closed Issues matching $s";
		}
	}else{
		if ($_GET['status'] && in_array($_GET['status'],array('open','resolved','closed'))){
			$title_status = ucfirst($_GET['status']).' Issues';
			$where = "status = '$_GET[status]'";
			$smarty->assign('form_status',$_GET['status']);
		} elseif ($_GET['status'] == 'notclosed' || ($_GET['opened_by'] && !$_GET['assigned'] && !$_GET['status'])) {
			$title_status = 'Open and Resolved Issues';
			$where = "status != 'closed'";
			$smarty->assign('form_status','notclosed');
		} elseif ($_GET['status'] == 'fixed'){
			$title_status = 'Resolved and Closed Issues';
			$where = "status != 'open'";
			$smarty->assign('form_status','fixed');
		} else{
			$title_status = 'Open Issues';
			$where = "status = 'open'";
		}
		
		if ($_GET['assigned_to'] && $_GET['opened_by']){
			$where .= " AND opened_user='" . addslashes($_GET['opened_by']) ."' AND assigned_user='" . addslashes($_GET['assigned_to']) . "'";
			$title = $title_status.' Assigned to ' . $_GET['assigned_to'] . ' and reported by ' . $_GET['opened_by'];
			if ($s){
				$title .= " matching $s";
			}
		}
		elseif ($_GET['assigned_to']){
			$where .= " AND assigned_user='" . addslashes($_GET['assigned_to']) ."'";
			$title = $title_status.' Assigned to ' . $_GET['assigned_to'];
			if ($s){
				$title .= " matching $s";
			}
		}
		elseif ($_GET['opened_by']){
			$where .= " AND opened_user='" . addslashes($_GET['opened_by']) ."'";
			$title = $title_status.' reported by ' . $_GET['opened_by'];
		} else {
			$title = 'All Open Issues';
		}

		if ($s){
			$title .= " matching $s";
		}
	}

	$smarty->assign('form_assigned_to',$_GET['assigned_to']);
	$smarty->assign('form_opened_by',$_GET['opened_by']);
	$smarty->assign('form_search',$_GET['s']);

	#
	# pagination
	#

	$count = db_fetch_single("SELECT COUNT(*) FROM bugs WHERE $where AND $search_extra");

	$per_page = 100;
	$pages = ceil($count / $per_page);
	if ($pages < 1) $pages = 1;
	$page = intval($_GET['p']);
	if ($page < 1) $page = 1;
	if ($page > $pages) $page = $pages;
	$start = ($page - 1) * $per_page;

	$smarty->assign('page', $page);
	$smarty->assign('num_pages', $pages);


	#
	# urls for the pages
	#

	$page_urls = array();

	for ($i=1; $i<=$pages; $i++){
		$pairs = array();
		foreach ($_GET as $k => $v){
			if ($k != 'p') $pairs[] = urlencode($k).'='.urlencode($v);
		}
		if ($i > 1) $pairs[] = "p=$i";
		$page_urls[$i] = "./?".implode('&', $pairs);
	}

	$smarty->assign('pages', $page_urls);


	#
	# fetch!
	#

	$bugs = db_fetch_all("SELECT * FROM bugs WHERE $where AND $search_extra ORDER BY date_modified DESC LIMIT $start, $per_page");

	foreach ($bugs as $k => $v){

		$bugs[$k]['age'] = ceil((time() - $v['date_create']) / (24 * 60 * 60));
	}

	$smarty->assign('count', $count);
	$smarty->assign_by_ref('bugs', $bugs);
	$smarty->assign('title', $title." ($count)");


	#
	# output
	#

	$smarty->assign('users', users_fetch_all());
	$smarty->assign('do_filter', 1);

	$smarty->display('page_index.txt');
?>
