<?php
/*
Plugin Name: Change Comment Parent
Description: Simple plug-in for editing the parent comments to any user comments. Use it to edit the threaded structure comments.
Version: 1.0.0
Author: Dmitriy Amirov (InSys)
Author URI: https://intsystem.org/
*/

class InsysChangeCommentParent {
	const VERSION = '1.0';
	
	function __construct()
	{
		add_action('init', array($this, 'execute'));
	}

	public function execute()
	{
		if (!current_user_can('moderate_comments' ) ) {
			return false;
		}
		
		if (is_admin()) {
			add_action('wp_ajax_insys_comment_parent', array($this, 'commentParentSet') );
		} else {
			if (get_option('thread_comments')) {
				add_filter('comment_text', array($this, 'commentTextAddHtml'), 10, 2);
			}
		}
	}

	private static function ajaxReturnAnswer($status = 'error', $args = array()) {
		$result = array('status' => $status);
		$result = array_merge($result, $args);
		echo(json_encode($result));
		wp_die();
	}

	public function commentParentSet(){
		if (!isset($_POST['child'])) {
			return self::ajaxReturnAnswer('error');
		}

		$parent = $child = null;

		if (isset($_POST['parent'])) {
			$parent = intval($_POST['parent']);
		}
		
		if (isset($_POST['child'])) {
			$child = intval($_POST['child']);
		}

		if (empty($child)) {
			return self::ajaxReturnAnswer('error');
		}

		if (!$parent || $child == $parent) {
			$parent = 0;
		}

		/* @var $wpdb wpdb */
		global $wpdb;

		if ($parent) {
			$parentIdTest = $parent;
			$antiLoop = 0;
			
			do {
				$parentIdTest = $wpdb->get_var("SELECT comment_parent FROM wp_comments WHERE comment_ID =" . intval($parentIdTest) . "");

				if ($parentIdTest == $child) {
					return self::ajaxReturnAnswer('error', array('error' => 'error, recursion comments structure, please select another parent comment'));
				}

				if ($antiLoop++ > 100) {
					$parent = 0;
					break;
				}
			} while($parentIdTest);
		}

		$wpdb->query("UPDATE wp_comments SET comment_parent = " . intval($parent) . " WHERE comment_ID = " . intval($child) . "");

		if (!$parent) {
			return self::ajaxReturnAnswer('ok', array('info' => 'ok, parent comment clear, reload page'));
		} else {
			return self::ajaxReturnAnswer('ok');
		}
	}


	public function commentTextAddHtml($commentText, $comment)
	{
		$this->addAssets();
		
		$html =
			'<div class="insys-comment-parent"><div>' .
				'<label><input type="radio" name="parent" value="' . $comment->comment_ID . '">parent</label>' .
				'<label><input type="radio" name="child" value="' . $comment->comment_ID . '">child</label>' .
				'<a href="#">apply</a> ' .
			'</div></div>';

		$commentText = $commentText . $html;

		return $commentText;
	}

	private $scriptsLoaded = false;
	
	private function addAssets() {
		if ($this->scriptsLoaded) {
			return false;
		}
		
		$this->scriptsLoaded = true;

		wp_enqueue_style(
			'insys-comment-parent',
			plugins_url('style.css', __FILE__),
			array(),
			self::VERSION
		);

		wp_enqueue_script(
			'insys-comment-parent',
			plugins_url('script.js', __FILE__),
			array('jquery'),
			self::VERSION
		);

		wp_localize_script(
			'insys-comment-parent',
			'insysCommentParent',
            array( 'plugin_url'=> plugins_url('', __FILE__), 'ajax_url' => admin_url( 'admin-ajax.php' ))
		);

		return true;
	}
}

new InsysChangeCommentParent();
