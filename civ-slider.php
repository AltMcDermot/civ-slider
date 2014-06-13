<?php
/**
 * Plugin Name: Simple Slider
 * Plugin URI: http://lastweak.com
 * Description: Simple slider
 * Version: 0.01
 * Author: Melton S.
 * Author URI: http://lastweak.com
 * License: merp
 */
wp_register_style( 'plugin-style', plugins_url('slide.css', __FILE__) );
    wp_enqueue_style( 'plugin-style' );
    wp_enqueue_script( 'slider-name', plugins_url('jquery.cycle2.min.js', __FILE__), array('jquery') );

include __DIR__ . "/slider.php";
?>