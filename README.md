# WP Auth0 Rest API Authentication Plugin
WordPress plugin that works with the Auth0 plugin to authenticate tokens in rest api endpoints

### Instructions
You must first download, install and set-up the Auth0 plugin. After it has been set up, you can use this plugin to help authenticate rest api calls. Currently the plugin is only used for custom endpoints.

1. Download this folder (the git repo)
2. Run `composer install` to install the vendor libraries
3. Copy the folder to your `wp-content/plugins` folder
4. Add your settings into the Settings > Rest Authentication options area in the wp-backend.

#### Creating an endpoint
Create your endpoint like normal. for your `permission_callback` callback, you can use the plugin like this:

```php
permission_callback => function ($request) {

    // Get your headers
    $headers = getallheaders();
    $authorization = isset($headers['authorization']) ? $headers['authorization'] : (isset($headers['Authorization']) ? $headers['Authorization'] : null);

    // Check for an authentication header
    if(!empty($authorization)){
        // Parse out the token
        $token = str_replace('Bearer ', '', $authorization);

        // Check for plugin class
        if(class_exists(WP_AUTH0_AUTHENTICATE_TOKEN)){
            // Verify token
            $Authentication = new WP_AUTH0_AUTHENTICATE_TOKEN();
            $found = $Authentication->verify_token($token);
            
            // Found will either be a WP_User or a WP_Error
            if(!is_wp_error($found)){

                // You can set the User's ID in the author parameter and access it in your enpoint's callback
                $request->set_param('author',$found->ID);
                return true;

            }else{
                // Returns the WP error 
                return $found;
            }
        }else{
            // Couldn't find the plugin for the authentication class
            return new WP_Error( 'rest_forbidden', 'Could not find authentication class ', array( 'status' => 401 ) );
        }
    }else{
        // No authentication headers found
        return new WP_Error( 'rest_forbidden', 'No Authentication Headers', array( 'status' => 401 ) );
    }

}
```