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
	const USER_META_BATCH = '_lug_demo_batch_id';

	private $accounts;

	public function hooks() {
		add_action( 'admin_menu', array( 'LUG_Demo_Admin', 'register_menu' ), 30 );
		add_action( 'admin_post_lug_demo_seed', array( $this, 'handle_seed' ) );
		add_action( 'admin_post_lug_demo_reset', array( $this, 'handle_reset' ) );
		add_filter( 'lug_demo_seed_adapters', array( $this, 'register_core_adapter' ) );
		add_action( 'login_form_lug_demo', array( $this, 'handle_quick_login' ) );
	}

	public function register_core_adapter( $adapters ) {
		$adapters['core'] = array(
			'label' => __( 'Demo accounts', 'lug-demo' ),
			'run'   => array( $this, 'seed_accounts' ),
			'reset' => array( $this, 'reset_accounts' ),
		);

		return $adapters;
	}

	public function handle_seed() {
		$this->verify_request();

		foreach ( (array) apply_filters( 'lug_demo_seed_adapters', array() ) as $adapter ) {
			if ( isset( $adapter['run'] ) && is_callable( $adapter['run'] ) ) {
				call_user_func( $adapter['run'], self::BATCH_ID );
			}
		}

		update_option( self::OPTION_BATCH, self::BATCH_ID, false );
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
		$this->redirect( 'reset' );
	}

	public function seed_accounts( $batch_id ) {
		foreach ( $this->account_fixtures() as $account ) {
			$user = get_user_by( 'login', $account['login'] );
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
			}
		}
	}

	public function reset_accounts( $batch_id ) {
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
