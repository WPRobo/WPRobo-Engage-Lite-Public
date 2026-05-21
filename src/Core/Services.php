<?php
namespace WPRobo_Engage_Lite\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit; }

use WPRobo_Engage_Lite\Admin\Campaign_Cpt;
use WPRobo_Engage_Lite\Admin\Menu;
use WPRobo_Engage_Lite\Admin\Meta_Box;
use WPRobo_Engage_Lite\Admin\Tools;
use WPRobo_Engage_Lite\Api\Rest_Api;
use WPRobo_Engage_Lite\Public\Display_Engine;
use WPRobo_Engage_Lite\Public\Enqueue;
use WPRobo_Engage_Lite\Includes\Lead_Handler;
use WPRobo_Engage_Lite\Includes\Analytics_Handler;

class Services {

	public function register(): void {
		$this->register_admin_services();
		$this->register_public_services();
		$this->register_api_services();
	}

	private function register_admin_services(): void {
		$campaign_cpt = new Campaign_Cpt();
		add_action( 'init', array( $campaign_cpt, 'register_cpt' ) );

		$menu = new Menu();
		add_action( 'admin_menu', array( $menu, 'register_menu' ) );
		add_filter( 'post_row_actions', array( $menu, 'modify_campaign_row_actions' ), 10, 2 );
		add_filter( 'parent_file', array( $menu, 'highlight_parent_menu' ) );
		add_filter( 'submenu_file', array( $menu, 'highlight_submenu_file' ) );
		add_action( 'admin_init', array( $menu, 'redirect_add_new_to_templates' ) );
		add_action( 'admin_init', array( $menu, 'handle_template_actions' ) );

		$meta_box = new Meta_Box();
		add_action( 'add_meta_boxes', array( $meta_box, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $meta_box, 'save_meta_data' ) );

		$tools = new Tools();
		add_action( 'admin_init', array( $tools, 'handle_export_action' ) );
		add_action( 'admin_init', array( $tools, 'handle_import_action' ) );
		add_filter( 'upload_mimes', array( $tools, 'allow_json_uploads' ) );

	}

	private function register_public_services(): void {
		$enqueue = new Enqueue();
		add_action( 'admin_enqueue_scripts', array( $enqueue, 'enqueue_admin_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $enqueue, 'enqueue_public_assets' ) );

		$display_engine = new Display_Engine();
		add_action( 'wp_footer', array( $display_engine, 'run' ) );

		$lead_handler = new Lead_Handler();
		add_action( 'wp_ajax_wprobo_engage_submission', array( $lead_handler, 'handle_submission' ) );
		add_action( 'wp_ajax_nopriv_wprobo_engage_submission', array( $lead_handler, 'handle_submission' ) );

		$analytics_handler = new Analytics_Handler();
		add_action( 'wp_ajax_wprobo_engage_track_impression', array( $analytics_handler, 'track_impression' ) );
		add_action( 'wp_ajax_nopriv_wprobo_engage_track_impression', array( $analytics_handler, 'track_impression' ) );
	}

	private function register_api_services(): void {
		$rest_api = new Rest_Api();
		add_action( 'rest_api_init', array( $rest_api, 'register_routes' ) );
	}
}
