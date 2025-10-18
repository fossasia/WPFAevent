<?php
class WPFA_Templates {
  private static \ = [
    'page-landing.php'         => 'WPFA – Landing',
    'page-speakers.php'        => 'WPFA – Speakers',
    'page-events.php'          => 'WPFA – Events',
    'page-past-events.php'     => 'WPFA – Past Events',
    'page-schedule.php'        => 'WPFA – Schedule',
    'page-code-of-conduct.php' => 'WPFA – Code of Conduct',
  ];

  public static function init() {
    add_filter( 'theme_page_templates', [ __CLASS__, 'register' ] );
    add_filter( 'template_include',     [ __CLASS__, 'load' ] );
  }

  public static function register( \ ) {
    foreach ( self::\ as \ => \ ) { \[\] = \; }
    return \;
  }

  public static function load( \ ) {
    if ( is_singular( 'page' ) ) {
      \ = get_page_template_slug( get_queried_object_id() );
      if ( isset( self::\[ \ ] ) ) {
        \ = WPFA_EVENT_PLUGIN_DIR . 'public/templates/' . \;
        if ( file_exists( \ ) ) { return \; }
      }
    }
    return \;
  }
}
