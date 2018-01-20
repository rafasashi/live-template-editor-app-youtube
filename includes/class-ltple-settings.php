<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class LTPLE_App_Youtube_Settings {

	/**
	 * The single instance of LTPLE_App_Youtube_Settings.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * The main plugin object.
	 * @var 	object
	 * @access  public
	 * @since 	1.0.0
	 */
	public $parent = null;

	/**
	 * Prefix for plugin settings.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $base = '';

	/**
	 * Available settings for plugin.
	 * @var     array
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = array();

	public function __construct ( $parent ) {
		
		$this->parent = $parent;
		
		$this->plugin 		 	= new stdClass();
		$this->plugin->slug  	= 'live-template-editor-app-youtube';
		
		add_action('ltple_plugin_settings', array($this, 'plugin_info' ) );
		
		add_action('ltple_plugin_settings', array($this, 'settings_fields' ) );
		
		add_action( 'ltple_admin_menu' , array( $this, 'add_menu_items' ) );	
	}
	
	public function plugin_info(){
		
		$this->parent->settings->addons['app-youtube-plugin'] = array(
			
			'title' 		=> 'App Youtube Plugin',
			'addon_link' 	=> 'https://github.com/rafasashi/live-template-editor-app-youtube',
			'addon_name' 	=> 'live-template-editor-app-youtube',
			'source_url' 	=> 'https://github.com/rafasashi/live-template-editor-app-youtube/archive/master.zip',
			'description'	=> 'Wordpress.com API integrator for Live Template Editor',
			'author' 		=> 'Rafasashi',
			'author_link' 	=> 'https://profiles.wordpress.org/rafasashi/',
		);		
	}

	/**
	 * Build settings fields
	 * @return array Fields to be displayed on settings page
	 */
	public function settings_fields () {
		
		$settings = [];
		
		$settings['youtube'] = array(
			'title'					=> __( 'Youtube', $this->plugin->slug ),
			'description'			=> __( 'Main Wordpress.com API settings', $this->plugin->slug ),
			'fields'				=> array(
				array(
					'id' 			=> 'wpcom_main_account',
					'label'			=> __( 'Main account' , $this->plugin->slug ),
					'description'	=> 'Main connected Youtube account',
					'type'			=> 'dropdown_main_apps',
					'app'			=> 'youtube',
				)			
			)
		);		
		
		if( !empty($settings) ){
		
			foreach( $settings as $slug => $data ){
				
				if( isset($this->parent->settings->settings[$slug]['fields']) && !empty($data['fields']) ){
					
					$fields = $this->parent->settings->settings[$slug]['fields'];
					
					$this->parent->settings->settings[$slug]['fields'] = array_merge($fields,$data['fields']);
				}
				else{
					
					$this->parent->settings->settings[$slug] = $data;
				}
			}
		}
	}
	
	/**
	 * Add settings page to admin menu
	 * @return void
	 */
	public function add_menu_items () {
		
		//add menu in youtube dashboard
		/*
		add_submenu_page(
			'live-template-editor-client',
			__( 'App Youtube', $this->plugin->slug ),
			__( 'App Youtube', $this->plugin->slug ),
			'edit_pages',
			'edit.php?post_type=post'
		);
		*/
	}
}
