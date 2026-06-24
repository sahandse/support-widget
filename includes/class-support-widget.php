<?php
/**
 * کلاس اصلی افزونه که بخش‌های مختلف را به هم متصل می‌کند.
 *
 * @package Support_Widget
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Support_Widget {

	/**
	 * نمونه مدیریت تنظیمات.
	 *
	 * @var Support_Widget_Settings
	 */
	protected $settings;

	/**
	 * نمونه نمایش بخش کاربری.
	 *
	 * @var Support_Widget_Frontend
	 */
	protected $frontend;

	public function __construct() {
		$this->settings = new Support_Widget_Settings();
		$this->frontend = new Support_Widget_Frontend();
	}

	/**
	 * ثبت هوک‌های مورد نیاز افزونه.
	 */
	public function run() {
		if ( is_admin() ) {
			$this->settings->register();
		}

		$this->frontend->register();
	}
}
