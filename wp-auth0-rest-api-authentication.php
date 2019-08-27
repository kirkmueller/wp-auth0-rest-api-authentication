<?php

/**
 * WP Auth0 Rest API Authentcation
 *
 * This plugin enables authentication against Auth0 for custom rest api calls
 *
 * @since             1.0.0
 * @package           wp-auth0-rest-api-authentication
 *
 * @wordpress-plugin
 * Plugin Name:       WP Auth0 Rest API Authentcation
 * Description:       This plugin enables authentication against Auth0 for custom rest api calls
 * Version:           1.0.0
 * Author:            Kirk Mueller
 * Text Domain:       wp-auth0-rest-api-authentication
 */


// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class WP_AUTH0_AUTHENTICATE_TOKEN  {

    private $version   = '1';

	public function __construct() {}

    public function init(){
        add_action( 'admin_menu', array($this,'wp_auth0_rest_api_options_page') );
    }

    public function wp_auth0_rest_api_options_page(){
        
        add_options_page( 
            'Auth0 Rest API Authentication',
            'Rest Authentication',
            'manage_options',
            'wp-auth0-rest-api-authentication',
            array($this, 'render_options_page')
        );
       
        add_settings_section(
            'wp-auth0-rest-authentication_options', 
            'Auth0 Settings', 
            array($this,'render_options_section'), 
            'wp-auth0-rest-api-authentication'
        );

        // audiences
        add_settings_field( 
            'wp-auth0-rest-authentication_audiences', 
            'Audiences', 
            array($this,'render_audiences_field'), 
            'wp-auth0-rest-api-authentication', 
            'wp-auth0-rest-authentication_options' 
        );

         // domains
         add_settings_field( 
            'wp-auth0-rest-authentication_domains', 
            'Domains', 
            array($this,'render_domains_field'), 
            'wp-auth0-rest-api-authentication', 
            'wp-auth0-rest-authentication_options' 
        );
        
        register_setting( 'wp-auth0-rest-api-authentication', 'wp-auth0-rest-authentication_domains' );
        register_setting( 'wp-auth0-rest-api-authentication', 'wp-auth0-rest-authentication_audiences' );
    }

    public function render_audiences_field(){
        echo '<input class="regular-text" name="wp-auth0-rest-authentication_audiences" id="wp-auth0-rest-authentication_audiences" type="text" value="' . get_option( 'wp-auth0-rest-authentication_audiences' ) . '" placeholder="Audiences" />';
    }

    public function render_domains_field(){
        echo '<input class="regular-text" name="wp-auth0-rest-authentication_domains" id="wp-auth0-rest-authentication_domains" type="text" value="' . get_option( 'wp-auth0-rest-authentication_domains' ) . '" placeholder="Domains" />';
    }

    public function render_options_section(){
        echo '';
    }

    public function render_options_page(){
        ?>

        <h1>WP Auth0 Rest API Authentication</h1>
        <p>This allows you to enter your Auth0 information to authenticate your custom rest api endpoints.</p>
        <br/>

        <form method="post" action="options.php">
            <?php
                settings_fields( 'wp-auth0-rest-api-authentication' );
                do_settings_sections( 'wp-auth0-rest-api-authentication' );
                submit_button();
            ?>
        </form>
        <?php
    }

    public function verify_token($token){
        require (dirname(__DIR__,1) . '/vendor/autoload.php');

        try{
            $audiences = get_option( 'wp-auth0-rest-authentication_audiences' );
            $domains = get_option( 'wp-auth0-rest-authentication_domains' );
            $verifier = new \Auth0\SDK\JWTVerifier([
                'supported_algs' => ['RS256'],
                'valid_audiences' => explode (",", $audiences),
                'authorized_iss' => explode (",", $domains),
            ]);
            $tokenInfo = $verifier->verifyAndDecode($token);

            // Verify Auth0 ID in token
            if(isset($tokenInfo->sub)){
                
                // Search for user in system with matching user ID
                $args = array(
                    'meta_key' => 'wp_auth0_id',
                    'meta_value' => $tokenInfo->sub,
                    'number' => 1
                );
                $user_query = new WP_User_Query( $args );
                
                // Get the results
                $user = $user_query->get_results();
                
                if(count($user) > 0){
                    return $user[0];
                }else{
                    // Token verified but user does not exist in wordpress database
                    return new WP_Error( 'rest_forbidden', 'Unauthorized Request. Could not find user.', array( 'status' => 401 ) );
                                
                }

            }else{
                // Could not verify token
                return new WP_Error( 'rest_forbidden', 'Unauthorized Request. Token could not be verified', array( 'status' => 401 ) );
            }

        }catch (Exception $e) {
            // Something went wrong with JWTVerifier
            return new WP_Error( 'rest_forbidden', 'Unauthorized Request. Internal Error', array( 'status' => 401 ) );
        }

    }

}

$WPAUTHREST = new WP_AUTH0_AUTHENTICATE_TOKEN();
$WPAUTHREST->init();