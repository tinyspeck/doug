<?
	#
	# $Id$
	#

	include('include/init.php');


	#
	# get list of users
	#

	$users = users_fetch_all();

	$smarty->assign_by_ref('users', $users);


	#
	# output
	#

	$smarty->display('page_login.txt');
?>