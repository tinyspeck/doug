<?
	#
	# $Id$
	#

	include('include/init.php');


	#
	# get list of users
	#

	$users = db_fetch_all("SELECT * FROM users ORDER BY name ASC");

	$smarty->assign_by_ref('users', $users);


	#
	# output
	#

	$smarty->display('page_login.txt');
?>