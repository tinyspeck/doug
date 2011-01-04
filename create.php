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
	# check if we've already got a bug for this external url?
	#

	if ($_POST[url]){

		$url_enc = AddSlashes($_POST[url]);

		$row = db_fetch_hash(db_query("SELECT * FROM bugs WHERE external_url='$url_enc'"));

		if ($row[id]){
			header("location: $cfg[root_url]$row[id]?found=1");
			exit;
		}
	}


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
			'external_url'	=> AddSlashes($_POST[url]),
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
		
		#
		# Send an email
		#
		
		$to_user = users_fetch($_POST['assigned']);
		if ($to_user['email']){
			$smarty->assign_by_ref('bug', bugs_fetch($bug_id));
			$smarty->assign('bug_note', array_shift(bugs_fetch_notes($bug_id, 'note')));

			loadlib('email');
			email_send(array(
				'to_email'	=> $to_user['email'],
				'template'	=> 'email_bug_create.txt',
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