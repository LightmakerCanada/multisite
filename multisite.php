<?php
/**
 * Plugin Name: JSON REST API for Multisite
 * Description: Based on original code by Ryan McCue.
 * Author: Adam Biggs
 * Author URI: http://lightmaker.com
 * Version: 0.1.1
 */

if ( ! is_multisite() )
  return;

$wp_json_multisite = new WP_JSON_Multisite();

class WP_JSON_Multisite {

  /**
   * Original endpoints before prefixing
   *
   * @var array
   */
  protected $original = array();

  /**
   * Route prefix for existing handlers
   *
   * @var string
   */
  protected $prefix = '/sites/(?P<site>[\w.]+)';


  /**
   * Register our hooks
   */
  public function __construct() {
    add_filter( 'json_endpoints', array( $this, 'change_endpoints' ), 1000 );
    add_filter( 'json_dispatch_args', array( $this, 'switch_to_blog' ), 1000 );
  }


  /**
   * Change the endpoints to suit multisite
   *
   * @param array $original Original endpoints
   * @return array New endpoints
   */
  public function change_endpoints( $original ) {
    $this->original = $original;

    $routes = array(
      // Preserve the index
      '/' => $original['/'],

      // Add our site-wide routes
      '/sites' => array(
        array( array( $this, 'getSites' ), WP_JSON_Server::READABLE ),
        array( array( $this, 'newSite' ), WP_JSON_Server::CREATABLE | WP_JSON_Server::ACCEPT_JSON ),
      ),
    );

    // Prefix the existing endpoints
    foreach ( $original as $route => $handlers ) {
      if ( $route === '/' ) {
        // The root route for sites is a Site entity
        $routes[ $this->prefix ] = array(
          array( array( $this, 'getSite' ), WP_JSON_Server::READABLE ),
          array( array( $this, 'editSite' ), WP_JSON_Server::EDITABLE | WP_JSON_Server::ACCEPT_JSON ),
        );
      }
      else {
        // All other routes can be passed through
        $routes[ $this->prefix . $route ] = $handlers;
      }
    }

    return $routes;
  }


  /**
   * Switch to the correct site when routing
   *
   * @param array $args
   * @return array
   */
  public function switch_to_blog( $args ) {
    // If we're on a network-wide route, bounce
    if ( empty( $args['site'] ) ) {
      return $args;
    }

    if ( is_numeric( $args['site'] ) ) {
      $site_id = absint( $args['site'] );
    }
    else {
      $site_id = get_id_from_blogname( $args['site'] );
    }

    if ( ! $site_id ) {
      return new WP_Error( 'json_multisite_invalid_site', __( 'Invalid site specified' ), array( 'status' => 404 ) );
    }

    // Perform the actual switch
    switch_to_blog( $site_id );

    return $args;
  }


  /**
   * Get all sites with a subset of their data
   *
   * @return WP_Error
   */
  public function getSites() {
    return new WP_Error( 'json_multisite_not_implemented', __( 'Not implemented' ), array( 'status' => 501 ) );
  }


  /**
   * Get details about a specifc site by it's ID
   *
   * @return array
   */
  public function getSite( $site ) {
    //return get_blog_details();
    return wp_load_alloptions();
  }


  /**
   * Create a new site
   *
   * @param array $data POSTed site data
   * @return int The new site's ID
   */
  public function newSite( $data ) {
    $domain  = $data['domain'];
    $path    = $data['path'];
    $title   = $data['title'];
    $user_id = $data['user_id'];
    $meta    = $data['meta'];
    $site_id = $data['site_id'];
    return wpmu_create_blog( $domain, $path, $title, $user_id, $meta, $site_id );
  }


  /**
   * Edit a site's details
   *
   * @param string|int $site Site name/ID
   * @return WP_Error
   */
  public function editSite( $site ) {
    return new WP_Error( 'json_multisite_not_implemented', __( 'Not implemented' ), array( 'status' => 501 ) );
  }
}
