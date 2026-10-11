<?php
/**
 * Demo admin screen.
 *
 * @package LUG_Demo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LUG_Demo_Admin {
	public static function register_menu() {
		add_menu_page( __( 'LUG Demo', 'lug-demo' ), __( 'LUG Demo', 'lug-demo' ), 'manage_options', 'lug-demo', array( __CLASS__, 'render' ), 'dashicons-welcome-view-site' );
	}

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$notice = isset( $_GET['lug_demo_notice'] ) ? sanitize_key( wp_unslash( $_GET['lug_demo_notice'] ) ) : '';
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'LUG Demo Environment', 'lug-demo' ); ?></h1>
			<?php if ( 'seeded' === $notice ) : ?><div class="notice notice-success"><p><?php echo esc_html__( 'Demo data seeded.', 'lug-demo' ); ?></p></div><?php endif; ?>
			<?php if ( 'reset' === $notice ) : ?><div class="notice notice-success"><p><?php echo esc_html__( 'Demo data reset.', 'lug-demo' ); ?></p></div><?php endif; ?>
			<p><?php echo esc_html__( 'This plugin is for lugtools.steelcitylug.com only. It coordinates demo fixtures owned by the other local plugins.', 'lug-demo' ); ?></p>
			<p><?php echo esc_html__( 'Visitors are encouraged to experiment. Seed / rebuild returns the demo to its curated starting point, while reset removes the demo-owned records so the site can be rebuilt cleanly.', 'lug-demo' ); ?></p>
			<p><strong><?php echo esc_html__( 'Active batch:', 'lug-demo' ); ?></strong> <?php echo esc_html( get_option( LUG_Demo::OPTION_BATCH, __( 'Not seeded', 'lug-demo' ) ) ); ?></p>
			<?php $report = get_option( LUG_Demo::OPTION_REPORT, array() ); ?>
			<?php if ( is_array( $report ) && ! empty( $report['completed'] ) ) : ?>
				<p><strong><?php echo esc_html__( 'Last operation:', 'lug-demo' ); ?></strong> <?php echo esc_html( ucfirst( (string) ( $report['action'] ?? '' ) ) . ' — ' . $report['completed'] ); ?></p>
				<?php if ( ! empty( $report['errors'] ) ) : ?>
					<div class="notice notice-warning"><p><strong><?php echo esc_html__( 'Some demo surfaces reported errors:', 'lug-demo' ); ?></strong></p><ul><?php foreach ( $report['errors'] as $error ) : ?><li><?php echo esc_html( $error ); ?></li><?php endforeach; ?></ul></div>
				<?php endif; ?>
			<?php endif; ?>
			<h2><?php echo esc_html__( 'Demo surfaces', 'lug-demo' ); ?></h2>
			<ul>
				<?php foreach ( self::surfaces() as $surface => $available ) : ?>
					<li><strong><?php echo esc_html( $surface ); ?>:</strong> <?php echo esc_html( $available ? __( 'available', 'lug-demo' ) : __( 'MISSING — required for the complete showcase', 'lug-demo' ) ); ?></li>
				<?php endforeach; ?>
			</ul>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-right:8px;"><?php wp_nonce_field( 'lug_demo_manage' ); ?><input type="hidden" name="action" value="lug_demo_seed" /><?php submit_button( __( 'Seed / rebuild demo', 'lug-demo' ), 'primary', 'submit', false ); ?></form>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;"><?php wp_nonce_field( 'lug_demo_manage' ); ?><input type="hidden" name="action" value="lug_demo_reset" /><?php submit_button( __( 'Reset demo data', 'lug-demo' ), 'secondary', 'submit', false ); ?></form>
		</div>
		<?php
	}

	private static function surfaces() {
		return array(
			'WordPress content' => true,
			'User accounts'    => true,
			'LUG events'       => function_exists( 'lug_event_provider_available' ) && lug_event_provider_available(),
			'Event shifts'     => function_exists( 'lug_event_shift_save' ) && function_exists( 'lug_event_shifts_table_exists' ) && lug_event_shifts_table_exists(),
			'Brick by Numbers' => defined( 'BBN_VERSION' ),
			'bbPress forums'   => function_exists( 'bbp_insert_forum' ),
			'BuddyPress'       => function_exists( 'bp_activity_add' ),
			'Officer roles'    => class_exists( 'LUG_Officers_Install' ),
			'AFOL badges'      => defined( 'SC_AFOL_BADGES_VERSION' ),
			'ZIP map'          => defined( 'LUG_USER_ZIP_MAP_VERSION' ),
			'Welcome messages' => function_exists( 'lug_welcome_pm_send_welcome_message_result' ),
			'Discord bridge'   => defined( 'LUG_BBP_DISCORD_VERSION' ) || defined( 'LUG_DISCORD_CONNECT_VERSION' ),
			'Layout tools'     => defined( 'LUG_LAYOUT_PLUGIN_VERSION' ) || defined( 'LUG_RD_VERSION' ),
			'Forum reply sorting' => function_exists( 'user_reply_sorting_for_forums' ) || defined( 'URSF_VERSION' ),
		);
	}
}
