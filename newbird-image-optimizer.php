<?php
/*
Plugin Name:  Newbird Image Optimizer
Plugin URI:   https://newbirddesign.com
Description:  Optimizes and compresses images as you upload them.
Version:      0.1
Author:       Matt Tanner
Author URI:   https://newbirddesign.com
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  newbird-image-optimizer
Domain Path:  /languages
*/

require_once('vendor/autoload.php');
use Spatie\ImageOptimizer\OptimizerChainFactory;

// Settings page
//   Compression percentage
//   Database values tracking amount saved
//   Using amount saved to calculate load time improvements
//   Video compressor https://github.com/PHP-FFMpeg/PHP-FFMpeg
//   Form to compress all images from given month (or all in general)

$plugin_dir = plugin_basename( __FILE__ );

/**
 * Add settings link to plugin on plugins page
 */
add_filter( 'plugin_action_links_' . $plugin_dir, function($links) {
  $settings_link = '<a href="options-general.php?page=newbird-image-optimizer-settings.php">Settings</a>';
  array_unshift( $links, $settings_link );

  return $links;
} );

/**
 * Content of settings page
 */
function settings_page() {
  if ( ! current_user_can( 'manage_options' ) ) {
    return;
  }

  function not_installed_message($package, $code) {
    return 'Command ' . $package . ' not found. Please SSH into the server and install the required package with: <code>' . $code . '</code>';
  }

  $current_user = exec('whoami');

  $packages = [
    'jpegoptim' => [
      'command' => 'sudo apt-get install jpegoptim',
      'installed' => false,
    ],
    'optipng' => [
      'command' => 'sudo apt-get install optipng',
      'installed' => false,
    ],
    'pngquant' => [
      'command' => 'sudo apt-get install pngquant',
      'installed' => false,
    ],
    'svgo' => [
      'command' => '
        (as user ' . $current_user .  '):<br>
        curl https://raw.githubusercontent.com/creationix/nvm/master/install.sh | bash<br>
        source ~/.profile<br>
        nvm install 10.13.0<br>
        npm install -g svgo@1.3.2
      ',
      'installed' => false,
    ],
    'gifsicle' => [
      'command' => 'sudo apt-get install gifsicle',
      'installed' => false,
    ],
    'cwebp' => [
      'command' => 'sudo apt-get install webp',
      'installed' => false,
    ],
  ];

  foreach($packages as $name => $package) {
    $output = $code = '';
    exec('which ' . $name . ' 2>&1', $output, $code);

    if($name === 'svgo') {
      if($code !== 0) {
        // exec('
        //   curl https://raw.githubusercontent.com/creationix/nvm/master/install.sh | bash &&
        //   export NVM_DIR="$HOME/.nvm &&
        //   [ -s "$NVM_DIR/nvm.sh" ] && \\. "$NVM_DIR/nvm.sh" &&
        //   [ -s "$NVM_DIR/bash_completion" ] && \\. "$NVM_DIR/bash_completion" &&
        //   source ~/.profile &&
        //   nvm install 10.13.0 &&
        //   npm install -g svgo@1.3.2 &&
        //   which svgo 2>&1
        // ', $output);
      }
      // exec('NVM_DIR=$HOME/nvm && nvm use 10.13.0 && npm list -g | grep svgo 2>&1', $output, $code);
      exec('nvm install 10.13.0', $output, $code);
      file_put_contents("/tmp/muh", var_export($output, true), 8);
    }

    if($code === 0) {
      $packages[$name]['installed'] = true;
    }
  }

  ?>

  <style type="text/css">
    .Error,
    .Message,
    .Success {
      border: 0.1rem solid rgba(0, 0, 0, 0.2);
      background-color: rgba(0, 0, 0, 0.15);
      padding: 13px 20px;
      margin-bottom: 15px;
    }

    .Error code,
    .Message code,
    .Success code {
      display: block;
      padding: 10px 15px;
      margin-top: 5px;
      background-color: rgba(0, 0, 0, 0.2);
    }

    .Error {
      border-color: rgba(214,54,56, 0.2);
      background-color: rgba(214,54,56, 0.15);
    }

    .Error code {
      background-color: rgba(214,54,56, 0.1);
    }

    .Success {
      border-color: rgba(0,163,42, 0.2);
      background-color: rgba(0,163,42, 0.15);
    }

    .wrap {
      max-width: 768px;
    }
  </style>

  <div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

    <div class="Messages">
      <h3><?php echo __('Required packages', 'optim'); ?></h3>
      <?php
        foreach($packages as $name => $package):
          if(!$package['installed']): ?>
            <div class="Error">
              <?php echo not_installed_message($name, $package['command']); ?>
            </div>
          <?php else: ?>
            <div class="Success">
              Package <?php echo $name ?> detected!
            </div>
          <?php endif;
        endforeach; ?>
    </div>
  </div>

  <?php
}

/**
 * Add settings page to menu
 */
add_action( 'admin_menu', function() {
  add_options_page(
    __( 'Image Optimizer' ),
    __( 'Image Optimizer' ),
    'manage_options',
    'image-optimizer-settings',
    'settings_page',
  );
});

/**
 * Optimize main upload
 */
add_filter( 'wp_handle_upload', function( $upload, $context ) {
  $optimizerChain = OptimizerChainFactory::create([ 'quality' => 75 ]);
  $optimizerChain->setTimeout(30)->optimize($upload['file']);

  return $upload;
}, 10, 2);

/**
 * Optimize all wp-generated attachments
 */
add_filter( 'wp_generate_attachment_metadata', function( $metadata, $attachment_id, $context ) {
  $uploads_dir = wp_get_upload_dir()['path'];

  foreach($metadata['sizes'] as $size) {
    $optimizerChain = OptimizerChainFactory::create([ 'quality' => 75 ]);
    $img_path = $uploads_dir . '/' . $size['file'];

    if(file_exists($img_path)) {
      $optimizerChain->setTimeout(30)->optimize($img_path);
    }
  }

  return $metadata;
}, 10, 3);
