<?php

/**
 * Load services definition file.
 */
$settings['container_yamls'][] = __DIR__ . '/services.yml';

/**
 * Include the Pantheon-specific settings file.
 *
 * n.b. The settings.pantheon.php file makes some changes
 *      that affect all envrionments that this site
 *      exists in.  Always include this file, even in
 *      a local development environment, to insure that
 *      the site settings remain consistent.
 */
include __DIR__ . "/settings.pantheon.php";

/**
 * If there is a local settings file, then include it
 */
$local_settings = __DIR__ . "/settings.local.php";
if (file_exists($local_settings)) {
  include $local_settings;
}
$settings['install_profile'] = 'varbase';

if (defined('PANTHEON_ENVIRONMENT')) {
  if (in_array($_ENV['PANTHEON_ENVIRONMENT'], array('dev', 'test', 'live'))) {
    $settings['trusted_host_patterns'][] = "{$_ENV['PANTHEON_ENVIRONMENT']}-{$_ENV['PANTHEON_SITE_NAME']}.getpantheon.io";
    $settings['trusted_host_patterns'][] = "{$_ENV['PANTHEON_ENVIRONMENT']}-{$_ENV['PANTHEON_SITE_NAME']}.pantheon.io";
    $settings['trusted_host_patterns'][] = "{$_ENV['PANTHEON_ENVIRONMENT']}-{$_ENV['PANTHEON_SITE_NAME']}.pantheonsite.io";
    $settings['trusted_host_patterns'][] = "{$_ENV['PANTHEON_ENVIRONMENT']}-{$_ENV['PANTHEON_SITE_NAME']}.panth.io";

    # Replace value with custom domain(s) added in the site Dashboard
    $settings['trusted_host_patterns'][] = '^.+.limestone.edu$';
    $settings['trusted_host_patterns'][] = '^limestone.edu$';
    $settings['trusted_host_patterns'][] = '^limestoned8.lndo.site$';
  }
}
// Configure Redis

if (defined('PANTHEON_ENVIRONMENT')) {
  // Include the Redis services.yml file. Adjust the path if you installed to a contrib or other subdirectory.
  $settings['container_yamls'][] = 'modules/contrib/redis/example.services.yml';

  //phpredis is built into the Pantheon application container.
  $settings['redis.connection']['interface'] = 'PhpRedis';
  // These are dynamic variables handled by Pantheon.
  $settings['redis.connection']['host']      = $_ENV['CACHE_HOST'];
  $settings['redis.connection']['port']      = $_ENV['CACHE_PORT'];
  $settings['redis.connection']['password']  = $_ENV['CACHE_PASSWORD'];

  $settings['cache']['default'] = 'cache.backend.redis'; // Use Redis as the default cache.
  $settings['cache_prefix']['default'] = 'pantheon-redis';
}

// Redirect multiple subdomains to a single domain.
// Check if Drupal or WordPress is running via command line
if (isset($_ENV['PANTHEON_ENVIRONMENT']) && ($_ENV['PANTHEON_ENVIRONMENT'] === 'live') && (php_sapi_name() != "cli")) {
  if (in_array($_SERVER['HTTP_HOST'], array(
    'my.limestone.edu'
  ))) {
    header('HTTP/1.0 301 Moved Permanently');
    header('Location: https://www.limestone.edu'. $_SERVER['REQUEST_URI']);

    // Name transaction "redirect" in New Relic for improved reporting (optional).
    if (extension_loaded('newrelic')) {
      newrelic_name_transaction("redirect");
    }

    exit();
  }
}
if (isset($_ENV['PANTHEON_ENVIRONMENT']) && ($_SERVER['HTTP_HOST'] == 'music.limestone.edu') && (php_sapi_name() != "cli")) {
  $newurl = 'https://finearts.limestone.edu/music'. $_SERVER['REQUEST_URI'];
  header('HTTP/1.0 301 Moved Permanently');
  header("Location: $newurl");

  // Name transaction "redirect" in New Relic for improved reporting (optional).
  if (extension_loaded('newrelic')) {
    newrelic_name_transaction("redirect");
  }

  exit();
}

if (isset($_ENV['PANTHEON_ENVIRONMENT']) && ($_SERVER['HTTP_HOST'] == 'art.limestone.edu') && (php_sapi_name() != "cli")) {
  $newurl = 'https://finearts.limestone.edu/art'. $_SERVER['REQUEST_URI'];
  header('HTTP/1.0 301 Moved Permanently');
  header("Location: $newurl");

  // Name transaction "redirect" in New Relic for improved reporting (optional).
  if (extension_loaded('newrelic')) {
    newrelic_name_transaction("redirect");
  }

  exit();
}
if (isset($_ENV['PANTHEON_ENVIRONMENT']) && ($_SERVER['HTTP_HOST'] == 'theatre.limestone.edu') && (php_sapi_name() != "cli")) {
  $newurl = 'https://finearts.limestone.edu/theatre'. $_SERVER['REQUEST_URI'];
  header('HTTP/1.0 301 Moved Permanently');
  header("Location: $newurl");

  // Name transaction "redirect" in New Relic for improved reporting (optional).
  if (extension_loaded('newrelic')) {
    newrelic_name_transaction("redirect");
  }

  exit();
}
if (isset($_ENV['PANTHEON_ENVIRONMENT']) && ($_SERVER['HTTP_HOST'] == 'creativewriting.limestone.edu') && (php_sapi_name() != "cli")) {
  $newurl = 'https://finearts.limestone.edu/creative-writing'. $_SERVER['REQUEST_URI'];
  header('HTTP/1.0 301 Moved Permanently');
  header("Location: $newurl");

  // Name transaction "redirect" in New Relic for improved reporting (optional).
  if (extension_loaded('newrelic')) {
    newrelic_name_transaction("redirect");
  }

  exit();
}
if (isset($_ENV['PANTHEON_ENVIRONMENT']) && ($_SERVER['HTTP_HOST'] == 'accessibility.limestone.edu') && (php_sapi_name() != "cli")) {
  $newurl = 'https://www.limestone.edu/accessibility'. $_SERVER['REQUEST_URI'];
  header('HTTP/1.0 301 Moved Permanently');
  header("Location: $newurl");

  // Name transaction "redirect" in New Relic for improved reporting (optional).
  if (extension_loaded('newrelic')) {
    newrelic_name_transaction("redirect");
  }

  exit();
}
if (isset($_ENV['PANTHEON_ENVIRONMENT']) && ($_SERVER['HTTP_HOST'] == 'corporategrant.limestone.edu') && (php_sapi_name() != "cli")) {
  $newurl = 'https://www.limestone.edu/corporate-grant'. $_SERVER['REQUEST_URI'];
  header('HTTP/1.0 301 Moved Permanently');
  header("Location: $newurl");

  // Name transaction "redirect" in New Relic for improved reporting (optional).
  if (extension_loaded('newrelic')) {
    newrelic_name_transaction("redirect");
  }

  exit();
}
if (isset($_ENV['PANTHEON_ENVIRONMENT']) && ($_SERVER['HTTP_HOST'] == 'mba.limestone.edu') && (php_sapi_name() != "cli")) {
  $newurl = 'https://www.limestone.edu/graduate'. $_SERVER['REQUEST_URI'];
  header('HTTP/1.0 301 Moved Permanently');
  header("Location: $newurl");

  // Name transaction "redirect" in New Relic for improved reporting (optional).
  if (extension_loaded('newrelic')) {
    newrelic_name_transaction("redirect");
  }

  exit();
}
if (isset($_ENV['PANTHEON_ENVIRONMENT']) && ($_SERVER['HTTP_HOST'] == 'summer.limestone.edu') && (php_sapi_name() != "cli")) {
  $newurl = 'https://www.limestone.edu/summer'. $_SERVER['REQUEST_URI'];
  header('HTTP/1.0 301 Moved Permanently');
  header("Location: $newurl");

  // Name transaction "redirect" in New Relic for improved reporting (optional).
  if (extension_loaded('newrelic')) {
    newrelic_name_transaction("redirect");
  }

  exit();
}



if (isset($_ENV['PANTHEON_ENVIRONMENT']) && php_sapi_name() != 'cli') {
  // Redirect to https://$primary_domain in the Live environment
  if ($_ENV['PANTHEON_ENVIRONMENT'] === 'live') {
    // Replace www.example.com with your registered domain name.
    $primary_domain = 'www.limestone.edu';
  }
  else {
    // Redirect to HTTPS on every Pantheon environment.
    $primary_domain = $_SERVER['HTTP_HOST'];
  }

  $requires_redirect = FALSE;

  // Ensure the site is being served from the primary domain.
  if ($_SERVER['HTTP_HOST'] != $primary_domain) {
    $requires_redirect = TRUE;
  }

  // If you're not using HSTS in the pantheon.yml file, uncomment this next block.
  // if (!isset($_SERVER['HTTP_USER_AGENT_HTTPS'])
  //     || $_SERVER['HTTP_USER_AGENT_HTTPS'] != 'ON') {
  //   $requires_redirect = TRUE;
  // }

  if ($requires_redirect === TRUE) {

    // Name transaction "redirect" in New Relic for improved reporting (optional).
    if (extension_loaded('newrelic')) {
      newrelic_name_transaction("redirect");
    }

    header('HTTP/1.0 301 Moved Permanently');
    header('Location: https://'. $primary_domain . $_SERVER['REQUEST_URI']);
    exit();
  }
  // Drupal 8 Trusted Host Settings
  if (is_array($settings)) {
    $settings['trusted_host_patterns'] = array('^'. preg_quote($primary_domain) .'$');
  }
}

$uri = $_SERVER['REQUEST_URI'];
$url_to_match = '/about/news';
$subdirectory_toredirect = '/news';
if( strpos( $uri, $url_to_match ) === 0) {

  $redirect_uri = str_replace( $url_to_match , "", $uri );
  if ( ( php_sapi_name() != "cli" ) ) {
    header( 'HTTP/1.0 301 Moved Permanently');
    header( 'Location: https://' . $_SERVER[ 'HTTP_HOST' ] . $subdirectory_toredirect . $redirect_uri );

    if (extension_loaded('newrelic')) {
      newrelic_name_transaction("redirect");
    }

    exit();
  }

}



// <DDSETTINGS>
// Please don't edit anything between <DDSETTINGS> tags.
// This section is autogenerated by Acquia Dev Desktop.
if (isset($_SERVER['DEVDESKTOP_DRUPAL_SETTINGS_DIR']) && file_exists($_SERVER['DEVDESKTOP_DRUPAL_SETTINGS_DIR'] . '/loc_lime_up_dd.inc')) {
  require $_SERVER['DEVDESKTOP_DRUPAL_SETTINGS_DIR'] . '/loc_lime_up_dd.inc';
}
// </DDSETTINGS>
