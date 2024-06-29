<?php
/**
 * Virtual instructor module Admin class file.
 *
 * TODO: change CSS/JS selectors in this file to use approved CSS classes.
 *
 * @since 4.13.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\AI\Virtual_Instructor;

use LDLMS_Post_Types;
use LearnDash\Core\Models\Virtual_Instructor;
use LearnDash\Core\Modules\AI\Virtual_Instructor\AJAX\Process_Setup_Wizard;
use LearnDash\Core\Modules\AJAX\Search_Posts;
use Learndash_Admin_Menus_Tabs;
use LearnDash_Settings_Section_AI_Integrations;
use WP_Post;
use WP_Role;

/**
 * Virtual instructor module Admin class.
 *
 * This class manages the admin side of the virtual instructor module.
 *
 * @since 4.13.0
 */
class Admin {
	/**
	 * Post type slug.
	 *
	 * @since 4.13.0
	 *
	 * @var string
	 */
	private $post_type;

	/**
	 * Constructor.
	 *
	 * @since 4.13.0
	 */
	public function __construct() {
		$this->post_type = learndash_get_post_type_slug( LDLMS_Post_Types::VIRTUAL_INSTRUCTOR );
	}

	/**
	 * Registers post type for virtual instructor.
	 *
	 * @since 4.13.0
	 *
	 * @param array<string, array<string, mixed>> $post_args Existing LearnDash post types args to be registered.
	 *
	 * @return array<string, array<string, mixed>> Returned LearnDash custom post types args to be registered.
	 */
	public function register_post_type( array $post_args ): array {
		$post_args[ $this->post_type ] = [
			'plugin_name'        => __( 'Virtual Instructor', 'learndash' ),
			'slug_name'          => $this->post_type,
			'post_type'          => $this->post_type,
			'template_redirect'  => false,
			'taxonomies'         => [],
			'cpt_options'        => [
				'has_archive'         => false,
				'hierarchical'        => false,
				'supports'            => [
					'title',
				],
				'labels'              => [
					'name'                     => __( 'Virtual Instructors', 'learndash' ),
					'singular_name'            => __( 'Virtual Instructor', 'learndash' ),
					'add_new'                  => __( 'Add New', 'learndash' ),
					'add_new_item'             => __( 'Add New Virtual Instructor', 'learndash' ),
					'edit_item'                => __( 'Edit Virtual Instructor', 'learndash' ),
					'new_item'                 => __( 'New Virtual Instructor', 'learndash' ),
					'all_items'                => __( 'Virtual Instructors', 'learndash' ),
					'view_item'                => __( 'View Virtual Instructor', 'learndash' ),
					'view_items'               => __( 'View Virtual Instructors', 'learndash' ),
					'search_items'             => __( 'Search Virtual Instructors', 'learndash' ),
					'not_found'                => __( 'No Virtual Instructor found', 'learndash' ),
					'not_found_in_trash'       => __( 'No Virtual Instructor found in Trash', 'learndash' ),
					'parent_item_colon'        => '',
					'menu_name'                => __( 'Virtual Instructors', 'learndash' ),
					'item_published'           => __( 'Virtual Instructor Created', 'learndash' ),
					'item_published_privately' => __( 'Virtual Instructor Created Privately', 'learndash' ),
					'item_reverted_to_draft'   => __( 'Virtual Instructor Reverted to Draft', 'learndash' ),
					'item_scheduled'           => __( 'Virtual Instructor Scheduled', 'learndash' ),
					'item_updated'             => __( 'Virtual Instructor Updated', 'learndash' ),
					'item_trashed'             => __( 'Virtual Instructor trashed.', 'learndash' ),
				],
				'capability_type'     => 'virtual_instructor',
				'capabilities'        => $this->get_user_capabilities_map(),
				'public'              => false,
				'map_meta_cap'        => true,
				'exclude_from_search' => true,
				'show_in_rest'        => false,
			],
			'options_page_title' => __( 'Virtual Instructor Settings', 'learndash' ),
			'fields'             => [],
			'default_options'    => [],
		];

		return $post_args;
	}

	/**
	 * Filters bulk post updated messages for virtual instructor.
	 *
	 * @since 4.13.0
	 *
	 * @param array<string, array<string, string>> $bulk_messages Existing messages.
	 * @param array<string, int>                   $bulk_counts Post counts for different update statuses.
	 *
	 * @return array<string, array<string, string>> Returned messages.
	 */
	public function filter_bulk_post_updated_messages( $bulk_messages, array $bulk_counts ): array {
		$screen = get_current_screen();

		if (
			! $screen
			|| $screen->post_type !== $this->post_type
		) {
			return $bulk_messages;
		}

		$bulk_messages['post'] = [
			/* translators: %s: Number of virtual instructors. */
			'updated'   => _n( '%s virtual instructor updated.', '%s virtual instructors updated.', $bulk_counts['updated'], 'learndash' ),
			'locked'    => ( 1 === $bulk_counts['locked'] )
				? __( '1 virtual instructor not updated, somebody is editing it.', 'learndash' )
				/* translators: %s: Number of virtual instructors. */
				: _n( '%s virtual instructor not updated, somebody is editing it.', '%s virtual instructors not updated, somebody is editing them.', $bulk_counts['locked'], 'learndash' ),
			/* translators: %s: Number of virtual instructors. */
			'deleted'   => _n( '%s virtual instructor permanently deleted.', '%s virtual instructors permanently deleted.', $bulk_counts['deleted'], 'learndash' ),
			/* translators: %s: Number of virtual instructors. */
			'trashed'   => _n( '%s virtual instructor moved to the Trash.', '%s virtual instructors moved to the Trash.', $bulk_counts['trashed'], 'learndash' ),
			/* translators: %s: Number of virtual instructors. */
			'untrashed' => _n( '%s virtual instructor restored from the Trash.', '%s virtual instructors restored from the Trash.', $bulk_counts['untrashed'], 'learndash' ),
		];

		return $bulk_messages;
	}

	/**
	 * Registers submenu;
	 *
	 * @since 4.13.0
	 *
	 * @param array<string, array<string, string>> $ld_submenu Existing LearnDash submenu.
	 *
	 * @return array<string, array<string, string>> Returned LearnDash submenu.
	 */
	public function register_submenu( $ld_submenu ): array {
		global $submenu;

		if (
			! current_user_can( LEARNDASH_ADMIN_CAPABILITY_CHECK )
			|| ! isset( $submenu[ 'edit.php?post_type=' . $this->post_type ] )
		) {
			return $ld_submenu;
		}

		$new_submenu = [
			$this->post_type => [
				'name'  => __( 'Virtual Instructors', 'learndash' ),
				'cap'   => LEARNDASH_ADMIN_CAPABILITY_CHECK,
				'link'  => 'edit.php?post_type=' . $this->post_type,
				'class' => 'submenu-virtual-instructor',
			],
		];

		// Adds submenu after 'ld-exam' submenu.

		$exam_post_slug = learndash_get_post_type_slug( LDLMS_Post_Types::EXAM );
		$index          = array_search( $exam_post_slug, array_keys( $ld_submenu ), true );

		array_splice( $ld_submenu, $index + 1, 0, $new_submenu );

		return $ld_submenu;
	}

	/**
	 * Registers submenu items.
	 *
	 * @since 4.13.0
	 *
	 * @return void
	 */
	public function register_submenu_items(): void {
		$ld_admin_tabs = Learndash_Admin_Menus_Tabs::get_instance();

		if ( ! $ld_admin_tabs instanceof Learndash_Admin_Menus_Tabs ) {
			return;
		}

		$ld_admin_tabs->add_admin_tab_item(
			'edit.php?post_type=' . $this->post_type,
			[
				'link' => 'edit.php?post_type=' . $this->post_type,
				'name' => __( 'Virtual Instructors', 'learndash' ),
				'id'   => 'edit-' . $this->post_type,
				'cap'  => LEARNDASH_ADMIN_CAPABILITY_CHECK,
			],
			5
		);
	}

	/**
	 * Manages meta boxes.
	 *
	 * @since 4.13.0
	 *
	 * @return void
	 */
	public function manage_meta_boxes(): void {
		remove_meta_box( 'slugdiv', $this->post_type, 'normal' );
	}

	/**
	 * Sets virtual instructor list columns.
	 *
	 * @since 4.13.0
	 *
	 * @param array<string, string> $columns Existing columns.
	 *
	 * @return array<string, string> Returned columns.
	 */
	public function manage_posts_columns( $columns ): array {
		unset( $columns['date'] );

		$columns['title']  = _x( 'Name', 'Virtual Instructor Name', 'learndash' );
		$columns['course'] = learndash_get_custom_label( 'courses' );
		$columns['group']  = learndash_get_custom_label( 'groups' );

		return $columns;
	}

	/**
	 * Outputs custom columns values.
	 *
	 * @since 4.13.0
	 *
	 * @param string $column_name Column name.
	 * @param int    $post_id     Post ID.
	 *
	 * @return void
	 */
	public function manage_posts_custom_column( string $column_name, int $post_id ): void {
		$post = get_post( $post_id );

		if ( ! $post instanceof WP_Post ) {
			return;
		}

		$virtual_instructor = Virtual_Instructor::create_from_post( $post );

		switch ( $column_name ) {
			case 'course':
			case 'group':
				$this->output_associated_fields_column( $virtual_instructor, $column_name );
				break;
		}
	}

	/**
	 * Changes default title placeholder on post edit screen.
	 *
	 * @since 4.13.0
	 *
	 * @param string  $title Original title text.
	 * @param WP_Post $post  WP post object.
	 *
	 * @return string Returned title placeholder.
	 */
	public function change_title_placeholder( $title, $post ): string {
		if ( $post->post_type !== $this->post_type ) {
			return $title;
		}

		return __( 'Add virtual instructor name', 'learndash' );
	}

	/**
	 * Filters sample permalink HTML.
	 *
	 * @since 4.13.0
	 *
	 * @param string  $html      Existing HTML.
	 * @param int     $post_id   Post ID.
	 * @param string  $new_title New title.
	 * @param string  $slug      Post slug.
	 * @param WP_Post $post      WP post object.
	 *
	 * @return string Returned sample permalink HTML.
	 */
	public function filter_get_sample_permalink_html( $html, $post_id, $new_title, $slug, $post ) {
		if ( $post->post_type !== $this->post_type ) {
			return $html;
		}

		// Returns empty string because virtual instructor post type doesn't have permalink.
		return '';
	}

	/**
	 * Gets user capabilities map for virtual instructor post type.
	 *
	 * @since 4.13.0
	 *
	 * @return array<string, string> User capabilities map.
	 */
	private function get_user_capabilities_map(): array {
		return [
			'read_post'              => 'read_virtual_instructor',
			'publish_posts'          => 'publish_virtual_instructors',
			'edit_posts'             => 'edit_virtual_instructors',
			'edit_post'              => 'edit_virtual_instructor',
			'edit_others_posts'      => 'edit_others_virtual_instructors',
			'delete_posts'           => 'delete_virtual_instructors',
			'delete_others_posts'    => 'delete_others_virtual_instructors',
			'read_private_posts'     => 'read_private_virtual_instructors',
			'delete_post'            => 'delete_virtual_instructor',
			'edit_published_posts'   => 'edit_published_virtual_instructors',
			'delete_published_posts' => 'delete_published_virtual_instructors',
		];
	}

	/**
	 * Registers user capabilities.
	 *
	 * @since 4.13.0
	 *
	 * @return void
	 */
	public function register_user_capabilities(): void {
		$admin_role = get_role( 'administrator' );

		if ( ! $admin_role instanceof WP_Role ) {
			return;
		}

		foreach ( $this->get_user_capabilities_map() as $capability ) {
			if ( ! $admin_role->has_cap( $capability ) ) {
				$admin_role->add_cap( $capability );
			}
		}
	}

	/**
	 * Outputs associated fields column value.
	 *
	 * @since 4.13.0
	 *
	 * @param Virtual_Instructor $virtual_instructor Virtual instructor object.
	 * @param string             $column              Column name.
	 *
	 * @return void
	 */
	public function output_associated_fields_column( Virtual_Instructor $virtual_instructor, string $column = 'course' ): void {
		if ( ! in_array( $column, [ 'course', 'group' ], true ) ) {
			return;
		}

		if ( $column === 'course' ) {
			$object_ids       = $virtual_instructor->get_course_ids();
			$applied_callable = 'is_applied_to_all_courses';
			$type_label       = learndash_get_custom_label( 'courses' );
		} else {
			$object_ids       = $virtual_instructor->get_group_ids();
			$applied_callable = 'is_applied_to_all_groups';
			$type_label       = learndash_get_custom_label( 'groups' );
		}

		if (
			empty( $object_ids )
			&& method_exists( $virtual_instructor, $applied_callable )
			&& ! call_user_func( [ $virtual_instructor, $applied_callable ] )
		) {
			echo '&mdash;';
			return;
		}

		$displayed_object_ids = array_slice( $object_ids, 0, 3 );

		echo '<ul class="object-list">';

		if (
			method_exists( $virtual_instructor, $applied_callable )
			&& call_user_func( [ $virtual_instructor, $applied_callable ] )
		) {
			printf(
				'<li class="object-list__item">%s</li>',
				sprintf(
					// translators: %s: Object type label.
					esc_html__( 'All %s', 'learndash' ),
					esc_html( $type_label )
				)
			);
		} else {
			foreach ( $displayed_object_ids as $object_id ) {
				printf(
					'<li class="object-list__item">%s</li>',
					esc_html( get_the_title( $object_id ) )
				);
			}

			if ( count( $object_ids ) > count( $displayed_object_ids ) ) {
				printf(
					'<li class="object-list__item object-list__others">%s</li>',
					sprintf(
						// translators: %d: Number of objects.
						esc_html__( 'and %d more', 'learndash' ),
						count( $object_ids ) - count( $displayed_object_ids )
					)
				);
			}
		}

		echo '</ul>';
	}

	/**
	 * Enqueues scripts.
	 *
	 * @since 4.13.0
	 *
	 * @return void
	 */
	public function enqueue_scripts(): void {
		$screen = get_current_screen();

		if (
			! $screen
			|| $screen->post_type !== $this->post_type
		) {
			return;
		}

		wp_enqueue_script(
			'learndash-virtual-instructor-setup-wizard',
			LEARNDASH_LMS_PLUGIN_URL . 'src/assets/dist/js/admin/modules/ai/virtual-instructor/setup-wizard.js',
			[ 'react', 'wp-element', 'wp-components', 'wp-api-fetch', 'wp-i18n', 'wp-url' ],
			LEARNDASH_VERSION,
			true
		);

		wp_localize_script(
			'learndash-virtual-instructor-setup-wizard',
			'learndashVirtualInstructorSetupWizard',
			[
				'ajaxurl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => [
					'search_posts' => wp_create_nonce( Search_Posts::$action ),
					'setup'        => wp_create_nonce( Process_Setup_Wizard::$action ),
				],
				'actions'      => [
					'search_posts' => Search_Posts::$action,
					'setup'        => Process_Setup_Wizard::$action,
				],
				'post_types'   => [
					LDLMS_Post_Types::COURSE => learndash_get_post_type_slug( LDLMS_Post_Types::COURSE ),
					LDLMS_Post_Types::GROUP  => learndash_get_post_type_slug( LDLMS_Post_Types::GROUP ),
				],
				'field_values' => [
					'openai_api_key' => LearnDash_Settings_Section_AI_Integrations::get_setting( 'openai_api_key' ),
					'banned_words'   => Settings\Page_Section::get_setting( 'banned_words' ),
					'error_message'  => Settings\Page_Section::get_setting( 'error_message' ),
				],
			]
		);

		wp_enqueue_style( 'wp-components' );
	}

	/**
	 * Updates setting virtual instructor settings in its own metadata as well so that they will be available in virtual instructor model.
	 *
	 * @since 4.13.0
	 *
	 * @param WP_Post $post    WP post object.
	 * @param string  $setting Setting name.
	 * @param mixed   $value   Setting value.
	 *
	 * @return void
	 */
	public function update_setting( $post, $setting, $value ): void {
		if ( $post->post_type !== $this->post_type ) {
			return;
		}

		update_post_meta( $post->ID, $setting, $value );
	}
}
