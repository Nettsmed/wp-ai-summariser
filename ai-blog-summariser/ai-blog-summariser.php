<?php
/**
 * Plugin Name: AI Blog Summariser
 * Plugin URI:  https://github.com/Nettsmed/wp-ai-summariser
 * Description: Automatically generates AI-powered summaries for blog posts using the Anthropic Claude API.
 * Version:     1.0.0
 * Author:      Nettsmed
 * License:     GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-blog-summariser
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AIBS_VERSION', '1.0.0' );
define( 'AIBS_OPTION_KEY', 'aibs_settings' );
define( 'AIBS_META_SUMMARY', '_ai_summary' );
define( 'AIBS_META_GENERATED', '_ai_summary_generated' );

// ---------------------------------------------------------------------------
// Settings helper
// ---------------------------------------------------------------------------

function aibs_get_settings() {
	$defaults = [
		'api_key'       => '',
		'model'         => 'claude-sonnet-4-6',
		'post_types'    => [ 'post' ],
		'auto_generate' => 'on_publish',
		'language'      => 'auto',
		'max_words'     => 50,
		'custom_prompt' => "You are a blog post summariser. Write a concise summary of the following blog post in {max_words} words or fewer. The summary should capture the main point and key takeaways. Write in {language}.\n\nTitle: {title}\n\nContent:\n{content}\n\nReturn ONLY the summary text, nothing else.",
	];

	$saved = get_option( AIBS_OPTION_KEY, [] );

	return wp_parse_args( $saved, $defaults );
}

// ---------------------------------------------------------------------------
// Admin menu
// ---------------------------------------------------------------------------

add_action( 'admin_menu', function () {
	add_options_page(
		'AI Summariser',
		'AI Summariser',
		'manage_options',
		'ai-blog-summariser',
		'aibs_settings_page'
	);
} );

// ---------------------------------------------------------------------------
// Register settings
// ---------------------------------------------------------------------------

add_action( 'admin_init', function () {
	register_setting( 'aibs_settings_group', AIBS_OPTION_KEY, 'aibs_sanitize_settings' );
} );

function aibs_sanitize_settings( $input ) {
	$output = [];

	$output['api_key'] = isset( $input['api_key'] ) ? sanitize_text_field( $input['api_key'] ) : '';
	$output['model']   = isset( $input['model'] ) ? sanitize_text_field( $input['model'] ) : 'claude-sonnet-4-6';

	$allowed_models = [ 'claude-sonnet-4-6', 'claude-haiku-4-5-20251001', 'claude-opus-4-6' ];
	if ( ! in_array( $output['model'], $allowed_models, true ) ) {
		$output['model'] = 'claude-sonnet-4-6';
	}

	$output['post_types'] = [];
	if ( isset( $input['post_types'] ) && is_array( $input['post_types'] ) ) {
		foreach ( $input['post_types'] as $pt ) {
			$output['post_types'][] = sanitize_key( $pt );
		}
	}
	if ( empty( $output['post_types'] ) ) {
		$output['post_types'] = [ 'post' ];
	}

	$allowed_auto = [ 'on_publish', 'on_update', 'manual' ];
	$output['auto_generate'] = isset( $input['auto_generate'] ) && in_array( $input['auto_generate'], $allowed_auto, true )
		? $input['auto_generate']
		: 'on_publish';

	$allowed_lang = [ 'auto', 'en', 'nb' ];
	$output['language'] = isset( $input['language'] ) && in_array( $input['language'], $allowed_lang, true )
		? $input['language']
		: 'auto';

	$max_words = isset( $input['max_words'] ) ? intval( $input['max_words'] ) : 50;
	$output['max_words'] = max( 20, min( 300, $max_words ) );

	$output['custom_prompt'] = isset( $input['custom_prompt'] )
		? sanitize_textarea_field( $input['custom_prompt'] )
		: '';

	return $output;
}

// ---------------------------------------------------------------------------
// Settings page
// ---------------------------------------------------------------------------

function aibs_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$settings    = aibs_get_settings();
	$post_types  = get_post_types( [ 'public' => true ] );
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'aibs_settings_group' ); ?>

			<table class="form-table" role="presentation">

				<tr>
					<th scope="row">
						<label for="aibs_api_key"><?php esc_html_e( 'Anthropic API Key', 'ai-blog-summariser' ); ?></label>
					</th>
					<td>
						<input
							type="password"
							id="aibs_api_key"
							name="<?php echo esc_attr( AIBS_OPTION_KEY ); ?>[api_key]"
							value="<?php echo esc_attr( $settings['api_key'] ); ?>"
							class="regular-text"
						/>
						<p class="description">
							<?php esc_html_e( 'Get your API key from https://console.anthropic.com/', 'ai-blog-summariser' ); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="aibs_model"><?php esc_html_e( 'AI Model', 'ai-blog-summariser' ); ?></label>
					</th>
					<td>
						<select id="aibs_model" name="<?php echo esc_attr( AIBS_OPTION_KEY ); ?>[model]">
							<option value="claude-sonnet-4-6" <?php selected( $settings['model'], 'claude-sonnet-4-6' ); ?>>
								claude-sonnet-4-6
							</option>
							<option value="claude-haiku-4-5-20251001" <?php selected( $settings['model'], 'claude-haiku-4-5-20251001' ); ?>>
								claude-haiku-4-5-20251001
							</option>
							<option value="claude-opus-4-6" <?php selected( $settings['model'], 'claude-opus-4-6' ); ?>>
								claude-opus-4-6
							</option>
						</select>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Post Types', 'ai-blog-summariser' ); ?></th>
					<td>
						<?php foreach ( $post_types as $pt ) : ?>
							<label style="display:block;margin-bottom:4px;">
								<input
									type="checkbox"
									name="<?php echo esc_attr( AIBS_OPTION_KEY ); ?>[post_types][]"
									value="<?php echo esc_attr( $pt ); ?>"
									<?php checked( in_array( $pt, $settings['post_types'], true ) ); ?>
								/>
								<?php echo esc_html( $pt ); ?>
							</label>
						<?php endforeach; ?>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Auto Generate', 'ai-blog-summariser' ); ?></th>
					<td>
						<label style="display:block;margin-bottom:4px;">
							<input type="radio" name="<?php echo esc_attr( AIBS_OPTION_KEY ); ?>[auto_generate]"
								value="on_publish" <?php checked( $settings['auto_generate'], 'on_publish' ); ?> />
							<?php esc_html_e( 'On first publish', 'ai-blog-summariser' ); ?>
						</label>
						<label style="display:block;margin-bottom:4px;">
							<input type="radio" name="<?php echo esc_attr( AIBS_OPTION_KEY ); ?>[auto_generate]"
								value="on_update" <?php checked( $settings['auto_generate'], 'on_update' ); ?> />
							<?php esc_html_e( 'On every update', 'ai-blog-summariser' ); ?>
						</label>
						<label style="display:block;margin-bottom:4px;">
							<input type="radio" name="<?php echo esc_attr( AIBS_OPTION_KEY ); ?>[auto_generate]"
								value="manual" <?php checked( $settings['auto_generate'], 'manual' ); ?> />
							<?php esc_html_e( 'Manual only', 'ai-blog-summariser' ); ?>
						</label>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="aibs_language"><?php esc_html_e( 'Summary Language', 'ai-blog-summariser' ); ?></label>
					</th>
					<td>
						<select id="aibs_language" name="<?php echo esc_attr( AIBS_OPTION_KEY ); ?>[language]">
							<option value="auto" <?php selected( $settings['language'], 'auto' ); ?>>
								<?php esc_html_e( 'Auto-detect', 'ai-blog-summariser' ); ?>
							</option>
							<option value="en" <?php selected( $settings['language'], 'en' ); ?>>
								<?php esc_html_e( 'English', 'ai-blog-summariser' ); ?>
							</option>
							<option value="nb" <?php selected( $settings['language'], 'nb' ); ?>>
								<?php esc_html_e( 'Norwegian Bokmål', 'ai-blog-summariser' ); ?>
							</option>
						</select>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="aibs_max_words"><?php esc_html_e( 'Max Words', 'ai-blog-summariser' ); ?></label>
					</th>
					<td>
						<input
							type="number"
							id="aibs_max_words"
							name="<?php echo esc_attr( AIBS_OPTION_KEY ); ?>[max_words]"
							value="<?php echo esc_attr( $settings['max_words'] ); ?>"
							min="20"
							max="300"
							class="small-text"
						/>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="aibs_custom_prompt"><?php esc_html_e( 'Custom Prompt', 'ai-blog-summariser' ); ?></label>
					</th>
					<td>
						<textarea
							id="aibs_custom_prompt"
							name="<?php echo esc_attr( AIBS_OPTION_KEY ); ?>[custom_prompt]"
							rows="8"
							class="large-text"
						><?php echo esc_textarea( $settings['custom_prompt'] ); ?></textarea>
						<p class="description">
							<?php esc_html_e(
								'Available placeholders: {content}, {title}, {max_words}, {language}',
								'ai-blog-summariser'
							); ?>
						</p>
					</td>
				</tr>

			</table>

			<input type="submit" value="<?php esc_attr_e( 'Save Settings', 'ai-blog-summariser' ); ?>" class="button-primary" />
		</form>
	</div>
	<?php
}

// ---------------------------------------------------------------------------
// Core API function
// ---------------------------------------------------------------------------

function aibs_generate_summary( $post_id ) {
	$settings = aibs_get_settings();

	if ( empty( $settings['api_key'] ) ) {
		return new WP_Error( 'no_api_key', __( 'No API key configured.', 'ai-blog-summariser' ) );
	}

	$post = get_post( $post_id );
	if ( ! $post ) {
		return new WP_Error( 'no_post', __( 'Post not found.', 'ai-blog-summariser' ) );
	}

	$content = strip_shortcodes( $post->post_content );
	$content = wp_strip_all_tags( $content );
	$content = trim( $content );

	if ( count( explode( ' ', $content ) ) < 50 ) {
		return new WP_Error( 'too_short', __( 'Post content is too short to summarise.', 'ai-blog-summariser' ) );
	}

	$words = explode( ' ', $content );
	if ( count( $words ) > 4000 ) {
		$content = implode( ' ', array_slice( $words, 0, 4000 ) );
	}

	$language_map = [
		'auto' => 'the same language as the blog post',
		'en'   => 'English',
		'nb'   => 'Norwegian Bokmål',
	];
	$language_str = isset( $language_map[ $settings['language'] ] )
		? $language_map[ $settings['language'] ]
		: $language_map['auto'];

	$prompt = str_replace(
		[ '{content}', '{title}', '{max_words}', '{language}' ],
		[ $content, $post->post_title, $settings['max_words'], $language_str ],
		$settings['custom_prompt']
	);

	$body = wp_json_encode( [
		'model'      => $settings['model'],
		'max_tokens' => 500,
		'system'     => $prompt,
		'messages'   => [
			[
				'role'    => 'user',
				'content' => 'Please summarise the blog post above.',
			],
		],
	] );

	$response = wp_remote_post(
		'https://api.anthropic.com/v1/messages',
		[
			'timeout' => 30,
			'headers' => [
				'x-api-key'         => $settings['api_key'],
				'anthropic-version' => '2023-06-01',
				'content-type'      => 'application/json',
			],
			'body' => $body,
		]
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$status_code = wp_remote_retrieve_response_code( $response );
	if ( 200 !== (int) $status_code ) {
		return new WP_Error( 'api_error', sprintf( __( 'API error: %d', 'ai-blog-summariser' ), $status_code ) );
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( empty( $data['content'][0]['text'] ) ) {
		return new WP_Error( 'empty_response', __( 'Empty response from API.', 'ai-blog-summariser' ) );
	}

	$summary = sanitize_textarea_field( $data['content'][0]['text'] );

	return $summary;
}

// ---------------------------------------------------------------------------
// save_post hook — auto-generate on publish/update
// ---------------------------------------------------------------------------

add_action( 'save_post', 'aibs_save_post', 20, 3 );

function aibs_save_post( $post_id, $post, $update ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( 'publish' !== $post->post_status ) {
		return;
	}

	$settings = aibs_get_settings();

	if ( ! in_array( $post->post_type, $settings['post_types'], true ) ) {
		return;
	}

	if ( 'manual' === $settings['auto_generate'] ) {
		return;
	}

	if ( 'on_publish' === $settings['auto_generate'] && get_post_meta( $post_id, AIBS_META_SUMMARY, true ) ) {
		return;
	}

	$result = aibs_generate_summary( $post_id );

	if ( is_wp_error( $result ) ) {
		return;
	}

	update_post_meta( $post_id, AIBS_META_SUMMARY, $result );
	update_post_meta( $post_id, AIBS_META_GENERATED, time() );
}

// ---------------------------------------------------------------------------
// Meta boxes
// ---------------------------------------------------------------------------

add_action( 'add_meta_boxes', 'aibs_add_meta_boxes' );

function aibs_add_meta_boxes() {
	$settings = aibs_get_settings();

	foreach ( $settings['post_types'] as $post_type ) {
		add_meta_box(
			'aibs_summary_meta_box',
			__( 'AI Summary', 'ai-blog-summariser' ),
			'aibs_meta_box_callback',
			$post_type,
			'normal',
			'default'
		);
	}
}

function aibs_meta_box_callback( $post ) {
	wp_nonce_field( 'aibs_save_meta', 'aibs_meta_nonce' );

	$summary = get_post_meta( $post->ID, AIBS_META_SUMMARY, true );
	?>
	<p>
		<label for="aibs_summary"><?php esc_html_e( 'Summary', 'ai-blog-summariser' ); ?></label>
	</p>
	<textarea
		id="aibs_summary"
		name="aibs_summary"
		rows="5"
		style="width:100%;"
	><?php echo esc_textarea( $summary ); ?></textarea>
	<p>
		<button type="button" id="aibs-regenerate-btn" class="button" data-post-id="<?php echo esc_attr( $post->ID ); ?>">
			<?php esc_html_e( 'Regenerate Summary', 'ai-blog-summariser' ); ?>
		</button>
		<span id="aibs-regenerate-status" style="margin-left:8px;"></span>
	</p>
	<?php
}

add_action( 'admin_enqueue_scripts', 'aibs_enqueue_admin_scripts' );

function aibs_enqueue_admin_scripts( $hook ) {
	if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
		return;
	}

	wp_enqueue_script( 'aibs-admin', false, [], AIBS_VERSION, true );

	wp_localize_script( 'aibs-admin', 'aibsData', [
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
	] );

	$js = "document.getElementById('aibs-regenerate-btn').addEventListener('click', function(e) {
	e.preventDefault();
	var btn = this;
	var status = document.getElementById('aibs-regenerate-status');
	btn.disabled = true;
	status.textContent = 'Generating...';
	fetch(aibsData.ajaxurl, {
		method: 'POST',
		headers: {'Content-Type': 'application/x-www-form-urlencoded'},
		body: new URLSearchParams({
			action: 'aibs_regenerate',
			post_id: btn.dataset.postId,
			nonce: document.getElementById('aibs_meta_nonce').value
		})
	})
	.then(r => r.json())
	.then(data => {
		if (data.success) {
			document.querySelector('textarea[name=\"aibs_summary\"]').value = data.data.summary;
			status.textContent = 'Summary updated!';
		} else {
			status.textContent = 'Error: ' + (data.data || 'Unknown error');
		}
		btn.disabled = false;
	})
	.catch(() => {
		status.textContent = 'Request failed.';
		btn.disabled = false;
	});
});";

	wp_add_inline_script( 'aibs-admin', $js );
}

add_action( 'save_post', 'aibs_save_meta_box', 10, 2 );

function aibs_save_meta_box( $post_id, $post ) {
	if ( ! isset( $_POST['aibs_meta_nonce'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['aibs_meta_nonce'] ) ), 'aibs_save_meta' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( isset( $_POST['aibs_summary'] ) ) {
		update_post_meta(
			$post_id,
			AIBS_META_SUMMARY,
			sanitize_textarea_field( wp_unslash( $_POST['aibs_summary'] ) )
		);
	}
}

// ---------------------------------------------------------------------------
// AJAX handler — regenerate summary
// ---------------------------------------------------------------------------

add_action( 'wp_ajax_aibs_regenerate', 'aibs_ajax_regenerate' );

function aibs_ajax_regenerate() {
	if ( ! check_ajax_referer( 'aibs_save_meta', 'nonce', false ) ) {
		wp_send_json_error( 'Invalid nonce', 403 );
	}

	$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;

	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_send_json_error( 'Unauthorized', 403 );
	}

	$result = aibs_generate_summary( $post_id );

	if ( is_wp_error( $result ) ) {
		wp_send_json_error( $result->get_error_message() );
	}

	update_post_meta( $post_id, AIBS_META_SUMMARY, $result );
	update_post_meta( $post_id, AIBS_META_GENERATED, time() );

	wp_send_json_success( [ 'summary' => $result ] );
}

// ---------------------------------------------------------------------------
// Shortcode [ai_summary]
// ---------------------------------------------------------------------------

add_shortcode( 'ai_summary', 'aibs_shortcode' );

function aibs_shortcode( $atts ) {
	$atts    = shortcode_atts( [ 'post_id' => get_the_ID() ], $atts );
	$summary = get_post_meta( intval( $atts['post_id'] ), AIBS_META_SUMMARY, true );

	if ( empty( $summary ) ) {
		return '';
	}

	return '<div class="ai-summary"><p>' . esc_html( $summary ) . '</p></div>';
}
