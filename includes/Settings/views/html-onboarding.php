<?php
/**
 * Admin View: Plugin onboarding
 *
 * @since 1.0.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="sf-onboarding">
	<div class="sf-header">
		<div class="logo">
			<a href="<?php echo esc_url( 'https://stalkfish.com/?utm_campaign=onboarding&utm_source=plugin' ); ?>" target="_blank">
				<img src="<?php echo plugins_url( '/', STALKFISH_PLUGIN_FILE ) . 'assets/images/app-logo.png'; ?>">
			</a>
		</div>
		<div class="nav">
			<a href="<?php echo esc_url( admin_url() . 'options-general.php?page=sf-settings' ); ?>"><?php esc_html_e( 'Already have an API Key?', 'stalkfish' ); ?></a>
		</div>
	</div>

	<div class="sf-body">
		<div id="step-1" class="sf-card">
			<div class="head">
				<div class="title">
					<span class="step">1</span>
					<p><?php esc_html_e( 'Register for an account', 'stalkfish' ); ?></p>
				</div>
				<p class="desc">
					<?php esc_html_e( 'Let\'s get you setup and active on Stalkfish app. Sign up for a free account via the button below.', 'stalkfish' ); ?>
					</p>
			</div>
			<div class="content">
				<img src="<?php echo esc_url( plugins_url( '/', STALKFISH_PLUGIN_FILE ) . 'assets/images/step-1.gif' ); ?>">
			</div>

			<div class="actions">
				<div class="steps">
					<span class="step-1 active"></span>
					<a href="<?php echo esc_url( admin_url() . 'admin.php?page=stalkfish-setup&step=2' ); ?>"><span class="step-2"></span></a>
					<a href="<?php echo esc_url( admin_url() . 'admin.php?page=stalkfish-setup&step=3' ); ?>"><span class="step-3"></span></a>
				</div>
				<div>
					<a href="<?php echo esc_url( 'https://app.stalkfish.com/register/?utm_campaign=onboarding&utm_source=plugin' ); ?>" target="_blank" class="action recommended"><?php esc_html_e( 'Create a free account', 'stalkfish' ); ?></a>
					<a href="<?php echo esc_url( admin_url() . 'admin.php?page=stalkfish-setup&step=2' ); ?>" class="action next"><?php esc_html_e( 'Next &nbsp;&#8250;', 'stalkfish' ); ?></a>
				</div>
			</div>
		</div>

		<div id="step-2" class="sf-card">
			<div class="head">
				<div class="title">
					<span class="step">2</span>
					<p><?php esc_html_e( 'Connect your website', 'stalkfish' ); ?></p>
				</div>
				<p class="desc"><?php esc_html_e( 'Once you have your account created, add your site to the app.', 'stalkfish' ); ?></p>
			</div>
			<div class="content">
				<img src="<?php echo esc_url( plugins_url( '/', STALKFISH_PLUGIN_FILE ) . 'assets/images/step-2.gif' ); ?>">
			</div>
			<div class="actions">
				<div class="steps">
					<a href="<?php echo esc_url( admin_url() . 'admin.php?page=stalkfish-setup' ); ?>"><span class="step-1"></span></a>
					<span class="step-2 active"></span>
					<a href="<?php echo esc_url( admin_url() . 'admin.php?page=stalkfish-setup&step=3' ); ?>"><span class="step-3"></span></a>
				</div>
				<div>
					<a href="<?php echo esc_url( admin_url() . 'admin.php?page=stalkfish-setup&step=3' ); ?>" class="action next"><?php esc_html_e( 'Next &nbsp;&#8250;', 'stalkfish' ); ?></a>
				</div>
			</div>
		</div>

		<div id="step-3" class="sf-card">
			<div class="head">
				<div class="title">
					<span class="step">3</span>
					<p><?php esc_html_e( 'Happy site monitoring 🥳', 'stalkfish' ); ?></p>
				</div>
				<p class="desc">
				<?php
					echo sprintf(
						'%1$s <a href="%4$s" target="_blank">%2$s</a>, %3$s',
						esc_html__( 'That\'s it now sit back and', 'stalkfish' ),
						esc_html__( 'open the app', 'stalkfish' ),
						esc_html__( 'as each events happen they will now get logged at site logs.', 'stalkfish' ),
						esc_url( 'https://app.stalkfish.com/?utm_campaign=onboarding&utm_source=plugin' ),
					);
					?>
					</p>
			</div>
			<div class="content">
				<img src="<?php echo esc_url( plugins_url( '/', STALKFISH_PLUGIN_FILE ) . 'assets/images/step-3.gif' ); ?>">
			</div>
			<div class="actions">
				<div class="steps">
					<a href="<?php echo esc_url( admin_url() . 'admin.php?page=stalkfish-setup' ); ?>"><span class="step-1"></span></a>
					<a href="<?php echo esc_url( admin_url() . 'admin.php?page=stalkfish-setup&step=2' ); ?>"><span class="step-2"></span></a>
					<span class="step-3 active"></span>
				</div>
				<div>
					<a href="<?php echo esc_url( admin_url() ); ?>" class="action next"><?php esc_html_e( 'I\'ve done it!', 'stalkfish' ); ?></a>
				</div>
			</div>
		</div>
	</div>
</div>
