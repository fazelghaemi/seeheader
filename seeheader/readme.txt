=== SeeHeader ===
Contributors: readystudio
Tags: header, navigation, mega menu, elementor, responsive
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 1.6.0
License: GPLv2 or later

An RTL WordPress header builder with eleven themes, a built-in menu editor, icon pack, mega menus, WordPress menus and an Elementor widget.

== نصب و راه‌اندازی ==
1. پوشه seeheader را در wp-content/plugins قرار دهید و افزونه را فعال کنید.
2. از «هدر ساز» پوسته، لوگو، رنگ‌ها و منبع منو را انتخاب کنید.
3. برای منوی داخلی، آیتم‌های سطح اول و زیرآیتم‌ها را بسازید. با انتخاب والد، زیرمنو ایجاد می‌شود.
4. برای مگامنو، گزینهٔ «مگامنو برای این آیتم» را روی آیتم سطح اول فعال کنید. زیرآیتم‌ها در پوستهٔ دیجی‌کالا دسته‌ها و در سایر پوسته‌ها گروه‌های ستونی هستند.
5. آیکن‌ها را از انتخابگر داخلی انتخاب کنید. در حالت منوی وردپرس نیز می‌توان برای هر آیتم آیکن تعیین کرد.
6. برای نمایش خودکار، «انتشار هدر» را فعال و ذخیره کنید. برای نمایش دستی، از شورت‌کد [seeheader] یا ویجت SeeHeader در المنتور استفاده کنید.

== امکانات ==
* ۱۱ پوسته، از جمله طرح‌های الهام‌گرفته از آروان‌کلاد و دیجی‌کالا.
* لوگوی اختصاصی یا لوگوی پوستهٔ فعال، جستجوی اصلی وردپرس، حساب کاربری و پیوندهای سفارشی.
* منوی داخلی با مرتب‌سازی، زیرمنو، ستون، نشان، توضیح، بنر و مگامنو.
* استفاده از منوی استاندارد وردپرس همراه با انتخاب آیکن از بستهٔ افزونه.
* پالت رنگ پوسته یا رنگ‌های دلخواه، عرض، ارتفاع لوگو، اندازهٔ ردیف اصلی و جستجو، فاصلهٔ منو، گردی ابزارها و تراز منو.
* نقطهٔ شکست قابل تنظیم، نمایش دسکتاپ/موبایل، هدر چسبان و نوار اعلان.
* تنظیمات مستقل پوسته، منو، نمایش و رنگ در ویجت المنتور.

فونت Yekan Bakh فقط در صفحهٔ تنظیمات پیشخوان بارگذاری می‌شود. هدر سایت فونت قالب فعال را به ارث می‌برد.

== Changelog ==
= 1.6.0 =
* Choose desktop, mobile submenu, back and top bar arrows from the bundled Arrows and Arrows Action icon packs.
* Render menu placeholders without fake # links while preserving actual URLs for search engines and users.
* Add intrinsic sizes and asynchronous decoding to offscreen mega menu images; connect disclosure controls to their panels and restore keyboard focus after back navigation.
* Validate used SVG paths directly inside the icon directory without scanning the full icon catalog on every front-end request.
* Load icon choices in batches in the admin picker to keep large icon packs responsive.
= 1.5.1 =
* Fixed desktop header placement, aligned ordinary dropdowns with their own menu item, and closed the hover gap above mega menus.
* Isolated menu button, hover and mobile navigation styles from global theme button rules.
* Replaced font dependent mobile arrows with CSS chevrons and tightened image enabled mega menu layout.
= 1.5.0 =
* Redesigned mobile navigation as a full-height, touch-friendly panel with dedicated submenu controls and back navigation.
* Added per-item badge icon, text and background colors, plus image cards for every image-enabled mega-menu item.
* Preserved existing menu items and their parent relationships; no migration or destructive reset.
= 1.4.0 =
* Added a visual icon gallery that automatically discovers new SVG files in assets/icons on page reload.
* Rebuilt the menu editor tree controls; fixed add/remove children and drag handles.
* Added menu text/icon hover colors and account button styling, including Elementor overrides.
* Fixed mobile menu layout and provided authenticated REST endpoints plus a local Codex MCP server (see mcp/README.md in the repository).
= 1.3.0 =
* Fixed the PHP 8 admin editor crash, mega menu markup, visibility, responsive breakpoints and desktop/mobile display settings.
* Added WordPress menu icons to the rendered menu, a shared icon picker, custom account icon and more Elementor controls.
* Added theme and custom palettes, detailed layout controls and keyboard/mobile navigation; removed unused legacy assets.
