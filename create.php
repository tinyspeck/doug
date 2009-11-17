<?
	#
	# $Id$
	#

	include('include/init.php');


	#
	# logged in?
	#

	if (!$user[name]){
		header("location: $cfg[root_url]login.php");
		exit;
	}


	#
	# get list of users
	#

	$users = users_fetch_all();

	$smarty->assign_by_ref('users', $users);


	#
	# create a new bug?
	#

	if ($_POST[done]){

		$bug_id = db_insert('bugs', array(
			'date_create'	=> time(),
			'date_modified'	=> time(),
			'opened_user'	=> AddSlashes($user[name]),
			'assigned_user'	=> AddSlashes($_POST[assigned]),
			'status'	=> 'open',
			'title'		=> AddSlashes($_POST[title]),
		));

		$attach = get_attachement();

		if ($attach || $_POST[description]){

			db_insert('notes', array(
				'bug_id'	=> $bug_id,
				'date_create'	=> time(),
				'user'		=> AddSlashes($user[name]),
				'type_id'	=> 'note',
				'note'		=> AddSlashes($_POST[description]),
				'attachment'	=> AddSlashes($attach),
			));

		}

		header("location: $cfg[root_url]$bug_id");
		exit;
	}


	#
	# output
	#

	$smarty->display('page_create.txt');
?>