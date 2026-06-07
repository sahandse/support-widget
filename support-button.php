<?php
/**
 * Plugin Name: دکمه پشتیبانی شناور
 * Plugin URI:  https://github.com/sahandse/support-widget
 * Description: دکمه شناور پشتیبانی با امکان افزودن شبکه‌های اجتماعی دلخواه و اسم اپراتور
 * Version:     2.0.0
 * Author:      سهند رضوان
 * License:     GPL v2 or later
 * Text Domain: support-button
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SUPPORT_BTN_VERSION', '2.0.0' );
define( 'SUPPORT_BTN_URL', plugin_dir_url( __FILE__ ) );

/* ---------------------------------------------------------------
   شبکه‌های از‌پیش‌تعریف‌شده
--------------------------------------------------------------- */

function support_btn_presets() {
	return [
		'telegram'  => [ 'label' => 'تلگرام',     'color' => '#0088CC' ],
		'whatsapp'  => [ 'label' => 'واتس‌اپ',    'color' => '#25D366' ],
		'bale'      => [ 'label' => 'بله',         'color' => '#1565C0' ],
		'rubika'    => [ 'label' => 'روبیکا',      'color' => '#F47B20' ],
		'eitaa'     => [ 'label' => 'ایتا',        'color' => '#00897B' ],
		'instagram' => [ 'label' => 'اینستاگرام',  'color' => '#C13584' ],
		'linkedin'  => [ 'label' => 'لینکدین',     'color' => '#0077B5' ],
		'twitter'   => [ 'label' => 'توییتر / X',  'color' => '#000000' ],
		'custom'    => [ 'label' => 'سایر...',     'color' => '#607D8B' ],
	];
}

/* ---------------------------------------------------------------
   دریافت کانال‌های ذخیره‌شده
--------------------------------------------------------------- */

function support_btn_get_channels() {
	$saved = get_option( 'support_btn_channels', [] );
	return is_array( $saved ) ? $saved : [];
}

function support_btn_has_any_link() {
	foreach ( support_btn_get_channels() as $ch ) {
		if ( ! empty( $ch['url'] ) ) return true;
	}
	return false;
}

/* ---------------------------------------------------------------
   پنل مدیریت
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
	register_setting( 'support_btn_options', 'support_btn_channels', [
		'sanitize_callback' => 'support_btn_sanitize_channels',
	] );
}

function support_btn_sanitize_channels( $input ) {
	if ( ! is_array( $input ) ) return [];
	$presets = support_btn_presets();
	$clean   = [];
	foreach ( $input as $row ) {
		if ( empty( $row['url'] ) ) continue;
		$network = array_key_exists( $row['network'] ?? '', $presets ) ? $row['network'] : 'custom';
		$clean[] = [
			'network'     => $network,
			'custom_name' => sanitize_text_field( $row['custom_name'] ?? '' ),
			'operator'    => sanitize_text_field( $row['operator'] ?? '' ),
			'url'         => esc_url_raw( $row['url'] ),
			'color'       => sanitize_hex_color( $row['color'] ?? '' ) ?: $presets[ $network ]['color'],
		];
	}
	return $clean;
}

function support_btn_settings_page() {
	$channels   = support_btn_get_channels();
	$presets    = support_btn_presets();
	$next_index = count( $channels );
	?>
	<div class="wrap" dir="rtl" style="max-width:960px">
		<h1 style="margin-bottom:4px">دکمه پشتیبانی شناور</h1>
		<p style="color:#666;margin-top:4px">
			هر ردیف یک دکمه روی سایت می‌سازد. لینک‌های خالی نمایش داده نمی‌شوند.
		</p>

		<form method="post" action="options.php">
			<?php settings_fields( 'support_btn_options' ); ?>

			<table class="wp-list-table widefat fixed striped" id="spb-table">
				<thead>
					<tr>
						<th style="width:190px;text-align:right;padding-right:12px">شبکه</th>
						<th style="text-align:right;padding-right:12px">اسم اپراتور</th>
						<th style="text-align:right;padding-right:12px">لینک</th>
						<th style="width:58px;text-align:center">رنگ</th>
						<th style="width:46px"></th>
					</tr>
				</thead>
				<tbody id="spb-rows">
				<?php foreach ( $channels as $i => $ch ) :
					$is_custom = ( $ch['network'] === 'custom' );
				?>
					<tr class="spb-row">
						<td style="padding:8px 12px;vertical-align:top">
							<select name="support_btn_channels[<?php echo $i; ?>][network]"
								class="spb-net-select" style="width:100%">
								<?php foreach ( $presets as $key => $p ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>"
									data-color="<?php echo esc_attr( $p['color'] ); ?>"
									<?php selected( $ch['network'], $key ); ?>>
									<?php echo esc_html( $p['label'] ); ?>
								</option>
								<?php endforeach; ?>
							</select>
							<input type="text"
								name="support_btn_channels[<?php echo $i; ?>][custom_name]"
								class="spb-custom-name"
								placeholder="نام شبکه را بنویسید..."
								value="<?php echo esc_attr( $ch['custom_name'] ?? '' ); ?>"
								style="width:100%;margin-top:5px;<?php echo $is_custom ? '' : 'display:none'; ?>" />
						</td>
						<td style="padding:8px 12px;vertical-align:top">
							<input type="text"
								name="support_btn_channels[<?php echo $i; ?>][operator]"
								value="<?php echo esc_attr( $ch['operator'] ?? '' ); ?>"
								placeholder="مثلاً: پشتیبانی فروش"
								style="width:100%" />
						</td>
						<td style="padding:8px 12px;vertical-align:top">
							<input type="url"
								name="support_btn_channels[<?php echo $i; ?>][url]"
								value="<?php echo esc_url( $ch['url'] ); ?>"
								placeholder="https://..."
								style="width:100%" />
						</td>
						<td style="padding:8px 6px;text-align:center;vertical-align:top">
							<input type="color"
								name="support_btn_channels[<?php echo $i; ?>][color]"
								value="<?php echo esc_attr( $ch['color'] ); ?>"
								class="spb-color"
								style="width:42px;height:36px;padding:2px;cursor:pointer;border:1px solid #ccc;border-radius:4px" />
						</td>
						<td style="padding:8px 6px;text-align:center;vertical-align:top">
							<button type="button" class="button spb-remove" title="حذف این ردیف">✕</button>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<p style="margin-top:10px">
				<button type="button" id="spb-add" class="button button-secondary">
					&#43; افزودن کانال جدید
				</button>
			</p>

			<hr style="margin:20px 0" />

			<?php submit_button( 'ذخیره تنظیمات' ); ?>
		</form>
	</div>

	<!-- قالب ردیف جدید -->
	<template id="spb-row-tpl">
		<tr class="spb-row">
			<td style="padding:8px 12px;vertical-align:top">
				<select class="spb-net-select" style="width:100%">
					<?php foreach ( $presets as $key => $p ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>"
						data-color="<?php echo esc_attr( $p['color'] ); ?>">
						<?php echo esc_html( $p['label'] ); ?>
					</option>
					<?php endforeach; ?>
				</select>
				<input type="text" class="spb-custom-name"
					placeholder="نام شبکه را بنویسید..."
					style="width:100%;margin-top:5px;display:none" />
			</td>
			<td style="padding:8px 12px;vertical-align:top">
				<input type="text" placeholder="مثلاً: پشتیبانی فروش" style="width:100%" />
			</td>
			<td style="padding:8px 12px;vertical-align:top">
				<input type="url" placeholder="https://..." style="width:100%" />
			</td>
			<td style="padding:8px 6px;text-align:center;vertical-align:top">
				<input type="color" value="#0088CC" class="spb-color"
					style="width:42px;height:36px;padding:2px;cursor:pointer;border:1px solid #ccc;border-radius:4px" />
			</td>
			<td style="padding:8px 6px;text-align:center;vertical-align:top">
				<button type="button" class="button spb-remove" title="حذف این ردیف">✕</button>
			</td>
		</tr>
	</template>

	<script>
	(function () {
		var idx   = <?php echo (int) $next_index; ?>;
		var tpl   = document.getElementById('spb-row-tpl');
		var tbody = document.getElementById('spb-rows');

		function setNames(tr) {
			var prefix = 'support_btn_channels[' + idx + ']';
			tr.querySelector('.spb-net-select').name     = prefix + '[network]';
			tr.querySelector('.spb-custom-name').name    = prefix + '[custom_name]';
			tr.querySelectorAll('input[type=text]')[0].name  = prefix + '[operator]';
			tr.querySelectorAll('input[type=url]')[0].name   = prefix + '[url]';
			tr.querySelector('.spb-color').name          = prefix + '[color]';
			idx++;
		}

		function addRow() {
			var clone = tpl.content.cloneNode(true);
			var tr    = clone.querySelector('tr');
			setNames(tr);
			tbody.appendChild(clone);
		}

		/* ردیف اول اگر خالی بود اضافه شود */
		if (tbody.children.length === 0) addRow();

		document.getElementById('spb-add').addEventListener('click', addRow);

		/* حذف ردیف */
		tbody.addEventListener('click', function (e) {
			if (!e.target.classList.contains('spb-remove')) return;
			var row = e.target.closest('tr');
			if (tbody.children.length > 1) {
				row.remove();
			} else {
				row.querySelectorAll('input').forEach(function (el) { el.value = ''; });
			}
		});

		/* تغییر شبکه: نشان/پنهان کردن فیلد سفارشی + به‌روزرسانی رنگ */
		tbody.addEventListener('change', function (e) {
			if (!e.target.classList.contains('spb-net-select')) return;
			var row        = e.target.closest('tr');
			var customInput = row.querySelector('.spb-custom-name');
			var colorInput  = row.querySelector('.spb-color');
			var opt         = e.target.options[e.target.selectedIndex];

			customInput.style.display = (e.target.value === 'custom') ? '' : 'none';
			if (opt.dataset.color) colorInput.value = opt.dataset.color;
		});
	})();
	</script>
	<?php
}

/* ---------------------------------------------------------------
   بارگذاری فایل‌های فرانت‌اند
--------------------------------------------------------------- */

add_action( 'wp_enqueue_scripts', 'support_btn_enqueue' );
function support_btn_enqueue() {
	if ( ! support_btn_has_any_link() ) return;
	wp_enqueue_style(  'support-button', SUPPORT_BTN_URL . 'assets/css/support-button.css', [], SUPPORT_BTN_VERSION );
	wp_enqueue_script( 'support-button', SUPPORT_BTN_URL . 'assets/js/support-button.js',  [], SUPPORT_BTN_VERSION, true );
}

/* ---------------------------------------------------------------
   خروجی HTML در فوتر سایت
--------------------------------------------------------------- */

add_action( 'wp_footer', 'support_btn_output' );
function support_btn_output() {
	$channels = support_btn_get_channels();
	$presets  = support_btn_presets();

	$has = false;
	foreach ( $channels as $ch ) {
		if ( ! empty( $ch['url'] ) ) { $has = true; break; }
	}
	if ( ! $has ) return;
	?>
	<div id="spb-widget" role="complementary" aria-label="پشتیبانی">
		<div id="spb-menu" aria-hidden="true">
			<?php foreach ( $channels as $ch ) :
				if ( empty( $ch['url'] ) ) continue;

				$net_key  = $ch['network'] ?? 'custom';
				$net_name = ( $net_key === 'custom' && ! empty( $ch['custom_name'] ) )
					? $ch['custom_name']
					: ( $presets[ $net_key ]['label'] ?? $net_key );
				$operator = trim( $ch['operator'] ?? '' );
				$color    = $ch['color'] ?? ( $presets[ $net_key ]['color'] ?? '#607D8B' );
			?>
			<a
				href="<?php echo esc_url( $ch['url'] ); ?>"
				target="_blank"
				rel="noopener noreferrer"
				class="spb-item"
				style="background:<?php echo esc_attr( $color ); ?>"
				title="<?php echo esc_attr( $operator ?: $net_name ); ?>"
			>
				<span class="spb-icon" aria-hidden="true"><?php echo support_btn_get_icon( $net_key ); ?></span>
				<span class="spb-label-wrap">
					<span class="spb-operator"><?php echo esc_html( $operator ?: $net_name ); ?></span>
					<?php if ( $operator ) : ?>
					<span class="spb-network"><?php echo esc_html( $net_name ); ?></span>
					<?php endif; ?>
				</span>
			</a>
			<?php endforeach; ?>
		</div>
		<button id="spb-toggle" aria-label="باز کردن منوی پشتیبانی" aria-expanded="false">
			<span class="spb-open-icon"  aria-hidden="true"><?php echo support_btn_svg_chat(); ?></span>
			<span class="spb-close-icon" aria-hidden="true">&#x2715;</span>
		</button>
	</div>
	<?php
}

/* ---------------------------------------------------------------
   آیکون‌های SVG
--------------------------------------------------------------- */

function support_btn_get_icon( $network ) {
	$map = [
		'telegram'  => 'support_btn_svg_telegram',
		'whatsapp'  => 'support_btn_svg_whatsapp',
		'bale'      => 'support_btn_svg_bale',
		'rubika'    => 'support_btn_svg_rubika',
		'eitaa'     => 'support_btn_svg_eitaa',
		'instagram' => 'support_btn_svg_instagram',
		'linkedin'  => 'support_btn_svg_linkedin',
		'twitter'   => 'support_btn_svg_twitter',
	];
	return isset( $map[ $network ] ) ? call_user_func( $map[ $network ] ) : support_btn_svg_link();
}

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

function support_btn_svg_instagram() {
	return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/></svg>';
}

function support_btn_svg_linkedin() {
	return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>';
}

function support_btn_svg_twitter() {
	return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.746l7.73-8.835L1.254 2.25H8.08l4.259 5.63zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>';
}

function support_btn_svg_link() {
	return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg>';
}
