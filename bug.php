<?
	#
	# $Id$
	#

	include('include/init.php');


	#
	# get the bug
	#

	$bug = bugs_fetch($_REQUEST[id]);

	$smarty->assign_by_ref('bug', $bug);

	if (!$bug[id]){
		die("bug not found");
	}


	#
	# get all the notes
	#

	$notes = bugs_fetch_notes($bug['id']);

	$smarty->assign_by_ref('notes', $notes);


	#
	# find out who's subscribed
	#

	$subs = array();
	$smarty->assign_by_ref('subs', $subs);

	$ret = db_fetch_all("SELECT * FROM subs WHERE bug_id=$bug[id]");
	foreach ($ret as $row){
		$subs[$row[user]] = 'reg';
	}

	if ($_GET[cal]){
		dumper($ret);
	}

	if (!$subs[$bug['assigned_user']]){
		$subs[$bug['assigned_user']] = 'auto';
	}
	if (!$subs[$bug['opened_user']]){
		$subs[$bug['opened_user']] = 'auto';
	}


	#
	# get list of users
	#

	$users = users_fetch_all();

	$smarty->assign_by_ref('users', $users);


	#
	# save subs?
	#

	if ($_POST[done] && $_POST['use-subs']){

		foreach ($users as $who){
			$name = $who[name];

			$was_subbed	= ($subs[$name] && $subs[$name] != 'auto') ? 1 : 0;
			$is_subbed	= $_POST["sub-$name"] ? 1 : 0;

			if ($was_subbed != $is_subbed){

				$name_enc = AddSlashes($name);

				if ($was_subbed){

					#
					# unsub
					#

					db_query("DELETE FROM subs WHERE bug_id=$bug[id] AND user='$name_enc'");

					db_insert('notes', array(
						'bug_id'	=> $bug[id],
						'date_create'	=> time(),
						'user'		=> AddSlashes($user[name]),
						'type_id'	=> 'unsub',
						'old_value'	=> AddSlashes($name),
					));

				}else{

					#
					# sub
					#

					db_insert('subs', array(
						'bug_id'	=> $bug[id],
						'user'		=> $name_enc,
					));

					db_insert('notes', array(
						'bug_id'	=> $bug[id],
						'date_create'	=> time(),
						'user'		=> AddSlashes($user[name]),
						'type_id'	=> 'sub',
						'old_value'	=> AddSlashes($name),
					));

				}
			}
		}

		header("location: $cfg[root_url]$bug[id]#bottom");
		exit;
	}


	#
	# change something
	#

	if ($_POST[done]){

		if ($_POST['use-status']	){ 
			local_set_bug_prop('status', $_POST['value-status'], 'status');
		
			if ($_POST['value-status'] == 'resolved' && $bug['assigned_user'] != $bug['opened_user']){
				local_set_bug_prop('assigned_user', $bug['opened_user'], 'assign');
			}
		}

		if ($_POST['use-resolution']	){ local_set_bug_prop('resolution',	$_POST['value-resolution'],	'resolution'); }
		if ($_POST['use-assign'] 	){ local_set_bug_prop('assigned_user',	$_POST['value-assign'],		'assign'); }
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
			
			db_update('bugs', array(
				'date_modified' => time(),
			), "id=$bug[id]");

		}
		
		#
		# Send emails
		#
		
		if ($_POST['use-assign']){
			$subs[$_POST['value-assign']] = 'auto';
		}
		
		loadlib('email');
		
		$bug = bugs_fetch($bug['id']);
		$smarty->assign_by_ref('bug', $bug);
		$smarty->assign('bug_note', array_pop(bugs_fetch_notes($bug['id'], 'note')));


		foreach (array_keys($subs) as $name){

			$to_user = users_fetch($name);

			if ($to_user['email'] && $to_user['name'] != $user['name']){

				email_send(array(
					'to_email'	=> $to_user['email'],
					'template'	=> 'email_bug_edit.txt',
				));
			}

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
			'date_modified' => time(),
		), "id=$bug[id]");
	}


	function format_note($txt){

		$map = array(
			'&lt;pre&gt;' => '<pre>',
			'&lt;/pre&gt;' => '</pre>',
		);

		$txt = preg_replace_callback('!(^|\s)#(\d+)!', 'local_link_bug', $txt);
		$txt = str_replace(array_keys($map), $map, $txt);
		$txt = preg_replace_callback('!<pre>(.*)</pre>!s', 'local_strip_br', $txt);

		return $txt;
	}

	function local_link_bug($m){

		$id = intval($m[2]);

		$bug = bugs_fetch($id);

		return "$m[1]<a href=\"/$id\" class=\"inline-status-{$bug[status]}\">#$id</a>";
	}

	function local_strip_br($m){

		return '<pre>'.str_replace('<br />', '', $m[1]).'</pre>';
	}


	#
	# output
	#

	$smarty->display('page_bug.txt');
?>
