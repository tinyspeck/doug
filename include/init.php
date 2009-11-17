<?
	$this_dir = dirname(__FILE__);

	define('INCLUDE_DIR', $this_dir);
	define('APP_DIR', "$this_dir/..");


	function loadlib($name){
		include_once(INCLUDE_DIR."/lib_$name.php");
	}

	include("$this_dir/config.php");

	loadlib('db');
	loadlib('smarty');
	loadlib('users');

	db_connect();

	putenv('TZ=PST8PDT');


	function dumper($foo){
            echo "<pre style=\"text-align: left;\">";
            echo HtmlSpecialChars(var_export($foo, 1));
            echo "</pre>\n";
	}



	#
	# log em in?
	#

	$user = array();
	$smarty->assign_by_ref('user', $user);

	if ($_ENV[TSAuth_User]){

		$user = users_fetch($_ENV[TSAuth_User]);
	}


	#
	# constants
	#

	$cfg[resolutions] = array(
		'fixed'		=> 'Fixed',
		'cant_dupe'	=> 'Can\'t duplicate',
		'cant_fix'	=> 'Can\'t fix',
		'wont_fix'	=> 'Won\'t fix',
		'not_issue'	=> 'Not an issue',
		'dupe'		=> 'Duplicate',
	);


	#
	# other stuff
	#

	$smarty->assign('max_attach_bytes', $cfg[max_attach_mb] * 1024 * 1024);
	$smarty->assign('max_attach_label', "$cfg[max_attach_mb] MB");


	function get_attachement(){

		if (!strlen($_FILES[attach][tmp_name])) return "";

		$target = dirname(__FILE__).'/../attachments';
		$base_name = preg_replace('![^a-z0-9.]!', '_', StrToLower($_FILES[attach][name]));
		$use_name = $base_name;
		$i = 1;

		while (file_exists("$target/$use_name")){

			$use_name = "{$i}_$base_name";
			$i++;
		}

		if (move_uploaded_file($_FILES[attach][tmp_name], "$target/$use_name")){

			return $use_name;
		}

		return "";
	}
?>
