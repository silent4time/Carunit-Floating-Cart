<?php
/**
 * Plugin Name: سبد شناور کاریونیت
 * Description: دکمه‌ی شناور سبد خرید با پنل کشویی، سبک و فارسی (RTL) برای ووکامرس، هماهنگ با تم کاریونیت.
 * Version: 1.2.2
 * Author: کاریونیت
 * Author URI: https://carunit.ir
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * Text Domain: carunit-floating-cart
 */
defined( 'ABSPATH' ) || exit;

define( 'CFC_VER', '1.2.2' );
define( 'CFC_URL', plugin_dir_url( __FILE__ ) );

add_action( 'before_woocommerce_init', function () {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

final class CFC_Float_Cart {
	const OPT = 'cfc_options';

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'wp_footer', array( __CLASS__, 'render' ), 5 );
		add_filter( 'woocommerce_add_to_cart_fragments', array( __CLASS__, 'fragments' ) );
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( __CLASS__, 'links' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
	}

	public static function opts() {
		return wp_parse_args( get_option( self::OPT, array() ), array(
			'position'    => 'left',
			'color'       => '#fbbc34',
			'auto_color'  => 1,
			'hide_cart'   => 1,
			'open_on_add' => 1,
		) );
	}

	private static function active() {
		if ( is_admin() || ! function_exists( 'WC' ) || ! WC()->cart ) {
			return false;
		}
		$o = self::opts();
		if ( $o['hide_cart'] && ( is_cart() || is_checkout() ) ) {
			return false;
		}
		return true;
	}

	private static function fa( $n ) {
		return strtr( (string) $n, array( '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹' ) );
	}

	private static function badge() {
		$n = WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;
		return '<span class="cfc-count" data-n="' . $n . '">' . self::fa( $n ) . '</span>';
	}

	private static function body() {
		ob_start();
		woocommerce_mini_cart();
		return '<div class="cfc-body">' . ob_get_clean() . '</div>';
	}

	public static function fragments( $f ) {
		if ( WC()->cart ) {
			$f['span.cfc-count'] = self::badge();
			$f['div.cfc-body']   = self::body();
		}
		return $f;
	}

	private static function on( $hex ) {
		$h = ltrim( $hex, '#' );
		if ( 3 === strlen( $h ) ) {
			$h = $h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2];
		}
		$l = 0.299 * hexdec( substr( $h, 0, 2 ) ) + 0.587 * hexdec( substr( $h, 2, 2 ) ) + 0.114 * hexdec( substr( $h, 4, 2 ) );
		return $l > 150 ? '#111' : '#fff';
	}

	public static function assets() {
		if ( ! self::active() ) {
			return;
		}
		$o    = self::opts();
		$deps = array( 'jquery' );
		foreach ( array( 'wc-cart-fragments', 'wc-add-to-cart' ) as $h ) {
			if ( wp_script_is( $h, 'registered' ) ) {
				$deps[] = $h;
			}
		}
		wp_enqueue_style( 'cfc', CFC_URL . 'assets/cfc.css', array(), CFC_VER );
		$hex = sanitize_hex_color( $o['color'] ) ?: '#fbbc34';
		$c   = $o['auto_color'] ? 'var(--wd-primary-color,#fbbc34)' : $hex;
		$on  = $o['auto_color'] ? '#111' : self::on( $hex );
		wp_add_inline_style( 'cfc', '.cfc{--cfc-c:' . $c . ';--cfc-on:' . $on . '}' );
		wp_enqueue_script( 'cfc', CFC_URL . 'assets/cfc.js', $deps, CFC_VER, true );
		wp_add_inline_script( 'cfc', 'window.cfcData=' . wp_json_encode( array( 'openOnAdd' => (bool) $o['open_on_add'] ) ) . ';', 'before' );
	}

	public static function render() {
		if ( ! self::active() ) {
			return;
		}
		$o = self::opts();
		?>
		<div id="cfc" class="cfc cfc-<?php echo esc_attr( $o['position'] ); ?>" dir="rtl">
			<button type="button" id="cfc-btn" class="cfc-btn" aria-label="سبد خرید" aria-expanded="false">
				<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>
				<?php echo self::badge(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</button>
			<div class="cfc-overlay"></div>
			<aside class="cfc-panel" aria-label="سبد خرید">
				<div class="cfc-head"><strong>سبد خرید</strong><button type="button" class="cfc-close" aria-label="بستن">&times;</button></div>
				<?php echo self::body(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</aside>
		</div>
		<?php
	}

	/* ---------- تنظیمات ---------- */
	public static function links( $l ) {
		array_unshift( $l, '<a href="' . esc_url( admin_url( 'admin.php?page=cfc' ) ) . '">تنظیمات</a>' );
		return $l;
	}

	public static function menu() {
		add_menu_page( 'سبد شناور کاریونیت', 'سبد شناور', 'manage_options', 'cfc', array( __CLASS__, 'page' ), 'dashicons-cart', 58 );
	}

	public static function register() {
		register_setting( 'cfc_group', self::OPT, array( 'sanitize_callback' => array( __CLASS__, 'sanitize' ) ) );
	}

	public static function sanitize( $in ) {
		$in = is_array( $in ) ? $in : array();
		return array(
			'position'    => ( isset( $in['position'] ) && 'right' === $in['position'] ) ? 'right' : 'left',
			'color'       => sanitize_hex_color( isset( $in['color'] ) ? $in['color'] : '' ) ?: '#fbbc34',
			'auto_color'  => empty( $in['auto_color'] ) ? 0 : 1,
			'hide_cart'   => empty( $in['hide_cart'] ) ? 0 : 1,
			'open_on_add' => empty( $in['open_on_add'] ) ? 0 : 1,
		);
	}

	public static function page() {
		$o = self::opts();
		$n = self::OPT;
		?>
		<div class="wrap" dir="rtl">
			<h1>سبد خرید شناور</h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'cfc_group' ); ?>
				<table class="form-table">
					<tr><th>موقعیت دکمه</th><td>
						<select name="<?php echo $n; ?>[position]">
							<option value="left" <?php selected( $o['position'], 'left' ); ?>>پایین چپ</option>
							<option value="right" <?php selected( $o['position'], 'right' ); ?>>پایین راست</option>
						</select></td></tr>
					<tr><th>استفاده از رنگ اصلی قالب (Woodmart)</th><td><input type="checkbox" name="<?php echo $n; ?>[auto_color]" value="1" <?php checked( $o['auto_color'], 1 ); ?>></td></tr>
					<tr><th>رنگ دلخواه (اگر گزینه‌ی بالا خاموش باشد)</th><td><input type="color" name="<?php echo $n; ?>[color]" value="<?php echo esc_attr( $o['color'] ); ?>"></td></tr>
					<tr><th>مخفی در سبد/تسویه‌حساب</th><td><input type="checkbox" name="<?php echo $n; ?>[hide_cart]" value="1" <?php checked( $o['hide_cart'], 1 ); ?>></td></tr>
					<tr><th>باز شدن پنل بعد از افزودن محصول</th><td><input type="checkbox" name="<?php echo $n; ?>[open_on_add]" value="1" <?php checked( $o['open_on_add'], 1 ); ?>></td></tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}

add_action( 'plugins_loaded', array( 'CFC_Float_Cart', 'init' ) );
