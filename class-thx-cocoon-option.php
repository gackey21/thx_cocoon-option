<?php
/*
Plugin Name: thx.jp²⁻¹ Cocoon Option
Plugin URI:
Description: Cocoon設定の利用
Version: 0.4.8
Author:Gackey.21
Author URI: https://thx.jp
License: GPL2
*/
?>
<?php
/*  Copyright 2019 Gackey.21 (email : gackey.21@gmail.com)

		This program is free software; you can redistribute it and/or modify
		it under the terms of the GNU General Public License, version 2, as
		published by the Free Software Foundation.

		This program is distributed in the hope that it will be useful,
		but WITHOUT ANY WARRANTY; without even the implied warranty of
		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
		GNU General Public License for more details.

		You should have received a copy of the GNU General Public License
		along with this program; if not, write to the Free Software
		Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
?>
<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
const REQUIRE_TCC_VER = '0.4.6';
$enabled_tcc_ver      = get_file_data(
	WP_PLUGIN_DIR . '/thx--jp--square/class-thx-customize-core.php',
	array(
		'version' => 'Version',
	)
);
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if (
	! is_plugin_active( 'thx--jp--square/class-thx-customize-core.php' )
	||
	! version_compare( REQUIRE_TCC_VER, $enabled_tcc_ver['version'], '<=' )
	) :
	add_action( 'admin_notices', 'thx_cocoon_option_admin_notices' );
	function thx_cocoon_option_admin_notices() {
		?>
		<div class="error notice is-dismissible">
			<p>
				<?php
				echo
				'thx.jp²⁻¹ Cocoon Option を使用するには、' .
				'thx.jp² ver.' .
				REQUIRE_TCC_VER .
				' 以上を有効化する必要があります。';
				?>
			</p>
		</div>
		<?php
		deactivate_plugins( plugin_basename( __FILE__ ) );
		unset( $_GET['activate'] );
	}
else : //! is_plugin_active( 'thx-customize-core/class-thx-customize-core.php' )
	if ( ! class_exists( 'Thx_Cocoon_Option' ) ) {
		class Thx_Cocoon_Option {
			//読み込むurl
			static $push_css_dir = array();
			static $push_amp_dir = array();
			static $replace_cocoon_css;
			static $replace_cocoon_amp;
			const CSS_DIR = __DIR__ . '/src/css/';
			//プラグインの汎用キャッシュディレクトリ
			const RESOURCES_PATH = thx_Customize_Core::RESOURCES_PATH . 'cocoon-option/';

			public function __construct() {
				$thx_co_option = get_option( 'thx_co_option' );

				//プラグインの汎用キャッシュディレクトリが無ければ作成
				if ( ! file_exists( self::RESOURCES_PATH ) ) {
					mkdir( self::RESOURCES_PATH, 0777, true );
				}

				//管理画面の設定
				add_action( 'admin_menu', array( $this, 'add_sub_menu' ) );
				add_action( 'admin_init', 'thx_cocoon_option_settings_init' );

				//プラグインメニューの設定
				add_filter(
					'plugin_action_links_' . plugin_basename( __FILE__ ),
					array( $this, 'add_action_links' )
				);

				//自動アップデート
				require plugin_dir_path( __FILE__ ) . 'plugin-update-checker/plugin-update-checker.php';
				$my_update_checker = Puc_v4_Factory::buildUpdateChecker(
					'https://github.com/gackey21/thx-cocoon--option/',
					__FILE__,
					'thx-cocoon-option'
				);

				//アンインストール
				if ( function_exists( 'register_uninstall_hook' ) ) {
					register_uninstall_hook( __FILE__, 'Thx_Cocoon_Option::thx_co_uninstall' );
				}

				//親amp.cssの変更＆出力
				add_filter( 'amp_parent_css', array( 'Thx_Cocoon_Amp', 'echo_amp_parent_css' ) );
				//カスタマイズampの出力
				add_filter( 'amp_all_css', array( 'Thx_Cocoon_Amp', 'echo_amp_all_css' ) );

				//追加関数の読み込み
				require_once( __DIR__ . '/src/php/class-thx-hsla-modulation-on-cocoon.php' );//hsla変調
				require_once( __DIR__ . '/src/php/is-mobile.php' );//スマホ判別

				//エントリーカード・サムネイルの余白を均等にする
				if ( '1' === $thx_co_option['equalizing_thumbnail_margin_in_entry_card']['amp'] ) {
					self::$push_amp_dir[] = self::CSS_DIR . 'entry-card-margin.php';
				}
				if ( '1' === $thx_co_option['equalizing_thumbnail_margin_in_entry_card']['style'] ) {
					self::$push_css_dir[] = self::CSS_DIR . 'entry-card-margin.php';
				}

				//カードをホバー（COLORS風）
				if ( '1' === $thx_co_option['wrap_hover']['amp'] ) {
					self::$push_amp_dir[] = self::CSS_DIR . 'wrap-hover.php';
				}
				if ( '1' === $thx_co_option['wrap_hover']['style'] ) {
					self::$push_css_dir[] = self::CSS_DIR . 'wrap-hover.php';
				}

				//画像の囲み効果（ブロックエディタ）
				if ( '1' === $thx_co_option['iwe_block_editor']['amp'] ) {
					self::$push_amp_dir[] = self::CSS_DIR . 'iwe-amp.php';
				}
				if ( '1' === $thx_co_option['iwe_block_editor']['style'] ) {
					self::$push_css_dir[] = self::CSS_DIR . 'iwe-css.php';
				}

				//ハイライト表示を横スクロールに変更
				if ( '1' === $thx_co_option['pre']['amp'] ) {
					self::$push_amp_dir[] = self::CSS_DIR . 'pre.php';
				}
				if ( '1' === $thx_co_option['pre']['style'] ) {
					self::$push_css_dir[] = self::CSS_DIR . 'pre.php';
				}

				//phpでcssを記述
				if ( '1' === $thx_co_option['php_css']['amp'] ) {
					self::$push_amp_dir[] = self::RESOURCES_PATH . 'thx-phped.css';
				}
				if ( '1' === $thx_co_option['php_css']['style'] ) {
					self::$push_css_dir[] = self::RESOURCES_PATH . 'thx-phped.css';
				}

				//親スタイルの変更
				if ( '1' === $thx_co_option['replace_cocoon_style']['amp'] ) {
					self::$replace_cocoon_amp = $thx_co_option['replace_cocoon_style_array'];
				}
				if ( '1' === $thx_co_option['replace_cocoon_style']['style'] ) {
					self::$replace_cocoon_css = $thx_co_option['replace_cocoon_style_array'];
				}

				// add_action('wp_enqueue_scripts', array($this, 'push_url'));
			}//__construct()

			static $_var = __DIR__ . '/src/css/-var.php';//変数ファイル
			static $src  = __DIR__ . '/src/';

			//アインインストール時にオプション削除
			static function thx_co_uninstall() {
				$thx_co_option = get_option( 'thx_co_option' );
				if ( '1' !== $thx_co_option['keep_option'] ) {
					delete_option( 'thx_co_option' );
				}
			}

			//設定リンク追加
			static function add_action_links( $links ) {
				$add_link = '<a href="admin.php?page=thx-jp-cocoon-option">設定</a>';
				array_unshift( $links, $add_link );
				return $links;
			}

			//サブメニュー作成
			static function add_sub_menu() {
				add_submenu_page(
					'thx-jp-customize-core',
					'thx.jp/ Cocoon Option の設定',
					'Cocoon Option',
					'manage_options',
					'thx-jp-cocoon-option',
					'thx_cocoon_option_form'
				);
			}
		}//class
	}//! class_exists

	require_once( 'src/php/menu.php' );
	require_once( 'src/php/class-thx-cocoon-amp.php' );
	require_once( 'src/php/cocoon-style.php' );

	// ヘッダー部の書き換えβ
	// $tmp_php = thx_Customize_Core::file_to_str(__DIR__.'/../../src/child/tmp/header-container.php');
	// $path = get_stylesheet_directory().'/tmp/header-container.php';
	// thx_Customize_Core::str_to_file($path, $tmp_php);
	// $tmp_php = $tcc -> file_to_str(__DIR__.'/src/child/tmp/css-custom.php');
	// $path = get_stylesheet_directory().'/tmp/css-custom.php';
	// $tcc -> str_to_file($path, $tmp_php);

	new Thx_Cocoon_Option;
endif;//! is_plugin_active( 'thx-customize-core/class-thx-customize-core.php' )

// ampの作られ方
// amp.phpにて
// <style amp-custom>
// get_template_directory_uri().'/amp.css';
// IcoMoonのスタイル
// スキンのスタイル
// get_template_part('tmp/css-custom');
// 子テーマのスタイル
// 投稿・固定ページに記入されているカスタムCSS
// </style>
