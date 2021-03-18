<?php
/**
 * Git Updater - GitLab.
 * Requires Git Updater plugin.
 *
 * @package git-updater-gitlab
 * @author  Andy Fragen
 * @link    https://github.com/afragen/git-updater-gitlab
 * @link    https://github.com/afragen/github-updater
 */

/**
 * Plugin Name:       Git Updater - GitLab
 * Plugin URI:        https://github.com/afragen/git-updater-gitlab
 * Description:       Add GitLab hosted repositories to the Git Updater plugin.
 * Version:           0.8.0
 * Author:            Andy Fragen
 * License:           MIT
 * Network:           true
 * Domain Path:       /languages
 * Text Domain:       git-updater-gitlab
 * GitHub Plugin URI: https://github.com/afragen/git-updater-gitlab
 * Primary Branch:    main
 * Requires at least: 5.2
 * Requires PHP:      7.0
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

( new Bootstrap() )->load_hooks();

add_action(
	'plugins_loaded',
	function() {
		( new Bootstrap() )->run();
	}
);
