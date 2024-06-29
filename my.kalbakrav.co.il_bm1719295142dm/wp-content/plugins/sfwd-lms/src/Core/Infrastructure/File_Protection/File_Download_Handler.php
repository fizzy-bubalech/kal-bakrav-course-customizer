<?php
/**
 * File download handler.
 *
 * @since 4.10.3
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Infrastructure\File_Protection;

use Learndash_Admin_File_Download_Handler;

/**
 * File download handler.
 *
 * @since 4.10.3
 */
class File_Download_Handler extends Learndash_Admin_File_Download_Handler {
	/**
	 * The file download action name. It is used to generate the download URL.
	 *
	 * @since 4.10.3
	 *
	 * @var string
	 */
	protected static $file_download_action = 'learndash_file_download';

	/**
	 * Returns whether the current user can download the file.
	 *
	 * @since 4.10.3
	 *
	 * @return bool
	 */
	protected static function can_be_downloaded(): bool {
		return is_user_logged_in();
	}
}
