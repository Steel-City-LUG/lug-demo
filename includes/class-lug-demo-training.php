<?php
/**
 * Training hub: the [lug_demo_training] shortcode.
 *
 * @package LUG_Demo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LUG_Demo_Training {
	public function hooks() {
		add_shortcode( 'lug_demo_training', array( $this, 'render' ) );
		add_action( 'template_redirect', array( $this, 'redirect_old' ) );
	}

	private function data() {
		$file = LUG_DEMO_DIR . 'fixtures/training-videos.php';
		$data = file_exists( $file ) ? (array) require $file : array();

		return wp_parse_args( $data, array( 'channel' => '', 'tools' => array() ) );
	}

	private function selected_tool( $tools ) {
		$requested = isset( $_GET['tool'] ) ? sanitize_key( wp_unslash( $_GET['tool'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display choice.
		if ( isset( $tools[ $requested ] ) ) {
			return $requested;
		}

		foreach ( $tools as $id => $tool ) {
			if ( ! empty( $tool['episodes'] ) ) {
				return $id;
			}
		}

		return (string) array_key_first( $tools );
	}

	private function selected_voice( $voices ) {
		$requested = isset( $_GET['voice'] ) ? sanitize_text_field( wp_unslash( $_GET['voice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display choice.

		return isset( $voices[ $requested ] ) ? $requested : (string) array_key_first( $voices );
	}

	private function picker_link( $args, $label, $current = false ) {
		$attributes = $current ? ' aria-current="true" class="is-current"' : '';

		return '<a href="' . esc_url( add_query_arg( $args, home_url( '/training/' ) ) ) . '"' . $attributes . '>' . esc_html( $label ) . '</a>';
	}

	private function training_page_exists() {
		$training = get_page_by_path( 'training', OBJECT, 'page' );

		return $training && 'publish' === $training->post_status;
	}

	public function redirect_old() {
		if ( ! is_page( 'layout-designer-training' ) || ! $this->training_page_exists() ) {
			return;
		}

		$args = array( 'tool' => 'layout-designer' );
		if ( isset( $_GET['voice'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only redirect choice.
			$args['voice'] = sanitize_text_field( wp_unslash( $_GET['voice'] ) );
		}

		wp_safe_redirect( add_query_arg( $args, home_url( '/training/' ) ), 301 );
		exit;
	}

	public function render() {
		$data   = $this->data();
		$tools  = (array) $data['tools'];
		$tool_id = $this->selected_tool( $tools );
		$tool   = isset( $tools[ $tool_id ] ) ? (array) $tools[ $tool_id ] : array();
		$voices = (array) ( $tool['playlists'] ?? array() );
		$voice  = $this->selected_voice( $voices );
		$episodes = (array) ( $tool['episodes'] ?? array() );
		if ( empty( $tool ) ) {
			return '';
		}

		$output = '<div class="lug-demo-training">';
		$output .= '<nav class="lug-demo-training-voices" aria-label="' . esc_attr__( 'Training tools', 'lug-demo' ) . '"><span>' . esc_html__( 'Tool:', 'lug-demo' ) . '</span>';
		foreach ( $tools as $id => $candidate ) {
			$output .= $this->picker_link( array( 'tool' => $id ), $candidate['name'], $id === $tool_id );
		}
		$output .= '</nav>';

		if ( ! empty( $tool['description'] ) ) {
			$output .= '<p class="lug-demo-training-description">' . esc_html( $tool['description'] ) . '</p>';
		}
		if ( empty( $episodes ) ) {
			return $output . '<h2>' . esc_html( $tool['name'] ) . '</h2><p class="lug-demo-training-soon">' . esc_html__( 'Coming soon.', 'lug-demo' ) . '</p></div>';
		}

		if ( count( $voices ) > 1 ) {
			$output .= '<nav class="lug-demo-training-voices" aria-label="' . esc_attr__( 'Narrator voice', 'lug-demo' ) . '"><span>' . esc_html__( 'Narrator:', 'lug-demo' ) . '</span>';
			foreach ( array_keys( $voices ) as $name ) {
				$output .= $this->picker_link( array( 'tool' => $tool_id, 'voice' => $name ), $name, $name === $voice );
			}
			$output .= '</nav>';
		}

		$playlist_url = ! empty( $voices[ $voice ] ) ? 'https://www.youtube.com/playlist?list=' . rawurlencode( $voices[ $voice ] ) : '';
		$first = reset( $episodes );
		if ( ! empty( $first['videos'][ $voice ] ) ) {
			$output .= '<section class="lug-demo-training-start"><h2>' . esc_html__( 'Start here', 'lug-demo' ) . '</h2><div class="lug-demo-training-player"><iframe src="' . esc_url( 'https://www.youtube-nocookie.com/embed/' . rawurlencode( $first['videos'][ $voice ] ) . '?list=' . rawurlencode( $voices[ $voice ] ) . '&rel=0' ) . '" title="' . esc_attr( $first['title'] ) . '" loading="lazy" allow="accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen" allowfullscreen></iframe></div><p>' . esc_html( $first['summary'] ) . '</p></section>';
		}

		$list_items = array();
		$output .= '<section class="lug-demo-training-topics"><h2>' . esc_html__( 'All episodes', 'lug-demo' ) . '</h2><ol>';
		foreach ( $episodes as $episode ) {
			$video_id = isset( $episode['videos'][ $voice ] ) ? $episode['videos'][ $voice ] : '';
			$url      = $video_id ? 'https://www.youtube.com/watch?v=' . rawurlencode( $video_id ) . '&list=' . rawurlencode( $voices[ $voice ] ) : $playlist_url;
			$output  .= '<li value="' . absint( $episode['number'] ) . '"><h3>' . esc_html( $episode['title'] ) . '</h3><p>' . esc_html( $episode['summary'] ) . '</p>';
			$output  .= $video_id ? '<a class="lug-demo-training-watch" href="' . esc_url( $url ) . '">' . esc_html__( 'Watch on YouTube', 'lug-demo' ) . '</a>' : '<span class="lug-demo-training-soon">' . esc_html__( 'Coming soon in this voice.', 'lug-demo' ) . '</span> <a href="' . esc_url( $playlist_url ) . '">' . esc_html__( 'Open the playlist', 'lug-demo' ) . '</a>';
			$output  .= '</li>';
			if ( $video_id ) {
				$list_items[] = array( '@type' => 'ListItem', 'position' => absint( $episode['number'] ), 'name' => $episode['title'], 'url' => 'https://www.youtube.com/watch?v=' . $video_id );
			}
		}
		$output .= '</ol></section><section class="lug-demo-training-next"><h2>' . esc_html__( 'Try it yourself', 'lug-demo' ) . '</h2><p>' . esc_html__( 'Log in with a demo account and open the Layout Designer to follow along. Nothing you change here is permanent.', 'lug-demo' ) . '</p><p><a href="' . esc_url( home_url( '/layout/' ) ) . '">' . esc_html__( 'Open the Layout Designer', 'lug-demo' ) . '</a> · <a href="' . esc_url( $playlist_url ) . '">' . esc_html( sprintf( /* translators: %s: narrator name */ __( 'Full playlist (%s)', 'lug-demo' ), $voice ) ) . '</a></p></section>';
		$output .= '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@type' => 'ItemList', 'name' => $tool['name'] . ' training videos', 'itemListElement' => $list_items ) ) . '</script></div>';

		return $output;
	}
}
