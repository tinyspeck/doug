<?
	#
	# $Id: $
	#

	#################################################################

	function stats_get_time_window($date=NULL){
		
		$today = strtotime(date('Y-m-d').' 00:00');

		if ($date){
			if (is_array($date)){
				$date_string = "$date[y]-$date[m]-$date[d]";
			} else {
				$date_string = $date;
			}
			$date_start = strtotime("$date_string 00:00");
		}

		if (!$date || $today == $date_start){
			$is_today = 1;
			$start = $today;
		} else {
			$is_today = 0;
			$start = $date_start;
		}
		
		$end = $start + (24*60*60);
		$stale = $end - (2*30*24*60*60);
		
		return array(	'start' 	=> $start,
				'end'		=> $end,
				'stale' 	=> $stale,
				'is_today' 	=> $is_today,
				'y'		=> date('Y',$start),
				'm'		=> date('n',$start),
				'd'		=> date('j',$start),
				'human'		=> date('D, F jS Y',$start),
			);
	}

	#################################################################

	function stats_generate_daily_bugs($time=NULL){
		
		if (!$time){
			$time = stats_get_time_window();
		}
		
		#
		# bugs opened on this day
		#
		$opened_ret = db_fetch_all("SELECT id FROM bugs WHERE date_create > $time[start] AND date_create < $time[end]");

		$opened_bugs = array();

		foreach ($opened_ret as $row){
			$opened_bugs[] = $row['id'];
		}
		
		#
		# bugs re-opened on this day
		#
		
		$reopened_ret = db_fetch_all("SELECT bug_id, date_create FROM notes WHERE date_create > $time[start] AND date_create < $time[end] AND type_id='status' AND new_value='open' ORDER BY date_create");

		$reopened_bugs = array();

		foreach ($reopened_ret as $row){
			$reopened_bugs[$row['bug_id']] = $row['date_create'];
		}
		
		#
		# bugs closed on this day
		#
		
		$closed_ret = db_fetch_all("SELECT bug_id, date_create FROM notes WHERE date_create > $time[start] AND date_create < $time[end] AND type_id='status' AND new_value='closed' ORDER BY date_create");

		$closed_bugs = array();
		
		foreach ($closed_ret as $row){
			$closed_bugs[$row['bug_id']] = $row['date_create'];
		}

		#
		# work out if any bugs were closed but then opened again
		#
		
		foreach(array_keys($reopened_bugs) as $id){
			if ($closed_bugs[$id] < $reopened_bugs[$id]){
				unset($closed_bugs[$id]);
			}
		}
		
		#
		# bugs resolved on this day
		#
		
		$resolved_ret = db_fetch_all("SELECT bug_id, date_create FROM notes WHERE date_create > $time[start] AND date_create < $time[end] AND type_id='status' AND new_value='resolved' ORDER BY date_create");
		
		$resolved_bugs = array();
		
		foreach ($resolved_ret as $row){
			$resolved_bugs[$row['bug_id']] = $row['date_create'];
		}
		
		#
		# work out if any bugs were closed but then opened again
		#
		
		foreach(array_keys($reopened_bugs) as $id){
			if ($resolved_bugs[$id] < $reopened_bugs[$id]){
				unset($resolved_bugs[$id]);
			}
		}
		
		#
		# work out if any bugs were resolved *and* closed
		#
		
		$resolved_closed_bugs = array();
		
		foreach(array_keys($closed_bugs) as $id){
			if (array_key_exists($id,$resolved_bugs)){
				$resolved_closed_bugs[] = $id;
				unset($closed_bugs[$id]);
				unset($resolved_bugs[$id]);
			}
		}
		
		#
		# flatten the date-sensitive arrays
		#

		$closed_bugs = array_unique(array_keys($closed_bugs));
		$resolved_bugs = array_unique(array_keys($resolved_bugs));
		$reopened_bugs = array_unique(array_keys($reopened_bugs));
		
		#
		# find bugs with other activity
		#
		
		$seen_bugs = array_merge($opened_bugs,$reopened_bugs,$closed_bugs,$resolved_bugs,$resolved_closed_bugs);

		#
		# bugs with activity
		#

		$active_ret = db_fetch_all("SELECT DISTINCT bug_id FROM notes WHERE date_create > $time[start] AND date_create < $time[end]");

		$active_bugs = array();

		foreach ($active_ret as $row){
			if (!in_array($row['bug_id'],$seen_bugs)){
				$active_bugs[] = $row['bug_id'];
			}
		}

		$open_bugs = array();
		$stale_bugs = array();

		#
		# today only (historically fetching these == pain in ass)
		#
		if ($time['is_today']){

			#
			# stale bugs
			#

			$stale_ret = db_fetch_all("SELECT id FROM bugs WHERE status='open' AND date_modified < $time[stale]");

			foreach ($stale_ret as $row){
				$stale_bugs[] = $row['id'];
			}

			#
			# all open bugs
			#

			$open_ret = db_fetch_all("SELECT id FROM bugs WHERE status='open'");

			foreach ($open_ret as $row){
				$open_bugs[] = $row['id'];
			}
		}

		return array(
			'time'		=> $time,
			'opened'	=> $opened_bugs,
			'reopened'	=> $reopened_bugs,
			'closed'	=> $closed_bugs,
			'resolved'	=> $resolved_bugs,
			'resolved_closed' => $resolved_closed_bugs,
			'active'	=> $active_bugs,
			'stale'		=> $stale_bugs,
			'open'		=> $open_bugs,
		);
	}
	
	#################################################################

	function stats_generate_daily_counts($stats){
		$counts = array();
		
		foreach (array('opened','reopened','closed','resolved','resolved_closed','active','stale','open') as $key){
			$counts[$key] = count($stats[$key]);
		}

		$counts['makework'] = $counts['opened']+$counts['reopened'];
		$counts['dowork'] = $counts['closed']+$counts['resolved']+$counts['resolved_closed'];

		$counts['delta'] = $counts['makework'] - $counts['dowork'];

		return $counts;
	}

	#################################################################

	function stats_db_write_daily($time,$bugs){
		$write = array( 'timestamp'	=> $time['start'],
				'year'		=> $time['y'],
				'month'		=> $time['m'],
				'day'		=> $time['d'],
			);

		foreach(array('opened','reopened','closed','resolved','resolved_closed','active','stale','open') as $key){
			$write[$key] = implode(',',$bugs[$key]);
		}
		
		db_insert('stats',$write);
	}

	#################################################################

	function stats_db_fetch_daily($time){
		$bugs = db_fetch_one("SELECT * FROM stats WHERE timestamp=$time[start]");
		
		foreach(array('opened','reopened','closed','resolved','resolved_closed','active','stale','open') as $key){
			if (strlen($bugs[$key])>2){
				$bugs[$key] = explode(',',$bugs[$key]);
			} else {
				$bugs[$key] = null;
			}
		}
		return $bugs;
	}

	#################################################################

	function stats_fetch_for_day($date=NULL){
		$time = stats_get_time_window($date);
		
		if ($time['is_today']){
			$bug_ids = stats_generate_daily_bugs($time);
		} else {
			$bug_ids = stats_db_fetch_daily($time);
		}

		$bugs = stats_load_bug_data($bug_ids);

		$counts = stats_generate_daily_counts($bug_ids);

		return array(
				'time'		=> $time,
				'bugs' 		=> $bugs,
				'counts'	=> $counts,
				);
	}

	#################################################################

	function stats_load_bug_data($bug_ids){
		$bugs = array();
		foreach(array('opened','reopened','closed','resolved','resolved_closed','active') as $key){
			$bugs[$key] = array();
			foreach ($bug_ids[$key] as $id){
				$bugs[$key][] = bugs_fetch($id);
			}
		}
		return $bugs;
	}

	#################################################################
?>