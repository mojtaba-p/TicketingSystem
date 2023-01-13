<?php

require_once TS_VENDOR . "autoload.php";
require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
require_once( ABSPATH . 'wp-admin/includes/class-wp-posts-list-table.php' );

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

add_action( "wp", "ts_spreadsheet_downloader" );

function ts_spreadsheet_downloader() {

	if ( ! isset(  $_GET['dl_excel'] ) ) {
		return;
	}

	$posts_in_array = ts_get_posts_in_array();

	$streamedResponse = new StreamedResponse();
	$streamedResponse->setCallback( function () use ( $posts_in_array ) {
		$spreadsheet = ts_excel_generator( $posts_in_array );
		$writer      = new Xlsx( $spreadsheet );
		$writer->save( "php://output" );
	} );

	$streamedResponse->setStatusCode( Response::HTTP_OK );
	$streamedResponse->headers->set( 'Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
	$streamedResponse->headers->set( 'Content-Disposition', 'attachment; filename="ts_excel_export.xlsx"' );
	$streamedResponse->send();
}

/**
 * create an array of all posts in excel format.
 *
 * @return mixed
 */
function ts_get_posts_in_array() {
	global $wp_query;

	// column names
	$columns = [
		"ID"           => "شناسه",
		"post_title"   => "عنوان",
		"post_author"  => "ارسال کننده",
		"post_date"    => "تاریخ",
		"taxonomy"     => "دسته ها",
		"_ts_status"   => "وضعیت",
		"_ts_priority" => "اولویت",
		"_ts_agent"    => "پشتیبان",
	];

	// excel file first row that have column names
	$excel_rows = [
		"A1" => $columns["ID"],
		"B1" => $columns["post_title"],
		"C1" => $columns["post_author"],
		"D1" => $columns["post_date"],
		"E1" => $columns["taxonomy"],
		"F1" => $columns["_ts_status"],
		"G1" => $columns["_ts_priority"],
		"H1" => $columns["_ts_agent"],
	];

	for ( $i = 0; $i <= count( $wp_query->posts ); $i++ ) {
		if ( ! isset( $wp_query->posts[ $i ] ) ) {
			continue;
		}
		$post_array = get_object_vars( $wp_query->posts[ $i ] );
		$excel_rows += ts_create_excel_row_from_post_array( $post_array, $i + 2 );

	}

	return $excel_rows;
}

/**
 * create an array of excel columns of post data
 *
 * @param $post_array
 * @param $index
 *
 * @return array
 */
function ts_create_excel_row_from_post_array( $post_array, $index ) {
	$post_array["taxonomy"]     = TS_Ticket::get_taxonomies( $post_array["ID"] );
	$post_array["_ts_status"]   = TS_Ticket::get_ticket_status( $post_array["ID"], "text" );
	$post_array["_ts_priority"] = TS_Ticket::get_ticket_priority( $post_array["ID"], 'text' );;
	$post_array["_ts_agent"] = TS_Ticket::get_ticket_agent( $post_array["ID"], "display_name" );

	$excel_row = [
		"A" . $index => $post_array["ID"],
		"B" . $index => $post_array["post_title"],
		"C" . $index => get_user_by( "ID", $post_array["post_author"] )->display_name,
		"D" . $index => ts_jdate_convert( $post_array["post_date"] ),
		"E" . $index => $post_array["taxonomy"],
		"F" . $index => $post_array["_ts_status"],
		"G" . $index => $post_array["_ts_priority"],
		"H" . $index => $post_array["_ts_agent"],
	];

	return $excel_row;
}

/**
 * create spreadsheet
 *
 * @return Spreadsheet
 * @throws \PhpOffice\PhpSpreadsheet\Exception
 */
function ts_excel_generator( $posts ) {
	$spreadsheet = new Spreadsheet();
	$sheet       = $spreadsheet->getActiveSheet();
	$sheet->setRightToLeft(true);
	$logedPerson = wp_get_current_user();

	$spreadsheet->getProperties()
	            ->setCreator("ts-ticket report".' - '. $logedPerson->display_name )
	            ->setTitle('Export ts-ticket posts - ')
	            ->setKeywords('export tickets ');

	foreach ( $posts as $key => $value ) {
		$sheet->setCellValue( $key, $value );
	}

	return $spreadsheet;
}


