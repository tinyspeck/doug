<?php
	#
	# $Id$
	#
	
	function users_fetch($name){
		$name_enc = AddSlashes($name);
		return db_fetch_one("SELECT * FROM users WHERE name='$name_enc'");
	}
	
	function users_fetch_all(){
		return db_fetch_all("SELECT * FROM users ORDER BY name ASC");
	}
	
	function users_update(&$user, $hash){
		if (!$user['name']){
			return 0;
		}
		
		$name_enc = addslashes($user['name']);
		db_update('users', $hash, "name='$name_enc'");
		
		return 1;
	}
	
	function users_create($name){
		if (!$name){
			return 0;
		}
		
		$name_enc = addslashes($name);
		db_insert('users', array('name' => $name_enc));
		
		return array('name' => $name);
	}
?>