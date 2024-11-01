<?php
/*
Plugin Name: User Register Filter
Plugin URI:  https://tibletech.com/es
Description: Restringe el registro de usuarios en tu WordPress en función de las reglas que especifiques: lista negra de dominios, de extensiones, de nombres, filtrado inteligente anti bots, listas blancas...
Version:     1.3
Author:      Tible Technologies
Author URI:  https://tibletech.com/es
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

// READ https://developer.wordpress.org/plugins/the-basics/header-requirements/
// Text Domain: wporg
// Domain Path: /languages


// Blocks direct access to this file:
defined('ABSPATH') or die(__('No script kiddies please!', 'tt_user_register_filter'));



function tt_user_register_filter($user_login, $user_email, $errors) {

	$user_email = strtolower(trim($user_email));

	$ending_email_blacklist = get_option('tt_user_register_filter_ending_email_blacklist');
	$ending_email_blacklist = str_replace("\n", ',', $ending_email_blacklist);
	$ending_email_blacklist = preg_replace("/[\s]*,[\s]*/", ',', $ending_email_blacklist);
	$ending_email_blacklist = explode(',', $ending_email_blacklist);

	if (!empty($ending_email_blacklist)) {
		foreach ($ending_email_blacklist as $banned_domain) {
			$banned_domain = strtolower(trim($banned_domain));
			if (empty($banned_domain)) {
				continue;
			}
			$len = strlen($banned_domain);
			if (substr($user_email, -$len) == $banned_domain) {
				$errors->add('bad_email_domain', "<strong>".__('ERROR', 'tt_user_register_filter')."</strong>: ".__('This email domain is not allowed.', 'tt_user_register_filter'));
				break;
			}
		}
	}

	// cualquier otro nombre a filtrar en el email
	$banned_words = array('smith', 'schmidt', 'infected', 'daddy', 'young', 'accounts', 'hoffman', 'supply', 'andrew', 'catie', 'store', 'philip', 'manny', 'nancy', 'stream', 'edward', 'husband', 'graham', 'cincinnati', 'food', 'first', 'care', 'dental', 'melissa', 'sales', 'tumblr', 'offer', 'viagra');
	foreach ($banned_words as $banned_word) {
		if (strpos($user_email, $banned_word) !== false) {
			$errors->add('bad_email_domain', "<strong>".__('ERROR', 'tt_user_register_filter')."</strong>: ".__('This email name is not allowed.', 'tt_user_register_filter'));
			break;
		}
	}


	// FILTRO INTELIGENTE:
	$intelligent_filter = get_option('tt_user_register_filter_intelligent_filter');
	if (!empty($intelligent_filter)) {

		// Una típica: se registra con username: pepito1987 y mail pepito@...
		$before_at_sign = substr($user_email, 0, strpos($user_email, '@'));

		if (preg_match("/[0-9]{4}$/", $user_login) && strpos($user_email, $before_at_sign) === 0) {
			$errors->add('bad_email_domain', "<strong>".__('ERROR', 'tt_user_register_filter')."</strong>: ".__('This combination of email and user name is very suspicious.', 'tt_user_register_filter'));
		}
	}

}

add_action('register_post', 'tt_user_register_filter', 10, 3);





/**
 * ADMIN MENU
 */
function tt_user_register_filter_admin_menu() {
	add_menu_page(__('User Register Filter Plugin Setup', 'tt_user_register_filter'), // título de la página
		'User Register Filter', // título del menú
		'administrator', // rol que puede acceder
		'tt-user-register-filter', // /wp-admin/admin.php?page=xxxxxxxx
		'tt_user_register_filter_page_settings', // función con la página de configuración
		'dashicons-admin-generic' // icono del menú
		);
}

add_action('admin_menu', 'tt_user_register_filter_admin_menu');




/**
 * SETTINGS PAGE:
 */
function tt_user_register_filter_page_settings() {
?>
	<h1><?php echo __('Exclusion lists settings', 'tt_user_register_filter'); ?></h1>

	<p><?php echo __('Any user that tries to be registered and matches any of the following rules, will be banned without being registered.', 'tt_user_register_filter'); ?></p>

	<form method="post" action="options.php">
		<?php
		settings_fields('tt_user_register_filter_settings_group');
		do_settings_sections('tt_user_register_filter_settings_group');
		?>
		<h2><?php echo __('Deny the registration if the email ends with:', 'tt_user_register_filter')?></h2>
		<textarea rows="9" cols="45"
			placeholder=".xyz&#10;@viagra.com&#10;pills.com"
			name="tt_user_register_filter_ending_email_blacklist"
			id="tt_user_register_filter_ending_email_blacklist"><?php echo get_option('tt_user_register_filter_ending_email_blacklist');?></textarea><br>
		<em><?php echo __('You can add a TLD (Top Level Domain) extension, a domain or full email addresses.', 'tt_user_register_filter')?></em><br>
		<em><?php echo __('You can split them by Enter, spaces or commas. Do not use quotation marks.', 'tt_user_register_filter')?></em>

		<hr>

		<label>
			<?php $intelligent_filter = get_option('tt_user_register_filter_intelligent_filter'); ?>
			<input type="checkbox" name="tt_user_register_filter_intelligent_filter" value="1" <?php if (!empty($intelligent_filter)) { echo "checked"; } ?>>
			<strong><?php echo __('Smart filtering.', 'tt_user_register_filter')?></strong> <?php echo __('Restricts registration of users with suspicious activity.', 'tt_user_register_filter')?>
		</label><br>
		<em><?php echo __('Its activation is recommended only if a high number of inappropriate records is detected.', 'tt_user_register_filter')?></em>


		<?php submit_button(); ?>
	</form>
<?php
}



/**
 * Registra las opciones del formulario para que se guarden en DB:
 */
function tt_user_register_filter_content_settings() {
	register_setting('tt_user_register_filter_settings_group',
		'tt_user_register_filter_ending_email_blacklist');

	register_setting('tt_user_register_filter_settings_group',
		'tt_user_register_filter_intelligent_filter');
}

add_action('admin_init', 'tt_user_register_filter_content_settings');



/**
 * Translation setup. As easy as specify lang dir.
 */
add_action('plugins_loaded', 'wan_load_textdomain');
function wan_load_textdomain() {
	load_plugin_textdomain('tt_user_register_filter', false, dirname(plugin_basename(__FILE__)).'/lang/');
}
