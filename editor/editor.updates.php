<?php
/**
 *  DMS 3rd party plugin/theme updating class.
 *
 *  @package DMS
 *  @since 1.0
 *
 *
 */
class PageLinesEditorUpdates {
	
	function __construct() {

		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'injectUpdatePlugins' ), 999 );		
		add_filter( 'site_transient_update_themes', array( $this, 'injectUpdateThemes' ), 999 );
		add_action( 'load-update-core.php', array( $this, 'del_store_data' ) );
		add_filter( 'plugins_api', array( $this, 'check_info' ), 10, 3 );
	}
	
	function del_store_data() {
		global $storeapi;
		if( ! is_object( $storeapi ) )
			$storeapi = new EditorStoreFront;
		
		$storeapi->del( 'store_mixed' );
	}
	
	function push_update_message() {

		if( ! pl_is_pro() && ! has_action( 'admin_notices', array( $this, 'push_update_message_text' ) ) )
			add_action( 'admin_notices', array( $this, 'push_update_message_text' ) );
	}
	
	function push_update_message_text() {
		
		require_once( ABSPATH . 'wp-admin/includes/screen.php' );
		$screen = get_current_screen();
		$account_set_url = admin_url( 'admin.php?page=PageLines-Admin');

		if( ! in_array( $screen->id, array( 'update-core', 'dashboard', 'toplevel_page_PageLines-Admin', 'themes' ) ) )
			return false;

		printf( '<div class="updated"><p>%s</p></div>',
		sprintf( __( 'There are available updates for some PageLines Products, <a href="%s">Click Here</a> to login and get them updated.', 'pagelines' ), $account_set_url )
		);
	}
	
	function injectUpdateThemes( $updates ) {

		global $storeapi;
		if( ! is_object( $storeapi ) )
			$storeapi = new EditorStoreFront;
		$mixed_array = $storeapi->get_latest();
		$themes = $this->get_pl_themes();
		
		foreach( $themes as $slug => $data ) {
			
			if( ! isset( $mixed_array[$slug]['version'] ) )
				continue;
				
			if( $mixed_array[$slug]['version'] > $data['Version'] ) {
				if( $this->pl_is_pro() )
					$updates->response[$slug] = $this->build_theme_array( $mixed_array[$slug], $data );
				else
					$this->push_update_message();
			} else {
				if( is_object( $updates ) && isset( $updates->response ) && isset( $updates->response[$slug] ) ) {					
					unset( $updates->response[$slug] );
				}
			}
		}
		return $updates;
	}

	function injectUpdatePlugins( $updates ) {
		
		global $pl_plugins;
		global $storeapi;
		if( ! is_object( $storeapi ) )
			$storeapi = new EditorStoreFront;

		$mixed_array = $storeapi->get_latest();
		
		if( ! $pl_plugins )
			$pl_plugins = $this->get_pl_plugins();		

		if( ! is_array( $pl_plugins ) || empty( $pl_plugins ) )
			return $updates;

		foreach( $pl_plugins as $path => $data ) {
			$slug = dirname( $path );

			// If PageLines plugin has no API data pass on it.
			if( ! isset( $mixed_array[$slug] ) ) {
				if( is_object( $updates ) && isset( $updates->response ) && isset( $updates->response[$path] ) ) {
					unset( $updates->response[$path] );
				}
			}

			// If PageLines plugin has API data and a version check it and build a response.
			if( isset( $mixed_array[$slug]['version'] ) && ( $mixed_array[$slug]['version'] > $data['Version'] ) ) {
				if( $this->pl_is_pro() )
					$updates->response[$path] = $this->build_plugin_object( $mixed_array[$slug], $data );
				else
					$this->push_update_message();	
			} else {
				if( is_object( $updates ) && isset( $updates->response ) && isset( $updates->response[$path] ) ) {
					unset( $updates->response[$path] );
					continue;
				}
			}
		}	
		return $updates;
	}
	
	function build_theme_array( $api_data, $data ) {
		
		$object = array();
		$object['new_version'] = $api_data['version'];
		$object['upgrade_notice'] = '';
		$object['url'] = $api_data['overview'];
		$object['package'] = $this->build_url( $api_data, $data );
		return $object;
	}
	
	function build_plugin_object( $api_data, $data ) {

		$object = new stdClass;		
//		$object->id = rand();
		$object->slug = $api_data['slug'];
		$object->new_version = $api_data['version'];
		$object->upgrade_notice = 'This is a PageLines Premium plugin.';
		$object->package = $this->build_url( $api_data, $data );
		$object->download_link = $this->build_url( $api_data, $data );
		return $object;
	}
	
	function check_info( $false, $action, $arg ){
			
		global $storeapi;
		if( ! is_object( $storeapi ) )
			$storeapi = new EditorStoreFront;

		$mixed_array = $storeapi->get_latest();
		
		if( is_object( $arg ) && isset( $arg->slug ) && isset( $mixed_array[$arg->slug] ) ) {
			$data = $mixed_array[$arg->slug];

			$obj = new stdClass();
			      $obj->slug = $data['slug'];
			      $obj->plugin_name = $data['name'];
			      $obj->new_version = $data['version'];
			      $obj->requires = '3.6';
			      $obj->tested = '3.7';
			      $obj->downloaded = 0; // needs API update....
			      $obj->last_updated = $data['last_mod'];
			      $obj->sections = array(
			        'description' => $this->build_desc( $data ),
			        'changelog' => $this->build_logs( $data )
			      );

			      $obj->homepage = $data['overview'];
			      return $obj;
		} 
		return false;
	}
	
	function build_logs( $data ) {
		
		$logs = explode( '*', $data['changelog'] );
		
		if( ! is_array( $logs ) || empty( $logs ) )
			return 'Nothing to see here!';
		
		$out = '<ul>';
		
		foreach( $logs as $k => $log ) {
			if( '' != $log )
				$out .= sprintf( '<li>%s</li>', $log );
		}
		
		return $out . '</ul>'; 
	}
	
	function build_desc( $data ) {
		
		$desc = sprintf( "<h1><img src='%s' /></h1>%s", $data['thumb'], $data['description'] );
		return $desc;
	}
	
	function build_url( $api_data, $data ) {				
		return sprintf( 'http://www.pagelines.com/api/store/v3/%s.zip?%s', $api_data['slug'], rand() );
	}
		
	function get_pl_plugins() {
		
		global $pl_plugins;
		$default_headers = array(
			'Version'	=> 'Version',
			'v3'	=> 'v3',
			'PageLines'	=> 'PageLines'
			);
	
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		
		$plugins = get_plugins();

		foreach ( $plugins as $path => $data ) {

			$fullpath = sprintf( '%s%s', trailingslashit( WP_PLUGIN_DIR ), $path );		
			$plugins[$path] = get_file_data( $fullpath, $default_headers );
		}
		
		foreach ( $plugins as $path => $data ) {
			if( ! $data['PageLines'] )
				unset( $plugins[$path] );
		}
		return $plugins;
	}
	
	function get_pl_themes() {
		$installed_themes = pl_get_themes();
		
		foreach( $installed_themes as $slug => $theme ) {

			if( 'dms' != $theme['Template'] )
				unset( $installed_themes[$slug]);
			if( 'dms' == $slug )
				unset( $installed_themes[$slug]);
		}
		return $installed_themes;
	}

	function pl_is_pro(){	
		// editor functions not loaded yet so we need this
		$status = get_option( 'dms_activation', array( 'active' => false, 'key' => '', 'message' => '', 'email' => '' ) );

		$pro = (isset($status['active']) && true === $status['active']) ? true : false;

		return $pro;	
	}
}

new PageLinesEditorUpdates;