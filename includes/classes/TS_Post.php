<?php
/**
 * Manage Post Creation, Update, Delete, etc... .
 */

// if this file directly called by user then die!!
defined( 'ABSPATH' ) or die( 'No Access.' );

class TS_Post {

	/**
	 * Post ID.
	 *
	 * @var integer.
	 */
	private $ID;

	/**
	 * Post Author.
	 *
	 * @var integer.
	 */
	protected $author;

	/**
	 * parent of this post.
	 *
	 * @var integer
	 */
	protected $parent;

	/**
	 * Post title.
	 *
	 * @var string
	 */
	protected $title;

	/**
	 * Post content.
	 *
	 * @var string.
	 */
	protected $content;

	/**
	 * Post creation date.
	 *
	 * @var string.
	 */
	protected $date;

	/**
	 * post type name.
	 *
	 * @var string.
	 */
	protected $post_type;

	/**
	 * taxonomy id for post.
	 *
	 * @var integer
	 */
	protected $taxonomy;

	/**
	 *
	 */
	protected $taxonomy_name;

	/**
	 * @var string
	 */
	private static $error = '';

	public function __construct() {
		$this->taxonomy_name = "ticket_type";
		$this->do_add_actions();
	}


	/**
	 * set filed if declared.
	 *
	 * @param $name
	 * @param $value
	 */
	public function __set( $name, $value ) {
		if ( in_array( $name, array( 'author', 'parent', 'title', 'content', "taxonomy" ) ) ) {
			$this->$name = $value;
		}
	}

	/**
	 * get field value;.
	 *
	 * @param $name
	 *
	 * @return mixed
	 */
	public function __get( $name ) {
		if ( in_array( $name, array( 'author', 'parent', 'title', 'content', "taxonomy" ) ) ) {

			return $this->$name;
		}

		return false;
	}

	/**
	 * load ticket reply from database by id
	 *
	 * @param $id
	 *
	 * @return null|void
	 */
	public function load( $id ) {

		$post = get_post( $id );

		if ( is_null( $post ) ) {
			return null;
		}

		$this->ID      = $id;
		$this->title   = $post->post_title;
		$this->author  = $post->post_author;
		$this->parent  = $post->post_parent;
		$this->content = $post->post_content;
		$this->date    = $post->post_date;

	}

	/**
	 * insert new post to db.
	 * first check title is not empty.
	 * set post attributes and then insert post.
	 * if post has taxonomies set them.
	 *
	 * @return int|WP_Error
	 */
	public function insert() {
		if ( ! isset( $this->title ) || empty( $this->title ) ) {
			self::$error = "عنوان باید وارد شود";

			return 0;
		}

		$post_arr = array(
			'post_type'    => $this->post_type,
			'post_title'   => sanitize_text_field( $this->title ),
			'post_content' => $this->content,
			'post_status'  => "publish",
			'post_author'  => $this->author,
		);

		if ( 0 != $this->parent ) {
			$post_arr['post_parent'] = $this->parent;
		}

		$post_id = wp_insert_post( $post_arr, true );

		if ( is_wp_error( $post_id ) ) {
			self::$error = $post_id->get_error_message();

			return 0;
		}

		$this->ID = $post_id;

		if ( 0 != $this->taxonomy ) {
			wp_set_post_terms( $this->ID, $this->taxonomy, $this->taxonomy_name );
		}

		return $this->ID;
	}

	public function check_error( ){
		global $ts_form_errors;
		if ( '' != $error = self::getError() ) {
			$ts_form_errors->add( "post_error", $error );
		}
	}

	/**
	 * upload an attachment
	 *
	 * @param $post_id
	 *
	 * @return int
	 */
	public static function upload_attachment( $post_id ): int {

		if ( $_FILES['attachment']['error'] != UPLOAD_ERR_OK ) {
			return 0;
		}

		require_once( ABSPATH . "wp-admin" . '/includes/image.php' );
		require_once( ABSPATH . "wp-admin" . '/includes/file.php' );
		require_once( ABSPATH . "wp-admin" . '/includes/media.php' );

		// add filter to hash file name
		add_filter( 'wp_handle_upload_prefilter', array( self::class, 'hash_file_name' ), 10 );
		$attach_id = media_handle_upload( 'attachment', $post_id );
		// remove filter to don't effect on other wp attachments
		remove_filter( 'wp_handle_upload_prefilter', array( self::class, 'hash_file_name' ) );

		if ( is_wp_error( $attach_id ) ) {
			self::$error = $attach_id->get_error_message();
		}

		if ( is_numeric( $attach_id ) ) {
			return $attach_id;
		} else {
			return 0;
		}

	}


	/**
	 * hash file name on upload.
	 *
	 * @param array $file
	 *
	 * @return array
	 */
	public static function hash_file_name( $file ) {

		$file["name"] = md5( time() ) . "-" . $file["name"];

		return $file;

	}


	/**
	 * @param $post_id
	 *
	 * @return string
	 */
	public static function get_attachment_guid( $post_id ) {
		$posts = get_posts(
			[ 'post_parent' => $post_id, 'post_type' => "attachment" ]
		);

		return $posts[0]->guid;
	}

	/**
	 * @param $post_id
	 *
	 * @return int post attachments count
	 */
	public static function get_attachment_count( $post_id ) {

		$posts = get_posts(
			[ 'post_parent' => $post_id, 'post_type' => "attachment" ]
		);

		return count( $posts );
	}

	/**
	 * execute before post delete
	 * before delete any ticket remove that ticket childs
	 *
	 * @param int $postid
	 *
	 * @return void
	 */
	public function remove_post_childs( $postid ): void {

		$post = get_post( $postid );

		if ( $post->post_type != 'ts_ticket' ) {
			return;
		}

		global $wpdb;

		$childs = $wpdb->get_results( "SELECT ID FROM {$wpdb->prefix}posts where `post_parent` = {$postid}" );

		foreach ( $childs as $child ) {
			wp_delete_post( $child->ID, 1 );
		}


	}

	/**
	 * return and clear $error variable
	 */
	public static function getError() {
		$err         = self::$error;
		self::$error = '';

		return $err;
	}


	/**
	 * say time on human language
	 *
	 * @param $datetime
	 * @param bool $full
	 *
	 * @return string
	 */
	public static function time_elapsed_string( $post_id, $full = false ) {
	    $datetime = get_post($post_id)->post_date;
		$now  = new DateTime();
		$ago  = new DateTime( $datetime );
		$diff = $now->diff( $ago );

		$diff->w = floor( $diff->d / 7 );
		$diff->d -= $diff->w * 7;

		$string = array(
			'y' => 'سال',
			'm' => 'ماه',
			'w' => 'هفته',
			'd' => 'روز',
			'h' => 'ساعت',
			'i' => 'دقیقه',
			's' => 'ثانیه',
		);
		foreach ( $string as $k => &$v ) {
			if ( $diff->$k ) {
				$v = $diff->$k . ' ' . $v . ( $diff->$k > 1 ? ' ' : '' );
			} else {
				unset( $string[ $k ] );
			}
		}

		if ( ! $full ) {
			$string = array_slice( $string, 0, 1 );
		}
		return $string ? implode( ', ', $string ) . ' قبل ' : 'لخظاتی قبل';

	}

	private function do_add_actions(): void {

		add_action( 'before_delete_post', array( $this, 'remove_post_childs' ) );

	}

}