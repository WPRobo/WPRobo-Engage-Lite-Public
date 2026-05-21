<?php

namespace WPRobo_Engage_Lite\Public;

if ( ! defined( 'ABSPATH' ) ) {
	exit; }

/**
 * Class Rule_Engine
 *
 * Advanced targeting and display rules engine.
 * Handles complex rule evaluation including user targeting, device detection,
 * referral sources, and behavioral triggers.
 *
 * @package WPRobo_Engage_Lite\Public
 */
class Rule_Engine {

	/**
	 * Evaluates a single display rule against the current context.
	 *
	 * @param array $rule The rule to evaluate.
	 * @return bool True if the rule matches the current context.
	 */
	public function evaluate_rule( array $rule ): bool {
		if ( ! isset( $rule['type'] ) ) {
			return false;
		}

		$type = $rule['type'];

		// Page/Post Targeting Rules
		if ( $this->is_page_rule( $type ) ) {
			return $this->evaluate_page_rule( $rule );
		}

		// User Targeting Rules
		if ( $this->is_user_rule( $type ) ) {
			return $this->evaluate_user_rule( $rule );
		}

		// Referral Source Rules
		if ( $this->is_referral_rule( $type ) ) {
			return $this->evaluate_referral_rule( $rule );
		}

		// Device Detection Rules
		if ( $this->is_device_rule( $type ) ) {
			return $this->evaluate_device_rule( $rule );
		}

		// Behavioral rules (handled client-side, always return true for server-side)
		if ( $this->is_behavioral_rule( $type ) ) {
			return true; // These are evaluated on the frontend
		}

		// Geolocation rules
		if ( $this->is_geolocation_rule( $type ) ) {
			return $this->evaluate_geolocation_rule( $rule );
		}

		// Custom JavaScript rules (handled client-side)
		if ( $this->is_custom_js_rule( $type ) ) {
			return true; // These are evaluated on the frontend
		}

		return false;
	}

	/**
	 * Checks if the rule type is a page targeting rule.
	 *
	 * @param string $type Rule type.
	 * @return bool
	 */
	private function is_page_rule( string $type ): bool {
		$page_rules = array(
			'all_pages',
			'homepage',
			'specific_page',
			'specific_post',
			'all_posts',
			'url_contains',
			'url_starts_with',
			'url_ends_with',
			'url_regex',
			'post_category',
			'post_tag',
			'custom_post_type',
			'archive_page',
			'search_page',
			'error_404',
		);

		return in_array( $type, $page_rules, true );
	}

	/**
	 * Checks if the rule type is a user targeting rule.
	 *
	 * @param string $type Rule type.
	 * @return bool
	 */
	private function is_user_rule( string $type ): bool {
		$user_rules = array(
			'user_logged_in',
			'user_logged_out',
			'user_role',
			'user_not_role',
		);

		return in_array( $type, $user_rules, true );
	}

	/**
	 * Checks if the rule type is a referral source rule.
	 *
	 * @param string $type Rule type.
	 * @return bool
	 */
	private function is_referral_rule( string $type ): bool {
		$referral_rules = array(
			'referral_direct',
			'referral_search',
			'referral_social',
			'referral_domain',
			'referral_utm',
		);

		return in_array( $type, $referral_rules, true );
	}

	/**
	 * Checks if the rule type is a device detection rule.
	 *
	 * @param string $type Rule type.
	 * @return bool
	 */
	private function is_device_rule( string $type ): bool {
		$device_rules = array(
			'device_desktop',
			'device_mobile',
			'device_tablet',
			'device_browser',
			'device_os',
		);

		return in_array( $type, $device_rules, true );
	}

	/**
	 * Checks if the rule type is a behavioral rule (client-side).
	 *
	 * @param string $type Rule type.
	 * @return bool
	 */
	private function is_behavioral_rule( string $type ): bool {
		$behavioral_rules = array(
			'time_on_site',
			'scroll_depth',
			'page_views_session',
			'page_views_lifetime',
			'visitor_new',
			'visitor_returning',
		);

		return in_array( $type, $behavioral_rules, true );
	}

	/**
	 * Checks if the rule type is a geolocation rule.
	 *
	 * @param string $type Rule type.
	 * @return bool
	 */
	private function is_geolocation_rule( string $type ): bool {
		$geolocation_rules = array(
			'geo_country',
			'geo_region',
			'geo_city',
			'geo_continent',
		);

		return in_array( $type, $geolocation_rules, true );
	}

	/**
	 * Checks if the rule type is a custom JavaScript rule.
	 *
	 * @param string $type Rule type.
	 * @return bool
	 */
	private function is_custom_js_rule( string $type ): bool {
		return $type === 'custom_js';
	}

	/**
	 * Evaluates page/post targeting rules.
	 *
	 * @param array $rule The rule to evaluate.
	 * @return bool
	 */
	private function evaluate_page_rule( array $rule ): bool {
		global $post;
		$type = $rule['type'];

		switch ( $type ) {
			case 'all_pages':
				return true;

			case 'homepage':
				return is_front_page() || is_home();

			case 'specific_page':
				if ( isset( $rule['value'] ) ) {
					$target_id = absint( $rule['value'] );
					if ( is_page() && $post && $target_id === $post->ID ) {
						return true;
					}
					if ( is_front_page() && $target_id === (int) get_option( 'page_on_front' ) ) {
						return true;
					}
				}
				return false;

			case 'specific_post':
				if ( isset( $rule['value'] ) && is_single() ) {
					return $post && absint( $rule['value'] ) === $post->ID;
				}
				return false;

			case 'all_posts':
				return is_single();

			case 'url_contains':
				if ( isset( $rule['value'] ) ) {
					$current_url = $this->get_current_url();
					return strpos( $current_url, $rule['value'] ) !== false;
				}
				return false;

			case 'url_starts_with':
				if ( isset( $rule['value'] ) ) {
					$current_url = $this->get_current_url();
					return strpos( $current_url, $rule['value'] ) === 0;
				}
				return false;

			case 'url_ends_with':
				if ( isset( $rule['value'] ) ) {
					$current_url  = $this->get_current_url();
					$value_length = strlen( $rule['value'] );
					return substr( $current_url, -$value_length ) === $rule['value'];
				}
				return false;

			case 'url_regex':
				if ( isset( $rule['value'] ) ) {
					$current_url = $this->get_current_url();
					return @preg_match( $rule['value'], $current_url ) === 1;
				}
				return false;

			case 'post_category':
				if ( isset( $rule['value'] ) && is_single() ) {
					return has_category( $rule['value'], $post );
				}
				return false;

			case 'post_tag':
				if ( isset( $rule['value'] ) && is_single() ) {
					return has_tag( $rule['value'], $post );
				}
				return false;

			case 'custom_post_type':
				if ( isset( $rule['value'] ) ) {
					return is_singular( $rule['value'] );
				}
				return false;

			case 'archive_page':
				if ( ! isset( $rule['value'] ) ) {
					return is_archive();
				}
				// Specific archive types
				switch ( $rule['value'] ) {
					case 'category':
						return is_category();
					case 'tag':
						return is_tag();
					case 'author':
						return is_author();
					case 'date':
						return is_date();
					default:
						return is_archive();
				}

			case 'search_page':
				return is_search();

			case 'error_404':
				return is_404();

			default:
				return false;
		}
	}

	/**
	 * Evaluates user targeting rules.
	 *
	 * @param array $rule The rule to evaluate.
	 * @return bool
	 */
	private function evaluate_user_rule( array $rule ): bool {
		$type = $rule['type'];

		switch ( $type ) {
			case 'user_logged_in':
				return is_user_logged_in();

			case 'user_logged_out':
				return ! is_user_logged_in();

			case 'user_role':
				if ( ! is_user_logged_in() || ! isset( $rule['value'] ) ) {
					return false;
				}
				$user  = wp_get_current_user();
				$roles = is_array( $rule['value'] ) ? $rule['value'] : array( $rule['value'] );
				foreach ( $roles as $role ) {
					if ( in_array( $role, $user->roles, true ) ) {
						return true;
					}
				}
				return false;

			case 'user_not_role':
				if ( ! is_user_logged_in() ) {
					return true; // Logged out users don't have the role
				}
				if ( ! isset( $rule['value'] ) ) {
					return false;
				}
				$user  = wp_get_current_user();
				$roles = is_array( $rule['value'] ) ? $rule['value'] : array( $rule['value'] );
				foreach ( $roles as $role ) {
					if ( in_array( $role, $user->roles, true ) ) {
						return false;
					}
				}
				return true;

			default:
				return false;
		}
	}

	/**
	 * Evaluates referral source rules.
	 *
	 * @param array $rule The rule to evaluate.
	 * @return bool
	 */
	private function evaluate_referral_rule( array $rule ): bool {
		$type     = $rule['type'];
		$referrer = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';

		switch ( $type ) {
			case 'referral_direct':
				return empty( $referrer );

			case 'referral_search':
				if ( empty( $referrer ) ) {
					return false;
				}
				$search_engines = array( 'google.', 'bing.', 'yahoo.', 'duckduckgo.', 'baidu.' );
				foreach ( $search_engines as $engine ) {
					if ( strpos( $referrer, $engine ) !== false ) {
						return true;
					}
				}
				return false;

			case 'referral_social':
				if ( empty( $referrer ) ) {
					return false;
				}
				$social_platforms = array( 'facebook.', 'twitter.', 'instagram.', 'linkedin.', 'pinterest.', 'reddit.', 'tiktok.' );
				foreach ( $social_platforms as $platform ) {
					if ( strpos( $referrer, $platform ) !== false ) {
						return true;
					}
				}
				return false;

			case 'referral_domain':
				if ( ! isset( $rule['value'] ) || empty( $referrer ) ) {
					return false;
				}
				$domains = is_array( $rule['value'] ) ? $rule['value'] : array( $rule['value'] );
				foreach ( $domains as $domain ) {
					if ( strpos( $referrer, $domain ) !== false ) {
						return true;
					}
				}
				return false;

			case 'referral_utm':
				if ( ! isset( $rule['value'] ) ) {
					return false;
				}
				// UTM parameters from query string
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only UTM params from URL, no state change
				$utm_source   = isset( $_GET['utm_source'] ) ? sanitize_text_field( wp_unslash( $_GET['utm_source'] ) ) : '';
				$utm_medium   = isset( $_GET['utm_medium'] ) ? sanitize_text_field( wp_unslash( $_GET['utm_medium'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$utm_campaign = isset( $_GET['utm_campaign'] ) ? sanitize_text_field( wp_unslash( $_GET['utm_campaign'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

				$params     = $rule['value'];
				$match_type = isset( $params['match'] ) ? $params['match'] : 'all';

				$matches = 0;
				$total   = 0;

				if ( isset( $params['source'] ) && ! empty( $params['source'] ) ) {
					++$total;
					if ( $utm_source === $params['source'] ) {
						++$matches;
					}
				}

				if ( isset( $params['medium'] ) && ! empty( $params['medium'] ) ) {
					++$total;
					if ( $utm_medium === $params['medium'] ) {
						++$matches;
					}
				}

				if ( isset( $params['campaign'] ) && ! empty( $params['campaign'] ) ) {
					++$total;
					if ( $utm_campaign === $params['campaign'] ) {
						++$matches;
					}
				}

				if ( $match_type === 'all' ) {
					return $total > 0 && $matches === $total;
				} else {
					return $matches > 0;
				}

			default:
				return false;
		}
	}

	/**
	 * Evaluates device detection rules.
	 *
	 * @param array $rule The rule to evaluate.
	 * @return bool
	 */
	private function evaluate_device_rule( array $rule ): bool {
		$type       = $rule['type'];
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

		switch ( $type ) {
			case 'device_desktop':
				return ! $this->is_mobile() && ! $this->is_tablet();

			case 'device_mobile':
				return $this->is_mobile() && ! $this->is_tablet();

			case 'device_tablet':
				return $this->is_tablet();

			case 'device_browser':
				if ( ! isset( $rule['value'] ) || empty( $user_agent ) ) {
					return false;
				}
				$browsers = is_array( $rule['value'] ) ? $rule['value'] : array( $rule['value'] );
				foreach ( $browsers as $browser ) {
					if ( $this->detect_browser( $browser, $user_agent ) ) {
						return true;
					}
				}
				return false;

			case 'device_os':
				if ( ! isset( $rule['value'] ) || empty( $user_agent ) ) {
					return false;
				}
				$operating_systems = is_array( $rule['value'] ) ? $rule['value'] : array( $rule['value'] );
				foreach ( $operating_systems as $os ) {
					if ( $this->detect_os( $os, $user_agent ) ) {
						return true;
					}
				}
				return false;

			default:
				return false;
		}
	}

	/**
	 * Gets the current URL.
	 *
	 * @return string
	 */
	private function get_current_url(): string {
		global $wp;
		return home_url( add_query_arg( array(), $wp->request ) );
	}

	/**
	 * Detects if the current device is mobile.
	 *
	 * @return bool
	 */
	private function is_mobile(): bool {
		if ( function_exists( 'wp_is_mobile' ) ) {
			return wp_is_mobile();
		}
		return false;
	}

	/**
	 * Detects if the current device is a tablet.
	 *
	 * @return bool
	 */
	private function is_tablet(): bool {
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		return preg_match( '/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i', $user_agent );
	}

	/**
	 * Detects browser from user agent.
	 *
	 * @param string $browser Browser name.
	 * @param string $user_agent User agent string.
	 * @return bool
	 */
	private function detect_browser( string $browser, string $user_agent ): bool {
		$browser = strtolower( $browser );

		switch ( $browser ) {
			case 'chrome':
				return strpos( $user_agent, 'Chrome' ) !== false && strpos( $user_agent, 'Edg' ) === false;
			case 'firefox':
				return strpos( $user_agent, 'Firefox' ) !== false;
			case 'safari':
				return strpos( $user_agent, 'Safari' ) !== false && strpos( $user_agent, 'Chrome' ) === false;
			case 'edge':
				return strpos( $user_agent, 'Edg' ) !== false;
			case 'ie':
			case 'internet explorer':
				return strpos( $user_agent, 'MSIE' ) !== false || strpos( $user_agent, 'Trident' ) !== false;
			default:
				return false;
		}
	}

	/**
	 * Detects operating system from user agent.
	 *
	 * @param string $os Operating system name.
	 * @param string $user_agent User agent string.
	 * @return bool
	 */
	private function detect_os( string $os, string $user_agent ): bool {
		$os = strtolower( $os );

		switch ( $os ) {
			case 'windows':
				return strpos( $user_agent, 'Windows' ) !== false;
			case 'macos':
			case 'mac':
				return strpos( $user_agent, 'Macintosh' ) !== false || strpos( $user_agent, 'Mac OS' ) !== false;
			case 'ios':
				return strpos( $user_agent, 'iPhone' ) !== false || strpos( $user_agent, 'iPad' ) !== false;
			case 'android':
				return strpos( $user_agent, 'Android' ) !== false;
			case 'linux':
				return strpos( $user_agent, 'Linux' ) !== false && strpos( $user_agent, 'Android' ) === false;
			default:
				return false;
		}
	}

	/**
	 * Evaluates geolocation rules.
	 * Geolocation requires an external IP-lookup service that is not bundled.
	 * Without a resolver, the rule cannot match, so it returns false.
	 *
	 * @param array $rule The rule to evaluate.
	 * @return bool
	 */
	private function evaluate_geolocation_rule( array $rule ): bool {
		return false;
	}
}
