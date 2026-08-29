<?php
/**
 * Public single-stage template.
 *
 * @package Formica_Dates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$stage_id    = get_queried_object_id();
$start       = formica_dates_get_datetime( $stage_id, FORMICA_DATES_STAGE_POST_TYPE );
$end         = formica_dates_get_end_datetime( $stage_id );
$description = formica_dates_get_stage_description( $stage_id );
$level       = get_post_meta( $stage_id, '_formica_stage_level', true );
$venue       = get_post_meta( $stage_id, '_formica_stage_venue', true );
$city        = get_post_meta( $stage_id, '_formica_stage_city', true );
$bring       = get_post_meta( $stage_id, '_formica_stage_bring', true );
$booking     = get_post_meta( $stage_id, '_formica_stage_booking', true );
$prices      = function_exists( 'formica_dates_helloasso_price_labels' ) ? formica_dates_helloasso_price_labels( $stage_id ) : array();
$event_ids   = formica_dates_get_related_event_ids( $stage_id, 'publish' );
$location    = implode( ', ', array_filter( array( $venue, $city ) ) );
$date_label  = $start ? wp_date( 'l j F Y', $start->getTimestamp(), wp_timezone() ) : '';
$time_label  = $start ? wp_date( 'H\hi', $start->getTimestamp(), wp_timezone() ) : '';

if ( $end ) {
	$time_label .= '–' . wp_date( 'H\hi', $end->getTimestamp(), wp_timezone() );
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'formica-stage-page' ); ?>>
<?php wp_body_open(); ?>
<div class="wp-site-blocks">
	<?php block_template_part( 'header' ); ?>

	<main id="main" class="formica-stage-single">
		<section class="formica-stage-hero">
			<div class="formica-stage-shell formica-stage-hero__inner">
				<div class="formica-stage-hero__content">
					<a class="formica-stage-back" href="<?php echo esc_url( home_url( '/#ateliers' ) ); ?>">← <?php esc_html_e( 'Tous les stages', 'formica-dates' ); ?></a>
					<p class="formica-stage-kicker"><?php esc_html_e( 'Stage d’improvisation', 'formica-dates' ); ?></p>
					<h1><?php echo esc_html( get_the_title( $stage_id ) ); ?></h1>
					<?php if ( $level ) : ?><p class="formica-stage-level"><?php echo esc_html( $level ); ?></p><?php endif; ?>
				</div>

				<?php if ( $start ) : ?>
					<time class="formica-stage-date-badge" datetime="<?php echo esc_attr( $start->format( DATE_ATOM ) ); ?>">
						<strong><?php echo esc_html( $start->format( 'd' ) ); ?></strong>
						<span><?php echo esc_html( formica_dates_month_label( $start ) ); ?><br><?php echo esc_html( $time_label ); ?></span>
					</time>
				<?php endif; ?>
			</div>
		</section>

		<div class="formica-stage-vichy" aria-hidden="true"></div>

		<section class="formica-stage-details">
			<div class="formica-stage-shell formica-stage-details__grid">
				<div class="formica-stage-description">
					<p class="formica-stage-section-label"><?php esc_html_e( 'Le programme', 'formica-dates' ); ?></p>
					<h2><?php esc_html_e( 'À propos du stage', 'formica-dates' ); ?></h2>
					<?php if ( $description ) : ?>
						<div class="formica-stage-rich-text"><?php echo wp_kses_post( wpautop( esc_html( $description ) ) ); ?></div>
					<?php endif; ?>

					<?php if ( $bring ) : ?>
						<div class="formica-stage-bring">
							<h3><?php esc_html_e( 'Dans ton sac', 'formica-dates' ); ?></h3>
							<?php echo wp_kses_post( wpautop( esc_html( $bring ) ) ); ?>
						</div>
					<?php endif; ?>
				</div>

				<aside class="formica-stage-practical" aria-label="<?php esc_attr_e( 'Informations pratiques', 'formica-dates' ); ?>">
					<h2><?php esc_html_e( 'En pratique', 'formica-dates' ); ?></h2>
					<?php if ( $date_label ) : ?>
						<div class="formica-stage-fact">
							<span><?php esc_html_e( 'Date', 'formica-dates' ); ?></span>
							<strong><?php echo esc_html( ucfirst( $date_label ) ); ?><br><?php echo esc_html( $time_label ); ?></strong>
						</div>
					<?php endif; ?>
					<?php if ( $level ) : ?>
						<div class="formica-stage-fact">
							<span><?php esc_html_e( 'Niveau', 'formica-dates' ); ?></span>
							<strong><?php echo esc_html( $level ); ?></strong>
						</div>
					<?php endif; ?>
					<?php if ( $location ) : ?>
						<div class="formica-stage-fact">
							<span><?php esc_html_e( 'Lieu', 'formica-dates' ); ?></span>
							<strong><?php echo esc_html( $location ); ?></strong>
						</div>
					<?php endif; ?>
					<?php if ( $prices ) : ?>
						<div class="formica-stage-fact">
							<span><?php echo esc_html( count( $prices ) > 1 ? __( 'Tarifs', 'formica-dates' ) : __( 'Tarif', 'formica-dates' ) ); ?></span>
							<strong><?php echo wp_kses( implode( '<br>', array_map( 'esc_html', $prices ) ), array( 'br' => array() ) ); ?></strong>
						</div>
					<?php endif; ?>
					<?php if ( $booking ) : ?>
						<a class="formica-stage-register" href="<?php echo esc_url( $booking ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Je m’inscris sur HelloAsso ↗', 'formica-dates' ); ?></a>
					<?php endif; ?>
				</aside>
			</div>
		</section>

		<?php if ( $event_ids ) : ?>
			<section class="formica-stage-shows">
				<div class="formica-stage-shell">
					<p class="formica-stage-section-label"><?php esc_html_e( 'Et aussi', 'formica-dates' ); ?></p>
					<h2><?php echo esc_html( count( $event_ids ) > 1 ? __( 'Spectacles associés', 'formica-dates' ) : __( 'Spectacle associé', 'formica-dates' ) ); ?></h2>
					<div class="formica-stage-show-list">
						<?php foreach ( $event_ids as $event_id ) : ?>
							<?php
							$event_date     = formica_dates_get_datetime( $event_id );
							$event_venue    = get_post_meta( $event_id, '_formica_event_venue', true );
							$event_city     = get_post_meta( $event_id, '_formica_event_city', true );
							$event_booking  = get_post_meta( $event_id, '_formica_event_booking', true );
							$event_location = implode( ', ', array_filter( array( $event_venue, $event_city ) ) );
							?>
							<article class="formica-stage-show">
								<div>
									<h3><?php echo esc_html( get_the_title( $event_id ) ); ?></h3>
									<?php if ( $event_date ) : ?><time datetime="<?php echo esc_attr( $event_date->format( DATE_ATOM ) ); ?>"><?php echo esc_html( formica_dates_format_datetime_range( $event_date ) ); ?></time><?php endif; ?>
									<?php if ( $event_location ) : ?><p><?php echo esc_html( $event_location ); ?></p><?php endif; ?>
								</div>
								<?php if ( $event_booking ) : ?><a href="<?php echo esc_url( $event_booking ); ?>"><?php esc_html_e( 'Réserver ↗', 'formica-dates' ); ?></a><?php endif; ?>
							</article>
						<?php endforeach; ?>
					</div>
				</div>
			</section>
		<?php endif; ?>
	</main>

	<?php block_template_part( 'footer' ); ?>
</div>
<?php wp_footer(); ?>
</body>
</html>
