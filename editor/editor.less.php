<?php

class EditorLess {
	
	function __construct() {}
		
	function enqueue_styles() {
		
		return; // REMOVE TO ENABLE
		
		
		// need to enqueue a STATIC vars file.
		// we need to enqueue all available less files.
		
		// add constants + imports to head
		add_action( 'wp_head', array( &$this, 'add_constants' ), 4);

		// remove main compiles-css
		add_action( 'wp_print_styles', array( &$this, 'dequeue_css' ), 12 );

		// add stylesheet/less to wp_enqueue_styles
		add_filter( 'style_loader_tag', array( &$this, 'enqueue_less_styles' ), 5, 2);


//		add_action( 'wp_head', array( &$this, 'make_imports' ), 3);

		
	// these were uses to enqueue the raw files, didnt work.
	//	$this->enqueue_core_less();

		$this->enqueue_less();
		$this->create_file();	
	}
	
	function add_constants() {		
		printf( "<style id='pl-custom-less' type='text/less'>%s</style>\n",
		$this->get_constants()
		);
	}
	
	function get_constants() {

		$pless = new PageLinesLess;
		$vars_array = $pless->constants;
		$vars = '';
		foreach($vars_array as $key => $value)
			$vars .= sprintf('@%s:%s%s;%s', $key, " ", $value, "\n");

		return $vars;
	}



	function dequeue_css() {
		
		wp_deregister_style( 'pagelines-less' );
	}
	
	function get_core_less() {
		
		$less = array( 'variables', 'mixins', 'colors' );
	

		return array( 

			'variables',
			'mixins',
			'colors',
			'reset', 
			'pl-core', 
			'pl-wordpress',
			'pl-plugins',
			'grid',
			'alerts',
			'labels-badges',
			'tooltip-popover',
			'buttons',
			'type',
//			'dropdowns',
			'accordion',
			'carousel',
			'responsive',
			'navs',
			'modals',
			'thumbnails',
			'component-animations',
			'utilities',
			'pl-objects',
			'pl-tables',
			'pl-editor',
			'wells',
//			'forms',
			'breadcrumbs', 
			'close', 
			'pager', 
			'pagination',
//			'progress-bars', 
			'icons',
			'fileupload'
			);

		global $render_css;
		return array_merge( $less, $render_css->get_core_lessfiles() );
	}
	

	

	function create_file() {

		$less = $this->get_constants();

		$core_files = $this->get_core_less();

		foreach( $core_files as $k => $file ) {

			$less .= pl_file_get_contents( trailingslashit( PL_CORE_LESS ) . $file . '.less' );

		}

		$less .= $this->get_sections();

		$this->write_css_file( $less );
	}


	function enqueue_less() {

		wp_enqueue_style( 'editor-less', $this->get_css_dir( 'url' ) . '/editor.less' );
	}

	function enqueue_less_styles($tag, $handle) {
	    global $wp_styles;
	    $match_pattern = '/\.less$/U';
	    if ( preg_match( $match_pattern, $wp_styles->registered[$handle]->src ) ) {
	        $handle = $wp_styles->registered[$handle]->handle;
	        $media = $wp_styles->registered[$handle]->args;
	        $href = $wp_styles->registered[$handle]->src;
	        $rel = isset($wp_styles->registered[$handle]->extra['alt']) && $wp_styles->registered[$handle]->extra['alt'] ? 'alternate stylesheet' : 'stylesheet';
	        $title = isset($wp_styles->registered[$handle]->extra['title']) ? "title='" . esc_attr( $wp_styles->registered[$handle]->extra['title'] ) . "'" : '';

	        $tag = "<link rel='stylesheet/less' href='$href' type='text/css'>\n";
	    }
	    return $tag;
	}

	function write_css_file( $txt ){

		add_filter('request_filesystem_credentials', '__return_true' );

		$method = '';
		$url = 'themes.php?page=pagelines';
				
		$folder = $this->get_css_dir( 'path' );
		$file = 'editor.less';
		
		if( !is_dir( $folder ) )
			wp_mkdir_p( $folder );

		include_once( ABSPATH . 'wp-admin/includes/file.php' );

	if ( is_writable( $folder ) ){
		$creds = request_filesystem_credentials($url, $method, false, false, null);
		if ( ! WP_Filesystem($creds) )
			return false;
	}

			global $wp_filesystem;
			if( is_object( $wp_filesystem ) )
				$wp_filesystem->put_contents( trailingslashit( $folder ) . $file, $txt, FS_CHMOD_FILE);
			else
				return false;

	}
		function get_css_dir( $type = '' ) {
		
		$folder = wp_upload_dir();
		
		if( 'path' == $type )
			return trailingslashit( $folder['basedir'] ) . 'pagelines'; 
		else
			return trailingslashit( $folder['baseurl'] ) . 'pagelines'; 	
	}
	
		function get_sections() {
		
		$out = '';
		global $load_sections;
		$available = $load_sections->pagelines_register_sections( true, true );

		$disabled = get_option( 'pagelines_sections_disabled', array() );

		/*
		* Filter out disabled sections
		*/
		foreach( $disabled as $type => $data )
			if ( isset( $disabled[$type] ) )
				foreach( $data as $class => $state )
					unset( $available[$type][ $class ] );

		/*
		* We need to reorder the array so sections css is loaded in the right order.
		* Core, then pagelines-sections, followed by anything else. 
		*/
		$sections = array();
		$sections['parent'] = $available['parent'];
		unset( $available['parent'] );
		$sections['child'] = (array) $available['child'];
		unset( $available['child'] );
		if ( is_array( $available ) )
			$sections = array_merge( $sections, $available );
		foreach( $sections as $t ) {
			foreach( $t as $key => $data ) {
				if ( $data['less'] && $data['loadme'] ) {						
					if ( is_file( $data['base_dir'] . '/style.less' ) )
						$out .= pl_file_get_contents( $data['base_dir'] . '/style.less' );
					elseif( is_file( $data['base_dir'] . '/color.less' ))
						$out .= pl_file_get_contents( $data['base_dir'] . '/color.less' );	
				}
			}	
		}
		return apply_filters('pagelines_lesscode', $out);
	}
/*
	// experimenting with escaping the less variables...
	function escape( $value ) {
	
	
	return $value;
		
		if( preg_match( '#"#', $value ) )		
			return '~' . rtrim( $value, '"' ) . '"';
		else
			return $value;
	}

	function enqueue_core_less() {
		
		foreach ( $this->get_core_less() as $k => $file ) {
			
			$id = $file;
			$file 	= sprintf( '%s.less', $file );
			$parent = sprintf( '%s/%s', PL_CORE_LESS, $file );
			$parent_url = sprintf( '%s/%s', PL_CORE_LESS_URL, $file );
			$child 	= sprintf( '%s/%s', PL_CHILD_LESS, $file );
			$child_url = sprintf( '%s/%s', PL_CHILD_LESS_URL, $file );
			if ( is_file( $child ) )
				wp_enqueue_style( $id, $child_url );
			else
				wp_enqueue_style( $id, $parent_url );
		}
		
	}

		function make_imports() {
		
		$files = $this->get_core_less();
		$out = '';
		
		foreach ( $files as $k => $file ) {
			

			$out .= sprintf( "<style id='pl-custom-less' type='text/less'>%s</style>\n",
				sprintf( '%s@import "%s/%s";', "\n", PL_CORE_LESS_URL, $file )
			);
		}
		
		echo $out;
	}
*/
} // EditorLess