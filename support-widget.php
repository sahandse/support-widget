<?php
/**
 * Plugin Name:       Support Widget
 * Plugin URI:        https://github.com/sahandse/support-widget
 * Description:        یک دکمه شناور پشتیبانی برای سایت وردپرس با پشتیبانی از تلگرام، واتساپ، بله، روبیکا و ایتا.
 * Version:           1.0.0
 * Requires at least: 5.6
 * Requires PHP:      7.2
 * Author:            Sahand
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       support-widget
 * Domain Path:       /languages
 */

// جلوگیری از دسترسی مستقیم به فایل
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SUPPORT_WIDGET_VERSION', '1.0.0' );
define( 'SUPPORT_WIDGET_FILE', __FILE__ );
define( 'SUPPORT_WIDGET_PATH', plugin_dir_path( __FILE__ ) );
define( 'SUPPORT_WIDGET_URL', plugin_dir_url( __FILE__ ) );
define( 'SUPPORT_WIDGET_OPTION', 'support_widget_settings' );

require_once SUPPORT_WIDGET_PATH . 'includes/class-support-widget.php';
require_once SUPPORT_WIDGET_PATH . 'includes/class-support-widget-settings.php';
require_once SUPPORT_WIDGET_PATH . 'includes/class-support-widget-frontend.php';

/**
 * مقادیر پیش‌فرض تنظیمات افزونه.
 *
 * @return array
 */
function support_widget_default_settings() {
	return array(
		'enabled'       => 1,
		'title'         => 'پشتیبانی',
		'position'      => 'bottom-right',
		'main_color'    => '#2563eb',
		'channels'      => array(
			'telegram' => array( 'enabled' => 0, 'value' => '', 'label' => 'تلگرام' ),
			'whatsapp' => array( 'enabled' => 0, 'value' => '', 'label' => 'واتساپ' ),
			'bale'     => array( 'enabled' => 0, 'value' => '', 'label' => 'بله' ),
			'rubika'   => array( 'enabled' => 0, 'value' => '', 'label' => 'روبیکا' ),
			'eitaa'    => array( 'enabled' => 0, 'value' => '', 'label' => 'ایتا' ),
		),
	);
}

/**
 * دریافت تنظیمات افزونه به همراه مقادیر پیش‌فرض.
 *
 * @return array
 */
function support_widget_get_settings() {
	$saved    = get_option( SUPPORT_WIDGET_OPTION, array() );
	$defaults = support_widget_default_settings();

	$settings = wp_parse_args( $saved, $defaults );

	// اطمینان از وجود همه کانال‌ها حتی اگر بعداً اضافه شده باشند.
	foreach ( $defaults['channels'] as $key => $channel ) {
		if ( empty( $settings['channels'][ $key ] ) ) {
			$settings['channels'][ $key ] = $channel;
		} else {
			$settings['channels'][ $key ] = wp_parse_args( $settings['channels'][ $key ], $channel );
		}
	}

	return $settings;
}

// راه‌اندازی افزونه.
function support_widget_init() {
	$plugin = new Support_Widget();
	$plugin->run();
}
add_action( 'plugins_loaded', 'support_widget_init' );

// تنظیم مقادیر پیش‌فرض هنگام فعال‌سازی افزونه.
register_activation_hook( __FILE__, function () {
	if ( false === get_option( SUPPORT_WIDGET_OPTION ) ) {
		add_option( SUPPORT_WIDGET_OPTION, support_widget_default_settings() );
	}
} );
