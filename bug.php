<?
	#
	# $Id$
	#

	include('include/init.php');


	#
	# get the bug
	#

	$bug = db_fetch_one("SELECT * FROM bugs WHERE id=".intval($_REQUEST[id]));

	$smarty->assign_by_ref('bug', $bug);

	if (!$bug[id]){
		die("bug not found");
	}


	#
	# get all the notes
	#

	$notes = db_fetch_all("SELECT * FROM notes WHERE bug_id=$bug[id] ORDER BY date_create ASC");

	$smarty->assign_by_ref('notes', $notes);


	#
	# change something
	#

	if ($_POST[done]){

		if ($_POST['use-status']	){ local_set_bug_prop('status',		$_POST['value-status'],		'status'); }
		if ($_POST['use-resolution']	){ local_set_bug_prop('resolution',	$_POST['value-resolution'],	'resolution'); }
		if ($_POST['use-assign']	){ local_set_bug_prop('assigned_user',	$_POST['value-assign'],		'assign'); }
		if ($_POST['use-title']		){ local_set_bug_prop('title',		$_POST['value-title'],		'title'); }

		$attach = get_attachement();

		if ($attach || $_POST[note]){

			db_insert('notes', array(
				'bug_id'	=> $bug[id],
				'date_create'	=> time(),
				'user'		=> AddSlashes($user[name]),
				'type_id'	=> 'note',
				'note'		=> AddSlashes($_POST[note]),
				'attachment'	=> AddSlashes($attach),
			));

		}

		header("location: $cfg[root_url]$bug[id]#bottom");
		exit;
	}


	function local_set_bug_prop($field, $new_value, $type_id){

		global $bug, $user;

		if ($bug[$field] == $new_value) return;

		db_insert('notes', array(
			'bug_id'	=> $bug[id],
			'date_create'	=> time(),
			'user'		=> AddSlashes($user[name]),
			'type_id'	=> $type_id,
			'old_value'	=> AddSlashes($bug[$field]),
			'new_value'	=> AddSlashes($new_value),
		));

		db_update('bugs', array(
			$field		=> AddSlashes($new_value),
		), "id=$bug[id]");
	}


	#
	# get list of users
	#

	$users = db_fetch_all("SELECT * FROM users ORDER BY name ASC");

	$smarty->assign_by_ref('users', $users);


	#
	# output
	#

	$smarty->display('page_bug.txt');
?>