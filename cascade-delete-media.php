<?php
/*
Plugin Name: Cascade Delete Media
Description: Delete featured image when deleting post (if not used in other posts)
Version: 0.1.0
Author: Headspin <vegard@headspin.no>
Author URI: http://www.headspin.no
Licence: GPL2
*/
class CascadeDeleteMedia {

	public function __construct() {

		// Hook into WordPress before-delete action
		add_action('before_delete_post', array($this, 'cascadeDelete'));
	}

	public function cascadeDelete($postId) {
		global $wpdb;

		$attachmentId = get_post_thumbnail_id($postId);

		if ($attachmentId) {
			$filename = pathinfo(get_attached_file($attachmentId), PATHINFO_FILENAME);

			/* Find other posts that have this attachment as featured image or
			 * a link to it in the post content
			 *
			 * TODO: This could be improved to use a better search
			 */
			$sql = "SELECT {$wpdb->posts}.ID
				FROM {$wpdb->posts}
				INNER JOIN {$wpdb->postmeta}
				ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id
				WHERE {$wpdb->posts}.ID != %d
				AND ({$wpdb->postmeta}.meta_key = '_thumbnail_id'
					AND {$wpdb->postmeta}.meta_value = %d)
				OR {$wpdb->posts}.post_content LIKE %s
				GROUP BY {$wpdb->posts}.ID";

			$postIds = $wpdb->get_results($wpdb->prepare($sql, $postId, $attachmentId, $filename));

			// If no other posts link to this attachment, DELETE IT :o
			if (count($postIds) === 0) {

				$forceDelete = TRUE;
				wp_delete_attachment($attachmentId, $forceDelete);
			}
		}
	}

}

new CascadeDeleteMedia();
