<?php
/**
 * Plugin Name: دکمه پشتیبانی شناور
 * Plugin URI:  https://github.com/sahandse/support-widget
 * Description: دکمه شناور پشتیبانی با ساعت کاری، پیام خوش‌آمد، آمار کلیک و تنظیمات پیشرفته
 * Version:     3.0.0
 * Author:      سهند رضوان
 * License:     GPL v2 or later
 * Text Domain: support-button
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SUPPORT_BTN_VERSION', '3.0.0' );
define( 'SUPPORT_BTN_URL', plugin_dir_url( __FILE__ ) );

/* ═══════════════════════════════════════════════════════════════
   داده‌ها
═══════════════════════════════════════════════════════════════ */

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

function support_btn_get_channels() {
	$saved = get_option( 'support_btn_channels', [] );
	return is_array( $saved ) ? $saved : [];
}

function support_btn_get_settings() {
	return wp_parse_args( get_option( 'support_btn_settings', [] ), [
		'position'       => 'right',
		'exclude_ids'    => '',
		'schedule_on'    => '0',
		'schedule_start' => '09:00',
		'schedule_end'   => '18:00',
		'schedule_days'  => [ '6', '0', '1', '2', '3' ],
		'offline_msg'    => 'در حال حاضر آفلاین هستیم',
		'bubble_on'      => '0',
		'bubble_text'    => 'سوالی دارید؟ اینجاییم!',
		'bubble_delay'   => '3',
	] );
}

function support_btn_get_stats() {
	return (array) get_option( 'support_btn_stats', [] );
}

function support_btn_has_any_link() {
	foreach ( support_btn_get_channels() as $ch ) {
		if ( ! empty( $ch['url'] ) ) return true;
	}
	return false;
}

function support_btn_should_show() {
	$s = support_btn_get_settings();
	if ( empty( $s['exclude_ids'] ) ) return true;
	$ids = array_filter( array_map( 'intval', preg_split( '/[\s,]+/', $s['exclude_ids'] ) ) );
	return ! in_array( (int) get_the_ID(), $ids, true );
}

/* ═══════════════════════════════════════════════════════════════
   پنل مدیریت
═══════════════════════════════════════════════════════════════ */

add_action( 'admin_menu', 'support_btn_admin_menu' );
function support_btn_admin_menu() {
	add_options_page( 'دکمه پشتیبانی', 'دکمه پشتیبانی', 'manage_options', 'support-button', 'support_btn_settings_page' );
}

add_action( 'admin_init', 'support_btn_register_settings' );
function support_btn_register_settings() {
	register_setting( 'support_btn_options', 'support_btn_channels', [ 'sanitize_callback' => 'support_btn_sanitize_channels' ] );
	register_setting( 'support_btn_options', 'support_btn_settings', [ 'sanitize_callback' => 'support_btn_sanitize_settings' ] );
}

function support_btn_sanitize_channels( $input ) {
	if ( ! is_array( $input ) ) return [];
	$presets = support_btn_presets();
	$clean   = [];
	foreach ( $input as $row ) {
		if ( empty( $row['url'] ) ) continue;
		$net     = array_key_exists( $row['network'] ?? '', $presets ) ? $row['network'] : 'custom';
		$clean[] = [
			'network'     => $net,
			'custom_name' => sanitize_text_field( $row['custom_name'] ?? '' ),
			'operator'    => sanitize_text_field( $row['operator'] ?? '' ),
			'url'         => esc_url_raw( $row['url'] ),
			'color'       => sanitize_hex_color( $row['color'] ?? '' ) ?: $presets[ $net ]['color'],
		];
	}
	return $clean;
}

function support_btn_sanitize_settings( $input ) {
	if ( ! is_array( $input ) ) return [];
	return [
		'position'       => in_array( $input['position'] ?? '', [ 'right', 'left' ], true ) ? $input['position'] : 'right',
		'exclude_ids'    => sanitize_text_field( $input['exclude_ids'] ?? '' ),
		'schedule_on'    => empty( $input['schedule_on'] ) ? '0' : '1',
		'schedule_start' => preg_match( '/^\d{2}:\d{2}$/', $input['schedule_start'] ?? '' ) ? $input['schedule_start'] : '09:00',
		'schedule_end'   => preg_match( '/^\d{2}:\d{2}$/', $input['schedule_end'] ?? '' ) ? $input['schedule_end'] : '18:00',
		'schedule_days'  => array_values( array_filter( array_map( 'intval', (array) ( $input['schedule_days'] ?? [] ) ), function ( $d ) { return $d >= 0 && $d <= 6; } ) ),
		'offline_msg'    => sanitize_text_field( $input['offline_msg'] ?? '' ),
		'bubble_on'      => empty( $input['bubble_on'] ) ? '0' : '1',
		'bubble_text'    => sanitize_text_field( $input['bubble_text'] ?? '' ),
		'bubble_delay'   => max( 0, min( 60, (int) ( $input['bubble_delay'] ?? 3 ) ) ),
	];
}

/* --- پاک کردن آمار --------------------------------------- */

add_action( 'admin_post_support_btn_reset_stats', function () {
	if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Forbidden', 403 );
	check_admin_referer( 'support_btn_reset_stats' );
	delete_option( 'support_btn_stats' );
	wp_safe_redirect( add_query_arg( [ 'page' => 'support-button', 'spb-tab' => 'stats', 'reset' => '1' ], admin_url( 'options-general.php' ) ) );
	exit;
} );

/* --- صفحه تنظیمات ---------------------------------------- */

function support_btn_settings_page() {
	$channels   = support_btn_get_channels();
	$settings   = support_btn_get_settings();
	$stats      = support_btn_get_stats();
	$presets    = support_btn_presets();
	$next_idx   = count( $channels );
	$active_tab = sanitize_key( $_GET['spb-tab'] ?? 'channels' );
	$days_fa    = [ 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه', 'شنبه' ];
	?>
	<div class="wrap" dir="rtl" style="max-width:980px">
		<h1 style="margin-bottom:6px">دکمه پشتیبانی شناور</h1>

		<nav class="nav-tab-wrapper" style="margin-bottom:0">
			<a href="#channels" class="nav-tab">کانال‌ها</a>
			<a href="#settings" class="nav-tab">تنظیمات</a>
			<a href="#stats"    class="nav-tab">
				آمار کلیک
				<?php if ( $stats ) : $total = array_sum( array_column( $stats, 'count' ) ); ?>
				<span class="update-plugins"><span class="plugin-count"><?php echo $total; ?></span></span>
				<?php endif; ?>
			</a>
		</nav>

		<div style="background:#fff;border:1px solid #c3c4c7;border-top:none;padding:20px 24px">

		<form method="post" action="options.php" id="spb-main-form">
		<?php settings_fields( 'support_btn_options' ); ?>

		<!-- ╔═══ Tab: کانال‌ها ═══════════════════════════════════╗ -->
		<div class="spb-tab" id="spb-tab-channels" style="display:none">
			<p style="color:#666;margin-top:0">هر ردیف یک دکمه در سایت می‌سازد. ردیف‌ها را با کشیدن مرتب کنید.</p>

			<table class="wp-list-table widefat fixed striped" id="spb-table">
				<thead>
					<tr>
						<th style="width:30px"></th>
						<th style="width:182px;text-align:right;padding-right:8px">شبکه</th>
						<th style="text-align:right;padding-right:8px">اسم اپراتور</th>
						<th style="text-align:right;padding-right:8px">لینک</th>
						<th style="width:56px;text-align:center">رنگ</th>
						<th style="width:42px"></th>
					</tr>
				</thead>
				<tbody id="spb-rows">
				<?php foreach ( $channels as $i => $ch ) :
					$is_custom = ( $ch['network'] === 'custom' ); ?>
					<tr class="spb-row" draggable="true">
						<td style="text-align:center;color:#bbb;cursor:grab;font-size:16px;padding:8px 4px">⠿</td>
						<td style="padding:8px;vertical-align:top">
							<select name="support_btn_channels[<?php echo $i; ?>][network]" class="spb-net-sel" style="width:100%">
								<?php foreach ( $presets as $key => $p ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>" data-color="<?php echo esc_attr( $p['color'] ); ?>" <?php selected( $ch['network'], $key ); ?>><?php echo esc_html( $p['label'] ); ?></option>
								<?php endforeach; ?>
							</select>
							<input type="text" name="support_btn_channels[<?php echo $i; ?>][custom_name]" class="spb-custom-name" placeholder="نام شبکه..." value="<?php echo esc_attr( $ch['custom_name'] ?? '' ); ?>" style="width:100%;margin-top:4px;<?php echo $is_custom ? '' : 'display:none'; ?>" />
						</td>
						<td style="padding:8px;vertical-align:top">
							<input type="text" name="support_btn_channels[<?php echo $i; ?>][operator]" value="<?php echo esc_attr( $ch['operator'] ?? '' ); ?>" placeholder="پشتیبانی فروش" style="width:100%" />
						</td>
						<td style="padding:8px;vertical-align:top">
							<input type="url" name="support_btn_channels[<?php echo $i; ?>][url]" value="<?php echo esc_url( $ch['url'] ); ?>" placeholder="https://..." style="width:100%" />
						</td>
						<td style="padding:8px 4px;text-align:center;vertical-align:top">
							<input type="color" name="support_btn_channels[<?php echo $i; ?>][color]" value="<?php echo esc_attr( $ch['color'] ); ?>" style="width:40px;height:36px;padding:2px;cursor:pointer;border:1px solid #ccc;border-radius:4px" />
						</td>
						<td style="padding:8px 4px;text-align:center;vertical-align:top">
							<button type="button" class="button spb-remove" title="حذف">✕</button>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<p style="margin-top:10px">
				<button type="button" id="spb-add" class="button button-secondary">+ افزودن کانال</button>
			</p>

			<template id="spb-row-tpl">
				<tr class="spb-row" draggable="true">
					<td style="text-align:center;color:#bbb;cursor:grab;font-size:16px;padding:8px 4px">⠿</td>
					<td style="padding:8px;vertical-align:top">
						<select class="spb-net-sel" style="width:100%">
							<?php foreach ( $presets as $key => $p ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" data-color="<?php echo esc_attr( $p['color'] ); ?>"><?php echo esc_html( $p['label'] ); ?></option>
							<?php endforeach; ?>
						</select>
						<input type="text" class="spb-custom-name" placeholder="نام شبکه..." style="width:100%;margin-top:4px;display:none" />
					</td>
					<td style="padding:8px;vertical-align:top">
						<input type="text" placeholder="پشتیبانی فروش" style="width:100%" />
					</td>
					<td style="padding:8px;vertical-align:top">
						<input type="url" placeholder="https://..." style="width:100%" />
					</td>
					<td style="padding:8px 4px;text-align:center;vertical-align:top">
						<input type="color" value="#0088CC" style="width:40px;height:36px;padding:2px;cursor:pointer;border:1px solid #ccc;border-radius:4px" />
					</td>
					<td style="padding:8px 4px;text-align:center;vertical-align:top">
						<button type="button" class="button spb-remove" title="حذف">✕</button>
					</td>
				</tr>
			</template>
		</div>

		<!-- ╔═══ Tab: تنظیمات ════════════════════════════════════╗ -->
		<div class="spb-tab" id="spb-tab-settings" style="display:none">

			<h3 style="border-bottom:1px solid #eee;padding-bottom:8px;margin-top:8px">موقعیت دکمه</h3>
			<fieldset style="display:flex;gap:20px">
				<label><input type="radio" name="support_btn_settings[position]" value="right" <?php checked( $settings['position'], 'right' ); ?> /> پایین-راست</label>
				<label><input type="radio" name="support_btn_settings[position]" value="left"  <?php checked( $settings['position'], 'left' ); ?>  /> پایین-چپ</label>
			</fieldset>

			<h3 style="border-bottom:1px solid #eee;padding-bottom:8px;margin-top:24px">مخفی کردن در صفحات خاص</h3>
			<p style="color:#666;margin-top:0;font-size:13px">شناسه (ID) صفحاتی که دکمه نباید نمایش داده شود، با ویرگول جدا کنید:</p>
			<input type="text" name="support_btn_settings[exclude_ids]" value="<?php echo esc_attr( $settings['exclude_ids'] ); ?>" placeholder="مثلاً: 5, 12, 34" class="regular-text" />

			<h3 style="border-bottom:1px solid #eee;padding-bottom:8px;margin-top:24px">ساعت کاری</h3>
			<label style="display:flex;align-items:center;gap:8px">
				<input type="checkbox" name="support_btn_settings[schedule_on]" value="1" <?php checked( $settings['schedule_on'], '1' ); ?> id="spb-sch-on" />
				فعال‌سازی ساعت کاری
			</label>
			<div id="spb-sch-box" style="margin-top:14px;padding-right:22px;<?php echo $settings['schedule_on'] === '1' ? '' : 'display:none'; ?>">
				<div style="display:flex;gap:24px;align-items:center;flex-wrap:wrap">
					<label>از: <input type="time" name="support_btn_settings[schedule_start]" value="<?php echo esc_attr( $settings['schedule_start'] ); ?>" /></label>
					<label>تا: <input type="time" name="support_btn_settings[schedule_end]"   value="<?php echo esc_attr( $settings['schedule_end'] ); ?>" /></label>
				</div>
				<p style="margin-top:12px;margin-bottom:6px"><strong>روزهای فعال:</strong></p>
				<div style="display:flex;gap:14px;flex-wrap:wrap">
					<?php foreach ( $days_fa as $num => $name ) : ?>
					<label><input type="checkbox" name="support_btn_settings[schedule_days][]" value="<?php echo $num; ?>" <?php checked( in_array( (string) $num, array_map( 'strval', (array) $settings['schedule_days'] ), true ) ); ?> /> <?php echo $name; ?></label>
					<?php endforeach; ?>
				</div>
				<p style="margin-top:14px">
					<label>
						پیام آفلاین:
						<input type="text" name="support_btn_settings[offline_msg]" value="<?php echo esc_attr( $settings['offline_msg'] ); ?>" placeholder="در حال حاضر آفلاین هستیم" class="regular-text" style="display:block;margin-top:4px" />
					</label>
					<span style="color:#777;font-size:12px">خالی بگذارید تا دکمه در ساعت غیرکاری پنهان شود.</span>
				</p>
			</div>

			<h3 style="border-bottom:1px solid #eee;padding-bottom:8px;margin-top:24px">پیام خوش‌آمد</h3>
			<label style="display:flex;align-items:center;gap:8px">
				<input type="checkbox" name="support_btn_settings[bubble_on]" value="1" <?php checked( $settings['bubble_on'], '1' ); ?> id="spb-bub-on" />
				نمایش حباب پیام
			</label>
			<div id="spb-bub-box" style="margin-top:14px;padding-right:22px;<?php echo $settings['bubble_on'] === '1' ? '' : 'display:none'; ?>">
				<p style="margin-top:0">
					<label>متن پیام:
						<input type="text" name="support_btn_settings[bubble_text]" value="<?php echo esc_attr( $settings['bubble_text'] ); ?>" placeholder="سوالی دارید؟ اینجاییم!" class="regular-text" style="display:block;margin-top:4px" />
					</label>
				</p>
				<label>تاخیر نمایش (ثانیه):
					<input type="number" name="support_btn_settings[bubble_delay]" value="<?php echo esc_attr( $settings['bubble_delay'] ); ?>" min="0" max="60" style="width:70px;margin-right:6px" />
				</label>
			</div>

		</div>

		<?php submit_button( 'ذخیره تنظیمات' ); ?>
		</form>

		<!-- ╔═══ Tab: آمار ════════════════════════════════════════╗ -->
		<div class="spb-tab" id="spb-tab-stats" style="display:none">
			<?php if ( isset( $_GET['reset'] ) ) : ?>
			<div class="notice notice-success inline" style="margin-top:0"><p>آمار با موفقیت پاک شد.</p></div>
			<?php endif; ?>

			<?php
			uasort( $stats, function ( $a, $b ) { return $b['count'] - $a['count']; } );
			if ( empty( $stats ) ) : ?>
			<p style="color:#777">هنوز هیچ کلیکی ثبت نشده است.</p>
			<?php else : ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th style="text-align:right">کانال</th>
						<th style="width:130px;text-align:center">تعداد کلیک</th>
						<th style="width:170px;text-align:center">آخرین کلیک</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $stats as $row ) : ?>
					<tr>
						<td style="text-align:right">
							<?php echo esc_html( $row['label'] ); ?>
							<br/><small style="color:#999"><?php echo esc_html( $row['url'] ); ?></small>
						</td>
						<td style="text-align:center;font-size:20px;font-weight:bold;color:#1a73e8"><?php echo (int) $row['count']; ?></td>
						<td style="text-align:center;color:#666"><?php echo $row['last'] ? esc_html( wp_date( 'Y/m/d H:i', $row['last'] ) ) : '—'; ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
				<tfoot>
					<tr>
						<th style="text-align:right">مجموع</th>
						<th style="text-align:center;font-size:20px;font-weight:bold"><?php echo array_sum( array_column( $stats, 'count' ) ); ?></th>
						<th></th>
					</tr>
				</tfoot>
			</table>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:16px" onsubmit="return confirm('آمار کلیک‌ها پاک شود؟')">
				<input type="hidden" name="action" value="support_btn_reset_stats" />
				<?php wp_nonce_field( 'support_btn_reset_stats' ); ?>
				<?php submit_button( 'پاک کردن آمار', 'delete small', '', false ); ?>
			</form>
			<?php endif; ?>
		</div>

		</div><!-- /panel -->
	</div>

<script>
(function () {
	/* ── Tabs ──────────────────────────────────────────── */
	var navLinks = document.querySelectorAll('.nav-tab');
	var tabs     = document.querySelectorAll('.spb-tab');

	function showTab(id) {
		tabs.forEach(function (t) { t.style.display = 'none'; });
		navLinks.forEach(function (a) { a.classList.remove('nav-tab-active'); });
		var panel = document.getElementById('spb-tab-' + id);
		var link  = document.querySelector('.nav-tab[href="#' + id + '"]');
		if (panel) panel.style.display = '';
		if (link)  link.classList.add('nav-tab-active');
	}

	navLinks.forEach(function (a) {
		a.addEventListener('click', function (e) {
			e.preventDefault();
			var id = this.getAttribute('href').replace('#', '');
			showTab(id);
			history.replaceState(null, '', '?page=support-button&spb-tab=' + id);
		});
	});

	showTab('<?php echo esc_js( $active_tab ); ?>');

	/* ── Toggle sub-sections ───────────────────────────── */
	function bindToggle(checkId, boxId) {
		var chk = document.getElementById(checkId);
		var box = document.getElementById(boxId);
		if (chk && box) chk.addEventListener('change', function () { box.style.display = this.checked ? '' : 'none'; });
	}
	bindToggle('spb-sch-on', 'spb-sch-box');
	bindToggle('spb-bub-on', 'spb-bub-box');

	/* ── Channels: Add / Remove ────────────────────────── */
	var idx   = <?php echo (int) $next_idx; ?>;
	var tpl   = document.getElementById('spb-row-tpl');
	var tbody = document.getElementById('spb-rows');

	function nameRow(tr, i) {
		var p = 'support_btn_channels[' + i + ']';
		tr.querySelector('.spb-net-sel').name        = p + '[network]';
		tr.querySelector('.spb-custom-name').name    = p + '[custom_name]';
		tr.querySelectorAll('input[type=text]')[0].name  = p + '[operator]';
		tr.querySelectorAll('input[type=url]')[0].name   = p + '[url]';
		tr.querySelectorAll('input[type=color]')[0].name = p + '[color]';
	}

	function addRow() {
		var clone = tpl.content.cloneNode(true);
		var tr = clone.querySelector('tr');
		nameRow(tr, idx++);
		tbody.appendChild(clone);
	}

	if (tbody.children.length === 0) addRow();

	document.getElementById('spb-add').addEventListener('click', addRow);

	tbody.addEventListener('click', function (e) {
		if (!e.target.classList.contains('spb-remove')) return;
		var row = e.target.closest('tr');
		if (tbody.children.length > 1) {
			row.remove();
		} else {
			row.querySelectorAll('input').forEach(function (el) { el.value = ''; });
		}
	});

	tbody.addEventListener('change', function (e) {
		if (!e.target.classList.contains('spb-net-sel')) return;
		var row  = e.target.closest('tr');
		var cust = row.querySelector('.spb-custom-name');
		var col  = row.querySelector('input[type=color]');
		var opt  = e.target.options[e.target.selectedIndex];
		cust.style.display = e.target.value === 'custom' ? '' : 'none';
		if (opt.dataset.color) col.value = opt.dataset.color;
	});

	/* ── Drag & Drop ───────────────────────────────────── */
	var dragging = null;

	tbody.addEventListener('dragstart', function (e) {
		dragging = e.target.closest('.spb-row');
		if (!dragging) return;
		e.dataTransfer.effectAllowed = 'move';
		setTimeout(function () { if (dragging) dragging.style.opacity = '0.4'; }, 0);
	});

	tbody.addEventListener('dragend', function () {
		if (dragging) { dragging.style.opacity = ''; dragging = null; }
		reindex();
	});

	tbody.addEventListener('dragover', function (e) {
		e.preventDefault();
		if (!dragging) return;
		var target = e.target.closest('.spb-row');
		if (!target || target === dragging) return;
		var mid = target.getBoundingClientRect().top + target.offsetHeight / 2;
		tbody.insertBefore(dragging, e.clientY < mid ? target : target.nextSibling);
	});

	tbody.addEventListener('drop', function (e) { e.preventDefault(); });

	function reindex() {
		Array.from(tbody.querySelectorAll('.spb-row')).forEach(function (tr, i) {
			tr.querySelectorAll('[name]').forEach(function (el) {
				el.name = el.name.replace(/support_btn_channels\[\d+\]/, 'support_btn_channels[' + i + ']');
			});
		});
		idx = tbody.children.length;
	}
})();
</script>
	<?php
}

/* ═══════════════════════════════════════════════════════════════
   آمار کلیک — AJAX
═══════════════════════════════════════════════════════════════ */

add_action( 'wp_ajax_support_btn_track',        'support_btn_track_click' );
add_action( 'wp_ajax_nopriv_support_btn_track', 'support_btn_track_click' );
function support_btn_track_click() {
	check_ajax_referer( 'support_btn_track', 'nonce' );
	$url = esc_url_raw( wp_unslash( $_POST['url'] ?? '' ) );
	if ( ! $url ) wp_die( '', '', [ 'response' => 204 ] );

	$key   = md5( $url );
	$label = sanitize_text_field( wp_unslash( $_POST['label'] ?? $url ) );
	$stats = get_option( 'support_btn_stats', [] );

	if ( ! isset( $stats[ $key ] ) ) {
		$stats[ $key ] = [ 'label' => $label, 'url' => $url, 'count' => 0, 'last' => 0 ];
	}
	$stats[ $key ]['count']++;
	$stats[ $key ]['last']  = time();
	$stats[ $key ]['label'] = $label;

	update_option( 'support_btn_stats', $stats, false );
	wp_die( '', '', [ 'response' => 204 ] );
}

/* ═══════════════════════════════════════════════════════════════
   فرانت‌اند
═══════════════════════════════════════════════════════════════ */

add_action( 'wp_enqueue_scripts', 'support_btn_enqueue' );
function support_btn_enqueue() {
	if ( ! support_btn_has_any_link() ) return;

	$s = support_btn_get_settings();
	wp_enqueue_style( 'support-button', SUPPORT_BTN_URL . 'assets/css/support-button.css', [], SUPPORT_BTN_VERSION );
	wp_enqueue_script( 'support-button', SUPPORT_BTN_URL . 'assets/js/support-button.js', [], SUPPORT_BTN_VERSION, true );
	wp_localize_script( 'support-button', 'spbConfig', [
		'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
		'nonce'    => wp_create_nonce( 'support_btn_track' ),
		'tzOffset' => (float) get_option( 'gmt_offset' ) * 60,
		'schedule' => $s['schedule_on'] === '1' ? [
			'start'      => $s['schedule_start'],
			'end'        => $s['schedule_end'],
			'days'       => array_values( array_map( 'intval', (array) $s['schedule_days'] ) ),
			'offlineMsg' => $s['offline_msg'],
		] : null,
		'bubble' => $s['bubble_on'] === '1' && $s['bubble_text'] ? [
			'text'  => $s['bubble_text'],
			'delay' => (int) $s['bubble_delay'],
		] : null,
	] );
}

add_action( 'wp_footer', 'support_btn_output' );
function support_btn_output() {
	if ( ! support_btn_has_any_link() || ! support_btn_should_show() ) return;

	$channels = support_btn_get_channels();
	$settings = support_btn_get_settings();
	$presets  = support_btn_presets();

	$has = false;
	foreach ( $channels as $ch ) { if ( ! empty( $ch['url'] ) ) { $has = true; break; } }
	if ( ! $has ) return;
	?>
	<div id="spb-widget" class="spb-pos-<?php echo esc_attr( $settings['position'] ); ?>" role="complementary" aria-label="پشتیبانی">

		<?php if ( $settings['bubble_on'] === '1' && $settings['bubble_text'] ) : ?>
		<div id="spb-bubble" class="spb-bubble" role="status" aria-live="polite">
			<button class="spb-bubble-dismiss" aria-label="بستن پیام">✕</button>
			<?php echo esc_html( $settings['bubble_text'] ); ?>
		</div>
		<?php endif; ?>

		<div id="spb-menu" aria-hidden="true">
			<?php foreach ( $channels as $ch ) :
				if ( empty( $ch['url'] ) ) continue;
				$net      = $ch['network'] ?? 'custom';
				$net_name = ( $net === 'custom' && ! empty( $ch['custom_name'] ) )
					? $ch['custom_name']
					: ( $presets[ $net ]['label'] ?? $net );
				$operator = trim( $ch['operator'] ?? '' );
				$color    = $ch['color'] ?? ( $presets[ $net ]['color'] ?? '#607D8B' );
				$title    = $operator ? "$operator — $net_name" : $net_name;
			?>
			<a href="<?php echo esc_url( $ch['url'] ); ?>"
			   target="_blank" rel="noopener noreferrer"
			   class="spb-item"
			   style="background:<?php echo esc_attr( $color ); ?>"
			   data-label="<?php echo esc_attr( $title ); ?>"
			   title="<?php echo esc_attr( $title ); ?>">
				<span class="spb-icon" aria-hidden="true"><?php echo support_btn_get_icon( $net ); ?></span>
				<span class="spb-label-wrap">
					<span class="spb-operator"><?php echo esc_html( $operator ?: $net_name ); ?></span>
					<?php if ( $operator ) : ?><span class="spb-network"><?php echo esc_html( $net_name ); ?></span><?php endif; ?>
				</span>
			</a>
			<?php endforeach; ?>
		</div>

		<?php if ( $settings['schedule_on'] === '1' && $settings['offline_msg'] ) : ?>
		<div id="spb-offline-msg" class="spb-offline-msg" aria-hidden="true">
			<?php echo esc_html( $settings['offline_msg'] ); ?>
		</div>
		<?php endif; ?>

		<button id="spb-toggle" aria-label="باز کردن منوی پشتیبانی" aria-expanded="false">
			<span class="spb-open-icon"  aria-hidden="true"><?php echo support_btn_svg_chat(); ?></span>
			<span class="spb-close-icon" aria-hidden="true">&#x2715;</span>
		</button>
	</div>
	<?php
}

/* ═══════════════════════════════════════════════════════════════
   آیکون‌های SVG
═══════════════════════════════════════════════════════════════ */

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

function support_btn_svg_chat()      { return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="28" height="28"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/></svg>'; }
function support_btn_svg_telegram()  { return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.562 8.248-1.97 9.289c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12L7.17 14.06l-2.96-.924c-.643-.204-.657-.643.136-.953l11.57-4.461c.537-.194 1.006.131.646.526z"/></svg>'; }
function support_btn_svg_whatsapp()  { return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M.057 24 1.744 17.837C.703 16.033.156 13.988.157 11.891.16 5.335 5.499 0 12.054 0c3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>'; }
function support_btn_svg_bale()      { return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 15-4-4 1.41-1.41L11 14.17l6.59-6.59L19 9l-8 8z"/></svg>'; }
function support_btn_svg_rubika()    { return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M12 2C6.477 2 2 6.477 2 12c0 4.236 2.636 7.855 6.356 9.312-.088-.791-.167-2.005.035-2.868.181-.78 1.172-4.97 1.172-4.97s-.299-.598-.299-1.482c0-1.388.806-2.428 1.808-2.428.853 0 1.267.641 1.267 1.408 0 .858-.546 2.141-.828 3.329-.236.995.499 1.806 1.48 1.806 1.773 0 3.141-1.872 3.141-4.573 0-2.39-1.717-4.061-4.168-4.061-2.837 0-4.502 2.128-4.502 4.326 0 .856.33 1.773.741 2.274a.3.3 0 0 1 .069.285c-.076.313-.245.995-.278 1.134-.044.183-.146.222-.336.134-1.249-.581-2.03-2.407-2.03-3.874 0-3.154 2.292-6.052 6.608-6.052 3.469 0 6.165 2.473 6.165 5.776 0 3.447-2.173 6.22-5.19 6.22-1.013 0-1.967-.527-2.292-1.148l-.623 2.378c-.226.869-.835 1.958-1.244 2.621.937.29 1.931.446 2.962.446 5.523 0 10-4.477 10-10S17.523 2 12 2z"/></svg>'; }
function support_btn_svg_eitaa()     { return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>'; }
function support_btn_svg_instagram() { return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/></svg>'; }
function support_btn_svg_linkedin()  { return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>'; }
function support_btn_svg_twitter()   { return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.746l7.73-8.835L1.254 2.25H8.08l4.259 5.63zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>'; }
function support_btn_svg_link()      { return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg>'; }
