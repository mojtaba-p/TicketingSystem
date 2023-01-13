<?php

/**
	 * some helpers that helps to better programming. :)
	 */

	defined('TS_PATH' ) or die('Error Bad Loading E:1');

	if ( ! function_exists('dd') ) {

		/**
		 * @param $data. the data we want to show.
		 * die and dump function.
		 */
		function dd(...$data) {
			echo "<pre>";
			foreach($data as $d){
				var_dump($d);
			}
			echo "</pre>";
			die();
		}

	}

	if ( ! function_exists( 'view_path' ) ) {

		/**
		 * returns php file path from views directory.
		 * @param string $file_name.
		 * @return string
		 */
		function view_path(string $file_name) : string {

			return TS_VIEW.$file_name;

		}

	}


