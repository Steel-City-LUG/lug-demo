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
			<p><strong><?php echo esc_html__( 'Active batch:', 'lug-demo' ); ?></strong> <?php echo esc_html( get_option( LUG_Demo::OPTION_BATCH, __( 'Not seeded', 'lug-demo' ) ) ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-right:8px;"><?php wp_nonce_field( 'lug_demo_manage' ); ?><input type="hidden" name="action" value="lug_demo_seed" /><?php submit_button( __( 'Seed / rebuild demo', 'lug-demo' ), 'primary', 'submit', false ); ?></form>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;"><?php wp_nonce_field( 'lug_demo_manage' ); ?><input type="hidden" name="action" value="lug_demo_reset" /><?php submit_button( __( 'Reset demo data', 'lug-demo' ), 'secondary', 'submit', false ); ?></form>
		</div>
		<?php
	}
}
