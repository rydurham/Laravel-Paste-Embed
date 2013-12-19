<?php
/**
 * @package LaravePasteEmbed
 * @version 1.2 
 */
/*
Plugin Name: Laravel Paste Embed
Plugin URI: http://rydurham.com/plugins/laravel-paste-embed/
Description: A plugin to embed pages & snippets from paste.laravel.com into a wordpress site. 
Author: Ryan Durham
Version: 1.2
Author URI: http://rydurham.com
License: GPL2

Thanks to Dayle Rees & co. for the styles: http://paste.laravel.com/css/style.css

Thanks to Taylor Dewey (@tddewey) for the advice! 

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

function lpe_func( $atts ) {
    //Retrieve the shortcode attributes:
    $paste = $atts['paste'];
    $caption = $atts['caption'];
    $width = ( isset($atts['width']) ) ? $atts['width'] : '100%';
    $height = ( isset($atts['height']) ) ? $atts['height'] : 'auto';

    //Make sure there is a Paste URL destination.
    if ( ! $paste ) {
        return '';
    }

    //Prep for WP_Http action
    if( ! class_exists( 'WP_Http' ) )
        include_once( ABSPATH . WPINC. '/class-http.php' );
    
    //Pull data from paste.laravel.com
    $url = esc_url("http://paste.laravel.com/$paste");
    $response = wp_remote_get($url);
    $response_code = wp_remote_retrieve_response_code( $response );

    if ( $response_code != '200' ) {
        $error_message = "Paste Error: " .  $response['response']['message'];
        return $error_message;
    } else {
        //Parse the body of the response to pull out the relevant content.
        $dom = new DOMDocument();
        $html = $dom->loadHTML( wp_remote_retrieve_body( $response ) );
        $code = $dom->getElementsByTagName( 'code' )->item( 0 )->nodeValue;
        
        //Capture the paste output in an object buffer.
        ob_start();
            echo "<div class=\"lpe_shell\" style=\"width: $width\">";
            if ($caption) {
                echo "<div class=\"lpe_title\">$caption</div>";
            }
            echo "<div class=\"lpe_link\"><a href=\"$url\" target=\"_blank\">link</a> </div>";
            echo "<pre class=\"prettyprint linenums \" style=\"height: $height;\">";
            echo htmlentities($code);
            echo "</pre></div>";
        return ob_get_clean();
    }
    
}
add_shortcode( 'lpe', 'lpe_func' );


/**
* Enqueue stylesheet and js - only when needed.
*/

add_filter('the_posts', 'lpe_conditionally_enqueue'); // the_posts gets triggered before wp_head
function lpe_conditionally_enqueue($posts){
    if (empty($posts)) return $posts;
 
    $shortcode_found = false; // use this flag to see if styles and scripts need to be enqueued
    foreach ($posts as $post) {
        if (stripos($post->post_content, '[lpe ') !== false) {
            $shortcode_found = true; // bingo!
            break;
        }
    }
 
    if ($shortcode_found) {
        // enqueue here
        prefix_add_lpe_stylesheet();
        prefix_add_lpe_script();
    }
 
    return $posts;

    //from http://beerpla.net/2010/01/13/wordpress-plugin-development-how-to-include-css-and-javascript-conditionally-and-only-when-needed-by-the-posts/
}

function prefix_add_lpe_stylesheet() {
    wp_register_style( 'lpe-style', plugins_url( 'css/style.css', __FILE__ ) );
    wp_enqueue_style( 'lpe-style' );
}

function prefix_add_lpe_script() {
    wp_register_script( 'lpe-script', plugins_url( 'js/prettify.js' , __FILE__ ) );
    wp_enqueue_script( 'lpe-script' );
}   


/**
 * Add Prettify action to the header.
 */
function lpe_header() {
    ?>
    <script type="text/javascript">
        window.onload = function(){prettyPrint();}
    </script>
    <?php
}
add_action( 'wp_footer', 'lpe_header' );
