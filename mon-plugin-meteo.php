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

// S'assure que WordPress est bien lancé pour éviter les accès directs
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Nom de la table pour le cache
define('MPM_WEATHER_CACHE_TABLE', 'wp_meteo_cache');

// Fonction d'activation du plugin
function mpm_activate_plugin() {
    global $wpdb;
    $table_name = $wpdb->prefix . MPM_WEATHER_CACHE_TABLE;
    $charset_collate = $wpdb->get_charset_collate();

    // Requête pour créer la table si elle n'existe pas
    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        location varchar(255) NOT NULL,
        date_cache date NOT NULL,
        data longtext NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
// Enregistre la fonction à l'activation du plugin
register_activation_hook( __FILE__, 'mpm_activate_plugin' );

// Enregistrement du bloc Gutenberg
function mon_plugin_meteo_register_block() {
    register_block_type( __DIR__ . '/mon-bloc-meteo' );
}
add_action( 'init', 'mon_plugin_meteo_register_block' );

// Enregistrement de l'API REST
function mpm_register_rest_route() {
    register_rest_route( 'mon-plugin-meteo/v1', '/weather', array(
        'methods' => 'GET',
        'callback' => 'mpm_get_weather_callback',
        'permission_callback' => '__return_true', // Pas de restriction de permission
    ) );
}
add_action( 'rest_api_init', 'mpm_register_rest_route' );

// Fonction de rappel de l'API REST
function mpm_get_weather_callback( WP_REST_Request $request ) {
    global $wpdb;
    $table_name = $wpdb->prefix . MPM_WEATHER_CACHE_TABLE;

    // Récupère les paramètres de la requête
    $lat = $request->get_param('lat');
    $lon = $request->get_param('lon');
    $today = date('Y-m-d');
    
    if (!$lat || !$lon) {
        return new WP_REST_Response(['error' => 'Coordonnées de latitude et longitude requises.'], 400);
    }
    
    // Vous devez d'abord convertir les coordonnées en nom de ville pour la mise en cache.
    // L'API WeatherAPI peut le faire pour vous.
    $weather_api_key = 'VOTRE_CLE_API_WEATHERAPI'; // REMPLACEZ CETTE CHAINE PAR VOTRE CLÉ API

    // 1. Appel à l'API WeatherAPI pour obtenir les données météo
    $url = "http://api.weatherapi.com/v1/current.json?key={$weather_api_key}&q={$lat},{$lon}";
    $response = wp_remote_get($url);
    
    if ( is_wp_error( $response ) ) {
        return new WP_REST_Response(['error' => 'Erreur lors de l\'appel à l\'API météo.'], 500);
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body);
    
    if (isset($data->error)) {
        return new WP_REST_Response(['error' => $data->error->message], 400);
    }
    
    // On extrait la localisation pour la recherche en base de données
    $location = $data->location->name;
    
    // 2. Vérification du cache
    $cached_data = $wpdb->get_row( $wpdb->prepare(
        "SELECT data FROM $table_name WHERE location = %s AND date_cache = %s",
        $location, $today
    ) );

    if ($cached_data) {
        // Le cache existe, on retourne les données
        return new WP_REST_Response(json_decode($cached_data->data), 200);
    } else {
        // 3. Les données n'existent pas, on insère dans la base de données
        $wpdb->insert(
            $table_name,
            array(
                'location' => $location,
                'date_cache' => $today,
                'data' => $body, // On stocke la réponse complète de l'API
            ),
            array('%s', '%s', '%s')
        );

        // 4. On retourne les données de l'API
        $response_data = [
            'temp' => $data->current->temp_c,
            'condition' => $data->current->condition->text,
            'location' => $data->location->name,
            'date' => $data->location->localtime
        ];
        return new WP_REST_Response($response_data, 200);
    }
}