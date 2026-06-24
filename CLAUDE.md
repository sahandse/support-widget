# CLAUDE.md

این فایل راهنمای کار با این مخزن برای دستیارهای هوش مصنوعی (مانند Claude Code) است.

## معرفی پروژه

**Support Widget** یک افزونه‌ی وردپرس است که یک دکمه‌ی شناور پشتیبانی به سایت اضافه
می‌کند. با کلیک روی دکمه، فهرستی از پیام‌رسان‌ها برای ارتباط با پشتیبانی باز می‌شود.

پیام‌رسان‌های پشتیبانی‌شده: **تلگرام، واتساپ، بله، روبیکا، ایتا**.

- زبان رابط کاربری و کامنت‌ها: فارسی
- نوع پروژه: افزونه‌ی وردپرس (PHP بدون فریم‌ورک بیرونی)
- بدون وابستگی‌ Composer/npm

## ساختار کدبیس

```
support-widget/
├── support-widget.php                       # فایل اصلی افزونه: ثابت‌ها، توابع تنظیمات، بارگذاری کلاس‌ها و هوک‌ها
├── includes/
│   ├── class-support-widget.php             # کلاس اصلی؛ بخش‌ها را به هم وصل می‌کند (run())
│   ├── class-support-widget-settings.php    # صفحه‌ی تنظیمات پیشخوان + پاک‌سازی ورودی‌ها (sanitize)
│   └── class-support-widget-frontend.php    # رندر دکمه در فوتر، ساخت لینک‌ها و آیکون‌های SVG
├── assets/
│   ├── css/widget.css                       # استایل دکمه‌ی بخش کاربری
│   ├── css/admin.css                        # استایل صفحه‌ی تنظیمات
│   └── js/widget.js                         # رفتار باز/بسته شدن منو
├── readme.txt                               # readme استاندارد وردپرس
└── CLAUDE.md
```

## معماری و جریان داده

1. `support-widget.php` روی هوک `plugins_loaded` تابع `support_widget_init()` را اجرا
   و یک نمونه از `Support_Widget` می‌سازد.
2. `Support_Widget::run()` در پیشخوان `Support_Widget_Settings` و در همه‌جا
   `Support_Widget_Frontend` را ثبت می‌کند.
3. تنظیمات در یک ردیف از جدول options با کلید ثابت `SUPPORT_WIDGET_OPTION`
   (`support_widget_settings`) به‌صورت آرایه ذخیره می‌شود.
4. `support_widget_get_settings()` تنظیمات ذخیره‌شده را با مقادیر پیش‌فرض
   (`support_widget_default_settings()`) ادغام می‌کند تا همیشه ساختار کامل برگردد.
5. در فرانت‌اند، `Support_Widget_Frontend::render()` روی `wp_footer` مارک‌آپ دکمه را
   چاپ می‌کند و `build_url()` مقدار هر کانال را به لینک پیام‌رسان تبدیل می‌کند.

### ساخت لینک پیام‌رسان (`build_url()`)

- اگر مقدار با `http(s)://` شروع شود، همان لینک کامل استفاده می‌شود.
- در غیر این صورت، نام کاربری از ابتدای `@` و `/` پاک شده و به دامنه‌ی پیام‌رسان اضافه می‌شود:
  - تلگرام → `https://t.me/<user>`
  - واتساپ → `https://wa.me/<digits>` (فقط ارقام؛ شماره با کد کشور و بدون `+` و `0`)
  - بله → `https://ble.ir/<user>`
  - روبیکا → `https://rubika.ir/<user>`
  - ایتا → `https://eitaa.com/<user>`

## قراردادها و سبک کد (Conventions)

- **استانداردهای وردپرس**: از توابع و الگوهای WordPress استفاده کن
  (`register_setting`, `add_options_page`, `wp_enqueue_*`, `wp_parse_args`).
- **امنیت**:
  - هر فایل با `if ( ! defined( 'ABSPATH' ) ) { exit; }` از دسترسی مستقیم محافظت می‌شود.
  - همه‌ی خروجی‌ها escape شوند: `esc_html`, `esc_attr`, `esc_url`.
  - همه‌ی ورودی‌ها در `Support_Widget_Settings::sanitize()` پاک‌سازی شوند
    (`sanitize_text_field`, `sanitize_hex_color`, whitelist برای مقادیر محدود).
  - تنها استثناء escape، چاپ آیکون‌های SVG داخلی است (با کامنت `phpcs:ignore` مشخص شده).
- **پیشوند (prefix)**: همه‌ی توابع سراسری با `support_widget_`، ثابت‌ها با
  `SUPPORT_WIDGET_`، و کلاس‌ها با `Support_Widget` شروع می‌شوند تا تداخل ایجاد نشود.
- **نام‌گذاری فایل کلاس‌ها**: `class-{slug}.php` با حروف کوچک و خط تیره (سبک وردپرس).
- **کامنت‌ها به فارسی** نوشته می‌شوند؛ این سبک را حفظ کن.
- **بدون وابستگی**: کتابخانه‌ی بیرونی (Composer/npm/CDN) اضافه نکن مگر اینکه لازم باشد.
- **نسخه‌بندی**: هنگام انتشار تغییر، نسخه را هم‌زمان در سه جا به‌روزرسانی کن:
  هدر `support-widget.php` (`Version`)، ثابت `SUPPORT_WIDGET_VERSION`، و `readme.txt` (`Stable tag` و Changelog).

## افزودن یک پیام‌رسان جدید

برای اضافه کردن کانال جدید این نقاط را به‌روزرسانی کن:

1. `support_widget_default_settings()` در `support-widget.php` → افزودن به آرایه‌ی `channels`.
2. `Support_Widget_Frontend::build_url()` → افزودن `case` برای ساخت لینک.
3. `Support_Widget_Frontend::get_icon()` → افزودن آیکون SVG.
4. `Support_Widget_Frontend::get_color()` → افزودن رنگ کانال.
5. `Support_Widget_Settings::render_page()` → افزودن متن راهنما (`$hints`).

کلید کانال باید در همه‌ی این نقاط یکسان باشد.

## توسعه و تست

- این افزونه نیاز به یک نصب وردپرس دارد؛ پوشه را در `wp-content/plugins/` قرار داده،
  افزونه را فعال و از «تنظیمات → دکمه پشتیبانی» پیکربندی کن.
- ابزار build یا تست خودکار وجود ندارد. تست به‌صورت دستی در مرورگر انجام می‌شود.
- اگر PHP CodeSniffer با استاندارد `WordPress` در دسترس بود، پیش از کامیت اجرا کن:
  `phpcs --standard=WordPress .`

## جریان کاری Git

- شاخه‌ی توسعه‌ی فعلی: `claude/claude-md-docs-OK4pX`
- روی همان شاخه‌ی تعیین‌شده توسعه بده، کامیت با پیام واضح بزن و push کن.
- بدون درخواست صریح کاربر، Pull Request نساز.
