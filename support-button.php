<?php
/**
 * Plugin Name: دکمه پشتیبانی شناور
 * Plugin URI:  https://github.com/sahandse/support-widget
 * Description: دکمه شناور پشتیبانی با پشتیبانی از تلگرام، واتس‌اپ، بله، روبیکا و ایتا
 * Version:     1.0.0
 * Author:      Sahand
 * License:     GPL v2 or later
 * Text Domain: support-button
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SUPPORT_BTN_VERSION', '1.0.0' );
define( 'SUPPORT_BTN_URL', plugin_dir_url( __FILE__ ) );

/* ---------------------------------------------------------------
   Admin menu & settings
--------------------------------------------------------------- */

add_action( 'admin_menu', 'support_btn_admin_menu' );
function support_btn_admin_menu() {
	add_options_page(
		'دکمه پشتیبانی',
		'دکمه پشتیبانی',
		'manage_options',
		'support-button',
		'support_btn_settings_page'
	);
}

add_action( 'admin_init', 'support_btn_register_settings' );
function support_btn_register_settings() {
	$fields = [ 'telegram', 'whatsapp', 'bale', 'rubika', 'eitaa' ];
	foreach ( $fields as $field ) {
		register_setting( 'support_btn_options', 'support_btn_' . $field, [
			'sanitize_callback' => 'esc_url_raw',
		] );
	}
}

function support_btn_settings_page() {
	$channels = [
		'telegram' => [ 'label' => 'تلگرام',   'placeholder' => 'https://t.me/username' ],
		'whatsapp' => [ 'label' => 'واتس‌اپ',  'placeholder' => 'https://wa.me/989123456789' ],
		'bale'     => [ 'label' => 'بله',       'placeholder' => 'https://ble.ir/username' ],
		'rubika'   => [ 'label' => 'روبیکا',    'placeholder' => 'https://rubika.ir/username' ],
		'eitaa'    => [ 'label' => 'ایتا',      'placeholder' => 'https://eitaa.com/username' ],
	];
	?>
	<div class="wrap" dir="rtl">
		<h1>تنظیمات دکمه پشتیبانی</h1>
		<p>هر شبکه‌ای که لینکش را وارد نکنید، در دکمه نمایش داده نمی‌شود.</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'support_btn_options' ); ?>
			<table class="form-table">
				<?php foreach ( $channels as $key => $ch ) : ?>
				<tr>
					<th><label for="support_btn_<?php echo $key; ?>"><?php echo $ch['label']; ?></label></th>
					<td>
						<input
							type="url"
							id="support_btn_<?php echo $key; ?>"
							name="support_btn_<?php echo $key; ?>"
							value="<?php echo esc_attr( get_option( 'support_btn_' . $key ) ); ?>"
							class="regular-text"
							placeholder="<?php echo $ch['placeholder']; ?>"
						/>
					</td>
				</tr>
				<?php endforeach; ?>
			</table>
			<?php submit_button( 'ذخیره تنظیمات' ); ?>
		</form>
	</div>
	<?php
}

/* ---------------------------------------------------------------
   Front-end assets
--------------------------------------------------------------- */

add_action( 'wp_enqueue_scripts', 'support_btn_enqueue' );
function support_btn_enqueue() {
	if ( ! support_btn_has_any_link() ) return;
	wp_enqueue_style( 'support-button', SUPPORT_BTN_URL . 'assets/css/support-button.css', [], SUPPORT_BTN_VERSION );
	wp_enqueue_script( 'support-button', SUPPORT_BTN_URL . 'assets/js/support-button.js', [], SUPPORT_BTN_VERSION, true );
}

function support_btn_has_any_link() {
	foreach ( [ 'telegram', 'whatsapp', 'bale', 'rubika', 'eitaa' ] as $k ) {
		if ( get_option( 'support_btn_' . $k ) ) return true;
	}
	return false;
}

/* ---------------------------------------------------------------
   Footer HTML
--------------------------------------------------------------- */

add_action( 'wp_footer', 'support_btn_output' );
function support_btn_output() {
	if ( ! support_btn_has_any_link() ) return;

	$channels = [
		'telegram' => [ 'label' => 'تلگرام',  'color' => '#0088cc', 'svg' => support_btn_svg_telegram() ],
		'whatsapp' => [ 'label' => 'واتس‌اپ', 'color' => '#25D366', 'svg' => support_btn_svg_whatsapp() ],
		'bale'     => [ 'label' => 'بله',      'color' => '#1565C0', 'svg' => support_btn_svg_bale() ],
		'rubika'   => [ 'label' => 'روبیکا',   'color' => '#F47B20', 'svg' => support_btn_svg_rubika() ],
		'eitaa'    => [ 'label' => 'ایتا',     'color' => '#00897B', 'svg' => support_btn_svg_eitaa() ],
	];
	?>
	<div id="spb-widget" role="complementary" aria-label="پشتیبانی">
		<div id="spb-menu" aria-hidden="true">
			<?php foreach ( $channels as $key => $ch ) :
				$url = get_option( 'support_btn_' . $key );
				if ( ! $url ) continue;
			?>
			<a
				href="<?php echo esc_url( $url ); ?>"
				target="_blank"
				rel="noopener noreferrer"
				class="spb-item spb-<?php echo $key; ?>"
				style="background:<?php echo $ch['color']; ?>"
				title="<?php echo esc_attr( $ch['label'] ); ?>"
			>
				<span class="spb-icon" aria-hidden="true"><?php echo $ch['svg']; ?></span>
				<span class="spb-label"><?php echo $ch['label']; ?></span>
			</a>
			<?php endforeach; ?>
		</div>
		<button id="spb-toggle" aria-label="باز کردن منوی پشتیبانی" aria-expanded="false">
			<span class="spb-open-icon" aria-hidden="true"><?php echo support_btn_svg_chat(); ?></span>
			<span class="spb-close-icon" aria-hidden="true">&#x2715;</span>
		</button>
	</div>
	<?php
}

/* ---------------------------------------------------------------
   SVG icons
--------------------------------------------------------------- */

function support_btn_svg_chat() {
	return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="28" height="28"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/></svg>';
}

function support_btn_svg_telegram() {
	return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.562 8.248-1.97 9.289c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12L7.17 14.06l-2.96-.924c-.643-.204-.657-.643.136-.953l11.57-4.461c.537-.194 1.006.131.646.526z"/></svg>';
}

function support_btn_svg_whatsapp() {
	return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M.057 24 1.744 17.837C.703 16.033.156 13.988.157 11.891.16 5.335 5.499 0 12.054 0c3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>';
}

function support_btn_svg_bale() {
	return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 15-4-4 1.41-1.41L11 14.17l6.59-6.59L19 9l-8 8z"/></svg>';
}

function support_btn_svg_rubika() {
	return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M12 2C6.477 2 2 6.477 2 12c0 4.236 2.636 7.855 6.356 9.312-.088-.791-.167-2.005.035-2.868.181-.78 1.172-4.97 1.172-4.97s-.299-.598-.299-1.482c0-1.388.806-2.428 1.808-2.428.853 0 1.267.641 1.267 1.408 0 .858-.546 2.141-.828 3.329-.236.995.499 1.806 1.48 1.806 1.773 0 3.141-1.872 3.141-4.573 0-2.39-1.717-4.061-4.168-4.061-2.837 0-4.502 2.128-4.502 4.326 0 .856.33 1.773.741 2.274a.3.3 0 0 1 .069.285c-.076.313-.245.995-.278 1.134-.044.183-.146.222-.336.134-1.249-.581-2.03-2.407-2.03-3.874 0-3.154 2.292-6.052 6.608-6.052 3.469 0 6.165 2.473 6.165 5.776 0 3.447-2.173 6.22-5.19 6.22-1.013 0-1.967-.527-2.292-1.148l-.623 2.378c-.226.869-.835 1.958-1.244 2.621.937.29 1.931.446 2.962.446 5.523 0 10-4.477 10-10S17.523 2 12 2z"/></svg>';
}

function support_btn_svg_eitaa() {
	return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>';
}
