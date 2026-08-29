<?php
/**
 * HelloAsso event import and synchronization.
 *
 * @package Formica_Dates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const FORMICA_DATES_HELLOASSO_OPTION = 'formica_dates_helloasso';
const FORMICA_DATES_HELLOASSO_CRON   = 'formica_dates_helloasso_sync';

/**
 * Returns the HelloAsso connection settings. Constants override stored values.
 */
function formica_dates_helloasso_settings() {
	$stored = get_option( FORMICA_DATES_HELLOASSO_OPTION, array() );
	$stored = is_array( $stored ) ? $stored : array();

	return array(
		'organization_slug' => defined( 'FORMICA_DATES_HELLOASSO_ORGANIZATION_SLUG' )
			? (string) FORMICA_DATES_HELLOASSO_ORGANIZATION_SLUG
			: (string) ( $stored['organization_slug'] ?? '' ),
		'client_id'         => defined( 'FORMICA_DATES_HELLOASSO_CLIENT_ID' )
			? (string) FORMICA_DATES_HELLOASSO_CLIENT_ID
			: (string) ( $stored['client_id'] ?? '' ),
		'client_secret'     => defined( 'FORMICA_DATES_HELLOASSO_CLIENT_SECRET' )
			? (string) FORMICA_DATES_HELLOASSO_CLIENT_SECRET
			: (string) ( $stored['client_secret'] ?? '' ),
	);
}

/**
 * Returns whether all required credentials are available.
 */
function formica_dates_helloasso_is_configured() {
	$settings = formica_dates_helloasso_settings();
	return '' !== $settings['organization_slug'] && '' !== $settings['client_id'] && '' !== $settings['client_secret'];
}

/**
 * Returns the fixed API base, with an optional wp-config override for sandbox use.
 */
function formica_dates_helloasso_api_base() {
	$base = defined( 'FORMICA_DATES_HELLOASSO_API_BASE' )
		? (string) FORMICA_DATES_HELLOASSO_API_BASE
		: 'https://api.helloasso.com/v5';

	return untrailingslashit( esc_url_raw( $base ) );
}

/**
 * Returns the transient key used for the current access token.
 */
function formica_dates_helloasso_token_key() {
	$settings = formica_dates_helloasso_settings();
	return 'formica_ha_token_' . substr( hash( 'sha256', $settings['client_id'] . '|' . formica_dates_helloasso_api_base() ), 0, 20 );
}

/**
 * Converts a failed HTTP response into a useful, non-secret error.
 */
function formica_dates_helloasso_http_error( $response, $fallback ) {
	if ( is_wp_error( $response ) ) {
		return new WP_Error( 'helloasso_http', $response->get_error_message() );
	}

	$status  = (int) wp_remote_retrieve_response_code( $response );
	$payload = json_decode( wp_remote_retrieve_body( $response ), true );
	$message = is_array( $payload ) && ! empty( $payload['message'] ) ? sanitize_text_field( $payload['message'] ) : $fallback;

	return new WP_Error( 'helloasso_http_' . $status, sprintf( '%s (HTTP %d)', $message, $status ) );
}

/**
 * Retrieves and caches an OAuth access token.
 */
function formica_dates_helloasso_access_token( $refresh = false ) {
	if ( ! formica_dates_helloasso_is_configured() ) {
		return new WP_Error( 'helloasso_not_configured', __( 'La connexion HelloAsso n’est pas encore configurée.', 'formica-dates' ) );
	}

	$cache_key = formica_dates_helloasso_token_key();
	if ( $refresh ) {
		delete_transient( $cache_key );
	} else {
		$cached = get_transient( $cache_key );
		if ( is_string( $cached ) && '' !== $cached ) {
			return $cached;
		}
	}

	$settings  = formica_dates_helloasso_settings();
	$token_url = preg_replace( '#/v5$#', '', formica_dates_helloasso_api_base() ) . '/oauth2/token';
	$response  = wp_remote_post(
		$token_url,
		array(
			'timeout' => 20,
			'body'    => array(
				'grant_type'    => 'client_credentials',
				'client_id'     => $settings['client_id'],
				'client_secret' => $settings['client_secret'],
			),
		)
	);

	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return formica_dates_helloasso_http_error( $response, __( 'Impossible de s’authentifier auprès de HelloAsso.', 'formica-dates' ) );
	}

	$payload = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $payload ) || empty( $payload['access_token'] ) ) {
		return new WP_Error( 'helloasso_invalid_token', __( 'HelloAsso a renvoyé une réponse d’authentification incomplète.', 'formica-dates' ) );
	}

	$expires = max( 60, (int) ( $payload['expires_in'] ?? 1800 ) - 60 );
	set_transient( $cache_key, (string) $payload['access_token'], $expires );

	return (string) $payload['access_token'];
}

/**
 * Performs an authenticated GET request, retrying once after a 401 response.
 */
function formica_dates_helloasso_get( $path, $retry = true ) {
	$token = formica_dates_helloasso_access_token();
	if ( is_wp_error( $token ) ) {
		return $token;
	}

	$response = wp_remote_get(
		formica_dates_helloasso_api_base() . '/' . ltrim( $path, '/' ),
		array(
			'timeout' => 20,
			'headers' => array(
				'Accept'        => 'application/json',
				'Authorization' => 'Bearer ' . $token,
			),
		)
	);

	if ( ! is_wp_error( $response ) && 401 === (int) wp_remote_retrieve_response_code( $response ) && $retry ) {
		$token = formica_dates_helloasso_access_token( true );
		if ( is_wp_error( $token ) ) {
			return $token;
		}
		return formica_dates_helloasso_get( $path, false );
	}

	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return formica_dates_helloasso_http_error( $response, __( 'La requête HelloAsso a échoué.', 'formica-dates' ) );
	}

	$payload = json_decode( wp_remote_retrieve_body( $response ), true );
	return is_array( $payload ) ? $payload : new WP_Error( 'helloasso_invalid_json', __( 'HelloAsso a renvoyé une réponse illisible.', 'formica-dates' ) );
}

/**
 * Retrieves every HelloAsso Event form belonging to the configured organization.
 */
function formica_dates_helloasso_list_events() {
	$settings = formica_dates_helloasso_settings();
	$page     = 1;
	$events   = array();

	do {
		$path = add_query_arg(
			array(
				'formTypes' => 'Event',
				'pageIndex' => $page,
				'pageSize'  => 100,
			),
			'organizations/' . rawurlencode( $settings['organization_slug'] ) . '/forms'
		);
		$data = formica_dates_helloasso_get( $path );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$batch = isset( $data['data'] ) && is_array( $data['data'] ) ? $data['data'] : array();
		foreach ( $batch as $event ) {
			if ( is_array( $event ) && 'Event' === ( $event['formType'] ?? '' ) && ! empty( $event['formSlug'] ) ) {
				$events[] = $event;
			}
		}

		$total_pages = max( 1, (int) ( $data['pagination']['totalPages'] ?? 1 ) );
		++$page;
	} while ( $page <= $total_pages && $page <= 20 );

	return $events;
}

/**
 * Retrieves the public details for one HelloAsso Event form.
 */
function formica_dates_helloasso_event_details( $form_slug ) {
	$settings = formica_dates_helloasso_settings();
	return formica_dates_helloasso_get(
		'organizations/' . rawurlencode( $settings['organization_slug'] ) . '/forms/Event/' . rawurlencode( $form_slug ) . '/public'
	);
}

/**
 * Creates a stable source identifier for an imported form.
 */
function formica_dates_helloasso_source_key( $organization_slug, $form_slug ) {
	return strtolower( trim( $organization_slug ) ) . '|event|' . strtolower( trim( $form_slug ) );
}

/**
 * Finds the stage previously imported from a HelloAsso event.
 */
function formica_dates_helloasso_find_stage( $organization_slug, $form_slug ) {
	$ids = get_posts(
		array(
			'post_type'      => FORMICA_DATES_STAGE_POST_TYPE,
			'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private', 'trash' ),
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_formica_stage_helloasso_key',
			'meta_value'     => formica_dates_helloasso_source_key( $organization_slug, $form_slug ),
			'no_found_rows'  => true,
		)
	);

	return $ids ? (int) $ids[0] : 0;
}

/**
 * Converts an ISO date from HelloAsso to the local SQL representation.
 */
function formica_dates_helloasso_local_datetime( $value ) {
	if ( ! is_string( $value ) || '' === $value ) {
		return '';
	}

	try {
		$date = new DateTimeImmutable( $value );
		return $date->setTimezone( wp_timezone() )->format( 'Y-m-d H:i:s' );
	} catch ( Exception $exception ) {
		return '';
	}
}

/**
 * Reduces public tier data to the fields needed by the Formica page.
 */
function formica_dates_helloasso_normalize_tiers( $tiers ) {
	$normalized = array();
	foreach ( is_array( $tiers ) ? $tiers : array() as $tier ) {
		if ( ! is_array( $tier ) || 'Donation' === ( $tier['tierType'] ?? '' ) ) {
			continue;
		}
		$normalized[] = array(
			'label'      => sanitize_text_field( $tier['label'] ?? '' ),
			'price'      => isset( $tier['price'] ) ? max( 0, (int) $tier['price'] ) : null,
			'min_amount' => isset( $tier['minAmount'] ) ? max( 0, (int) $tier['minAmount'] ) : null,
		);
	}

	return $normalized;
}

/**
 * Updates a stage from HelloAsso without overwriting Formica-only editorial fields.
 */
function formica_dates_helloasso_upsert_stage( $details, $fallback = array() ) {
	if ( ! is_array( $details ) ) {
		return new WP_Error( 'helloasso_invalid_event', __( 'Les données de l’événement HelloAsso sont incomplètes.', 'formica-dates' ) );
	}

	$settings          = formica_dates_helloasso_settings();
	$organization_slug = sanitize_title( $details['organizationSlug'] ?? $settings['organization_slug'] );
	$form_slug         = sanitize_title( $details['formSlug'] ?? ( $fallback['formSlug'] ?? '' ) );
	if ( '' === $organization_slug || '' === $form_slug ) {
		return new WP_Error( 'helloasso_missing_slug', __( 'Impossible d’identifier cet événement HelloAsso.', 'formica-dates' ) );
	}

	$post_id            = formica_dates_helloasso_find_stage( $organization_slug, $form_slug );
	$is_new             = 0 === $post_id;
	$title              = sanitize_text_field( $details['title'] ?? ( $fallback['title'] ?? $form_slug ) );
	$description        = sanitize_textarea_field( wp_strip_all_tags( $details['description'] ?? ( $fallback['description'] ?? '' ) ) );
	$previous_title     = $post_id ? (string) get_post_meta( $post_id, '_formica_stage_helloasso_title', true ) : '';
	$previous_summary   = $post_id ? (string) get_post_meta( $post_id, '_formica_stage_helloasso_description', true ) : '';
	$current_title      = $post_id ? (string) get_the_title( $post_id ) : '';
	$current_summary    = $post_id ? (string) get_post_meta( $post_id, '_formica_stage_description', true ) : '';
	$can_update_title   = $is_new || '' === $current_title || $current_title === $previous_title;
	$can_update_summary = $is_new || '' === $current_summary || $current_summary === $previous_summary;

	$post_data = array(
		'post_type' => FORMICA_DATES_STAGE_POST_TYPE,
	);
	if ( $is_new ) {
		$post_data['post_status'] = in_array( $details['state'] ?? 'Public', array( 'Public', 'Private' ), true ) ? 'publish' : 'draft';
	}
	if ( $can_update_title ) {
		$post_data['post_title'] = $title ?: $form_slug;
	}

	if ( $is_new ) {
		$post_id = wp_insert_post( $post_data, true );
	} else {
		$post_data['ID'] = $post_id;
		$post_id         = wp_update_post( $post_data, true );
	}
	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	$place       = isset( $details['place'] ) && is_array( $details['place'] ) ? $details['place'] : array();
	$booking_url = esc_url_raw( $details['url'] ?? ( $fallback['url'] ?? '' ) );
	$meta        = array(
		'_formica_stage_datetime'                    => formica_dates_helloasso_local_datetime( $details['startDate'] ?? ( $fallback['startDate'] ?? '' ) ),
		'_formica_stage_end_datetime'                => formica_dates_helloasso_local_datetime( $details['endDate'] ?? ( $fallback['endDate'] ?? '' ) ),
		'_formica_stage_venue'                       => sanitize_text_field( $place['name'] ?? ( $place['address'] ?? '' ) ),
		'_formica_stage_city'                        => sanitize_text_field( $place['city'] ?? '' ),
		'_formica_stage_booking'                     => $booking_url,
		'_formica_stage_helloasso_key'               => formica_dates_helloasso_source_key( $organization_slug, $form_slug ),
		'_formica_stage_helloasso_organization_slug' => $organization_slug,
		'_formica_stage_helloasso_form_slug'         => $form_slug,
		'_formica_stage_helloasso_state'             => sanitize_key( $details['state'] ?? ( $fallback['state'] ?? '' ) ),
		'_formica_stage_helloasso_activity_type'     => sanitize_text_field( $details['activityType'] ?? '' ),
		'_formica_stage_helloasso_place_address'     => sanitize_text_field( $place['address'] ?? '' ),
		'_formica_stage_helloasso_tiers'             => formica_dates_helloasso_normalize_tiers( $details['tiers'] ?? array() ),
		'_formica_stage_helloasso_title'             => $title,
		'_formica_stage_helloasso_description'       => $description,
		'_formica_stage_helloasso_synced_at'         => current_time( 'mysql', true ),
	);

	if ( $can_update_summary ) {
		$meta['_formica_stage_description'] = $description;
	}

	foreach ( $meta as $key => $value ) {
		if ( '' === $value || array() === $value ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, $value );
		}
	}

	return (int) $post_id;
}

/**
 * Formats imported HelloAsso prices for public display.
 */
function formica_dates_helloasso_price_labels( $post_id ) {
	$tiers  = get_post_meta( $post_id, '_formica_stage_helloasso_tiers', true );
	$labels = array();

	foreach ( is_array( $tiers ) ? $tiers : array() as $tier ) {
		$label = sanitize_text_field( $tier['label'] ?? '' );
		if ( isset( $tier['min_amount'] ) && null !== $tier['min_amount'] ) {
			$price = sprintf( __( 'à partir de %s €', 'formica-dates' ), number_format_i18n( (int) $tier['min_amount'] / 100, 2 ) );
		} elseif ( isset( $tier['price'] ) && null !== $tier['price'] ) {
			$price = 0 === (int) $tier['price']
				? __( 'gratuit', 'formica-dates' )
				: sprintf( __( '%s €', 'formica-dates' ), number_format_i18n( (int) $tier['price'] / 100, 2 ) );
		} else {
			$price = '';
		}
		$labels[] = implode( ' — ', array_filter( array( $label, $price ) ) );
	}

	return array_values( array_filter( $labels ) );
}

/**
 * Registers HelloAsso source metadata for REST-safe reads in the editor.
 */
function formica_dates_helloasso_register_meta() {
	$strings = array(
		'_formica_stage_helloasso_key',
		'_formica_stage_helloasso_organization_slug',
		'_formica_stage_helloasso_form_slug',
		'_formica_stage_helloasso_state',
		'_formica_stage_helloasso_activity_type',
		'_formica_stage_helloasso_place_address',
		'_formica_stage_helloasso_title',
		'_formica_stage_helloasso_description',
		'_formica_stage_helloasso_synced_at',
	);

	foreach ( $strings as $key ) {
		register_post_meta(
			FORMICA_DATES_STAGE_POST_TYPE,
			$key,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => false,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => static function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}
}
add_action( 'init', 'formica_dates_helloasso_register_meta', 15 );

/**
 * Adds the import screen below the Stages menu.
 */
function formica_dates_helloasso_admin_menu() {
	add_submenu_page(
		'edit.php?post_type=' . FORMICA_DATES_STAGE_POST_TYPE,
		__( 'Importer depuis HelloAsso', 'formica-dates' ),
		__( 'Importer depuis HelloAsso', 'formica-dates' ),
		'manage_options',
		'formica-dates-helloasso',
		'formica_dates_helloasso_admin_page'
	);
}
add_action( 'admin_menu', 'formica_dates_helloasso_admin_menu' );

/**
 * Stores one short-lived notice for the current administrator.
 */
function formica_dates_helloasso_set_notice( $type, $message ) {
	set_transient(
		'formica_ha_notice_' . get_current_user_id(),
		array( 'type' => $type, 'message' => sanitize_text_field( $message ) ),
		MINUTE_IN_SECONDS
	);
}

/**
 * Outputs and clears the current HelloAsso administration notice.
 */
function formica_dates_helloasso_admin_notice() {
	$key    = 'formica_ha_notice_' . get_current_user_id();
	$notice = get_transient( $key );
	delete_transient( $key );
	if ( ! is_array( $notice ) || empty( $notice['message'] ) ) {
		return;
	}
	?>
	<div class="notice notice-<?php echo esc_attr( 'error' === $notice['type'] ? 'error' : 'success' ); ?> is-dismissible"><p><?php echo esc_html( $notice['message'] ); ?></p></div>
	<?php
}

/**
 * Returns the HelloAsso import page URL.
 */
function formica_dates_helloasso_admin_url() {
	return admin_url( 'edit.php?post_type=' . FORMICA_DATES_STAGE_POST_TYPE . '&page=formica-dates-helloasso' );
}

/**
 * Renders connection settings and the selectable event list.
 */
function formica_dates_helloasso_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$settings = formica_dates_helloasso_settings();
	$events   = formica_dates_helloasso_is_configured() ? formica_dates_helloasso_list_events() : array();
	?>
	<div class="wrap formica-helloasso-admin">
		<h1><?php esc_html_e( 'Stages · HelloAsso', 'formica-dates' ); ?></h1>
		<?php formica_dates_helloasso_admin_notice(); ?>
		<p><?php esc_html_e( 'Importez les événements HelloAsso comme stages. Les horaires, le lieu, les tarifs et le lien d’inscription seront synchronisés ; le niveau, « quoi amener » et les spectacles associés resteront gérés dans WordPress.', 'formica-dates' ); ?></p>

		<div class="formica-helloasso-card">
			<h2><?php esc_html_e( 'Connexion à l’association', 'formica-dates' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="formica_dates_helloasso_save_settings">
				<?php wp_nonce_field( 'formica_dates_helloasso_save_settings' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="formica-ha-organization"><?php esc_html_e( 'Slug de l’association', 'formica-dates' ); ?></label></th>
						<td><input id="formica-ha-organization" name="organization_slug" class="regular-text" type="text" value="<?php echo esc_attr( $settings['organization_slug'] ); ?>" required <?php disabled( defined( 'FORMICA_DATES_HELLOASSO_ORGANIZATION_SLUG' ) ); ?>></td>
					</tr>
					<tr>
						<th scope="row"><label for="formica-ha-client-id"><?php esc_html_e( 'Client ID', 'formica-dates' ); ?></label></th>
						<td><input id="formica-ha-client-id" name="client_id" class="regular-text" type="text" value="<?php echo esc_attr( $settings['client_id'] ); ?>" autocomplete="off" required <?php disabled( defined( 'FORMICA_DATES_HELLOASSO_CLIENT_ID' ) ); ?>></td>
					</tr>
					<tr>
						<th scope="row"><label for="formica-ha-client-secret"><?php esc_html_e( 'Client secret', 'formica-dates' ); ?></label></th>
						<td>
							<input id="formica-ha-client-secret" name="client_secret" class="regular-text" type="password" value="" autocomplete="new-password" placeholder="<?php echo esc_attr( $settings['client_secret'] ? __( 'Secret déjà enregistré', 'formica-dates' ) : '' ); ?>" <?php disabled( defined( 'FORMICA_DATES_HELLOASSO_CLIENT_SECRET' ) ); ?>>
							<p class="description"><?php esc_html_e( 'Laissez vide pour conserver le secret actuel. Une fois enregistré, il n’est jamais réaffiché.', 'formica-dates' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Enregistrer et tester la connexion', 'formica-dates' ) ); ?>
			</form>
		</div>

		<?php if ( is_wp_error( $events ) ) : ?>
			<div class="notice notice-error inline"><p><?php echo esc_html( $events->get_error_message() ); ?></p></div>
		<?php elseif ( formica_dates_helloasso_is_configured() ) : ?>
			<div class="formica-helloasso-card">
				<h2><?php esc_html_e( 'Événements disponibles', 'formica-dates' ); ?></h2>
				<?php if ( $events ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="formica_dates_helloasso_import">
						<?php wp_nonce_field( 'formica_dates_helloasso_import' ); ?>
						<table class="widefat striped">
							<thead><tr><td class="check-column"><input type="checkbox" aria-label="<?php esc_attr_e( 'Tout sélectionner', 'formica-dates' ); ?>"></td><th><?php esc_html_e( 'Événement', 'formica-dates' ); ?></th><th><?php esc_html_e( 'Date', 'formica-dates' ); ?></th><th><?php esc_html_e( 'État', 'formica-dates' ); ?></th><th><?php esc_html_e( 'WordPress', 'formica-dates' ); ?></th></tr></thead>
							<tbody>
							<?php foreach ( $events as $event ) : ?>
								<?php
								$form_slug = sanitize_title( $event['formSlug'] ?? '' );
								$stage_id  = formica_dates_helloasso_find_stage( $settings['organization_slug'], $form_slug );
								$start     = formica_dates_helloasso_local_datetime( $event['startDate'] ?? '' );
								?>
								<tr>
									<th scope="row" class="check-column"><input name="form_slugs[]" type="checkbox" value="<?php echo esc_attr( $form_slug ); ?>"></th>
									<td><strong><?php echo esc_html( $event['title'] ?? $form_slug ); ?></strong><br><a href="<?php echo esc_url( $event['url'] ?? '' ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Voir sur HelloAsso ↗', 'formica-dates' ); ?></a></td>
								<td><?php echo $start ? esc_html( wp_date( 'd/m/Y à H\hi', DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $start, wp_timezone() )->getTimestamp(), wp_timezone() ) ) : '—'; ?></td>
									<td><?php echo esc_html( $event['state'] ?? '—' ); ?></td>
									<td><?php if ( $stage_id ) : ?><a href="<?php echo esc_url( get_edit_post_link( $stage_id ) ); ?>"><?php esc_html_e( 'Stage lié', 'formica-dates' ); ?></a><?php else : ?>—<?php endif; ?></td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
						<?php submit_button( __( 'Importer ou synchroniser la sélection', 'formica-dates' ) ); ?>
					</form>
				<?php else : ?>
					<p><?php esc_html_e( 'Aucun événement HelloAsso n’a été trouvé pour cette association.', 'formica-dates' ); ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
	<style>
		.formica-helloasso-admin { max-width: 1100px; }
		.formica-helloasso-card { background: #fff; border: 1px solid #dcdcde; margin: 20px 0; padding: 8px 24px 18px; }
		.formica-helloasso-card .widefat { margin-top: 16px; }
	</style>
	<script>
		document.addEventListener('DOMContentLoaded', function () {
			var toggle = document.querySelector('.formica-helloasso-card thead .check-column input');
			if (!toggle) return;
			toggle.addEventListener('change', function () {
				document.querySelectorAll('.formica-helloasso-card tbody input[name="form_slugs[]"]').forEach(function (input) {
					input.checked = toggle.checked;
				});
			});
		});
	</script>
	<?php
}

/**
 * Saves credentials without ever redisplaying the client secret.
 */
function formica_dates_helloasso_save_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Vous n’avez pas l’autorisation nécessaire.', 'formica-dates' ) );
	}
	check_admin_referer( 'formica_dates_helloasso_save_settings' );

	$existing = get_option( FORMICA_DATES_HELLOASSO_OPTION, array() );
	$existing = is_array( $existing ) ? $existing : array();
	$settings = array(
		'organization_slug' => defined( 'FORMICA_DATES_HELLOASSO_ORGANIZATION_SLUG' ) ? ( $existing['organization_slug'] ?? '' ) : sanitize_title( wp_unslash( $_POST['organization_slug'] ?? '' ) ),
		'client_id'         => defined( 'FORMICA_DATES_HELLOASSO_CLIENT_ID' ) ? ( $existing['client_id'] ?? '' ) : trim( (string) wp_unslash( $_POST['client_id'] ?? '' ) ),
		'client_secret'     => (string) ( $existing['client_secret'] ?? '' ),
	);
	if ( ! defined( 'FORMICA_DATES_HELLOASSO_CLIENT_SECRET' ) && ! empty( $_POST['client_secret'] ) ) {
		$settings['client_secret'] = trim( (string) wp_unslash( $_POST['client_secret'] ) );
	}

	delete_transient( formica_dates_helloasso_token_key() );
	update_option( FORMICA_DATES_HELLOASSO_OPTION, $settings, false );
	$result = formica_dates_helloasso_list_events();
	if ( is_wp_error( $result ) ) {
		formica_dates_helloasso_set_notice( 'error', sprintf( __( 'Configuration enregistrée, mais la connexion a échoué : %s', 'formica-dates' ), $result->get_error_message() ) );
	} else {
		formica_dates_helloasso_set_notice( 'success', sprintf( _n( 'Connexion réussie : %d événement trouvé.', 'Connexion réussie : %d événements trouvés.', count( $result ), 'formica-dates' ), count( $result ) ) );
	}

	wp_safe_redirect( formica_dates_helloasso_admin_url() );
	exit;
}
add_action( 'admin_post_formica_dates_helloasso_save_settings', 'formica_dates_helloasso_save_settings' );

/**
 * Imports or synchronizes the selected HelloAsso events.
 */
function formica_dates_helloasso_import_selected() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Vous n’avez pas l’autorisation nécessaire.', 'formica-dates' ) );
	}
	check_admin_referer( 'formica_dates_helloasso_import' );

	$selected = isset( $_POST['form_slugs'] ) ? array_map( 'sanitize_title', (array) wp_unslash( $_POST['form_slugs'] ) ) : array();
	$selected = array_values( array_unique( array_filter( $selected ) ) );
	if ( ! $selected ) {
		formica_dates_helloasso_set_notice( 'error', __( 'Sélectionnez au moins un événement à importer.', 'formica-dates' ) );
		wp_safe_redirect( formica_dates_helloasso_admin_url() );
		exit;
	}

	$events = formica_dates_helloasso_list_events();
	if ( is_wp_error( $events ) ) {
		formica_dates_helloasso_set_notice( 'error', $events->get_error_message() );
		wp_safe_redirect( formica_dates_helloasso_admin_url() );
		exit;
	}

	$by_slug = array();
	foreach ( $events as $event ) {
		$by_slug[ sanitize_title( $event['formSlug'] ?? '' ) ] = $event;
	}
	$success = 0;
	$errors  = array();
	foreach ( $selected as $form_slug ) {
		if ( ! isset( $by_slug[ $form_slug ] ) ) {
			$errors[] = $form_slug;
			continue;
		}
		$details = formica_dates_helloasso_event_details( $form_slug );
		$result  = is_wp_error( $details ) ? $details : formica_dates_helloasso_upsert_stage( $details, $by_slug[ $form_slug ] );
		if ( is_wp_error( $result ) ) {
			$errors[] = $form_slug . ' : ' . $result->get_error_message();
		} else {
			++$success;
		}
	}

	if ( $errors ) {
		formica_dates_helloasso_set_notice( 'error', sprintf( __( '%1$d événement(s) synchronisé(s). Échec pour : %2$s', 'formica-dates' ), $success, implode( ', ', $errors ) ) );
	} else {
		formica_dates_helloasso_set_notice( 'success', sprintf( _n( '%d stage importé ou synchronisé.', '%d stages importés ou synchronisés.', $success, 'formica-dates' ), $success ) );
	}

	wp_safe_redirect( formica_dates_helloasso_admin_url() );
	exit;
}
add_action( 'admin_post_formica_dates_helloasso_import', 'formica_dates_helloasso_import_selected' );

/**
 * Shows source and synchronization information on imported stages.
 */
function formica_dates_helloasso_add_meta_box() {
	add_meta_box(
		'formica-stage-helloasso',
		__( 'Source HelloAsso', 'formica-dates' ),
		'formica_dates_helloasso_render_meta_box',
		FORMICA_DATES_STAGE_POST_TYPE,
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes', 'formica_dates_helloasso_add_meta_box' );

function formica_dates_helloasso_render_meta_box( $post ) {
	$form_slug = get_post_meta( $post->ID, '_formica_stage_helloasso_form_slug', true );
	if ( ! $form_slug ) {
		printf( '<p>%s</p><p><a class="button" href="%s">%s</a></p>', esc_html__( 'Ce stage n’est pas encore lié à un événement HelloAsso.', 'formica-dates' ), esc_url( formica_dates_helloasso_admin_url() ), esc_html__( 'Importer un événement', 'formica-dates' ) );
		return;
	}

	$state     = get_post_meta( $post->ID, '_formica_stage_helloasso_state', true );
	$synced_at = get_post_meta( $post->ID, '_formica_stage_helloasso_synced_at', true );
	$booking   = get_post_meta( $post->ID, '_formica_stage_booking', true );
	?>
	<p><strong><?php esc_html_e( 'Événement lié', 'formica-dates' ); ?></strong><br><?php echo esc_html( $form_slug ); ?></p>
	<?php if ( $state ) : ?><p><?php echo esc_html( sprintf( __( 'État HelloAsso : %s', 'formica-dates' ), $state ) ); ?></p><?php endif; ?>
	<?php if ( $synced_at ) : ?><p><?php echo esc_html( sprintf( __( 'Dernière synchronisation : %s UTC', 'formica-dates' ), $synced_at ) ); ?></p><?php endif; ?>
	<p>
		<?php if ( $booking ) : ?><a class="button" href="<?php echo esc_url( $booking ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Voir sur HelloAsso ↗', 'formica-dates' ); ?></a><?php endif; ?>
		<a class="button" href="<?php echo esc_url( formica_dates_helloasso_admin_url() ); ?>"><?php esc_html_e( 'Gérer la synchronisation', 'formica-dates' ); ?></a>
	</p>
	<p class="description"><?php esc_html_e( 'Les champs éditoriaux Formica ne sont pas écrasés lorsqu’ils ont été personnalisés.', 'formica-dates' ); ?></p>
	<?php
}

/**
 * Schedules a lightweight refresh for stages already imported by an administrator.
 */
function formica_dates_helloasso_ensure_cron() {
	$scheduled = wp_next_scheduled( FORMICA_DATES_HELLOASSO_CRON );
	if ( formica_dates_helloasso_is_configured() && ! $scheduled ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', FORMICA_DATES_HELLOASSO_CRON );
	} elseif ( ! formica_dates_helloasso_is_configured() && $scheduled ) {
		wp_unschedule_event( $scheduled, FORMICA_DATES_HELLOASSO_CRON );
	}
}
add_action( 'init', 'formica_dates_helloasso_ensure_cron', 30 );

/**
 * Synchronizes only imported stages; new HelloAsso events require manual selection.
 */
function formica_dates_helloasso_cron_sync() {
	if ( ! formica_dates_helloasso_is_configured() ) {
		return;
	}

	$stage_ids = get_posts(
		array(
			'post_type'      => FORMICA_DATES_STAGE_POST_TYPE,
			'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => '_formica_stage_helloasso_form_slug',
			'no_found_rows'  => true,
		)
	);
	foreach ( $stage_ids as $stage_id ) {
		$form_slug = get_post_meta( $stage_id, '_formica_stage_helloasso_form_slug', true );
		$details   = formica_dates_helloasso_event_details( $form_slug );
		if ( ! is_wp_error( $details ) ) {
			formica_dates_helloasso_upsert_stage( $details );
		}
	}
}
add_action( FORMICA_DATES_HELLOASSO_CRON, 'formica_dates_helloasso_cron_sync' );

/**
 * Removes the scheduled refresh when the plugin is disabled.
 */
function formica_dates_helloasso_deactivate() {
	$timestamp = wp_next_scheduled( FORMICA_DATES_HELLOASSO_CRON );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, FORMICA_DATES_HELLOASSO_CRON );
	}
}
register_deactivation_hook( dirname( __DIR__ ) . '/formica-dates.php', 'formica_dates_helloasso_deactivate' );
