<?php

namespace WPRobo_Engage_Lite\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; }

/**
 * Class Rule_Templates
 *
 * Provides predefined rule templates for common targeting scenarios.
 * Users can apply these templates with one click to quickly set up common campaign targeting rules.
 *
 * @package WPRobo_Engage_Lite\Admin
 */
class Rule_Templates {

	/**
	 * Returns all available rule templates.
	 *
	 * @since 1.0.0
	 * @return array Array of rule templates.
	 */
	public static function get_templates(): array {
		return array(
			'mobile_social'        => array(
				'name'        => __( 'Mobile Social Media Traffic', 'wprobo-engage-lite' ),
				'description' => __( 'Target mobile visitors from social media platforms', 'wprobo-engage-lite' ),
				'category'    => 'traffic',
				'rules'       => array(
					array(
						'type'   => 'device_mobile',
						'action' => 'show',
						'value'  => '',
					),
					array(
						'type'   => 'referral_social',
						'action' => 'show',
						'value'  => '',
					),
				),
			),
			'engaged_desktop'      => array(
				'name'        => __( 'Engaged Desktop Users', 'wprobo-engage-lite' ),
				'description' => __( 'Target desktop visitors who have spent time and scrolled on your site', 'wprobo-engage-lite' ),
				'category'    => 'engagement',
				'rules'       => array(
					array(
						'type'   => 'device_desktop',
						'action' => 'show',
						'value'  => '',
					),
					array(
						'type'   => 'time_on_site',
						'action' => 'show',
						'value'  => '30',
					),
					array(
						'type'   => 'scroll_depth',
						'action' => 'show',
						'value'  => '50',
					),
				),
			),
			'new_visitors_welcome' => array(
				'name'        => __( 'Welcome New Visitors', 'wprobo-engage-lite' ),
				'description' => __( 'Show welcome message to first-time visitors', 'wprobo-engage-lite' ),
				'category'    => 'visitor',
				'rules'       => array(
					array(
						'type'   => 'visitor_new',
						'action' => 'show',
						'value'  => '',
					),
					array(
						'type'   => 'scroll_depth',
						'action' => 'show',
						'value'  => '25',
					),
				),
			),
			'returning_visitors'   => array(
				'name'        => __( 'Returning Visitors Only', 'wprobo-engage-lite' ),
				'description' => __( 'Target only visitors who have been to your site before', 'wprobo-engage-lite' ),
				'category'    => 'visitor',
				'rules'       => array(
					array(
						'type'   => 'visitor_returning',
						'action' => 'show',
						'value'  => '',
					),
				),
			),
			'logged_out_users'     => array(
				'name'        => __( 'Guests Only (Not Logged In)', 'wprobo-engage-lite' ),
				'description' => __( 'Show only to visitors who are not logged in', 'wprobo-engage-lite' ),
				'category'    => 'user',
				'rules'       => array(
					array(
						'type'   => 'user_logged_out',
						'action' => 'show',
						'value'  => '',
					),
				),
			),
			'hide_from_admin'      => array(
				'name'        => __( 'Hide from Administrators', 'wprobo-engage-lite' ),
				'description' => __( 'Prevent administrators from seeing the campaign', 'wprobo-engage-lite' ),
				'category'    => 'user',
				'rules'       => array(
					array(
						'type'   => 'user_role',
						'action' => 'hide',
						'value'  => 'administrator',
					),
				),
			),
			'blog_readers'         => array(
				'name'        => __( 'Blog Post Readers', 'wprobo-engage-lite' ),
				'description' => __( 'Target visitors reading blog posts', 'wprobo-engage-lite' ),
				'category'    => 'content',
				'rules'       => array(
					array(
						'type'   => 'all_posts',
						'action' => 'show',
						'value'  => '',
					),
					array(
						'type'   => 'scroll_depth',
						'action' => 'show',
						'value'  => '60',
					),
				),
			),
			'homepage_only'        => array(
				'name'        => __( 'Homepage Only', 'wprobo-engage-lite' ),
				'description' => __( 'Show only on the homepage', 'wprobo-engage-lite' ),
				'category'    => 'content',
				'rules'       => array(
					array(
						'type'   => 'homepage',
						'action' => 'show',
						'value'  => '',
					),
				),
			),
			'exit_intent_desktop'  => array(
				'name'        => __( 'Exit Intent (Desktop Only)', 'wprobo-engage-lite' ),
				'description' => __( 'Target desktop users showing exit intent', 'wprobo-engage-lite' ),
				'category'    => 'engagement',
				'rules'       => array(
					array(
						'type'   => 'device_desktop',
						'action' => 'show',
						'value'  => '',
					),
					array(
						'type'   => 'time_on_site',
						'action' => 'show',
						'value'  => '5',
					),
				),
			),
			'search_traffic'       => array(
				'name'        => __( 'Search Engine Traffic', 'wprobo-engage-lite' ),
				'description' => __( 'Target visitors from search engines like Google', 'wprobo-engage-lite' ),
				'category'    => 'traffic',
				'rules'       => array(
					array(
						'type'   => 'referral_search',
						'action' => 'show',
						'value'  => '',
					),
				),
			),
			'frequent_visitors'    => array(
				'name'        => __( 'Frequent Visitors', 'wprobo-engage-lite' ),
				'description' => __( 'Target visitors who have viewed multiple pages', 'wprobo-engage-lite' ),
				'category'    => 'engagement',
				'rules'       => array(
					array(
						'type'   => 'page_views_lifetime',
						'action' => 'show',
						'value'  => '5',
					),
				),
			),
			'mobile_only'          => array(
				'name'        => __( 'Mobile Devices Only', 'wprobo-engage-lite' ),
				'description' => __( 'Show only on mobile phones and tablets', 'wprobo-engage-lite' ),
				'category'    => 'device',
				'rules'       => array(
					array(
						'type'   => 'device_mobile',
						'action' => 'show',
						'value'  => '',
					),
				),
			),
		);
	}

	/**
	 * Gets templates grouped by category.
	 *
	 * @since 1.0.0
	 * @return array Templates grouped by category.
	 */
	public static function get_templates_by_category(): array {
		$templates = self::get_templates();
		$grouped   = array(
			'traffic'    => array(),
			'engagement' => array(),
			'visitor'    => array(),
			'user'       => array(),
			'content'    => array(),
			'device'     => array(),
		);

		foreach ( $templates as $key => $template ) {
			$category = $template['category'];
			if ( isset( $grouped[ $category ] ) ) {
				$grouped[ $category ][ $key ] = $template;
			}
		}

		return $grouped;
	}

	/**
	 * Gets a specific template by key.
	 *
	 * @since 1.0.0
	 * @param string $template_key Template key.
	 * @return array|null Template data or null if not found.
	 */
	public static function get_template( string $template_key ): ?array {
		$templates = self::get_templates();
		return $templates[ $template_key ] ?? null;
	}
}
