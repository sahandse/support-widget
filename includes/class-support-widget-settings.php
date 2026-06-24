<?php
/**
 * مدیریت صفحه تنظیمات افزونه در پیشخوان وردپرس.
 *
 * @package Support_Widget
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Support_Widget_Settings {

	/**
	 * ثبت هوک‌های مربوط به پیشخوان.
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter(
			'plugin_action_links_' . plugin_basename( SUPPORT_WIDGET_FILE ),
			array( $this, 'add_settings_link' )
		);
	}

	/**
	 * افزودن منوی تنظیمات زیر منوی «تنظیمات» وردپرس.
	 */
	public function add_menu() {
		add_options_page(
			'دکمه پشتیبانی',
			'دکمه پشتیبانی',
			'manage_options',
			'support-widget',
			array( $this, 'render_page' )
		);
	}

	/**
	 * ثبت گزینه و تابع پاک‌سازی ورودی‌ها.
	 */
	public function register_settings() {
		register_setting(
			'support_widget_group',
			SUPPORT_WIDGET_OPTION,
			array( $this, 'sanitize' )
		);
	}

	/**
	 * بارگذاری استایل و اسکریپت صفحه تنظیمات.
	 *
	 * @param string $hook شناسه صفحه فعلی پیشخوان.
	 */
	public function enqueue_assets( $hook ) {
		if ( 'settings_page_support-widget' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'support-widget-admin',
			SUPPORT_WIDGET_URL . 'assets/css/admin.css',
			array(),
			SUPPORT_WIDGET_VERSION
		);
	}

	/**
	 * افزودن لینک «تنظیمات» در فهرست افزونه‌ها.
	 *
	 * @param array $links لینک‌های موجود.
	 * @return array
	 */
	public function add_settings_link( $links ) {
		$url      = admin_url( 'options-general.php?page=support-widget' );
		$settings = '<a href="' . esc_url( $url ) . '">تنظیمات</a>';
		array_unshift( $links, $settings );

		return $links;
	}

	/**
	 * پاک‌سازی و اعتبارسنجی ورودی‌های فرم تنظیمات.
	 *
	 * @param array $input داده‌های خام ارسالی فرم.
	 * @return array
	 */
	public function sanitize( $input ) {
		$defaults = support_widget_default_settings();
		$clean    = array();

		$clean['enabled']    = empty( $input['enabled'] ) ? 0 : 1;
		$clean['title']      = isset( $input['title'] ) ? sanitize_text_field( $input['title'] ) : $defaults['title'];
		$clean['main_color'] = isset( $input['main_color'] ) ? sanitize_hex_color( $input['main_color'] ) : $defaults['main_color'];

		$allowed_positions  = array( 'bottom-right', 'bottom-left' );
		$clean['position']  = ( isset( $input['position'] ) && in_array( $input['position'], $allowed_positions, true ) )
			? $input['position']
			: $defaults['position'];

		$clean['channels'] = array();
		foreach ( $defaults['channels'] as $key => $channel ) {
			$clean['channels'][ $key ] = array(
				'label'   => $channel['label'],
				'enabled' => empty( $input['channels'][ $key ]['enabled'] ) ? 0 : 1,
				'value'   => isset( $input['channels'][ $key ]['value'] )
					? sanitize_text_field( $input['channels'][ $key ]['value'] )
					: '',
			);
		}

		return $clean;
	}

	/**
	 * نمایش صفحه تنظیمات.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = support_widget_get_settings();
		$hints    = array(
			'telegram' => 'نام کاربری بدون @ یا لینک کامل t.me',
			'whatsapp' => 'شماره با کد کشور بدون + و ۰ (مثال: 989123456789)',
			'bale'     => 'نام کاربری یا لینک ble.ir',
			'rubika'   => 'نام کاربری یا لینک rubika.ir',
			'eitaa'    => 'نام کاربری یا لینک eitaa.com',
		);
		?>
		<div class="wrap support-widget-admin" dir="rtl">
			<h1>تنظیمات دکمه پشتیبانی</h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'support_widget_group' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">فعال‌سازی دکمه</th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( SUPPORT_WIDGET_OPTION ); ?>[enabled]" value="1" <?php checked( 1, $settings['enabled'] ); ?> />
								نمایش دکمه پشتیبانی در سایت
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="sw-title">عنوان دکمه</label></th>
						<td>
							<input type="text" id="sw-title" class="regular-text" name="<?php echo esc_attr( SUPPORT_WIDGET_OPTION ); ?>[title]" value="<?php echo esc_attr( $settings['title'] ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="sw-position">موقعیت دکمه</label></th>
						<td>
							<select id="sw-position" name="<?php echo esc_attr( SUPPORT_WIDGET_OPTION ); ?>[position]">
								<option value="bottom-right" <?php selected( 'bottom-right', $settings['position'] ); ?>>پایین – راست</option>
								<option value="bottom-left" <?php selected( 'bottom-left', $settings['position'] ); ?>>پایین – چپ</option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="sw-color">رنگ اصلی</label></th>
						<td>
							<input type="color" id="sw-color" name="<?php echo esc_attr( SUPPORT_WIDGET_OPTION ); ?>[main_color]" value="<?php echo esc_attr( $settings['main_color'] ); ?>" />
						</td>
					</tr>
				</table>

				<h2>راه‌های ارتباطی</h2>
				<p class="description">برای هر پیام‌رسان، نام کاربری یا شماره را وارد کرده و تیک فعال‌سازی را بزنید.</p>

				<table class="form-table support-widget-channels" role="presentation">
					<?php foreach ( $settings['channels'] as $key => $channel ) : ?>
						<tr>
							<th scope="row"><?php echo esc_html( $channel['label'] ); ?></th>
							<td>
								<label class="sw-channel-toggle">
									<input type="checkbox" name="<?php echo esc_attr( SUPPORT_WIDGET_OPTION ); ?>[channels][<?php echo esc_attr( $key ); ?>][enabled]" value="1" <?php checked( 1, $channel['enabled'] ); ?> />
									فعال
								</label>
								<input type="text" class="regular-text" name="<?php echo esc_attr( SUPPORT_WIDGET_OPTION ); ?>[channels][<?php echo esc_attr( $key ); ?>][value]" value="<?php echo esc_attr( $channel['value'] ); ?>" placeholder="<?php echo esc_attr( $hints[ $key ] ); ?>" />
								<p class="description"><?php echo esc_html( $hints[ $key ] ); ?></p>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>

				<?php submit_button( 'ذخیره تغییرات' ); ?>
			</form>
		</div>
		<?php
	}
}
