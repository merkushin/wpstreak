<?php
/**
 * Settings → Streakfire: connect to Streakfire Pro and manage it.
 *
 * @var string|null $notice_type      'success', 'warning' or 'error'; null when there's no message.
 * @var string      $notice_message   Message about the last action.
 * @var bool        $is_connected
 * @var bool        $is_reachable     False when the Streakfire API couldn't be reached.
 * @var string      $email            The connected account's email.
 * @var bool        $is_pro           Whether the account has Pro reminders.
 * @var bool        $reminder_enabled
 * @var int         $reminder_hour    0-23, in the site's timezone.
 * @var string      $timezone         The site's timezone, e.g. "America/Mexico_City" or "+03:00".
 * @var string|null $synced_ago       How long ago the days were last synced, e.g. "5 mins"; null if never.
 * @var int|null    $streak           The streak Streakfire has, across the account's sites; null if unknown.
 * @var string      $site_url         This site's address.
 * @var string      $privacy_url      Streakfire's privacy policy.
 * @var string      $action_url       admin-post.php.
 * @var \Merkushin\Wpstreak\Pro\SettingsPage $this
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="wrap">
	<h1><?php esc_html_e( 'Streakfire', 'streakfire' ); ?></h1>

	<?php if ( null !== $notice_type ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice_type ); ?> is-dismissible"><p><?php echo esc_html( $notice_message ); ?></p></div>
	<?php endif; ?>

	<?php if ( ! $is_connected ) : ?>
		<h2><?php esc_html_e( 'Streakfire Pro', 'streakfire' ); ?></h2>
		<p><?php esc_html_e( 'Never lose a streak by accident. With Streakfire Pro:', 'streakfire' ); ?></p>
		<ul class="ul-disc">
			<li><?php esc_html_e( 'You get an email at the hour you choose when you haven\'t published yet that day.', 'streakfire' ); ?></li>
			<li><?php esc_html_e( 'Your streak history is kept in your Streakfire account, so it survives reinstalls and site moves.', 'streakfire' ); ?></li>
		</ul>
		<p>
			<?php
			printf(
				/* translators: %s: Link to Streakfire's privacy policy. */
				esc_html__( 'Connecting sends Streakfire this site\'s address, its timezone and the dates you published on, never your posts. Nothing is sent until you connect. %s', 'streakfire' ),
				'<a href="' . esc_url( $privacy_url ) . '">' . esc_html__( 'Privacy policy', 'streakfire' ) . '</a>'
			);
			?>
		</p>
		<form method="post" action="<?php echo esc_url( $action_url ); ?>">
			<input type="hidden" name="action" value="streakfire_connect" />
			<?php wp_nonce_field( 'streakfire_connect' ); ?>
			<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Connect to Streakfire', 'streakfire' ); ?></button></p>
		</form>
	<?php else : ?>
		<?php if ( ! $is_reachable ) : ?>
			<div class="notice notice-warning inline"><p><?php esc_html_e( 'Streakfire couldn\'t be reached, so some details are missing. Please reload the page in a minute.', 'streakfire' ); ?></p></div>
		<?php endif; ?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Account', 'streakfire' ); ?></th>
				<td><?php echo esc_html( $email ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Plan', 'streakfire' ); ?></th>
				<td><?php echo esc_html( $is_pro ? __( 'Pro', 'streakfire' ) : __( 'Free', 'streakfire' ) ); ?></td>
			</tr>
			<?php if ( null !== $streak ) : ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Streak in your account', 'streakfire' ); ?></th>
					<td>
						<?php
						/* translators: %s: Number of days. */
						echo esc_html( sprintf( _n( '%s day', '%s days', $streak, 'streakfire' ), (string) $streak ) );
						?>
					</td>
				</tr>
			<?php endif; ?>
			<tr>
				<th scope="row"><?php esc_html_e( 'Last synced', 'streakfire' ); ?></th>
				<td>
					<?php
					if ( null === $synced_ago ) {
						esc_html_e( 'Not yet', 'streakfire' );
					} else {
						/* translators: %s: Time since the last sync, e.g. "5 mins". */
						echo esc_html( sprintf( __( '%s ago', 'streakfire' ), $synced_ago ) );
					}
					?>
				</td>
			</tr>
		</table>

		<?php if ( $is_pro ) : ?>
			<h2><?php esc_html_e( 'Reminders', 'streakfire' ); ?></h2>
			<form method="post" action="<?php echo esc_url( $action_url ); ?>">
				<input type="hidden" name="action" value="streakfire_reminders" />
				<?php wp_nonce_field( 'streakfire_reminders' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Email reminder', 'streakfire' ); ?></th>
						<td>
							<label for="streakfire-reminder-enabled">
								<input type="checkbox" id="streakfire-reminder-enabled" name="reminder_enabled" value="1"<?php checked( $reminder_enabled ); ?> />
								<?php
								/* translators: %s: Account email address. */
								echo esc_html( sprintf( __( 'Email %s when I haven\'t published yet', 'streakfire' ), $email ) );
								?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="streakfire-reminder-hour"><?php esc_html_e( 'Send it at', 'streakfire' ); ?></label></th>
						<td>
							<select id="streakfire-reminder-hour" name="reminder_hour">
								<?php $this->render_hour_options( $reminder_hour ); ?>
							</select>
							<p class="description">
								<?php
								/* translators: %s: The site's timezone, e.g. "America/Mexico_City". */
								echo esc_html( sprintf( __( 'In your site\'s timezone (%s).', 'streakfire' ), $timezone ) );
								?>
							</p>
						</td>
					</tr>
				</table>
				<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Save reminders', 'streakfire' ); ?></button></p>
			</form>

			<form method="post" action="<?php echo esc_url( $action_url ); ?>">
				<input type="hidden" name="action" value="streakfire_manage" />
				<?php wp_nonce_field( 'streakfire_manage' ); ?>
				<p><button type="submit" class="button"><?php esc_html_e( 'Manage subscription', 'streakfire' ); ?></button></p>
			</form>
		<?php else : ?>
			<h2><?php esc_html_e( 'Upgrade to Pro', 'streakfire' ); ?></h2>
			<p><?php esc_html_e( 'Get an email at the hour you choose when you haven\'t published yet that day.', 'streakfire' ); ?></p>
			<form method="post" action="<?php echo esc_url( $action_url ); ?>">
				<input type="hidden" name="action" value="streakfire_upgrade" />
				<?php wp_nonce_field( 'streakfire_upgrade' ); ?>
				<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Upgrade to Pro', 'streakfire' ); ?></button></p>
			</form>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Disconnect', 'streakfire' ); ?></h2>
		<p>
			<?php
			/* translators: %s: This site's address. */
			echo esc_html( sprintf( __( 'Stops sending %s\'s publishing days to Streakfire. Your streak history stays in your account, and connecting again picks it up.', 'streakfire' ), $site_url ) );
			?>
		</p>
		<form method="post" action="<?php echo esc_url( $action_url ); ?>">
			<input type="hidden" name="action" value="streakfire_disconnect" />
			<?php wp_nonce_field( 'streakfire_disconnect' ); ?>
			<p><button type="submit" class="button"><?php esc_html_e( 'Disconnect', 'streakfire' ); ?></button></p>
		</form>
	<?php endif; ?>
</div>
