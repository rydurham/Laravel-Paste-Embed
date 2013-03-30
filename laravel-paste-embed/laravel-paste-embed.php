<?php
/**
 * @package LaravePasteEmbed
 * @version 1.1 
 */
/*
Plugin Name: Laravel Paste Embed
Plugin URI: http://rydurham.com/plugins/laravel-paste-embed/
Description: A plugin to embed pages & snippets from paste.laravel.com into a wordpress site. 
Author: Ryan Durham
Version: 1.1
Author URI: http://rydurham.com
License: GPL2

Thanks to Dayle Rees & co. for the styles: http://paste.laravel.com/css/style.css

Copyright 2013  Ryan Durham  (email : rydurham@gmail.com)

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


// [lpe foo="foo-value"]
function lpe_func( $atts ) {
    extract( shortcode_atts( array(
        'paste' => null,
        'caption' => null,
        'width' => '100%',
        'height' => 'auto'
    ), $atts ) );

    //Make sure there is a Paste URL destination.
    if (!$paste) {
        return '';
    }

    //Prep for WP_Http action
    if( !class_exists( 'WP_Http' ) )
        include_once( ABSPATH . WPINC. '/class-http.php' );
    
    //Pull data from paste.laravel.com
    $url = "http://paste.laravel.com/$paste";
    $resp = wp_remote_get($url);

    if ($resp['response']['code'] != '200') 
    {
        $error_message = "Paste Error: " .  $resp['response']['message'];
        return $error_message;
    }
    else 
    {
        
        //Parse the body of the response to pull out the relevant content.
        $dom = new DOMDocument();
        $html = $dom->loadHTML($resp['body']);
        $code = $dom->getElementsByTagName('code')->item(0)->nodeValue;
        $pasteOutput = "<div class=\"lpe_shell\" style=\"width: $width\">";
        if ($caption) {
            $pasteOutput .= "<div class=\"lpe_title\">$caption</div>";
        }
        $pasteOutput .= "<div class=\"lpe_link\"><a href=\"$url\" target=\"_blank\">link</a> </div>"
            . "<pre class=\"prettyprint linenums \" style=\"height: $height;\">"
            . htmlentities($code)
            . "</pre></div>";

        return $pasteOutput;
    }
    
}
add_shortcode( 'lpe', 'lpe_func' );


/**
* Register with hook 'wp_enqueue_scripts', which can be used for front end CSS and JavaScript
*/
add_action( 'wp_enqueue_scripts', 'prefix_add_lpe_stylesheet' );
add_action( 'wp_enqueue_scripts', 'prefix_add_lpe_script' );

/**
* Enqueue stylesheet and js
*/
function prefix_add_lpe_stylesheet() {
    wp_register_style( 'lpe-style', plugins_url('css/style.css', __FILE__) );
    wp_enqueue_style( 'lpe-style' );
}

function prefix_add_lpe_script() {
    wp_register_script( 'lpe-script', plugins_url('js/prettify.js', __FILE__) );
    wp_enqueue_script( 'lpe-script' );
}   


/**
 * Add Prettify action to the footer.
 */
function lpe_header() {
    ?>
    <script type="text/javascript">
        window.onload = function(){prettyPrint();}
    </script>
    <?php
}
add_action('wp_head', 'lpe_header');
