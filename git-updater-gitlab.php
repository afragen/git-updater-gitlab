<?php
/**
 * Git Updater - GitLab.
 * Requires GitHub Updater plugin.
 *
 * @package git-updater-gitlab
 * @author  Andy Fragen
 * @link    https://github.com/afragen/git-updater-gitlab
 * @link    https://github.com/afragen/github-updater
 */

/**
 * Plugin Name:       GitHub Updater - GitLab
 * Plugin URI:        https://github.com/afragen/git-updater-gitlab
 * Description:       Add GitLab hosted repositories to the GitHub Updater plugin.
 * Version:           0.4.0.1
 * Author:            Andy Fragen
 * License:           MIT
 * Network:           true
 * Domain Path:       /languages
 * Text Domain:       git-updater-gitlab
 * GitHub Plugin URI: https://github.com/afragen/git-updater-gitlab
 * Primary Branch:    main
 * Requires at least: 5.1
 * Requires PHP:      5.6
 */

namespace Fragen\Git_Updater\GitLab;

/*
 * Exit if called directly.
 * PHP version check and exit.
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Load Autoloader.
require_once __DIR__ . '/vendor/autoload.php';

( new Bootstrap( __FILE__ ) )->load_hooks();

add_action(
	'plugins_loaded',
	function() {
		( new Bootstrap( __FILE__ ) )->run();
	}
);
