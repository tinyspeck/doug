<?php
	#
	# $Id$
	#
	
	function bugs_fetch($id){
		return db_fetch_one("SELECT * FROM bugs WHERE id=".intval($id));
	}
	
	function bugs_fetch_notes($id, $type = 0){
		$id_enc = intval($id);
		
		$sql = "SELECT * FROM notes WHERE bug_id=$id_enc ";
		if ($type){
			$type_enc = addslashes($type);
			$sql .= "AND type_id='$type_enc' ";
		}
		$sql .= "ORDER BY date_create ASC";
		return db_fetch_all($sql);
	}
?>