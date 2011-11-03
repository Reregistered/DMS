<?php

class ExtensionSections extends PageLinesExtensions {
	
	/**
	 * Section install tab.
	 * 
	 */
 	function extension_sections( $tab = '', $mode = 'install' ) {
 		
		if($tab == 'child' && !is_child_theme())
			return $this->ui->extension_banner( __( 'A PageLines child theme is not currently activated', 'pagelines' ) );
	
		if ( !$this->has_extend_plugin() )
			return $this->ui->get_extend_plugin( $this->has_extend_plugin('status'), $tab );

		$list = array();
		$type = 'section';
				
		if ( 'install' == $mode ) {
			$sections = $this->get_latest_cached( 'sections' );

			if ( !is_object( $sections ) ) 
				return $sections;

			foreach( $sections as $key => $ext ) {
			
				$ext = (array) $ext;
			
				if( !$this->show_in_tab( 'section', $key, $ext, $tab ) )
					continue; 
		
				$list[$key] = $this->master_list( $type, $key, $ext, $tab );	
			}
		} // end install mode
		
		if ( 'installed' == $mode ) {
			
			global $load_sections;
			
			// Get sections
	 		$available = $load_sections->pagelines_register_sections( true, true );

	 		$disabled = get_option( 'pagelines_sections_disabled', array() );

			$upgradable = $this->get_latest_cached( 'sections' );

	 		foreach( $available as $section ) {

				$section = self::sort_status( $section, $disabled, $available, $upgradable );

	 			foreach( $section as $key => $ext ) { // main loop

					if( !$this->show_in_tab( 'section', $key, $ext, $tab ) )
						continue;

					$list[ basename( $ext['base_dir'] ) ] = $this->master_list( $type, $key, $ext, $tab );
				}
	 		}	
		
		} // end installed mode

		return $this->ui->extension_list( array( 'list' => $list, 'tab' => $tab, 'type' => 'sections' ) );
 	}


	function sort_status( $section, $disabled, $available, $upgradable) {
		
		foreach( $section as $key => $ext) {
			$section[$key]['status'] = ( isset( $disabled[ $ext['type'] ][ $ext['class'] ] ) ) ? 'disabled' : 'enabled';
			$section[$key] = self::check_version( $section[$key], $upgradable );
			$section[$key]['class_exists'] = ( isset( $available['child'][ $ext['class'] ] ) || isset( $available['custom'][ $ext['class'] ] ) ) ? true : false;
		}

		return pagelines_array_sort( $section, 'name' ); // Sort Alphabetically
	}

	function check_version( $ext, $upgradable ) {
		
		if ( isset( $ext['base_dir'] ) ) {
			$upgrade = basename( $ext['base_dir'] );
			if ( isset( $upgradable->$upgrade->version ) ) {
				$ext['apiversion'] = ( isset( $upgradable->$upgrade->version ) ) ? $upgradable->$upgrade->version : '';
				$ext['slug'] = $upgradable->$upgrade->slug;
			}
		}
		return $ext;
	}
}