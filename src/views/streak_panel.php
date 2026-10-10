<?php
/**
 * Writing streak panel shown above the Posts list.
 *
 * @var string      $accent_class
 * @var string      $status_key           One of 'start', 'on_fire', 'alive'.
 * @var int         $streak
 * @var string      $streak_label         $streak formatted for the locale.
 * @var string|null $last_post_label      Day of the last published post, formatted for the site; null if none.
 * @var int         $next_milestone
 * @var string      $next_milestone_label $next_milestone formatted for the locale.
 * @var bool        $is_active_today
 * @var int         $progress             Milestone progress, 0-100.
 * @var string      $progress_label       $progress formatted for the locale.
 * @var bool        $is_panel_visible     False when the user hid the panel in Screen Options.
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="inkmeter-panel" class="inkmeter-panel notice <?php echo esc_attr( $accent_class ); ?>"<?php echo $is_panel_visible ? '' : ' hidden'; ?>>
	<div class="inkmeter-panel__lead">
		<div class="inkmeter-panel__eyebrow"><?php esc_html_e( 'Writing momentum', 'inkmeter' ); ?></div>
		<h2 class="inkmeter-panel__title"><?php esc_html_e( 'Protect the streak. Build the habit.', 'inkmeter' ); ?></h2>
		<p class="inkmeter-panel__description">
			<?php
			if ( 'start' === $status_key ) {
				esc_html_e( 'Start your next streak', 'inkmeter' );
			} elseif ( 'on_fire' === $status_key ) {
				esc_html_e( 'You are on fire today', 'inkmeter' );
			} else {
				esc_html_e( 'You are still alive, publish today to keep it going', 'inkmeter' );
			}
			?>
		</p>
		<div class="inkmeter-panel__stats">
			<div class="inkmeter-panel__stat">
				<span class="inkmeter-panel__stat-label"><?php esc_html_e( 'Current run', 'inkmeter' ); ?></span>
				<span class="inkmeter-panel__stat-value">
					<?php
					/* translators: %s: Number of days. */
					echo esc_html( sprintf( _n( '%s day', '%s days', $streak, 'inkmeter' ), $streak_label ) );
					?>
				</span>
			</div>
			<div class="inkmeter-panel__stat">
				<span class="inkmeter-panel__stat-label"><?php esc_html_e( 'Last published', 'inkmeter' ); ?></span>
				<span class="inkmeter-panel__stat-value"><?php echo esc_html( $last_post_label ?? __( 'No published posts yet', 'inkmeter' ) ); ?></span>
			</div>
			<div class="inkmeter-panel__stat">
				<span class="inkmeter-panel__stat-label"><?php esc_html_e( 'Next milestone', 'inkmeter' ); ?></span>
				<span class="inkmeter-panel__stat-value">
					<?php
					/* translators: %s: Number of days. */
					echo esc_html( sprintf( _n( '%s day', '%s days', $next_milestone, 'inkmeter' ), $next_milestone_label ) );
					?>
				</span>
			</div>
		</div>
	</div>
	<div class="inkmeter-panel__meta">
		<div>
			<div class="inkmeter-panel__score">
				<span class="inkmeter-panel__score-value"><?php echo esc_html( $streak_label ); ?></span>
				<span class="inkmeter-panel__score-unit">
					<?php
					/* translators: Unit shown after the large streak number, e.g. "12 days". */
					echo esc_html( _nx( 'day', 'days', $streak, 'streak counter unit', 'inkmeter' ) );
					?>
				</span>
			</div>
			<div class="inkmeter-panel__status">
				<span class="inkmeter-panel__status-dot"></span>
				<span><?php echo esc_html( $is_active_today ? __( 'Published today', 'inkmeter' ) : __( 'Needs a post today', 'inkmeter' ) ); ?></span>
			</div>
		</div>
		<div>
			<div class="inkmeter-panel__progress-copy">
				<span><?php esc_html_e( 'Milestone progress', 'inkmeter' ); ?></span>
				<span>
					<?php
					/* translators: %s: Progress towards the next milestone, a number from 0 to 100. */
					echo esc_html( sprintf( __( '%s%%', 'inkmeter' ), $progress_label ) );
					?>
				</span>
			</div>
			<div class="inkmeter-panel__progress-track">
				<div class="inkmeter-panel__progress-bar" style="width:<?php echo esc_attr( (string) $progress ); ?>%;"></div>
			</div>
		</div>
	</div>
</div>
