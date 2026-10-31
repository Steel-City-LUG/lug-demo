<?php
/**
 * Demo coordinator.
 *
 * @package LUG_Demo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LUG_Demo {
	const BATCH_ID       = 'steel-city-lug-demo-v1';
	const OPTION_BATCH   = 'lug_demo_active_batch';
	const OPTION_MENU    = 'lug_demo_navigation_menu';
	const OPTION_EVENTS  = 'lug_demo_event_ids';
	const OPTION_FORUMS  = 'lug_demo_forum_ids';
	const OPTION_TOPICS  = 'lug_demo_topic_ids';
	const OPTION_REPLIES = 'lug_demo_reply_ids';
	const OPTION_ACTIVITY = 'lug_demo_activity_ids';
	const OPTION_SHIFTS   = 'lug_demo_shift_ids';
	const OPTION_ISPY     = 'lug_demo_ispy_ids';
	const OPTION_SITE_STATE = 'lug_demo_site_state';
	const OPTION_WELCOME_PAGE = 'lug_demo_welcome_page';
	const OPTION_OFFICER_STATE = 'lug_demo_officer_state';
	const OPTION_REPORT = 'lug_demo_last_report';
	const OPTION_SEEDED_VERSION = 'lug_demo_seeded_version';
	const OPTION_PROFILE_STATE = 'lug_demo_profile_state';
	const OPTION_SYSTEM_SENDER_STATE = 'lug_demo_system_sender_state';
	const USER_META_PROFILE_BATCH = '_lug_demo_profile_batch_id';
	const USER_META_BATCH = '_lug_demo_batch_id';
	const USER_META_SUPER = '_lug_demo_super';
	const CONTENT_META_BATCH = '_lug_demo_content_batch_id';
	const SANDBOXED_CAPABILITIES = array(
		'manage_options',
		'manage_network_options',
		'activate_plugins',
		'update_plugins',
		'delete_plugins',
		'install_plugins',
		'edit_plugins',
		'update_themes',
		'delete_themes',
		'install_themes',
		'edit_themes',
		'switch_themes',
		'update_core',
		'edit_users',
		'create_users',
		'delete_users',
		'promote_users',
		'customize',
		'edit_theme_options',
	);

	private $accounts;

	public function hooks() {
		add_action( 'admin_menu', array( 'LUG_Demo_Admin', 'register_menu' ), 30 );
		add_action( 'admin_post_lug_demo_seed', array( $this, 'handle_seed' ) );
		add_action( 'admin_post_lug_demo_reset', array( $this, 'handle_reset' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_action( 'wp_footer', array( $this, 'render_demo_footer' ), 20 );
		add_action( 'admin_init', array( $this, 'enforce_admin_sandbox' ), 1 );
		add_filter( 'user_has_cap', array( $this, 'restrict_sandbox_capabilities' ), 99, 4 );
		add_filter( 'lug_inventory_manager_user_ids', array( $this, 'add_demo_inventory_managers' ) );
		add_filter( 'lug_demo_seed_adapters', array( $this, 'register_core_adapter' ) );
		add_shortcode( 'lug_demo_accounts', array( $this, 'render_account_directory' ) );
		add_action( 'login_form_lug_demo', array( $this, 'handle_quick_login' ) );
		add_action( 'init', array( $this, 'maybe_seed_after_activation' ), 20 );
	}

	public function maybe_seed_after_activation() {
		$events_ready = ! empty( get_option( self::OPTION_EVENTS, array() ) );
		// This marketing-only upgrade must not reseed existing demo accounts, events or content.
		if ( $events_ready && '0.1.12' === LUG_DEMO_VERSION ) {
			if ( LUG_DEMO_VERSION !== get_option( self::OPTION_SEEDED_VERSION ) ) {
				update_option( self::OPTION_SEEDED_VERSION, LUG_DEMO_VERSION, false );
			}
			return;
		}
		if ( ! current_user_can( 'manage_options' ) || ( LUG_DEMO_VERSION === get_option( self::OPTION_SEEDED_VERSION, '' ) && $events_ready ) ) {
			return;
		}

		$report = array( 'action' => 'automatic_seed', 'completed' => current_time( 'mysql' ), 'adapters' => array(), 'errors' => array() );
		foreach ( (array) apply_filters( 'lug_demo_seed_adapters', array() ) as $key => $adapter ) {
			if ( isset( $adapter['run'] ) && is_callable( $adapter['run'] ) ) {
				try {
					$result = call_user_func( $adapter['run'], self::BATCH_ID );
					if ( is_wp_error( $result ) ) $report['errors'][ $key ] = $result->get_error_message();
					else $report['adapters'][ $key ] = isset( $adapter['label'] ) ? $adapter['label'] : $key;
				} catch ( Throwable $error ) {
					$report['errors'][ $key ] = $error->getMessage();
				}
			}
		}
		update_option( self::OPTION_BATCH, self::BATCH_ID, false );
		update_option( self::OPTION_REPORT, $report, false );
		update_option( self::OPTION_SEEDED_VERSION, LUG_DEMO_VERSION, false );
	}

	public function register_core_adapter( $adapters ) {
		$adapters['core'] = array(
			'label' => __( 'Demo accounts', 'lug-demo' ),
			'run'   => array( $this, 'seed_accounts' ),
			'reset' => array( $this, 'reset_accounts' ),
		);
		$adapters['member_profiles'] = array(
			'label' => __( 'Member profiles and locations', 'lug-demo' ),
			'run'   => array( $this, 'seed_member_profiles' ),
			'reset' => array( $this, 'reset_member_profiles' ),
		);
		$adapters['officers'] = array(
			'label' => __( 'Demo officer assignments', 'lug-demo' ),
			'run'   => array( $this, 'seed_officer_assignments' ),
			'reset' => array( $this, 'reset_officer_assignments' ),
		);
		$adapters['site_content'] = array(
			'label' => __( 'Demo site content', 'lug-demo' ),
			'run'   => array( $this, 'seed_site_content' ),
			'reset' => array( $this, 'reset_site_content' ),
		);
		$adapters['lug_events'] = array(
			'label' => __( 'LUG events', 'lug-demo' ),
			'run'   => array( $this, 'seed_events' ),
			'reset' => array( $this, 'reset_events' ),
		);
		$adapters['ispy'] = array(
			'label' => __( 'Active I-Spy challenge', 'lug-demo' ),
			'run'   => array( $this, 'seed_ispy' ),
			'reset' => array( $this, 'reset_ispy' ),
		);
		$adapters['event_shifts'] = array(
			'label' => __( 'Event shifts and signups', 'lug-demo' ),
			'run'   => array( $this, 'seed_event_shifts' ),
			'reset' => array( $this, 'reset_event_shifts' ),
		);
		$adapters['bbpress'] = array(
			'label' => __( 'Demo forums', 'lug-demo' ),
			'run'   => array( $this, 'seed_forums' ),
			'reset' => array( $this, 'reset_forums' ),
		);
		$adapters['buddypress'] = array(
			'label' => __( 'Community activity', 'lug-demo' ),
			'run'   => array( $this, 'seed_activity' ),
			'reset' => array( $this, 'reset_activity' ),
		);

		return $adapters;
	}

	public function enqueue_frontend_assets() {
		wp_enqueue_style(
			'lug-demo',
			plugins_url( 'assets/demo.css', LUG_DEMO_FILE ),
			array(),
			LUG_DEMO_VERSION
		);
	}

	public function render_demo_footer() {
		echo '<footer class="lug-demo-footer"><p><strong>' . esc_html__( 'Steel City LUG tools demo', 'lug-demo' ) . '</strong> — ' . esc_html__( 'Explore freely; the site can be reset to its starting point.', 'lug-demo' ) . '</p><nav aria-label="' . esc_attr__( 'Demo links', 'lug-demo' ) . '"><a href="' . esc_url( home_url( '/plugin-tour/' ) ) . '">' . esc_html__( 'Plugin tour', 'lug-demo' ) . '</a><a href="' . esc_url( home_url( '/getting-started/' ) ) . '">' . esc_html__( 'Getting started', 'lug-demo' ) . '</a><a href="' . esc_url( home_url( '/build-along-on-your-phone/' ) ) . '">' . esc_html__( 'Build along on your phone', 'lug-demo' ) . '</a></nav></footer>';
	}

	public function restrict_sandbox_capabilities( $allcaps, $caps, $args, $user ) {
		if ( ! $user instanceof WP_User || ! $this->is_sandboxed_user( $user->ID ) ) {
			return $allcaps;
		}

		foreach ( self::SANDBOXED_CAPABILITIES as $capability ) {
			$allcaps[ $capability ] = false;
		}

		// The demo super account may exercise lug-core's user editor, but only
		// through the explicitly allowed user-edit screen below. It never gets
		// WordPress's broader manage_options or user-management capabilities.
		if ( $this->is_demo_super_user( $user->ID ) ) {
			$allcaps['edit_users'] = true;
		}

		return $allcaps;
	}

	public function enforce_admin_sandbox() {
		if ( ! is_user_logged_in() || ! $this->is_sandboxed_user( get_current_user_id() ) ) {
			return;
		}

		$script = basename( (string) ( $_SERVER['PHP_SELF'] ?? '' ) );
		if ( 'admin-ajax.php' === $script ) {
			return;
		}
		if ( 'admin-post.php' === $script ) {
			$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';
			if ( 0 === strpos( $action, 'lug_' ) ) {
				return;
			}
			wp_safe_redirect( home_url( '/' ) );
			exit;
		}
		if ( 'user-edit.php' === $script && $this->is_demo_super_user( get_current_user_id() ) ) {
			$edited_user_id = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0;
			if ( $edited_user_id && self::BATCH_ID === get_user_meta( $edited_user_id, self::USER_META_BATCH, true ) ) {
				return;
			}
			wp_safe_redirect( home_url( '/' ) );
			exit;
		}

		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( 'admin.php' === $script && $this->is_allowed_plugin_page( $page ) ) {
			return;
		}

		wp_safe_redirect( home_url( '/' ) );
		exit;
	}

	private function is_allowed_plugin_page( $page ) {
		$allowed = (array) apply_filters(
			'lug_demo_allowed_admin_pages',
			array(
				'lug-demo',
				'lug-officers',
				'lug-welcome-pm',
				'lug-user-zip-map',
				'lug-layout-designer',
				'lug-rotating-displays',
			)
		);

		return '' !== $page && in_array( $page, array_map( 'sanitize_key', $allowed ), true );
	}

	private function is_sandboxed_user( $user_id ) {
		return (bool) $user_id && self::BATCH_ID === get_user_meta( (int) $user_id, self::USER_META_BATCH, true );
	}

	private function is_demo_super_user( $user_id ) {
		return $this->is_sandboxed_user( $user_id ) && '1' === (string) get_user_meta( (int) $user_id, self::USER_META_SUPER, true );
	}

	public function add_demo_inventory_managers( $ids ) {
		foreach ( $this->account_fixtures() as $account ) {
			if ( empty( $account['super'] ) ) {
				continue;
			}
			$user = get_user_by( 'login', $account['login'] );
			if ( $user && $this->is_demo_super_user( $user->ID ) ) {
				$ids[] = (int) $user->ID;
			}
		}

		return array_values( array_unique( array_map( 'absint', (array) $ids ) ) );
	}

	public function handle_seed() {
		$this->verify_request();
		$report = array(
			'action'    => 'seed',
			'completed' => current_time( 'mysql' ),
			'adapters'  => array(),
			'errors'    => array(),
		);

		foreach ( (array) apply_filters( 'lug_demo_seed_adapters', array() ) as $key => $adapter ) {
			if ( isset( $adapter['run'] ) && is_callable( $adapter['run'] ) ) {
				try {
					$result = call_user_func( $adapter['run'], self::BATCH_ID );
					if ( is_wp_error( $result ) ) {
						$report['errors'][ $key ] = $result->get_error_message();
					} else {
						$report['adapters'][ $key ] = isset( $adapter['label'] ) ? $adapter['label'] : $key;
					}
				} catch ( Throwable $error ) {
					$report['errors'][ $key ] = $error->getMessage();
				}
			}
		}

		update_option( self::OPTION_BATCH, self::BATCH_ID, false );
		update_option( self::OPTION_REPORT, $report, false );
		$this->redirect( 'seeded' );
	}

	public function handle_reset() {
		$this->verify_request();

		$adapters = array_reverse( (array) apply_filters( 'lug_demo_seed_adapters', array() ) );
		foreach ( $adapters as $adapter ) {
			if ( isset( $adapter['reset'] ) && is_callable( $adapter['reset'] ) ) {
				call_user_func( $adapter['reset'], self::BATCH_ID );
			}
		}

		delete_option( self::OPTION_BATCH );
		update_option(
			self::OPTION_REPORT,
			array(
				'action'    => 'reset',
				'completed' => current_time( 'mysql' ),
				'adapters'  => array(),
				'errors'    => array(),
			),
			false
		);
		$this->redirect( 'reset' );
	}

	public function seed_accounts( $batch_id ) {
		// Rebuild starts from the exact current fixture set, removing accounts
		// from older demo versions (including retired personas).
		$this->reset_accounts( $batch_id );
		foreach ( $this->account_fixtures() as $account ) {
			$user = get_user_by( 'login', $account['login'] );
			if ( $user && self::BATCH_ID !== get_user_meta( $user->ID, self::USER_META_BATCH, true ) ) {
				// Do not overwrite or later delete a real site user on a login collision.
				continue;
			}
			$user_id = $user ? (int) $user->ID : 0;

			$data = array(
				'user_login'   => $account['login'],
				'user_pass'    => $account['password'],
				'user_email'   => $account['email'],
				'role'         => $account['role'],
				'display_name' => $account['display_name'],
				'description'  => $account['description'],
			);

			if ( $user_id ) {
				$data['ID'] = $user_id;
				$user_id = wp_update_user( $data );
			} else {
				$user_id = wp_insert_user( $data );
			}

			if ( ! is_wp_error( $user_id ) ) {
				update_user_meta( $user_id, self::USER_META_BATCH, $batch_id );
				if ( ! empty( $account['super'] ) ) {
					update_user_meta( $user_id, self::USER_META_SUPER, '1' );
				} else {
					delete_user_meta( $user_id, self::USER_META_SUPER );
				}
			}
		}

		$system = get_user_by( 'login', 'system-messages' );
		if ( $system && function_exists( 'get_option' ) ) {
			$previous = get_option( 'lug_system_message_sender_id', null );
			if ( ! get_option( self::OPTION_SYSTEM_SENDER_STATE, false ) ) {
				update_option( self::OPTION_SYSTEM_SENDER_STATE, array( 'exists' => false !== $previous, 'value' => $previous ), false );
			}
			update_option( 'lug_system_message_sender_id', (int) $system->ID, false );
		}
	}

	public function reset_accounts( $batch_id ) {
		$sender_state = get_option( self::OPTION_SYSTEM_SENDER_STATE, false );
		if ( is_array( $sender_state ) ) {
			if ( ! empty( $sender_state['exists'] ) ) update_option( 'lug_system_message_sender_id', $sender_state['value'], false );
			else delete_option( 'lug_system_message_sender_id' );
			delete_option( self::OPTION_SYSTEM_SENDER_STATE );
		}
		if ( ! function_exists( 'wp_delete_user' ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
		}

		$users = get_users(
			array(
				'fields'     => 'ids',
				'number'     => -1,
				'meta_key'   => self::USER_META_BATCH,
				'meta_value' => $batch_id,
			)
		);

		foreach ( $users as $user_id ) {
			wp_delete_user( (int) $user_id );
		}
	}

	public function seed_member_profiles( $batch_id ) {
		$profiles = (array) require LUG_DEMO_DIR . 'fixtures/profiles.php';
		foreach ( $profiles as $login => $profile ) {
			$user = get_user_by( 'login', $login );
			if ( ! $user ) {
				continue;
			}

			update_user_meta( $user->ID, self::USER_META_PROFILE_BATCH, $batch_id );
			foreach ( $profile as $key => $value ) {
				$meta_key = sanitize_key( $key );
				$state    = (array) get_option( self::OPTION_PROFILE_STATE, array() );
				if ( ! isset( $state[ $user->ID ][ $meta_key ] ) ) {
					$state[ $user->ID ][ $meta_key ] = array(
						'exists' => metadata_exists( 'user', $user->ID, $meta_key ),
						'value'  => get_user_meta( $user->ID, $meta_key, true ),
					);
					update_option( self::OPTION_PROFILE_STATE, $state, false );
				}
				update_user_meta( $user->ID, $meta_key, sanitize_text_field( $value ) );
			}

			if ( function_exists( 'xprofile_set_field_data' ) && ! empty( $profile['zip'] ) ) {
				$field_id = 0;
				$settings = get_option( 'lug_user_zip_map_settings', array() );
				if ( is_array( $settings ) ) {
					$field_id = absint( $settings['zip_profile_field_id'] ?? 0 );
				}
				if ( $field_id ) {
					xprofile_set_field_data( $field_id, $user->ID, $profile['zip'] );
				}
			}
		}
	}

	public function reset_member_profiles( $batch_id ) {
		$users = get_users(
			array(
				'fields'     => 'ids',
				'number'     => -1,
				'meta_key'   => self::USER_META_PROFILE_BATCH,
				'meta_value' => $batch_id,
			)
		);

		foreach ( $users as $user_id ) {
			$state = (array) get_option( self::OPTION_PROFILE_STATE, array() );
			foreach ( (array) ( $state[ $user_id ] ?? array() ) as $meta_key => $previous ) {
				if ( ! empty( $previous['exists'] ) ) {
					update_user_meta( $user_id, $meta_key, $previous['value'] );
				} else {
					delete_user_meta( $user_id, $meta_key );
				}
			}
			delete_user_meta( $user_id, self::USER_META_PROFILE_BATCH );
		}
		delete_option( self::OPTION_PROFILE_STATE );
	}

	public function seed_officer_assignments( $batch_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'lug_officer_positions';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return;
		}

		$this->reset_officer_assignments( $batch_id );
		$assignments = array(
			'President'  => 'lug-demo-organizer',
			'Secretary'  => 'lug-demo-member',
			'Ambassador' => 'lug-demo-organizer',
			'Treasurer'  => 'demo-super',
		);
		$previous = array();

		foreach ( $assignments as $title => $login ) {
			$user = get_user_by( 'login', $login );
			$position = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE title = %s", $title ), ARRAY_A );
			if ( ! $user || ! is_array( $position ) ) {
				continue;
			}

			$previous[] = array(
				'id'                => (int) $position['id'],
				'current_member_id' => $position['current_member_id'],
				'term_start_date'   => $position['term_start_date'],
				'term_end_date'     => $position['term_end_date'],
			);
			$wpdb->update(
				$table,
				array(
					'current_member_id' => (int) $user->ID,
					'term_start_date'   => current_time( 'Y-m-d' ),
					'term_end_date'     => gmdate( 'Y-m-d', strtotime( '+1 year' ) ),
				),
				array( 'id' => (int) $position['id'] ),
				array( '%d', '%s', '%s' ),
				array( '%d' )
			);
		}

		update_option( self::OPTION_OFFICER_STATE, $previous, false );
	}

	public function reset_officer_assignments( $batch_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'lug_officer_positions';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) {
			foreach ( (array) get_option( self::OPTION_OFFICER_STATE, array() ) as $position ) {
				$wpdb->update(
					$table,
					array(
						'current_member_id' => $position['current_member_id'],
						'term_start_date'   => $position['term_start_date'],
						'term_end_date'     => $position['term_end_date'],
					),
					array( 'id' => (int) $position['id'] ),
					array( '%d', '%s', '%s' ),
					array( '%d' )
				);
			}
		}
		delete_option( self::OPTION_OFFICER_STATE );
	}

	public function seed_site_content( $batch_id ) {
		$this->reset_site_content( $batch_id );

		$fixture_file = LUG_DEMO_DIR . 'fixtures/site-content.php';
		$fixtures     = file_exists( $fixture_file ) ? (array) require $fixture_file : array();
		$page_ids     = array();

		foreach ( $fixtures as $fixture ) {
			$post_id = wp_insert_post(
				array(
					'post_title'   => $fixture['title'],
					'post_name'    => $fixture['slug'],
					'post_content' => $fixture['content'],
					'post_excerpt' => isset( $fixture['excerpt'] ) ? $fixture['excerpt'] : '',
					'post_status'  => 'publish',
					'post_type'    => isset( $fixture['post_type'] ) ? $fixture['post_type'] : 'page',
				),
				true
			);

			if ( ! is_wp_error( $post_id ) ) {
				update_post_meta( $post_id, self::CONTENT_META_BATCH, $batch_id );
				$page_ids[ $fixture['slug'] ] = (int) $post_id;
			}
		}

		$menu_id = $this->seed_navigation_menu( $page_ids );
		if ( ! empty( $page_ids['welcome-to-the-demo-lug'] ) ) {
			$this->configure_site_shell( $page_ids['welcome-to-the-demo-lug'], $menu_id );
		}
	}

	public function reset_site_content( $batch_id ) {
		$content_ids = get_posts(
			array(
				'post_type'      => 'any',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'   => true,
				'meta_key'       => self::CONTENT_META_BATCH,
				'meta_value'     => $batch_id,
			)
		);

		foreach ( $content_ids as $post_id ) {
			wp_delete_post( (int) $post_id, true );
		}

		$menu_id = (int) get_option( self::OPTION_MENU, 0 );
		if ( $menu_id ) {
			wp_delete_nav_menu( $menu_id );
			delete_option( self::OPTION_MENU );
		}

		$this->restore_site_shell();
	}

	public function seed_events( $batch_id ) {
		if ( ! function_exists( 'lug_event_provider_available' ) || ! lug_event_provider_available() ) {
			return;
		}

		$this->reset_events( $batch_id );
		global $wpdb;
		$table     = function_exists( 'lug_event_provider_table' ) ? lug_event_provider_table() : 'lug_events';
		$columns   = array_map( 'strtolower', (array) $wpdb->get_col( "DESCRIBE `{$table}`", 0 ) );
		$events    = require LUG_DEMO_DIR . 'fixtures/events.php';
		$event_ids = array();

		foreach ( (array) $events as $event ) {
			$organizer = ! empty( $event['creator_login'] ) ? get_user_by( 'login', $event['creator_login'] ) : get_user_by( 'login', 'lug-demo-organizer' );
			$creator   = $organizer ? (int) $organizer->ID : get_current_user_id();
			$data = array(
				'name'                => $event['name'],
				'start_date'          => $event['start_date'],
				'end_date'            => $event['end_date'],
				'description'         => $event['description'],
				'attendees'           => $event['attendees'],
				'classification'      => $event['classification'],
				'status'              => 'open',
				'creator'             => $creator,
				'url'                 => $event['url'],
				'event_location'      => $event['event_location'],
				'percent_kids'        => 0,
				'percent_teens'       => 0,
				'percent_adults'      => 0,
				'social_links'        => $event['social_links'],
				'entrance_fee'        => $event['entrance_fee'],
				'community_enjoyment' => $event['community_enjoyment'],
			);

			$data = array_intersect_key( $data, array_flip( $columns ) );
			if ( false !== $wpdb->insert( $table, $data ) ) {
				$event_ids[] = (int) $wpdb->insert_id;
			}
		}

		update_option( self::OPTION_EVENTS, $event_ids, false );
	}

	public function reset_events( $batch_id ) {
		$event_ids = (array) get_option( self::OPTION_EVENTS, array() );
		global $wpdb;
		$event_table = function_exists( 'lug_event_provider_table' ) ? lug_event_provider_table() : 'lug_events';
		$event_columns = array_map( 'strtolower', (array) $wpdb->get_col( "DESCRIBE `{$event_table}`", 0 ) );
		$legacy_delete_supported = function_exists( 'lug_event_delete' ) && in_array( 'url', $event_columns, true ) && $this->table_exists( 'lug_events_participants' );
		foreach ( $event_ids as $event_id ) {
			// Credits are batch-owned through the event id. Remove the ledger rows
			// before deleting the event so reset never leaves PAB history behind.
			if ( $this->table_exists( 'lug_pab_log' ) ) {
				$wpdb->delete( 'lug_pab_log', array( 'event_id' => (int) $event_id ), array( '%d' ) );
			}
			if ( $legacy_delete_supported ) {
				lug_event_delete( (int) $event_id );
			} else {
				$wpdb->delete( 'lug_events_shifts', array( 'event' => (int) $event_id ), array( '%d' ) );
				if ( function_exists( 'lug_event_shift_order_table_name' ) && $this->table_exists( lug_event_shift_order_table_name() ) ) {
					$wpdb->delete( lug_event_shift_order_table_name(), array( 'event_id' => (int) $event_id ), array( '%d' ) );
				}
				$wpdb->delete( $event_table, array( 'id' => (int) $event_id ), array( '%d' ) );
			}
		}
		delete_option( self::OPTION_EVENTS );
	}

	public function seed_ispy( $batch_id ) {
		if ( ! function_exists( 'lug_ispy_table_name' ) || ! function_exists( 'lug_ispy_db_table_exists' ) || ! function_exists( 'lug_ispy_run_schema_upgrade' ) ) {
			return;
		}
		lug_ispy_run_schema_upgrade( true );
		$events_table = lug_ispy_table_name( 'events' );
		$items_table  = lug_ispy_table_name( 'items' );
		if ( ! $events_table || ! $items_table || ! lug_ispy_db_table_exists( $events_table ) || ! lug_ispy_db_table_exists( $items_table ) ) {
			return;
		}

		$this->reset_ispy( $batch_id );
		$event_ids = (array) get_option( self::OPTION_EVENTS, array() );
		$current_event_id = 0;
		global $wpdb;
		foreach ( $event_ids as $event_id ) {
			$row = $wpdb->get_row( $wpdb->prepare( 'SELECT id, start_date, end_date FROM lug_events WHERE id = %d', (int) $event_id ), ARRAY_A );
			if ( is_array( $row ) && strtotime( (string) $row['start_date'] ) <= current_time( 'timestamp' ) && strtotime( (string) $row['end_date'] ) >= current_time( 'timestamp' ) ) {
				$current_event_id = (int) $row['id'];
				break;
			}
		}
		if ( ! $current_event_id ) {
			// Keep the public challenge available even if the event provider's
			// date format differs from the WordPress site clock.
			$current_event_id = ! empty( $event_ids ) ? (int) reset( $event_ids ) : 0;
		}
		if ( ! $current_event_id ) {
			return;
		}
		// LUG Core's public I-Spy gate consumes the linked event end date as a
		// local calendar date. Keep the current showcase event publicly active
		// while preserving the full datetime on the past and future fixtures.
		$wpdb->update( 'lug_events', array( 'end_date' => wp_date( 'Y-m-d', strtotime( '+1 day', current_time( 'timestamp' ) ) ) ), array( 'id' => $current_event_id ), array( '%s' ), array( '%d' ) );
		$now = current_time( 'mysql' );
		$event_columns = array_map( 'strtolower', (array) $wpdb->get_col( "DESCRIBE `{$events_table}`", 0 ) );
		$event_data = array( 'name' => 'Build Along: Steel City Landmarks', 'message' => 'Find the hidden landmarks, scan the QR code, and build along from your phone.', 'lug_event' => $current_event_id, 'active' => 1, 'started' => 0, 'completed' => 0, 'updated_by' => get_current_user_id(), 'created_at' => $now, 'updated_at' => $now );
		$wpdb->insert( $events_table, array_intersect_key( $event_data, array_flip( $event_columns ) ) );
		$ispy_id = (int) $wpdb->insert_id;
		if ( ! $ispy_id ) {
			return;
		}
		$item_names = array( 'Steel City bridge', 'Neighborhood row house', 'LUG meetup sign', 'Three-color mosaic', 'Hidden minifigure' );
		$item_columns = array_map( 'strtolower', (array) $wpdb->get_col( "DESCRIBE `{$items_table}`", 0 ) );
		foreach ( $item_names as $priority => $name ) {
			$item_data = array( 'event' => $ispy_id, 'priority' => $priority + 1, 'name' => $name, 'hint_image_id' => 0, 'hint_members_only' => 0, 'lasteditor' => get_current_user_id(), 'edited' => $now );
			$wpdb->insert( $items_table, array_intersect_key( $item_data, array_flip( $item_columns ) ) );
		}
		update_option( self::OPTION_ISPY, array( $ispy_id ), false );
	}

	public function reset_ispy( $batch_id ) {
		if ( ! function_exists( 'lug_ispy_table_name' ) || ! function_exists( 'lug_ispy_db_table_exists' ) ) {
			return;
		}
		$events_table = lug_ispy_table_name( 'events' );
		$items_table  = lug_ispy_table_name( 'items' );
		if ( ! $events_table || ! $items_table || ! lug_ispy_db_table_exists( $events_table ) || ! lug_ispy_db_table_exists( $items_table ) ) {
			return;
		}
		global $wpdb;
		foreach ( (array) get_option( self::OPTION_ISPY, array() ) as $ispy_id ) {
			$wpdb->delete( $items_table, array( 'event' => (int) $ispy_id ), array( '%d' ) );
			$wpdb->delete( $events_table, array( 'id' => (int) $ispy_id ), array( '%d' ) );
		}
		delete_option( self::OPTION_ISPY );
	}

	public function seed_event_shifts( $batch_id ) {
		$this->ensure_event_shift_schema();
		if ( ! function_exists( 'lug_event_shift_save' ) || ! function_exists( 'lug_event_shifts_table_exists' ) || ! lug_event_shifts_table_exists() ) {
			return;
		}

		$this->reset_event_shifts( $batch_id );
		global $wpdb;
		$event_ids = (array) get_option( self::OPTION_EVENTS, array() );
		$fixtures  = require LUG_DEMO_DIR . 'fixtures/shifts.php';
		$shift_ids = array();
		$signup_users = array();
		foreach ( array( 'lug-demo-member', 'lug-demo-organizer' ) as $login ) {
			$user = get_user_by( 'login', $login );
			if ( $user ) {
				$signup_users[] = (int) $user->ID;
			}
		}

		foreach ( $event_ids as $event_id ) {
			$event = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM lug_events WHERE id = %d', (int) $event_id ), ARRAY_A );
			if ( ! is_array( $event ) ) {
				continue;
			}

			foreach ( (array) $fixtures as $fixture ) {
				$saved = lug_event_shift_save(
					$event,
					array(
						'name'        => $fixture['name'],
						'cups'        => $fixture['cups'],
						'cap'         => $fixture['cap'],
						'displaydate' => 3,
						'priority'    => 0,
					)
				);

				if ( is_wp_error( $saved ) || empty( $saved['id'] ) ) {
					continue;
				}

				$shift_id = (int) $saved['id'];
				$shift_ids[] = $shift_id;
				if ( function_exists( 'perform_signup' ) && $this->table_exists( 'lug_events_participants' ) ) {
					foreach ( (array) $signup_users as $user_id ) {
						if ( $fixture['signups'] > 0 ) {
							perform_signup( $user_id, $shift_id );
							$fixture['signups']--;
						}
					}
				}
			}
		}

		// Close the elapsed fixture after signups exist. lug_close_event() locks
		// its shifts and uses the normal LUG Core credit-distribution path, which
		// makes the demo activity visible in PAB history just like a real event.
		$shift_columns = array_map( 'strtolower', (array) $wpdb->get_col( 'DESCRIBE `lug_events_shifts`', 0 ) );
		$can_close_with_credits = function_exists( 'lug_close_event' )
			&& $this->table_exists( 'lug_events_participants' )
			&& $this->table_exists( 'lug_pab_log' )
			&& in_array( 'locked', $shift_columns, true );
		if ( $can_close_with_credits ) {
			$now = current_time( 'timestamp' );
			foreach ( $event_ids as $event_id ) {
				$event = $wpdb->get_row( $wpdb->prepare( 'SELECT end_date, status FROM lug_events WHERE id = %d', (int) $event_id ), ARRAY_A );
				if ( is_array( $event ) && 'closed' !== strtolower( (string) $event['status'] ) && strtotime( (string) $event['end_date'] ) < $now ) {
					lug_close_event( (int) $event_id );
				}
			}
		}

		update_option( self::OPTION_SHIFTS, $shift_ids, false );
	}

	private function ensure_event_shift_schema() {
		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}
		global $wpdb;
		$charset = $wpdb->get_charset_collate();
		dbDelta( "CREATE TABLE lug_events_shifts (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			event bigint(20) unsigned NOT NULL DEFAULT 0,
			name varchar(255) NOT NULL DEFAULT '',
			cups decimal(10,2) NOT NULL DEFAULT 0,
			cap int(11) NOT NULL DEFAULT 0,
			displaydate tinyint(3) NOT NULL DEFAULT 3,
			start_date datetime NULL,
			end_date datetime NULL,
			locked tinyint(1) NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			KEY event (event),
			KEY start_date (start_date)
		) ENGINE=InnoDB {$charset};" );
		dbDelta( "CREATE TABLE lug_events_participants (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			shift bigint(20) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			UNIQUE KEY user_shift (user_id,shift),
			KEY shift (shift)
		) ENGINE=InnoDB {$charset};" );
	}

	public function reset_event_shifts( $batch_id ) {
		if ( function_exists( 'delete_shift_data' ) && $this->table_exists( 'lug_events_participants' ) ) {
			foreach ( (array) get_option( self::OPTION_SHIFTS, array() ) as $shift_id ) {
				delete_shift_data( (int) $shift_id );
			}
		} else {
			global $wpdb;
			foreach ( (array) get_option( self::OPTION_SHIFTS, array() ) as $shift_id ) {
				$wpdb->delete( 'lug_events_shifts', array( 'id' => (int) $shift_id ), array( '%d' ) );
				if ( function_exists( 'lug_event_shift_order_table_name' ) && $this->table_exists( lug_event_shift_order_table_name() ) ) {
					$wpdb->delete( lug_event_shift_order_table_name(), array( 'shift_id' => (int) $shift_id ), array( '%d' ) );
				}
			}
		}
		delete_option( self::OPTION_SHIFTS );
	}

	public function seed_forums( $batch_id ) {
		if ( ! function_exists( 'bbp_insert_forum' ) || ! function_exists( 'bbp_insert_topic' ) || ! function_exists( 'bbp_insert_reply' ) ) {
			return;
		}

		$this->reset_forums( $batch_id );
		$moderator = get_user_by( 'login', 'lug-demo-organizer' );
		$member    = get_user_by( 'login', 'lug-demo-member' );
		$author_id = $moderator ? (int) $moderator->ID : get_current_user_id();
		$member_id = $member ? (int) $member->ID : $author_id;
		$forums    = require LUG_DEMO_DIR . 'fixtures/forums.php';
		$forum_ids = array();
		$topic_ids = array();
		$reply_ids = array();

		foreach ( (array) $forums as $forum_fixture ) {
			$forum_id = bbp_insert_forum(
				array(
					'post_title'   => $forum_fixture['title'],
					'post_content' => $forum_fixture['content'],
					'post_status'  => 'publish',
					'post_author'  => $author_id,
				)
			);

			if ( ! $forum_id || is_wp_error( $forum_id ) ) {
				continue;
			}

			$forum_ids[] = (int) $forum_id;
			update_post_meta( (int) $forum_id, '_lug_demo_batch_id', $batch_id );
			foreach ( (array) $forum_fixture['topics'] as $topic_fixture ) {
				$topic_id = bbp_insert_topic(
					array(
						'post_parent'  => $forum_id,
						'post_title'   => $topic_fixture['title'],
						'post_content' => $topic_fixture['content'],
						'post_status'  => 'publish',
						'post_author'  => $author_id,
					)
				);

				if ( ! $topic_id || is_wp_error( $topic_id ) ) {
					continue;
				}

				$topic_ids[] = (int) $topic_id;
				update_post_meta( (int) $topic_id, '_lug_demo_batch_id', $batch_id );
				foreach ( (array) $topic_fixture['replies'] as $index => $reply ) {
					$reply_id = bbp_insert_reply(
						array(
							'post_parent'  => $topic_id,
							'post_title'   => '',
							'post_content' => $reply['content'],
							'post_status'  => 'publish',
							'post_author'  => 0 === $index ? $member_id : $author_id,
						)
					);

					if ( $reply_id && ! is_wp_error( $reply_id ) ) {
						$reply_ids[] = (int) $reply_id;
						update_post_meta( (int) $reply_id, '_lug_demo_batch_id', $batch_id );
					}
				}
			}
		}

		update_option( self::OPTION_FORUMS, $forum_ids, false );
		update_option( self::OPTION_TOPICS, $topic_ids, false );
		update_option( self::OPTION_REPLIES, $reply_ids, false );
	}

	public function reset_forums( $batch_id ) {
		$reply_ids = (array) get_option( self::OPTION_REPLIES, array() );
		$topic_ids = (array) get_option( self::OPTION_TOPICS, array() );
		$forum_ids = (array) get_option( self::OPTION_FORUMS, array() );

		foreach ( $reply_ids as $post_id ) {
			wp_delete_post( (int) $post_id, true );
		}
		foreach ( $topic_ids as $post_id ) {
			wp_delete_post( (int) $post_id, true );
		}
		foreach ( $forum_ids as $post_id ) {
			wp_delete_post( (int) $post_id, true );
		}

		// Also remove orphaned records from older or interrupted seed runs.
		$orphaned_ids = get_posts(
			array(
				'post_type'      => array( 'bbp_forum', 'bbp_topic', 'bbp_reply' ),
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_key'       => '_lug_demo_batch_id',
				'meta_value'     => $batch_id,
			)
		);
		foreach ( $orphaned_ids as $post_id ) {
			wp_delete_post( (int) $post_id, true );
		}

		// Clean up pre-batch demo records created before batch metadata was added.
		$fixtures = require LUG_DEMO_DIR . 'fixtures/forums.php';
		$known_forum_titles = array_values( array_filter( array_map( 'sanitize_text_field', wp_list_pluck( (array) $fixtures, 'title' ) ) ) );
		$known_topic_titles = array();
		foreach ( (array) $fixtures as $fixture ) {
			foreach ( (array) ( $fixture['topics'] ?? array() ) as $topic ) {
				$known_topic_titles[] = sanitize_text_field( $topic['title'] );
			}
		}
		$known_ids = array();
		foreach ( array( 'forum' => $known_forum_titles, 'topic' => $known_topic_titles ) as $post_type => $titles ) {
			foreach ( array_unique( $titles ) as $title ) {
				foreach ( get_posts( array( 'post_type' => $post_type, 'post_status' => 'any', 'posts_per_page' => -1, 'title' => $title, 'fields' => 'ids' ) ) as $post_id ) {
					$known_ids[] = (int) $post_id;
				}
			}
		}
		foreach ( array_unique( $known_ids ) as $post_id ) {
			$children = get_posts( array( 'post_type' => array( 'forum', 'topic', 'reply' ), 'post_parent' => $post_id, 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids' ) );
			foreach ( $children as $child_id ) {
				$grandchildren = get_posts( array( 'post_type' => array( 'topic', 'reply' ), 'post_parent' => (int) $child_id, 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids' ) );
				foreach ( $grandchildren as $grandchild_id ) {
					wp_delete_post( (int) $grandchild_id, true );
				}
				wp_delete_post( (int) $child_id, true );
			}
			wp_delete_post( (int) $post_id, true );
		}

		delete_option( self::OPTION_FORUMS );
		delete_option( self::OPTION_TOPICS );
		delete_option( self::OPTION_REPLIES );
	}

	public function seed_activity( $batch_id ) {
		if ( ! function_exists( 'bp_activity_add' ) ) {
			return;
		}

		$this->reset_activity( $batch_id );
		$fixtures  = require LUG_DEMO_DIR . 'fixtures/activity.php';
		$activity  = array();
		$users     = array();
		foreach ( array( 'lug-demo-member', 'lug-demo-organizer' ) as $login ) {
			$user = get_user_by( 'login', $login );
			if ( $user ) {
				$users[] = (int) $user->ID;
			}
		}

		foreach ( (array) $fixtures as $index => $fixture ) {
			$user_id = ! empty( $users ) ? $users[ $index % count( $users ) ] : get_current_user_id();
			$activity_id = bp_activity_add(
				array(
					'user_id'       => $user_id,
					'action'        => $fixture['action'],
					'content'       => $fixture['content'],
					'component'     => 'activity',
					'type'          => 'activity_update',
					'recorded_time' => gmdate( 'Y-m-d H:i:s', time() - ( $index * DAY_IN_SECONDS ) ),
				)
			);

			if ( $activity_id ) {
				$activity[] = (int) $activity_id;
			}
		}

		update_option( self::OPTION_ACTIVITY, $activity, false );
	}

	public function reset_activity( $batch_id ) {
		if ( function_exists( 'bp_activity_delete' ) ) {
			foreach ( (array) get_option( self::OPTION_ACTIVITY, array() ) as $activity_id ) {
				bp_activity_delete( array( 'id' => (int) $activity_id ) );
			}
		}
		delete_option( self::OPTION_ACTIVITY );
	}

	private function seed_navigation_menu( $page_ids ) {
		$menu_id = wp_create_nav_menu( __( 'Demo LUG Navigation', 'lug-demo' ) );
		if ( is_wp_error( $menu_id ) ) {
			return 0;
		}

		$items = array(
			'welcome-to-the-demo-lug' => __( 'Home', 'lug-demo' ),
			'getting-started'         => __( 'Getting Started', 'lug-demo' ),
			'plugin-tour'             => __( 'Plugin Tour', 'lug-demo' ),
			'active-tools'            => __( 'All Active Tools', 'lug-demo' ),
			'build-along-on-your-phone' => __( 'Build Along on Your Phone', 'lug-demo' ),
			'training'                => __( 'Training Videos', 'lug-demo' ),
			'about-this-demo'         => __( 'About the Demo', 'lug-demo' ),
			'community-guidelines'    => __( 'Guidelines', 'lug-demo' ),
		);

		foreach ( $items as $slug => $label ) {
			if ( empty( $page_ids[ $slug ] ) ) {
				continue;
			}

			wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'     => $label,
				'menu-item-object'    => 'page',
				'menu-item-object-id' => $page_ids[ $slug ],
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
			)
			);
		}

		update_option( self::OPTION_MENU, (int) $menu_id, false );
		return (int) $menu_id;
	}

	private function configure_site_shell( $welcome_page_id, $menu_id ) {
		if ( ! get_option( self::OPTION_SITE_STATE, false ) ) {
			$locations = get_theme_mod( 'nav_menu_locations', array() );
			update_option(
				self::OPTION_SITE_STATE,
				array(
					'show_on_front'     => get_option( 'show_on_front' ),
					'page_on_front'     => get_option( 'page_on_front' ),
					'nav_menu_locations' => is_array( $locations ) ? $locations : array(),
				),
				false
			);
		}

		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', (int) $welcome_page_id );

		if ( $menu_id ) {
			$locations = get_theme_mod( 'nav_menu_locations', array() );
			$registered = get_registered_nav_menus();
			if ( is_array( $registered ) && ! empty( $registered ) ) {
				$location = array_key_first( $registered );
				$locations[ $location ] = (int) $menu_id;
				set_theme_mod( 'nav_menu_locations', $locations );
			}
		}
		update_option( self::OPTION_WELCOME_PAGE, (int) $welcome_page_id, false );
	}

	private function restore_site_shell() {
		$state = get_option( self::OPTION_SITE_STATE, false );
		if ( is_array( $state ) ) {
			update_option( 'show_on_front', $state['show_on_front'] );
			update_option( 'page_on_front', $state['page_on_front'] );
			set_theme_mod( 'nav_menu_locations', (array) $state['nav_menu_locations'] );
			delete_option( self::OPTION_SITE_STATE );
		}
		delete_option( self::OPTION_WELCOME_PAGE );
	}

	private function table_exists( $table ) {
		global $wpdb;
		$table = preg_replace( '/[^A-Za-z0-9_]/', '', (string) $table );
		return '' !== $table && $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
	}

	public function handle_quick_login() {
		$login = isset( $_GET['lug_demo_login'] ) ? sanitize_user( wp_unslash( $_GET['lug_demo_login'] ) ) : '';
		$user  = $login ? get_user_by( 'login', $login ) : false;

		if ( ! $user || self::BATCH_ID !== get_user_meta( $user->ID, self::USER_META_BATCH, true ) ) {
			wp_die( esc_html__( 'That demo account is not available.', 'lug-demo' ), '', array( 'response' => 404 ) );
		}

		wp_set_auth_cookie( $user->ID, false, is_ssl() );
		wp_safe_redirect( home_url( '/' ) );
		exit;
	}

	public function render_account_directory() {
		$output = '<div class="lug-demo-account-directory"><h2>' . esc_html__( 'Try a demo account', 'lug-demo' ) . '</h2><p>' . esc_html__( 'Use any of these fictional accounts to explore the site from a different perspective. You can always return to this page and try another one.', 'lug-demo' ) . '</p><div class="lug-demo-accounts">';

		foreach ( $this->account_fixtures() as $account ) {
			if ( ! empty( $account['internal'] ) ) {
				continue;
			}
			$user = get_user_by( 'login', $account['login'] );
			if ( ! $user || self::BATCH_ID !== get_user_meta( $user->ID, self::USER_META_BATCH, true ) ) {
				continue;
			}

			$login_url = add_query_arg(
				array(
					'action'         => 'lug_demo',
					'lug_demo_login' => $account['login'],
				),
				wp_login_url()
			);
			$output .= '<article class="lug-demo-account"><h3>' . esc_html( $account['display_name'] ) . '</h3><p>' . esc_html( $account['description'] ) . '</p><p><strong>' . esc_html__( 'Login:', 'lug-demo' ) . '</strong> <code>' . esc_html( $account['login'] ) . '</code><br><strong>' . esc_html__( 'Password:', 'lug-demo' ) . '</strong> <code>' . esc_html( $account['password'] ) . '</code></p><p><a class="button" href="' . esc_url( $login_url ) . '">' . esc_html__( 'Explore as this account', 'lug-demo' ) . '</a></p></article>';
		}

		return $output . '</div></div>';
	}

	private function account_fixtures() {
		if ( null === $this->accounts ) {
			$this->accounts = (array) require LUG_DEMO_DIR . 'fixtures/accounts.php';
		}

		return $this->accounts;
	}

	private function verify_request() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage demo data.', 'lug-demo' ) );
		}

		check_admin_referer( 'lug_demo_manage' );
	}

	private function redirect( $notice ) {
		$url = add_query_arg( array( 'page' => 'lug-demo', 'lug_demo_notice' => sanitize_key( $notice ) ), admin_url( 'admin.php' ) );
		wp_safe_redirect( $url );
		exit;
	}
}
