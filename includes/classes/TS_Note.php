<?php

// if this file directly called by user then die!!
defined( 'ABSPATH' ) or die( 'No Access.' );

class TS_Note {

	private $ID;

	private $note_parent;

	private $note_content;

	private $note_author;


	/**
	 * Note constructor.
	 *
	 * @param $data
	 */
	public function __construct( $data ) {

		if ( isset( $data['parent'], $data['author'], $data['content'] ) ) {

			$this->note_parent  = $data['parent'];
			$this->note_author  = $data['author'];
			$this->note_content = $data['content'];

			$this->add_note();

		} elseif ( isset( $data['note_id'] ) ) {

			$this->ID = $data['note_id'];

		} elseif ( isset( $data['ticket_id'] ) ) {

			$this->note_parent = $data['ticket_id'];

		}
	}


	/**
	 * add note to database.
	 */
	public function add_note() {

		wp_insert_post( array(
			"post_author"  => $this->note_author,
			"post_content" => $this->note_content,
			"post_parent"  => $this->note_parent,
			"post_type"    => "ts_ticket_note",
			"post_status"  => "publish",
		) );

	}

	public function get_note_by_id() {

		$note = get_post( $this->ID );

		return $note;

	}

	public function get_notes_by_ticket() {
		$notes = get_posts( array( "post_type" => "ts_ticket_note", "post_parent" => $this->note_parent ) );

		return $notes;

	}


}