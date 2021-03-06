<?php

namespace Roots\Sage\Extras;

use Roots\Sage\Config;

/**
 * Add <body> classes
 */
function body_class($classes) {
  // Add page slug if it doesn't exist
  if (is_single() || is_page() && !is_front_page()) {
    if (!in_array(basename(get_permalink()), $classes)) {
      $classes[] = basename(get_permalink());
    }
  }

  // Add class if sidebar is active
  // if (Config\display_sidebar()) {
  //   $classes[] = 'sidebar-primary';
  // }
  // if(get_field('page_layout')) {
  //   $classes[] = get_field('page_layout');
  // }

  if ( Config\page_should_have_sidebar() ) {
    $classes[] = Config\page_should_have_sidebar();
  }

  return $classes;
}
add_filter('body_class', __NAMESPACE__ . '\\body_class');

/**
 * Clean up the_excerpt()
 */
function excerpt_more() {
  return ' &hellip; <a href="' . get_permalink() . '">' . __('Continued', 'sage') . '</a>';
}
add_filter('excerpt_more', __NAMESPACE__ . '\\excerpt_more');


/**
 * Remove the "{Archive type}: " text from get_the_archive_title()
 *
 * Archive title function returns title strings with the archive
 * type prepended to the actual title.
 *
 * @see https://developer.wordpress.org/reference/functions/get_the_archive_title/
 */
function clean_archive_titles($title) {
  $title = explode(': ', $title, 2);

  return $title[1];
}
add_filter('get_the_archive_title',  __NAMESPACE__ . '\\clean_archive_titles');


function sage_wrap_base_cpts($templates) {
  $cpt = get_post_type(); // Get the current post type
  if ($cpt) {
     array_unshift($templates, 'base-' . $cpt . '.php'); // Shift the template to the front of the array
  }
  return $templates; // Return our modified array with base-$cpt.php at the front of the queue
}
add_filter('sage/wrap_base', __NAMESPACE__ . '\\sage_wrap_base_cpts'); // Add our function to the sage_wrap_base filter


/**
 * Specific archives use isotope.js for post filtering
 * This adds the appropriate filter widget to those archives
 *
 * @see  templates/widget-isotope_filters.php
 * @see  Extra/add_isotope_filter_widget()
 */
function sidebar_isotope_filters() {
  $filterable_page = false;

  if ( is_post_type_archive( 'employee' ) ) {
    $filterable_page = true;
    $filter_by_title = 'Team';
    $filter_by_slug = 'team';
  }
  if ( is_post_type_archive( 'module' ) ) {
    $filterable_page = true;
    $filter_by_title = 'Module Feature';
    $filter_by_slug = 'feature';
  }
  if ( is_post_type_archive( 'product' ) ) {
    $filterable_page = true;
    $filter_by_title = 'Product Type';
    $filter_by_slug = 'type';
  }

  if (!$filterable_page) { return; }

  add_isotope_filter_widget($filter_by_title, $filter_by_slug);
}
add_action('before_sidebar', __NAMESPACE__ . '\\sidebar_isotope_filters');

/**
 * Helper function to add isotope filter widget
 *
 * @param string $filter_by_title Appears in widget title
 * @param string $filter_by_slug  Term slug to pull tags form
 */
function add_isotope_filter_widget(  $filter_by_title, $filter_by_slug ) {
  include( locate_template('templates/widget-isotope_filters.php') );
}

function generate_content_excerpt($content, $word_limit){
  $content_excerpt = strip_tags( $content );
  $content_excerpt = explode( ' ', $content_excerpt, $word_limit+1 );

  if(count($content_excerpt) > $word_limit) {
    array_pop($content_excerpt);
  }

  return implode(' ', $content_excerpt) . '...';
}

/**
 * Enable shortcodes in sidebar widgets
 */
add_filter('widget_text', 'do_shortcode');

/**
 * Remove post limit from specific archives
 */
function archive_no_post_limit($query) {
  if ( $query->is_post_type_archive( array('module','product','employee') ) ) {
    $query->set('posts_per_page', -1);
    $query->set('nopaging', true);
  }
  return $query;
}
add_filter('pre_get_posts', __NAMESPACE__ . '\\archive_no_post_limit');

/**
 * Excerpt Length
 */
function set_custom_excerpt_length( $length ) {
  if ( get_field( 'post_excerpt_length', 'options' ) ) {
    $length = get_field( 'post_excerpt_length', 'options' );
  }

  return $length;
}
add_filter( 'excerpt_length', __NAMESPACE__ . '\\set_custom_excerpt_length', 999 );

//add .current-cat css class to categories of single post in 'categories' widget//
add_filter('wp_list_categories', __NAMESPACE__ . '\\highlight_single_posts_categories');
function highlight_single_posts_categories( $output ) {
  global $post;
  if ( is_single() ) {
    $categories = wp_get_post_categories( $post->ID );
    if ( $categories ) {
      foreach( $categories as $value ) {
        if ( preg_match( '#item-' . $value . '">#', $output ) ) {
          $output = str_replace( 'item-' . $value . '">', 'item-' . $value . ' current-cat">', $output );
        }
      }
    }
  }

  return $output;
}
