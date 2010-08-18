<?
	#
	# $Id$
	#

	include('include/init.php');


	$from = 'laura';
	$to = 'cal';


	#########################################################

	$from_enc = AddSlashes($from);
	$to_enc = AddSlashes($to);

	$ids = db_fetch_all("SELECT id FROM bugs WHERE opened_user='$from_enc' AND (status='open' OR status='resolved')");
	$count = count($ids);

	echo "migrating $count active issues opened by $from...<br />";


	foreach ($ids as $row){

		db_insert('notes', array(
			'bug_id'	=> $row[id],
			'date_create'	=> time(),
			'user'		=> 'migration-tool',
			'type_id'	=> 'owner',
			'old_value'	=> $from_enc,
			'new_value'	=> $to_enc,
		));

		db_update('bugs', array(
			'opened_user'	=> $to_enc,
		), "id=$row[id]");

		echo "[$row[id]] ";
	}

?>