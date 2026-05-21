<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- uninstall script runs in isolated scope.
// phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter -- table names are $wpdb->prefix + hardcoded string.
/**
 * Uninstall script for WPRobo Engage
 *
 * This file is executed when the plugin is uninstalled.
 * It will delete all plugin data if the user has enabled the option.
 *
 * @package WPRobo_Engage_Lite
 */

// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Check if user has enabled delete on uninstall option
$delete_on_uninstall = get_option( 'wpr_delete_on_uninstall', '0' );

if ( '1' !== $delete_on_uninstall ) {
	// User wants to keep the data
	return;
}

global $wpdb;

// Delete all campaigns (custom post type)
$campaigns = get_posts(
	[
		'post_type'      => 'wpr_campaign',
		'posts_per_page' => -1,
		'post_status'    => 'any',
	]
);

foreach ( $campaigns as $campaign ) {
	wp_delete_post( $campaign->ID, true );
}

// Delete all options
delete_option( 'wpr_allowed_roles' );
delete_option( 'wpr_delete_on_uninstall' );

// Delete custom tables if they exist
$analytics_table = $wpdb->prefix . 'wprobo_engage_analytics';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- uninstall cleanup; $table_name is $wpdb->prefix + hardcoded string.
$wpdb->query( "DROP TABLE IF EXISTS {$analytics_table}" );

$leads_table = $wpdb->prefix . 'wprobo_engage_leads';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- uninstall cleanup; $table_name is $wpdb->prefix + hardcoded string.
$wpdb->query( "DROP TABLE IF EXISTS {$leads_table}" );

// Delete all post meta for campaigns
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- uninstall cleanup; bulk delete with no WP API equivalent.
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = 'wpr_campaign')" );
