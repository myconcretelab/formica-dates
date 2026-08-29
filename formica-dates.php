<?php
/**
 * Plugin Name: Formica Dates
 * Description: Gestion centralisée des spectacles et des stages, avec leurs dates et leurs associations.
 * Version: 1.3.0
 * Author: Compagnie Formica
 * Text Domain: formica-dates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const FORMICA_DATES_VERSION = '1.3.0';
const FORMICA_DATES_POST_TYPE = 'formica_event';
const FORMICA_DATES_STAGE_POST_TYPE = 'formica_stage';

/**
 * Registers the event content type and its metadata.
 */
function formica_dates_register_post_type() {
	register_post_type(
		FORMICA_DATES_POST_TYPE,
		array(
			'labels' => array(
				'name'               => __( 'Dates', 'formica-dates' ),
				'singular_name'      => __( 'Date', 'formica-dates' ),
				'add_new'            => __( 'Ajouter une date', 'formica-dates' ),
				'add_new_item'       => __( 'Ajouter une date', 'formica-dates' ),
				'edit_item'          => __( 'Modifier la date', 'formica-dates' ),
				'new_item'           => __( 'Nouvelle date', 'formica-dates' ),
				'view_item'          => __( 'Voir la date', 'formica-dates' ),
				'search_items'       => __( 'Rechercher une date', 'formica-dates' ),
				'not_found'          => __( 'Aucune date trouvée', 'formica-dates' ),
				'not_found_in_trash' => __( 'Aucune date dans la corbeille', 'formica-dates' ),
				'all_items'          => __( 'Toutes les dates', 'formica-dates' ),
				'menu_name'          => __( 'Dates', 'formica-dates' ),
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => true,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'menu_icon'           => 'dashicons-calendar-alt',
			'menu_position'       => 20,
			'supports'            => array( 'title' ),
			'template'            => array(),
		)
	);

	register_post_type(
		FORMICA_DATES_STAGE_POST_TYPE,
		array(
			'labels' => array(
				'name'               => __( 'Stages', 'formica-dates' ),
				'singular_name'      => __( 'Stage', 'formica-dates' ),
				'add_new'            => __( 'Ajouter un stage', 'formica-dates' ),
				'add_new_item'       => __( 'Ajouter un stage', 'formica-dates' ),
				'edit_item'          => __( 'Modifier le stage', 'formica-dates' ),
				'new_item'           => __( 'Nouveau stage', 'formica-dates' ),
				'view_item'          => __( 'Voir le stage', 'formica-dates' ),
				'search_items'       => __( 'Rechercher un stage', 'formica-dates' ),
				'not_found'          => __( 'Aucun stage trouvé', 'formica-dates' ),
				'not_found_in_trash' => __( 'Aucun stage dans la corbeille', 'formica-dates' ),
				'all_items'          => __( 'Tous les stages', 'formica-dates' ),
				'menu_name'          => __( 'Stages', 'formica-dates' ),
				'archives'           => __( 'Stages', 'formica-dates' ),
				'attributes'         => __( 'Informations du stage', 'formica-dates' ),
			),
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => true,
			'exclude_from_search' => false,
			'has_archive'         => false,
			'rewrite'             => array(
				'slug'       => 'stages',
				'with_front' => false,
			),
			'menu_icon'           => 'dashicons-welcome-learn-more',
			'menu_position'       => 21,
			'supports'            => array( 'title' ),
			'template'            => array(),
		)
	);

	$meta_fields = array(
		'_formica_event_datetime' => array(
			'type'              => 'string',
			'sanitize_callback' => 'formica_dates_sanitize_datetime',
		),
		'_formica_event_venue'    => array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		),
		'_formica_event_city'     => array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		),
		'_formica_event_booking'  => array(
			'type'              => 'string',
			'sanitize_callback' => 'esc_url_raw',
		),
		'_formica_event_variant'  => array(
			'type'              => 'string',
			'sanitize_callback' => 'formica_dates_sanitize_variant',
		),
	);

	foreach ( $meta_fields as $meta_key => $args ) {
		register_post_meta(
			FORMICA_DATES_POST_TYPE,
			$meta_key,
			array_merge(
				$args,
				array(
					'single'        => true,
					'show_in_rest'  => true,
					'auth_callback' => static function () {
						return current_user_can( 'edit_posts' );
					},
				)
			)
		);
	}

	$stage_meta_fields = array(
		'_formica_stage_datetime'     => array(
			'type'              => 'string',
			'sanitize_callback' => 'formica_dates_sanitize_datetime',
		),
		'_formica_stage_end_datetime' => array(
			'type'              => 'string',
			'sanitize_callback' => 'formica_dates_sanitize_datetime',
		),
		'_formica_stage_venue'        => array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		),
		'_formica_stage_city'         => array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		),
		'_formica_stage_booking'      => array(
			'type'              => 'string',
			'sanitize_callback' => 'esc_url_raw',
		),
		'_formica_stage_description'  => array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_textarea_field',
		),
		'_formica_stage_level'        => array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		),
		'_formica_stage_bring'        => array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_textarea_field',
		),
		'_formica_stage_event_ids'    => array(
			'type'              => 'array',
			'sanitize_callback' => 'formica_dates_sanitize_related_ids',
			'show_in_rest'      => array(
				'schema' => array(
					'type'  => 'array',
					'items' => array( 'type' => 'integer' ),
				),
			),
		),
	);

	foreach ( $stage_meta_fields as $meta_key => $args ) {
		register_post_meta(
			FORMICA_DATES_STAGE_POST_TYPE,
			$meta_key,
			array_merge(
				array(
					'single'        => true,
					'show_in_rest'  => true,
					'auth_callback' => static function () {
						return current_user_can( 'edit_posts' );
					},
				),
				$args
			)
		);
	}
}
add_action( 'init', 'formica_dates_register_post_type' );

/**
 * Flushes the stage permalink rules once after a plugin upgrade.
 */
function formica_dates_maybe_upgrade() {
	$installed_version = get_option( 'formica_dates_version', '0.0.0' );

	if ( version_compare( $installed_version, FORMICA_DATES_VERSION, '<' ) ) {
		flush_rewrite_rules( false );
		update_option( 'formica_dates_version', FORMICA_DATES_VERSION );
	}
}
add_action( 'init', 'formica_dates_maybe_upgrade', 20 );

/**
 * Sanitizes a local event date stored as a sortable SQL datetime.
 */
function formica_dates_sanitize_datetime( $value ) {
	$value = sanitize_text_field( $value );
	$date  = DateTimeImmutable::createFromFormat( 'Y-m-d\TH:i', $value, wp_timezone() );

	if ( ! $date ) {
		$date = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $value, wp_timezone() );
	}

	return $date ? $date->format( 'Y-m-d H:i:s' ) : '';
}

/**
 * Limits visual variants to the supported palette.
 */
function formica_dates_sanitize_variant( $value ) {
	$value = sanitize_key( $value );
	return in_array( $value, array( 'orange', 'paper', 'green' ), true ) ? $value : 'orange';
}

/**
 * Keeps only valid event IDs in a relationship value.
 */
function formica_dates_sanitize_related_ids( $values ) {
	$values = is_array( $values ) ? $values : array();
	$ids    = array_filter( array_map( 'absint', $values ) );
	$ids    = array_filter(
		$ids,
		static function ( $post_id ) {
			return FORMICA_DATES_POST_TYPE === get_post_type( $post_id );
		}
	);

	return array_values( array_unique( $ids ) );
}

/**
 * Formats a stored datetime for a datetime-local field.
 */
function formica_dates_datetime_field_value( $value ) {
	if ( ! $value ) {
		return '';
	}

	$date = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $value, wp_timezone() );
	return $date ? $date->format( 'Y-m-d\TH:i' ) : '';
}

/**
 * Returns the stages linked to an event.
 */
function formica_dates_get_related_stage_ids( $event_id, $post_status = 'publish' ) {
	$stages = get_posts(
		array(
			'post_type'      => FORMICA_DATES_STAGE_POST_TYPE,
			'post_status'    => $post_status,
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => '_formica_stage_datetime',
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		)
	);

	return array_values(
		array_filter(
			$stages,
			static function ( $stage_id ) use ( $event_id ) {
				$event_ids = formica_dates_sanitize_related_ids( get_post_meta( $stage_id, '_formica_stage_event_ids', true ) );
				return in_array( (int) $event_id, $event_ids, true );
			}
		)
	);
}

/**
 * Returns the event IDs linked to a stage, optionally filtered by status.
 */
function formica_dates_get_related_event_ids( $stage_id, $post_status = null ) {
	$event_ids = formica_dates_sanitize_related_ids( get_post_meta( $stage_id, '_formica_stage_event_ids', true ) );

	if ( $post_status ) {
		$event_ids = array_filter(
			$event_ids,
			static function ( $event_id ) use ( $post_status ) {
				return $post_status === get_post_status( $event_id );
			}
		);
	}

	return array_values( $event_ids );
}

/**
 * Adds the structured event fields below the title.
 */
function formica_dates_add_meta_boxes() {
	add_meta_box(
		'formica-event-details',
		__( 'Informations de la représentation', 'formica-dates' ),
		'formica_dates_render_meta_box',
		FORMICA_DATES_POST_TYPE,
		'normal',
		'high'
	);

	add_meta_box(
		'formica-stage-details',
		__( 'Informations du stage', 'formica-dates' ),
		'formica_dates_render_stage_meta_box',
		FORMICA_DATES_STAGE_POST_TYPE,
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'formica_dates_add_meta_boxes' );

/**
 * Outputs the event form.
 */
function formica_dates_render_meta_box( $post ) {
	$datetime = get_post_meta( $post->ID, '_formica_event_datetime', true );
	$venue    = get_post_meta( $post->ID, '_formica_event_venue', true );
	$city     = get_post_meta( $post->ID, '_formica_event_city', true );
	$booking  = get_post_meta( $post->ID, '_formica_event_booking', true );
	$variant  = get_post_meta( $post->ID, '_formica_event_variant', true ) ?: 'orange';
	$datetime = formica_dates_datetime_field_value( $datetime );
	$stages   = get_posts(
		array(
			'post_type'      => FORMICA_DATES_STAGE_POST_TYPE,
			'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
			'posts_per_page' => -1,
			'meta_key'       => '_formica_stage_datetime',
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
		)
	);
	$related_stage_ids = formica_dates_get_related_stage_ids(
		$post->ID,
		array( 'publish', 'draft', 'pending', 'future', 'private' )
	);

	wp_nonce_field( 'formica_dates_save_event', 'formica_dates_nonce' );
	?>
	<div class="formica-event-fields">
		<p>
			<label for="formica-event-datetime"><strong><?php esc_html_e( 'Date et heure', 'formica-dates' ); ?></strong></label><br>
			<input id="formica-event-datetime" name="formica_event_datetime" type="datetime-local" value="<?php echo esc_attr( $datetime ); ?>" required>
		</p>
		<div class="formica-event-fields__columns">
			<p>
				<label for="formica-event-venue"><strong><?php esc_html_e( 'Lieu', 'formica-dates' ); ?></strong></label><br>
				<input id="formica-event-venue" name="formica_event_venue" type="text" value="<?php echo esc_attr( $venue ); ?>" placeholder="Le Théâtre de Poche">
			</p>
			<p>
				<label for="formica-event-city"><strong><?php esc_html_e( 'Ville', 'formica-dates' ); ?></strong></label><br>
				<input id="formica-event-city" name="formica_event_city" type="text" value="<?php echo esc_attr( $city ); ?>" placeholder="Nantes">
			</p>
		</div>
		<p>
			<label for="formica-event-booking"><strong><?php esc_html_e( 'Lien de réservation', 'formica-dates' ); ?></strong></label><br>
			<input id="formica-event-booking" name="formica_event_booking" type="url" value="<?php echo esc_attr( $booking ); ?>" placeholder="https://…">
		</p>
		<p>
			<label for="formica-event-variant"><strong><?php esc_html_e( 'Couleur', 'formica-dates' ); ?></strong></label><br>
			<select id="formica-event-variant" name="formica_event_variant">
				<option value="orange" <?php selected( $variant, 'orange' ); ?>><?php esc_html_e( 'Orange', 'formica-dates' ); ?></option>
				<option value="paper" <?php selected( $variant, 'paper' ); ?>><?php esc_html_e( 'Crème et vichy', 'formica-dates' ); ?></option>
				<option value="green" <?php selected( $variant, 'green' ); ?>><?php esc_html_e( 'Vert', 'formica-dates' ); ?></option>
			</select>
		</p>
		<div class="formica-event-fields__relations">
			<h3><?php esc_html_e( 'Stages associés', 'formica-dates' ); ?></h3>
			<?php if ( $stages ) : ?>
				<p class="description"><?php esc_html_e( 'Cochez les stages à afficher avec ce spectacle.', 'formica-dates' ); ?></p>
				<div class="formica-relation-list">
					<?php foreach ( $stages as $stage ) : ?>
						<?php $stage_date = formica_dates_get_datetime( $stage->ID, FORMICA_DATES_STAGE_POST_TYPE ); ?>
						<label>
							<input name="formica_event_stage_ids[]" type="checkbox" value="<?php echo esc_attr( $stage->ID ); ?>" <?php checked( in_array( $stage->ID, $related_stage_ids, true ) ); ?>>
							<span>
								<strong><?php echo esc_html( get_the_title( $stage ) ); ?></strong>
								<?php if ( $stage_date ) : ?><small><?php echo esc_html( wp_date( 'd/m/Y à H\hi', $stage_date->getTimestamp(), wp_timezone() ) ); ?></small><?php endif; ?>
							</span>
						</label>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<p class="description"><?php esc_html_e( 'Créez d’abord un stage pour pouvoir l’associer à ce spectacle.', 'formica-dates' ); ?></p>
			<?php endif; ?>
		</div>
	</div>
	<style>
		.formica-event-fields input[type="text"],
		.formica-event-fields input[type="url"] { width: 100%; }
		.formica-event-fields__columns { display: grid; gap: 16px; grid-template-columns: 1fr 1fr; }
		.formica-event-fields__relations { border-top: 1px solid #dcdcde; margin-top: 24px; padding-top: 8px; }
		.formica-relation-list { border: 1px solid #dcdcde; max-height: 240px; overflow: auto; }
		.formica-relation-list label { align-items: flex-start; display: flex; gap: 8px; padding: 10px 12px; }
		.formica-relation-list label + label { border-top: 1px solid #f0f0f1; }
		.formica-relation-list small { display: block; margin-top: 2px; }
		@media (max-width: 782px) { .formica-event-fields__columns { grid-template-columns: 1fr; } }
	</style>
	<?php
}

/**
 * Outputs the stage form and its event relationships.
 */
function formica_dates_render_stage_meta_box( $post ) {
	$datetime       = formica_dates_datetime_field_value( get_post_meta( $post->ID, '_formica_stage_datetime', true ) );
	$end_datetime   = formica_dates_datetime_field_value( get_post_meta( $post->ID, '_formica_stage_end_datetime', true ) );
	$venue          = get_post_meta( $post->ID, '_formica_stage_venue', true );
	$city           = get_post_meta( $post->ID, '_formica_stage_city', true );
	$booking        = get_post_meta( $post->ID, '_formica_stage_booking', true );
	$description    = get_post_meta( $post->ID, '_formica_stage_description', true );
	$level          = get_post_meta( $post->ID, '_formica_stage_level', true );
	$bring          = get_post_meta( $post->ID, '_formica_stage_bring', true );
	$event_ids      = formica_dates_get_related_event_ids( $post->ID );
	$legacy_content = get_post_field( 'post_excerpt', $post->ID ) ?: get_post_field( 'post_content', $post->ID );
	$events         = get_posts(
		array(
			'post_type'      => FORMICA_DATES_POST_TYPE,
			'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
			'posts_per_page' => -1,
			'meta_key'       => '_formica_event_datetime',
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
		)
	);

	if ( ! $description && $legacy_content ) {
		$description = wp_strip_all_tags( strip_shortcodes( $legacy_content ) );
	}

	wp_nonce_field( 'formica_dates_save_stage', 'formica_stage_nonce' );
	?>
	<div class="formica-event-fields">
		<p class="description"><?php esc_html_e( 'Créez une fiche distincte pour chaque stage ou chaque créneau, même lorsqu’ils ont lieu le même jour.', 'formica-dates' ); ?></p>
		<p>
			<label for="formica-stage-description"><strong><?php esc_html_e( 'Description', 'formica-dates' ); ?></strong></label><br>
			<textarea id="formica-stage-description" name="formica_stage_description" rows="6" required><?php echo esc_textarea( $description ); ?></textarea>
		</p>
		<div class="formica-event-fields__columns">
			<p>
				<label for="formica-stage-datetime"><strong><?php esc_html_e( 'Début', 'formica-dates' ); ?></strong></label><br>
				<input id="formica-stage-datetime" name="formica_stage_datetime" type="datetime-local" value="<?php echo esc_attr( $datetime ); ?>" required>
			</p>
			<p>
				<label for="formica-stage-end-datetime"><strong><?php esc_html_e( 'Fin', 'formica-dates' ); ?></strong></label><br>
				<input id="formica-stage-end-datetime" name="formica_stage_end_datetime" type="datetime-local" value="<?php echo esc_attr( $end_datetime ); ?>">
			</p>
		</div>
		<div class="formica-event-fields__columns">
			<p>
				<label for="formica-stage-level"><strong><?php esc_html_e( 'Niveau', 'formica-dates' ); ?></strong></label><br>
				<input id="formica-stage-level" name="formica_stage_level" type="text" value="<?php echo esc_attr( $level ); ?>" placeholder="Tous niveaux, débutant, confirmé…" required>
			</p>
			<p>
				<label for="formica-stage-venue"><strong><?php esc_html_e( 'Lieu', 'formica-dates' ); ?></strong></label><br>
				<input id="formica-stage-venue" name="formica_stage_venue" type="text" value="<?php echo esc_attr( $venue ); ?>" placeholder="Studio Formica" required>
			</p>
		</div>
		<div class="formica-event-fields__columns">
			<p>
				<label for="formica-stage-city"><strong><?php esc_html_e( 'Ville', 'formica-dates' ); ?></strong></label><br>
				<input id="formica-stage-city" name="formica_stage_city" type="text" value="<?php echo esc_attr( $city ); ?>" placeholder="Nantes" required>
			</p>
			<p>
				<label for="formica-stage-booking"><strong><?php esc_html_e( 'Lien HelloAsso', 'formica-dates' ); ?></strong></label><br>
				<input id="formica-stage-booking" name="formica_stage_booking" type="url" value="<?php echo esc_attr( $booking ); ?>" placeholder="https://www.helloasso.com/…" required>
			</p>
		</div>
		<p>
			<label for="formica-stage-bring"><strong><?php esc_html_e( 'Quoi amener', 'formica-dates' ); ?></strong></label><br>
			<textarea id="formica-stage-bring" name="formica_stage_bring" rows="4" required><?php echo esc_textarea( $bring ); ?></textarea>
		</p>
		<div class="formica-event-fields__relations">
			<h3><?php esc_html_e( 'Spectacles associés', 'formica-dates' ); ?></h3>
			<?php if ( $events ) : ?>
				<p class="description"><?php esc_html_e( 'Vous pouvez associer plusieurs représentations au même stage.', 'formica-dates' ); ?></p>
				<div class="formica-relation-list">
					<?php foreach ( $events as $event ) : ?>
						<?php $event_date = formica_dates_get_datetime( $event->ID ); ?>
						<label>
							<input name="formica_stage_event_ids[]" type="checkbox" value="<?php echo esc_attr( $event->ID ); ?>" <?php checked( in_array( $event->ID, $event_ids, true ) ); ?>>
							<span>
								<strong><?php echo esc_html( get_the_title( $event ) ); ?></strong>
								<?php if ( $event_date ) : ?><small><?php echo esc_html( wp_date( 'd/m/Y à H\hi', $event_date->getTimestamp(), wp_timezone() ) ); ?></small><?php endif; ?>
							</span>
						</label>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<p class="description"><?php esc_html_e( 'Aucun spectacle n’est encore disponible.', 'formica-dates' ); ?></p>
			<?php endif; ?>
		</div>
	</div>
	<style>
		.formica-event-fields input[type="text"],
		.formica-event-fields input[type="url"],
		.formica-event-fields textarea { width: 100%; }
		.formica-event-fields__columns { display: grid; gap: 16px; grid-template-columns: 1fr 1fr; }
		.formica-event-fields__relations { border-top: 1px solid #dcdcde; margin-top: 24px; padding-top: 8px; }
		.formica-relation-list { border: 1px solid #dcdcde; max-height: 240px; overflow: auto; }
		.formica-relation-list label { align-items: flex-start; display: flex; gap: 8px; padding: 10px 12px; }
		.formica-relation-list label + label { border-top: 1px solid #f0f0f1; }
		.formica-relation-list small { display: block; margin-top: 2px; }
		@media (max-width: 782px) { .formica-event-fields__columns { grid-template-columns: 1fr; } }
	</style>
	<?php
}

/**
 * Persists the event fields.
 */
function formica_dates_save_event( $post_id ) {
	if (
		! isset( $_POST['formica_dates_nonce'] ) ||
		! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['formica_dates_nonce'] ) ), 'formica_dates_save_event' ) ||
		( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
		! current_user_can( 'edit_post', $post_id )
	) {
		return;
	}

	$fields = array(
		'_formica_event_datetime' => array( 'formica_event_datetime', 'formica_dates_sanitize_datetime' ),
		'_formica_event_venue'    => array( 'formica_event_venue', 'sanitize_text_field' ),
		'_formica_event_city'     => array( 'formica_event_city', 'sanitize_text_field' ),
		'_formica_event_booking'  => array( 'formica_event_booking', 'esc_url_raw' ),
		'_formica_event_variant'  => array( 'formica_event_variant', 'formica_dates_sanitize_variant' ),
	);

	foreach ( $fields as $meta_key => $field ) {
		list( $field_name, $sanitize_callback ) = $field;
		$value = isset( $_POST[ $field_name ] ) ? call_user_func( $sanitize_callback, wp_unslash( $_POST[ $field_name ] ) ) : '';

		if ( '' === $value ) {
			delete_post_meta( $post_id, $meta_key );
		} else {
			update_post_meta( $post_id, $meta_key, $value );
		}
	}

	$stage_ids = isset( $_POST['formica_event_stage_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['formica_event_stage_ids'] ) ) : array();
	formica_dates_sync_event_stages( $post_id, $stage_ids );
}
add_action( 'save_post_' . FORMICA_DATES_POST_TYPE, 'formica_dates_save_event' );

/**
 * Updates the canonical relationship stored on stages from an event edit screen.
 */
function formica_dates_sync_event_stages( $event_id, $selected_stage_ids ) {
	$selected_stage_ids = array_values( array_unique( array_filter( array_map( 'absint', $selected_stage_ids ) ) ) );
	$all_stage_ids      = get_posts(
		array(
			'post_type'      => FORMICA_DATES_STAGE_POST_TYPE,
			'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	foreach ( $all_stage_ids as $stage_id ) {
		$event_ids      = formica_dates_get_related_event_ids( $stage_id );
		$is_selected    = in_array( $stage_id, $selected_stage_ids, true );
		$already_linked = in_array( (int) $event_id, $event_ids, true );

		if ( $is_selected && ! $already_linked ) {
			$event_ids[] = (int) $event_id;
			update_post_meta( $stage_id, '_formica_stage_event_ids', formica_dates_sanitize_related_ids( $event_ids ) );
		} elseif ( ! $is_selected && $already_linked ) {
			$event_ids = array_values( array_diff( $event_ids, array( (int) $event_id ) ) );
			if ( $event_ids ) {
				update_post_meta( $stage_id, '_formica_stage_event_ids', $event_ids );
			} else {
				delete_post_meta( $stage_id, '_formica_stage_event_ids' );
			}
		}
	}
}

/**
 * Persists the stage fields and event relationships.
 */
function formica_dates_save_stage( $post_id ) {
	if (
		! isset( $_POST['formica_stage_nonce'] ) ||
		! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['formica_stage_nonce'] ) ), 'formica_dates_save_stage' ) ||
		( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
		! current_user_can( 'edit_post', $post_id )
	) {
		return;
	}

	$fields = array(
		'_formica_stage_datetime'     => array( 'formica_stage_datetime', 'formica_dates_sanitize_datetime' ),
		'_formica_stage_end_datetime' => array( 'formica_stage_end_datetime', 'formica_dates_sanitize_datetime' ),
		'_formica_stage_venue'        => array( 'formica_stage_venue', 'sanitize_text_field' ),
		'_formica_stage_city'         => array( 'formica_stage_city', 'sanitize_text_field' ),
		'_formica_stage_booking'      => array( 'formica_stage_booking', 'esc_url_raw' ),
		'_formica_stage_description'  => array( 'formica_stage_description', 'sanitize_textarea_field' ),
		'_formica_stage_level'        => array( 'formica_stage_level', 'sanitize_text_field' ),
		'_formica_stage_bring'        => array( 'formica_stage_bring', 'sanitize_textarea_field' ),
	);

	foreach ( $fields as $meta_key => $field ) {
		list( $field_name, $sanitize_callback ) = $field;
		$value = isset( $_POST[ $field_name ] ) ? call_user_func( $sanitize_callback, wp_unslash( $_POST[ $field_name ] ) ) : '';

		if ( '' === $value ) {
			delete_post_meta( $post_id, $meta_key );
		} else {
			update_post_meta( $post_id, $meta_key, $value );
		}
	}

	$event_ids = isset( $_POST['formica_stage_event_ids'] ) ? formica_dates_sanitize_related_ids( wp_unslash( $_POST['formica_stage_event_ids'] ) ) : array();
	if ( $event_ids ) {
		update_post_meta( $post_id, '_formica_stage_event_ids', $event_ids );
	} else {
		delete_post_meta( $post_id, '_formica_stage_event_ids' );
	}
}
add_action( 'save_post_' . FORMICA_DATES_STAGE_POST_TYPE, 'formica_dates_save_stage' );

/**
 * Adds useful columns to the event list.
 */
function formica_dates_admin_columns( $columns ) {
	return array(
		'cb'             => $columns['cb'],
		'title'          => __( 'Spectacle', 'formica-dates' ),
		'event_datetime' => __( 'Date', 'formica-dates' ),
		'event_location' => __( 'Lieu', 'formica-dates' ),
		'event_stages'   => __( 'Stages associés', 'formica-dates' ),
		'date'           => $columns['date'],
	);
}
add_filter( 'manage_' . FORMICA_DATES_POST_TYPE . '_posts_columns', 'formica_dates_admin_columns' );

function formica_dates_admin_column_content( $column, $post_id ) {
	if ( 'event_datetime' === $column ) {
		$date = formica_dates_get_datetime( $post_id );
		echo $date ? esc_html( wp_date( 'd/m/Y \à H\hi', $date->getTimestamp(), wp_timezone() ) ) : '—';
	}

	if ( 'event_location' === $column ) {
		$location = array_filter(
			array(
				get_post_meta( $post_id, '_formica_event_venue', true ),
				get_post_meta( $post_id, '_formica_event_city', true ),
			)
		);
		echo $location ? esc_html( implode( ' · ', $location ) ) : '—';
	}

	if ( 'event_stages' === $column ) {
		$titles = array_map(
			'get_the_title',
			formica_dates_get_related_stage_ids(
				$post_id,
				array( 'publish', 'draft', 'pending', 'future', 'private' )
			)
		);
		echo $titles ? esc_html( implode( ', ', $titles ) ) : '—';
	}
}
add_action( 'manage_' . FORMICA_DATES_POST_TYPE . '_posts_custom_column', 'formica_dates_admin_column_content', 10, 2 );

/**
 * Adds date, location and related shows to the stage list.
 */
function formica_dates_stage_admin_columns( $columns ) {
	return array(
		'cb'             => $columns['cb'],
		'title'          => __( 'Stage', 'formica-dates' ),
		'stage_datetime' => __( 'Date', 'formica-dates' ),
		'stage_level'    => __( 'Niveau', 'formica-dates' ),
		'stage_location' => __( 'Lieu', 'formica-dates' ),
		'stage_events'   => __( 'Spectacles associés', 'formica-dates' ),
		'date'           => $columns['date'],
	);
}
add_filter( 'manage_' . FORMICA_DATES_STAGE_POST_TYPE . '_posts_columns', 'formica_dates_stage_admin_columns' );

function formica_dates_stage_admin_column_content( $column, $post_id ) {
	if ( 'stage_datetime' === $column ) {
		$date = formica_dates_get_datetime( $post_id, FORMICA_DATES_STAGE_POST_TYPE );
		echo $date ? esc_html( wp_date( 'd/m/Y à H\hi', $date->getTimestamp(), wp_timezone() ) ) : '—';
	}

	if ( 'stage_location' === $column ) {
		$location = array_filter(
			array(
				get_post_meta( $post_id, '_formica_stage_venue', true ),
				get_post_meta( $post_id, '_formica_stage_city', true ),
			)
		);
		echo $location ? esc_html( implode( ' · ', $location ) ) : '—';
	}

	if ( 'stage_level' === $column ) {
		$level = get_post_meta( $post_id, '_formica_stage_level', true );
		echo $level ? esc_html( $level ) : '—';
	}

	if ( 'stage_events' === $column ) {
		$titles = array_map( 'get_the_title', formica_dates_get_related_event_ids( $post_id ) );
		echo $titles ? esc_html( implode( ', ', $titles ) ) : '—';
	}
}
add_action( 'manage_' . FORMICA_DATES_STAGE_POST_TYPE . '_posts_custom_column', 'formica_dates_stage_admin_column_content', 10, 2 );

/**
 * Sorts the administration list by event date by default.
 */
function formica_dates_admin_order( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() || $query->get( 'orderby' ) ) {
		return;
	}

	$post_type = $query->get( 'post_type' );
	if ( in_array( $post_type, array( FORMICA_DATES_POST_TYPE, FORMICA_DATES_STAGE_POST_TYPE ), true ) ) {
		$query->set( 'meta_key', FORMICA_DATES_STAGE_POST_TYPE === $post_type ? '_formica_stage_datetime' : '_formica_event_datetime' );
		$query->set( 'orderby', 'meta_value' );
		$query->set( 'order', 'ASC' );
	}
}
add_action( 'pre_get_posts', 'formica_dates_admin_order' );

/**
 * Registers the dynamic date-list block.
 */
function formica_dates_register_block() {
	register_block_type(
		__DIR__ . '/blocks/events-list',
		array(
			'render_callback' => 'formica_dates_render_events_block',
		)
	);
}
add_action( 'init', 'formica_dates_register_block' );

/**
 * Uses the plugin's Formica-styled template for public stage pages.
 */
function formica_dates_stage_single_template( $template ) {
	if ( is_singular( FORMICA_DATES_STAGE_POST_TYPE ) ) {
		$stage_template = __DIR__ . '/templates/single-formica_stage.php';
		if ( file_exists( $stage_template ) ) {
			return $stage_template;
		}
	}

	return $template;
}
add_filter( 'single_template', 'formica_dates_stage_single_template' );

/**
 * Loads the dedicated stage page styles only when needed.
 */
function formica_dates_enqueue_stage_page_styles() {
	if ( ! is_singular( FORMICA_DATES_STAGE_POST_TYPE ) ) {
		return;
	}

	$style_path = __DIR__ . '/assets/stage-single.css';
	wp_enqueue_style(
		'formica-stage-single',
		plugins_url( 'assets/stage-single.css', __FILE__ ),
		array(),
		file_exists( $style_path ) ? (string) filemtime( $style_path ) : FORMICA_DATES_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'formica_dates_enqueue_stage_page_styles' );

/**
 * Returns an event datetime in the site timezone.
 */
function formica_dates_get_datetime( $post_id, $post_type = FORMICA_DATES_POST_TYPE ) {
	$meta_key = FORMICA_DATES_STAGE_POST_TYPE === $post_type ? '_formica_stage_datetime' : '_formica_event_datetime';
	$value    = get_post_meta( $post_id, $meta_key, true );
	return $value ? DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $value, wp_timezone() ) : false;
}

/**
 * Returns a short French month label independent from the site locale.
 */
function formica_dates_month_label( DateTimeInterface $date ) {
	$months = array( 1 => 'JANV.', 'FÉVR.', 'MARS', 'AVR.', 'MAI', 'JUIN', 'JUIL.', 'AOÛT', 'SEPT.', 'OCT.', 'NOV.', 'DÉC.' );
	return $months[ (int) $date->format( 'n' ) ];
}

/**
 * Returns a stage description, including content saved before version 1.2.
 */
function formica_dates_get_stage_description( $post_id ) {
	$description = get_post_meta( $post_id, '_formica_stage_description', true );
	if ( ! $description ) {
		$description = get_post_field( 'post_excerpt', $post_id ) ?: get_post_field( 'post_content', $post_id );
	}

	return $description ? wp_strip_all_tags( strip_shortcodes( $description ) ) : '';
}

/**
 * Returns a short, plain-text description for a stage or event.
 */
function formica_dates_get_summary( $post_id ) {
	$summary = FORMICA_DATES_STAGE_POST_TYPE === get_post_type( $post_id )
		? formica_dates_get_stage_description( $post_id )
		: ( get_post_field( 'post_excerpt', $post_id ) ?: get_post_field( 'post_content', $post_id ) );

	return $summary ? wp_trim_words( wp_strip_all_tags( strip_shortcodes( $summary ) ), 28 ) : '';
}

/**
 * Formats a start and optional end date for related-item details.
 */
function formica_dates_format_datetime_range( $start, $end = false ) {
	if ( ! $start ) {
		return '';
	}

	$label = wp_date( 'd/m/Y · H\hi', $start->getTimestamp(), wp_timezone() );
	if ( $end ) {
		$label .= $start->format( 'Y-m-d' ) === $end->format( 'Y-m-d' )
			? '–' . wp_date( 'H\hi', $end->getTimestamp(), wp_timezone() )
			: ' – ' . wp_date( 'd/m/Y · H\hi', $end->getTimestamp(), wp_timezone() );
	}

	return $label;
}

/**
 * Renders the bidirectional relationship details shown on date cards.
 */
function formica_dates_render_related_items( $post_id, $post_type ) {
	$is_stage    = FORMICA_DATES_STAGE_POST_TYPE === $post_type;
	$related_ids = $is_stage ? formica_dates_get_related_event_ids( $post_id, 'publish' ) : formica_dates_get_related_stage_ids( $post_id );

	if ( ! $related_ids ) {
		return '';
	}

	$related_type = $is_stage ? FORMICA_DATES_POST_TYPE : FORMICA_DATES_STAGE_POST_TYPE;
	$heading      = $is_stage ? __( 'Spectacle associé', 'formica-dates' ) : __( 'Stage ce jour-là', 'formica-dates' );
	if ( count( $related_ids ) > 1 ) {
		$heading = $is_stage ? __( 'Spectacles associés', 'formica-dates' ) : __( 'Stages ce jour-là', 'formica-dates' );
	}

	ob_start();
	?>
	<section class="formica-date-card__related" aria-label="<?php echo esc_attr( $heading ); ?>">
		<strong class="formica-date-card__related-heading"><?php echo esc_html( $heading ); ?></strong>
		<div class="formica-related-items">
			<?php foreach ( $related_ids as $related_id ) : ?>
				<?php
				$prefix   = FORMICA_DATES_STAGE_POST_TYPE === $related_type ? '_formica_stage_' : '_formica_event_';
				$start    = formica_dates_get_datetime( $related_id, $related_type );
				$end      = FORMICA_DATES_STAGE_POST_TYPE === $related_type ? formica_dates_get_end_datetime( $related_id ) : false;
				$venue    = get_post_meta( $related_id, $prefix . 'venue', true );
				$city     = get_post_meta( $related_id, $prefix . 'city', true );
				$booking  = get_post_meta( $related_id, $prefix . 'booking', true );
				$level    = FORMICA_DATES_STAGE_POST_TYPE === $related_type ? get_post_meta( $related_id, '_formica_stage_level', true ) : '';
				$location = implode( ', ', array_filter( array( $venue, $city ) ) );
				$summary  = formica_dates_get_summary( $related_id );
				?>
				<div class="formica-related-item">
					<div>
						<strong class="formica-related-item__title">
							<?php if ( FORMICA_DATES_STAGE_POST_TYPE === $related_type ) : ?><a href="<?php echo esc_url( get_permalink( $related_id ) ); ?>"><?php endif; ?>
							<?php echo esc_html( get_the_title( $related_id ) ); ?>
							<?php if ( FORMICA_DATES_STAGE_POST_TYPE === $related_type ) : ?></a><?php endif; ?>
						</strong>
						<?php if ( $start ) : ?>
							<time datetime="<?php echo esc_attr( $start->format( DATE_ATOM ) ); ?>"><?php echo esc_html( formica_dates_format_datetime_range( $start, $end ) ); ?></time>
						<?php endif; ?>
						<?php if ( $location ) : ?><span><?php echo esc_html( $location ); ?></span><?php endif; ?>
						<?php if ( $level ) : ?><span><?php echo esc_html( sprintf( __( 'Niveau : %s', 'formica-dates' ), $level ) ); ?></span><?php endif; ?>
						<?php if ( $summary ) : ?><p><?php echo esc_html( $summary ); ?></p><?php endif; ?>
					</div>
					<?php if ( $booking ) : ?>
						<a href="<?php echo esc_url( $booking ); ?>"><?php echo esc_html( FORMICA_DATES_STAGE_POST_TYPE === $related_type ? __( 'S’inscrire ↗', 'formica-dates' ) : __( 'Réserver ↗', 'formica-dates' ) ); ?></a>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/**
 * Returns a stage end datetime in the site timezone.
 */
function formica_dates_get_end_datetime( $post_id ) {
	$value = get_post_meta( $post_id, '_formica_stage_end_datetime', true );
	return $value ? DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $value, wp_timezone() ) : false;
}

/**
 * Renders the dynamic event list.
 */
function formica_dates_render_events_block( $attributes ) {
	$mode         = isset( $attributes['mode'] ) && 'past' === $attributes['mode'] ? 'past' : 'upcoming';
	$layout       = isset( $attributes['layout'] ) && 'compact' === $attributes['layout'] ? 'compact' : 'detailed';
	$limit        = isset( $attributes['postsToShow'] ) ? min( 50, max( 1, (int) $attributes['postsToShow'] ) ) : 6;
	$show_booking = ! isset( $attributes['showBooking'] ) || (bool) $attributes['showBooking'];
	$content_type = isset( $attributes['contentType'] ) && 'stages' === $attributes['contentType'] ? 'stages' : 'events';
	$is_stage     = 'stages' === $content_type;
	$post_type    = $is_stage ? FORMICA_DATES_STAGE_POST_TYPE : FORMICA_DATES_POST_TYPE;
	$meta_prefix  = $is_stage ? '_formica_stage_' : '_formica_event_';
	$datetime_key = $meta_prefix . 'datetime';
	$now          = current_datetime()->format( 'Y-m-d H:i:s' );

	$query = new WP_Query(
		array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'meta_key'       => $datetime_key,
			'orderby'        => 'meta_value',
			'order'          => 'past' === $mode ? 'DESC' : 'ASC',
			'meta_query'     => array(
				array(
					'key'     => $datetime_key,
					'value'   => $now,
					'compare' => 'past' === $mode ? '<' : '>=',
					'type'    => 'DATETIME',
				),
			),
		)
	);

	$classes = array( 'formica-dates', 'formica-dates--' . $layout, 'formica-dates--' . $mode, 'formica-dates--' . $content_type );
	$wrapper = get_block_wrapper_attributes( array( 'class' => implode( ' ', $classes ) ) );

	ob_start();
	?>
	<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php if ( $query->have_posts() ) : ?>
			<?php while ( $query->have_posts() ) : $query->the_post(); ?>
				<?php
				$post_id  = get_the_ID();
				$date     = formica_dates_get_datetime( $post_id, $post_type );
				$end_date = $is_stage ? formica_dates_get_end_datetime( $post_id ) : false;
				$venue    = get_post_meta( $post_id, $meta_prefix . 'venue', true );
				$city     = get_post_meta( $post_id, $meta_prefix . 'city', true );
				$booking  = get_post_meta( $post_id, $meta_prefix . 'booking', true );
				$level    = $is_stage ? get_post_meta( $post_id, '_formica_stage_level', true ) : '';
				$variant  = $is_stage ? 'paper' : formica_dates_sanitize_variant( get_post_meta( $post_id, '_formica_event_variant', true ) );
				$location = implode( ', ', array_filter( array( $venue, $city ) ) );
				$summary  = $is_stage ? formica_dates_get_summary( $post_id ) : '';
				$related  = formica_dates_render_related_items( $post_id, $post_type );
				?>
				<?php if ( 'compact' === $layout ) : ?>
					<article class="formica-date-compact">
						<time datetime="<?php echo esc_attr( $date ? $date->format( DATE_ATOM ) : '' ); ?>">
							<?php echo $date ? esc_html( wp_date( 'd/m/Y · H\hi', $date->getTimestamp(), wp_timezone() ) ) : ''; ?>
						</time>
						<h3>
							<?php if ( $is_stage ) : ?><a href="<?php echo esc_url( get_permalink() ); ?>"><?php endif; ?>
							<?php echo esc_html( get_the_title() ); ?>
							<?php if ( $is_stage ) : ?></a><?php endif; ?>
						</h3>
						<?php if ( $location ) : ?><p><?php echo esc_html( $location ); ?></p><?php endif; ?>
						<?php echo $related; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</article>
				<?php else : ?>
					<article class="formica-event formica-date-card formica-date-card--<?php echo esc_attr( $variant ); ?>">
						<time class="formica-date-card__date" datetime="<?php echo esc_attr( $date ? $date->format( DATE_ATOM ) : '' ); ?>">
							<strong><?php echo $date ? esc_html( $date->format( 'd' ) ) : ''; ?></strong>
							<span><?php echo $date ? esc_html( formica_dates_month_label( $date ) ) : ''; ?><br><?php echo $date ? esc_html( $date->format( 'H\Hi' ) ) : ''; ?><?php echo $end_date ? esc_html( '–' . $end_date->format( 'H\Hi' ) ) : ''; ?></span>
						</time>
						<div class="formica-date-card__main">
							<?php if ( $is_stage ) : ?><span class="formica-date-card__type"><?php echo esc_html( $level ? sprintf( __( 'Stage · %s', 'formica-dates' ), $level ) : __( 'Stage', 'formica-dates' ) ); ?></span><?php endif; ?>
							<h3 class="formica-date-card__title">
								<?php if ( $is_stage ) : ?><a href="<?php echo esc_url( get_permalink() ); ?>"><?php endif; ?>
								<?php echo esc_html( get_the_title() ); ?>
								<?php if ( $is_stage ) : ?></a><?php endif; ?>
							</h3>
							<?php if ( $summary ) : ?><p class="formica-date-card__summary"><?php echo esc_html( $summary ); ?></p><?php endif; ?>
						</div>
						<?php if ( $location ) : ?><p class="formica-date-card__location"><?php echo wp_kses( str_replace( ', ', '<br>', esc_html( $location ) ), array( 'br' => array() ) ); ?></p><?php endif; ?>
						<?php if ( $show_booking && $booking ) : ?>
							<a class="formica-date-card__booking" href="<?php echo esc_url( $booking ); ?>"><?php echo esc_html( $is_stage ? __( 'S’inscrire ↗', 'formica-dates' ) : __( 'Réserver ↗', 'formica-dates' ) ); ?></a>
						<?php endif; ?>
						<?php echo $related; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</article>
				<?php endif; ?>
			<?php endwhile; ?>
		<?php else : ?>
			<p class="formica-dates__empty">
				<?php
				echo esc_html(
					$is_stage
						? ( 'past' === $mode ? __( 'Aucun stage passé.', 'formica-dates' ) : __( 'De nouveaux stages arrivent bientôt.', 'formica-dates' ) )
						: ( 'past' === $mode ? __( 'Aucune date passée.', 'formica-dates' ) : __( 'De nouvelles dates arrivent bientôt.', 'formica-dates' ) )
				);
				?>
			</p>
		<?php endif; ?>
	</div>
	<?php
	wp_reset_postdata();
	return ob_get_clean();
}

/**
 * Seeds the current programme and a compact archive example.
 */
function formica_dates_activate() {
	formica_dates_register_post_type();

	$existing = get_posts(
		array(
			'post_type'      => FORMICA_DATES_POST_TYPE,
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);

	if ( ! $existing ) {
		$events = array(
			array( 'Le Grand Bazar', '2026-06-21 20:30:00', 'Le Théâtre de Poche', 'Nantes', 'orange' ),
			array( 'Formica fait son cinéma', '2026-07-05 21:00:00', 'Cour du Château', 'Clisson', 'paper' ),
			array( "Match d'impro : la revanche", '2026-09-19 20:30:00', 'Salle Vasse', 'Nantes', 'green' ),
			array( 'Cabaret sans filet', '2026-05-22 20:30:00', 'Le Dix', 'Nantes', 'orange' ),
			array( 'Histoires de comptoir', '2026-04-11 20:30:00', 'La Fabrique', 'Rezé', 'paper' ),
			array( 'Impro sur canapé', '2026-02-14 20:00:00', 'Théâtre du Cyclope', 'Nantes', 'green' ),
		);

		foreach ( $events as $event ) {
			list( $title, $datetime, $venue, $city, $variant ) = $event;
			$post_id = wp_insert_post(
				array(
					'post_type'   => FORMICA_DATES_POST_TYPE,
					'post_status' => 'publish',
					'post_title'  => $title,
				)
			);

			if ( $post_id && ! is_wp_error( $post_id ) ) {
				update_post_meta( $post_id, '_formica_event_datetime', $datetime );
				update_post_meta( $post_id, '_formica_event_venue', $venue );
				update_post_meta( $post_id, '_formica_event_city', $city );
				update_post_meta( $post_id, '_formica_event_booking', home_url( '/#contact' ) );
				update_post_meta( $post_id, '_formica_event_variant', $variant );
			}
		}
	}

	$page    = get_page_by_path( 'dates-passees' );
	$page_id = $page ? $page->ID : wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => __( 'Dates passées', 'formica-dates' ),
				'post_name'    => 'dates-passees',
				'post_content' => '<!-- wp:heading {"level":1,"className":"is-style-formica-section-title"} --><h1 class="wp-block-heading is-style-formica-section-title">Dates passées</h1><!-- /wp:heading --><!-- wp:formica/events-list {"mode":"past","layout":"compact","postsToShow":24,"showBooking":false} /-->',
			)
		);

	if ( $page_id && ! is_wp_error( $page_id ) ) {
		update_post_meta( $page_id, '_wp_page_template', 'page-no-title' );
	}

	update_option( 'formica_dates_version', FORMICA_DATES_VERSION );
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'formica_dates_activate' );

require_once __DIR__ . '/includes/helloasso.php';
