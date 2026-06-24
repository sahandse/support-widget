<?php
/**
 * نمایش دکمه شناور پشتیبانی در بخش کاربری سایت.
 *
 * @package Support_Widget
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Support_Widget_Frontend {

	/**
	 * ثبت هوک‌های بخش کاربری.
	 */
	public function register() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( $this, 'render' ) );
	}

	/**
	 * بارگذاری استایل و اسکریپت دکمه در صورت فعال بودن.
	 */
	public function enqueue_assets() {
		$settings = support_widget_get_settings();

		if ( empty( $settings['enabled'] ) || ! $this->has_active_channel( $settings ) ) {
			return;
		}

		wp_enqueue_style(
			'support-widget',
			SUPPORT_WIDGET_URL . 'assets/css/widget.css',
			array(),
			SUPPORT_WIDGET_VERSION
		);

		wp_enqueue_script(
			'support-widget',
			SUPPORT_WIDGET_URL . 'assets/js/widget.js',
			array(),
			SUPPORT_WIDGET_VERSION,
			true
		);

		// تزریق رنگ اصلی به‌صورت متغیر CSS.
		$color = $settings['main_color'] ? $settings['main_color'] : '#2563eb';
		wp_add_inline_style(
			'support-widget',
			':root{--sw-main-color:' . esc_html( $color ) . ';}'
		);
	}

	/**
	 * بررسی وجود حداقل یک کانال فعال و دارای مقدار.
	 *
	 * @param array $settings تنظیمات افزونه.
	 * @return bool
	 */
	protected function has_active_channel( $settings ) {
		foreach ( $settings['channels'] as $channel ) {
			if ( ! empty( $channel['enabled'] ) && ! empty( $channel['value'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * ساخت لینک نهایی هر پیام‌رسان از روی مقدار ورودی.
	 *
	 * @param string $type  نوع پیام‌رسان.
	 * @param string $value مقدار وارد شده توسط مدیر.
	 * @return string
	 */
	protected function build_url( $type, $value ) {
		$value = trim( $value );

		// اگر کاربر لینک کامل وارد کرده باشد همان را برمی‌گردانیم.
		if ( preg_match( '#^https?://#i', $value ) ) {
			return esc_url( $value );
		}

		$username = ltrim( $value, '@/' );

		switch ( $type ) {
			case 'telegram':
				$url = 'https://t.me/' . rawurlencode( $username );
				break;
			case 'whatsapp':
				$digits = preg_replace( '/[^0-9]/', '', $value );
				$url    = 'https://wa.me/' . $digits;
				break;
			case 'bale':
				$url = 'https://ble.ir/' . rawurlencode( $username );
				break;
			case 'rubika':
				$url = 'https://rubika.ir/' . rawurlencode( $username );
				break;
			case 'eitaa':
				$url = 'https://eitaa.com/' . rawurlencode( $username );
				break;
			default:
				$url = '';
		}

		return esc_url( $url );
	}

	/**
	 * آیکون SVG هر پیام‌رسان.
	 *
	 * @param string $type نوع پیام‌رسان.
	 * @return string
	 */
	protected function get_icon( $type ) {
		$icons = array(
			'telegram' => '<svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24"><path d="M21.94 4.6 18.9 19c-.23 1.02-.84 1.27-1.7.79l-4.7-3.46-2.27 2.18c-.25.25-.46.46-.94.46l.34-4.78L18.3 6.1c.38-.34-.08-.53-.6-.19L7.93 12.2 3.3 10.75c-1-.31-1.02-1 .21-1.49l18.07-6.97c.84-.31 1.57.2 1.36 1.31z"/></svg>',
			'whatsapp' => '<svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24"><path d="M.06 24l1.68-6.13A11.86 11.86 0 0 1 .14 11.9C.14 5.34 5.49 0 12.06 0a11.82 11.82 0 0 1 8.41 3.49 11.78 11.78 0 0 1 3.48 8.41c0 6.56-5.35 11.9-11.91 11.9a11.93 11.93 0 0 1-5.7-1.45L.06 24zM6.6 20.2c1.68.99 3.28 1.59 5.45 1.59 5.45 0 9.89-4.43 9.89-9.88a9.82 9.82 0 0 0-2.9-6.99 9.83 9.83 0 0 0-6.98-2.9c-5.46 0-9.9 4.43-9.9 9.88 0 2.27.66 3.97 1.78 5.74l-.99 3.63 3.65-.96zM17.5 14.7c-.07-.12-.27-.2-.56-.34-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.96-.94 1.16-.17.2-.35.22-.64.07-.3-.15-1.25-.46-2.38-1.47-.88-.78-1.47-1.76-1.65-2.05-.17-.3-.02-.46.13-.6.13-.14.3-.35.44-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.6-.92-2.2-.24-.57-.48-.5-.67-.5l-.57-.01c-.2 0-.52.07-.79.37-.27.3-1.04 1.01-1.04 2.47s1.06 2.87 1.21 3.07c.15.2 2.1 3.2 5.08 4.49.71.3 1.26.49 1.69.62.71.23 1.36.2 1.87.12.57-.08 1.76-.72 2-1.41.25-.7.25-1.29.18-1.41z"/></svg>',
			'bale'     => '<svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm4.6 7.3-1.7 8c-.13.57-.47.7-.95.44l-2.62-1.93-1.27 1.22c-.14.14-.26.26-.53.26l.19-2.67 4.86-4.39c.21-.19-.05-.3-.33-.11l-6 3.78-2.59-.81c-.56-.18-.57-.56.12-.83l10.1-3.9c.47-.17.88.11.69.67z"/></svg>',
			'rubika'   => '<svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zM8 8h4.5a3.5 3.5 0 0 1 1.2 6.79L16 18h-2.3l-2.1-3H10v3H8V8zm2 2v3h2.4a1.5 1.5 0 0 0 0-3H10z"/></svg>',
			'eitaa'    => '<svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm3.5 6-1 8.5c-.07.45-.34.56-.7.35L11 14.8l-1.05 1c-.12.12-.22.22-.45.22l.16-2.3 4.2-3.8c.18-.16-.04-.25-.28-.1l-5.2 3.27-2.24-.7c-.49-.15-.5-.48.1-.71L14.7 7.3c.4-.15.76.1.6.7z"/></svg>',
		);

		return isset( $icons[ $type ] ) ? $icons[ $type ] : '';
	}

	/**
	 * رنگ اختصاصی هر پیام‌رسان.
	 *
	 * @param string $type نوع پیام‌رسان.
	 * @return string
	 */
	protected function get_color( $type ) {
		$colors = array(
			'telegram' => '#229ED9',
			'whatsapp' => '#25D366',
			'bale'     => '#1da1f2',
			'rubika'   => '#8a4af3',
			'eitaa'    => '#ff7700',
		);

		return isset( $colors[ $type ] ) ? $colors[ $type ] : '#2563eb';
	}

	/**
	 * چاپ مارک‌آپ دکمه پشتیبانی در فوتر سایت.
	 */
	public function render() {
		$settings = support_widget_get_settings();

		if ( empty( $settings['enabled'] ) || ! $this->has_active_channel( $settings ) ) {
			return;
		}

		$position = 'bottom-left' === $settings['position'] ? 'sw-pos-left' : 'sw-pos-right';
		?>
		<div class="support-widget <?php echo esc_attr( $position ); ?>" dir="rtl">
			<div class="support-widget__menu" id="support-widget-menu" hidden>
				<?php
				foreach ( $settings['channels'] as $type => $channel ) :
					if ( empty( $channel['enabled'] ) || empty( $channel['value'] ) ) {
						continue;
					}

					$url = $this->build_url( $type, $channel['value'] );

					if ( empty( $url ) ) {
						continue;
					}
					?>
					<a class="support-widget__item" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener nofollow" style="--sw-item-color:<?php echo esc_attr( $this->get_color( $type ) ); ?>">
						<span class="support-widget__icon"><?php echo $this->get_icon( $type ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<span class="support-widget__label"><?php echo esc_html( $channel['label'] ); ?></span>
					</a>
				<?php endforeach; ?>
			</div>

			<button type="button" class="support-widget__toggle" id="support-widget-toggle" aria-expanded="false" aria-controls="support-widget-menu" aria-label="<?php echo esc_attr( $settings['title'] ); ?>">
				<span class="support-widget__toggle-icon" aria-hidden="true">
					<svg class="sw-icon-chat" viewBox="0 0 24 24" fill="currentColor" width="26" height="26"><path d="M12 3C6.5 3 2 6.58 2 11c0 2.05.98 3.92 2.6 5.34L4 21l4.9-2.13c.97.27 2 .42 3.1.42 5.5 0 10-3.58 10-8s-4.5-8-10-8z"/></svg>
					<svg class="sw-icon-close" viewBox="0 0 24 24" fill="currentColor" width="26" height="26"><path d="M18.3 5.71 12 12l6.3 6.29-1.42 1.42L10.59 13.4 4.3 19.71 2.88 18.3 9.17 12 2.88 5.71 4.3 4.29l6.29 6.3 6.29-6.3z"/></svg>
				</span>
				<span class="support-widget__toggle-text"><?php echo esc_html( $settings['title'] ); ?></span>
			</button>
		</div>
		<?php
	}
}
