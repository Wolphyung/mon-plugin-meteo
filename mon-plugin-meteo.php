<?php
/**
 * Plugin Name:       Mon Plugin Météo
 * Plugin URI:        https://github.com/votre-nom-utilisateur/mon-plugin-meteo
 * Description:       Affiche la météo de l'utilisateur.
 * Version:           1.0.0
 * Author:            Votre Nom
 * Author URI:        https://example.com
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mon-plugin-meteo
 */
function mon_plugin_meteo_register_block() {
    register_block_type( __DIR__ . '/mon-bloc-meteo' );
}
add_action( 'init', 'mon_plugin_meteo_register_block' );