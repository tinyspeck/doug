<?
	#
	# $Id$
	#

	include('include/init.php');
		
	#
	# Who are we talking about here?
	#
	
	if ($_GET['id']){
		$profile = users_fetch($_GET['id']);
	}
	
	if (!$profile['name']){
		header("location: $cfg[root_url]");
		exit;
	}
	
	$smarty->assign_by_ref('profile', $profile);
	
	if ($profile['name'] == $user['name']){
		$smarty->assign('is_own', 1);
		
		if ($_POST['done']){
			users_update($profile, array('email' => addslashes($_POST['email'])));
			
			header("location: $cfg[root_url]users/$profile[name]/?saved=1");
			exit;
		}
	}

	#
	# output
	#

	$smarty->display('page_user.txt');
?>