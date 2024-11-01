<?php
/**
 * Admin View: Settings
 *
 * @package Stalkfish
 */

namespace Stalkfish\Settings\views;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tab_exists        = isset( $tabs[ $current_tab ] ) || has_action( 'sf_sections_' . $current_tab ) || has_action( 'sf_settings_' . $current_tab ) || has_action( 'sf_settings_tabs_' . $current_tab );
$current_tab_label = isset( $tabs[ $current_tab ] ) ? $tabs[ $current_tab ] : '';

global $current_user;

if ( ! $tab_exists ) {
	wp_safe_redirect( admin_url( 'options-general.php?page=sf-settings' ) );
	exit;
}
?>
<div class="wrap sf <?php echo esc_attr( $current_tab ); ?>">
	<h1 class="menu-title"><?php esc_html_e( 'Stalkfish Settings', 'stalkfish' ); ?></h1>
	<div class="sf-wrapper">
		<form method="<?php echo esc_attr( apply_filters( 'sf_settings_form_method_tab_' . $current_tab, 'post' ) ); ?>" id="mainform" action="" enctype="multipart/form-data">
			<nav class="nav-tab-wrapper sf-nav-tab-wrapper">
				<?php

				foreach ( $tabs as $slug => $label ) {
					echo '<a href="' . esc_html( admin_url( 'admin.php?page=sf-settings&tab=' . esc_attr( $slug ) ) ) . '" class="nav-tab ' . ( $current_tab === $slug ? 'nav-tab-active' : '' ) . '">' . esc_html( $label ) . '</a>';
				}

				do_action( 'sf_settings_tabs' );

				?>
			</nav>
			<div class="tab-content">
				<h1 class="screen-reader-text"><?php echo esc_html( $current_tab_label ); ?></h1>
				<?php
				do_action( 'sf_sections_' . $current_tab );

				self::show_messages();

				do_action( 'sf_settings_' . $current_tab );
				?>
				<p class="submit">
					<?php if ( empty( $GLOBALS['hide_save_button'] ) ) : ?>
						<button name="save" class="button-primary sf-save-button" type="submit" value="<?php esc_attr_e( 'Save changes', 'stalkfish' ); ?>"><?php esc_html_e( 'Save changes', 'stalkfish' ); ?></button>
					<?php endif; ?>
					<?php wp_nonce_field( 'sf-settings' ); ?>
				</p>
			</div>
		</form>
	</div>
</div>
