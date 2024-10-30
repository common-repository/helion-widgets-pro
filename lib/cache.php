<?php

function helion_get_cover($bookstore, $ident, $size) {
	
	if(preg_match("/_ebook$/", $ident)) {
		$temp_ident = explode("_ebook", $ident);
		$ident = $temp_ident[0];
	}
	
	$bookstore = h_validate_bookstore($bookstore);
	$ident = h_validate_ident($ident);
	$size = h_validate_size($size);
	
	if(!$ident || !$bookstore || !$size) return false;
	
	global $wpdb;
	
	if(file_exists(ABSPATH . "wp-content/helion-cache/$bookstore/$size/$ident.jpg")) {
		// todo: update usage
		$src = home_url("/wp-content/helion-cache/$bookstore/$size/$ident.jpg");
	} else {
                // from static
		$src = "https://static01.helion.com.pl/global/okladki/$size/$ident.jpg";
		if(get_option("helion_current_cache_size") <= get_option("helion_cache_user")) {
                    
                    // w przypadku braku katalogu stworz go
                    if(!is_dir(ABSPATH . "wp-content/helion-cache/$bookstore/$size")){
                        mkdir(ABSPATH . "wp-content/helion-cache/$bookstore/$size", 0775, true);
                    }
                    
			switch(helion_detect_connection_method()) {
				case 'fopen':
					if(copy($src, 
						ABSPATH . "wp-content/helion-cache/$bookstore/$size/$ident.jpg")) {
						$src = home_url("/wp-content/helion-cache/$bookstore/$size/$ident.jpg");
					} else {
						$src = home_url("/wp-content/helion-cache/$bookstore/$size/$ident.jpg");
					}
					break;
				case 'curl':
					$out = fopen(ABSPATH . 
						"wp-content/helion-cache/$bookstore/$size/$ident.jpg", 'wb');

					if($out) {
						$ch = curl_init();
						@curl_setopt($ch, CURLOPT_FILE, $out);
						@curl_setopt($ch, CURLOPT_HEADER, 0);
                                                @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
						@curl_setopt($ch, CURLOPT_URL, $src);
						curl_exec($ch);
						curl_close($ch); 
						fclose($out);
						$src = home_url("/wp-content/helion-cache/$bookstore/$size/$ident.jpg");
					} else {
						$src = "https://static01.helion.com.pl/global/okladki/$size/$ident.jpg";
					}
					break;
				default:
					$src = "https://static01.helion.com.pl/global/okladki/$size/$ident.jpg";
					break;
			}
		} else {
			$src = "https://static01.helion.com.pl/global/okladki/$size/$ident.jpg";
		}
	}
	
	$dimensions = explode("x", $size);

	return array(
		"src" => $src,
		"bookstore" => $bookstore,
		"ident" => $ident,
		"width" => $dimensions[0],
		"height" => $dimensions[1],
		"alt" => helion_get_book_title($bookstore, $ident),
                "onerror" => "this.src='https://static01.helion.com.pl/global/okladki/$size/helion-brak.jpg'"
		);
}

function helion_is_cover_cached($bookstore, $ident, $size) {

	$bookstore = h_validate_bookstore($bookstore);
	$ident = h_validate_ident($ident);
	$size = h_validate_size($size);
	
	if(!$ident || !$bookstore || !$size) return false;
	
	if(file_exists(ABSPATH . "wp-content/helion-cache/$bookstore/$size/$ident.jpg")) {
		// update usage counter
		return true;
	} else {
		// cache it
		return false;
	}
}

/**
 * Returns current cache size
 * @return int cache size in kiB
 */
function helion_get_current_cache_size() {
	$total = helion_dirsize(ABSPATH . "wp-content/helion-cache");
	$current = $total['size'] / 1024;
	
	return $current;
}

function h_disabled_shell_exec() {
	$disabled_functions = @ini_get('disable_functions');
	
	if(!empty($disabled_functions)) {
		$arr = explode(',', $disabled_functions);
		if(in_array("shell_exec", $arr)) {
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

function helion_dirsize($path) {
	$totalsize = 0;
	$totalcount = 0;
	$dircount = 0;
	if($handle = opendir($path)) {
		while(false !== ($file = readdir($handle))) {
		  $nextpath = $path . '/' . $file;
		  if ($file != '.' && $file != '..' && !is_link ($nextpath)) {
			if (is_dir ($nextpath)) {
				  $dircount++;
				  $result = helion_dirsize($nextpath);
				  $totalsize += $result['size'];
				  $totalcount += $result['count'];
				  $dircount += $result['dircount'];
			}
			elseif (is_file ($nextpath)) {
			  $totalsize += filesize ($nextpath);
			  $totalcount++;
			}
		  }
		}
	  }
	  closedir ($handle);
	  $total['size'] = $totalsize;
	  $total['count'] = $totalcount;
	  $total['dircount'] = $dircount;
	  return $total;
} 

/**
 * Suggests a cache size based on chosen bookstores and cover sizes.
 * Configured to return an approx. amount needed for storing 1000 covers
 * of largest size for every bookstore selected.
 *
 * @return int cache size in MiB
 */
function helion_suggested_cache_size() {

	// in kiB
	$cover_weight = array(
		"326x466" => "30",
		"181x236" => "22",
		"125x163" => "11",
		"120x156" => "10",
		"90x119" => "7",
		"88x115" => "6",
		"72x95" => "5",
		"65x85" => "4",
		);
		
	// TODO: check different places to find used cover sizes
	// default: 181x236
	$largest_cover = "181x236";
	
	// TODO: check how many different cover sizes have been selected
	// default: 3
	$cover_sizes = 3;
	
	// check how many bookstores the user selected
	// default: 2
	$bookstores = array_count_values(get_option("helion_bookstores"));
	
	if($bookstores["1"] != 0) {
		$bookstores_selected = $bookstores["1"];
	} else {
		$bookstores_selected = 2;
	}
		
	// default: 128 MiB
	return (int) $cover_weight[$largest_cover] * $bookstores_selected * $cover_sizes * 1000 / 1024;
}

function rrmdir($dir) {
	if(is_dir($dir)) {
		$objects = scandir($dir);
		foreach ($objects as $object) {
			if($object != "." && $object != "..") {
				if(filetype($dir . "/" . $object) == "dir") {
                                    rrmdir($dir . "/" . $object); 
				} else {
                                    if(file_exists($dir . "/" . $object)){
					unlink($dir . "/" . $object);
                                    }
				}
			}
		}
		reset($objects);
		rmdir($dir);
	}
}

function helion_clear_cache() {
	rrmdir(ABSPATH . "wp-content/helion-cache");
}

function helion_setup_cache() {
	$bookstores = array("helion", "sensus", "onepress", "septem", "ebookpoint", "bezdroza");
	$covers = array("326x466", "181x236", "125x163", "120x156", "90x119", "88x115", "72x95", "65x85");
	
	@mkdir(ABSPATH . "wp-content/helion-cache", 0775);
	foreach($bookstores as $bookstore) {
		@mkdir(ABSPATH . "wp-content/helion-cache/" . $bookstore, 0775);
		foreach($covers as $cover) {
			@mkdir(ABSPATH . "wp-content/helion-cache/" . $bookstore . "/" . $cover, 0775);
		}
	}
	
	@mkdir(ABSPATH . "wp-content/helion-cache/xml");
}

/**
 * Retrieve info about cached cover from the database
 *
 * TODO: new field in array: is cover phisically in the cache?
 *
 * @param bookstore helion|sensus|onepress etc.
 * @param ident ident parameter from Helion
 * @param size ex. 65x123
 * @return array or false if no info in db
 */
function helion_cache_cover_info($bookstore, $ident, $size) {

	$bookstore = h_validate_bookstore($bookstore);
	$ident = h_validate_ident($ident);
	$size = h_validate_size($size);
	
	if(!$ident || !$bookstore || !$size) return false;
	
	global $wpdb;
	
	$result = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . 
		"helion_cover_cache WHERE bookstore = %s AND ident = %s " . 
		"AND size = %s", $bookstore, $ident, $size), ARRAY_A);

	if($result) {
		return array(
			"bookstore" => $bookstore,
			"ident" => $ident,
			"size" => $size,
			"usage" => $result['usage'],
			"to_be_cached" => $result['to_be_cached'],
			"cached_date" => $result['cached_date'],
			);
	} else {
		return false;
	}
}

/**
 * Cronjobs
 */

function helion_cron_cache_covers() {

}

function helion_cron_remove_covers() {

}

function helion_reset_cache() {
	$current = (int) helion_get_current_cache_size();
	$user = (int) get_option("helion_cache_user");
	if($current > $user) {
		helion_clear_cache();
		helion_setup_cache();
		update_option("helion_current_cache_size", 0);
	}
	
	// sprawdź ponownie za miesiąc
	wp_schedule_single_event(time() + 30 * 24 * 60 * 60, 'helion_reset_cache');
}

function helion_cron_cache_size() {
	update_option("helion_current_cache_size", (int) helion_get_current_cache_size());
}

?>
