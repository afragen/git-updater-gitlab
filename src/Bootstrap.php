<?php
/**
 * GitHub Updater - GitLab
 *
 * @author    Andy Fragen
 * @license   MIT
 * @link      https://github.com/afragen/git-updater-gitlab
 * @package   git-updater-gitlab
 */

namespace Fragen\Git_Updater\GitLab;

use Fragen\GitHub_Updater\API\GitLab_API;

/*
 * Exit if called directly.
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Load textdomain.
add_action(
	'init',
	function () {
		load_plugin_textdomain( 'git-updater-gitlab' );
	}
);

/**
 * Class Bootstrap
 */
class Bootstrap {
	/**
	 * Holds main plugin file.
	 *
	 * @var $file
	 */
	protected $file;

	/**
	 * Holds main plugin directory.
	 *
	 * @var $dir
	 */
	protected $dir;

	/**
	 * Constructor.
	 *
	 * @param  string $file Main plugin file.
	 * @return void
	 */
	public function __construct( $file ) {
		$this->file = $file;
		$this->dir  = dirname( $file );
	}

	/**
	 * Run the bootstrap.
	 *
	 * @return bool|void
	 */
	public function run() {
		// Exit if GitHub Updater not running.
		if ( ! class_exists( '\\Fragen\\GitHub_Updater\\Bootstrap' ) ) {
			return false;
		}

		new GitLab_API();
	}

	/**
	 * Load hooks.
	 *
	 * @return void
	 */
	public function load_hooks() {
		\add_filter(
			'gu_get_repo_parts',
			function ( $repos, $type ) {
				$repos['types'] = array_merge( $repos['types'], [ 'GitLab' => 'gitlab_' . $type ] );
				$repos['uris']  = array_merge( $repos['uris'], [ 'GitLab' => 'https://gitlab.com/' ] );

				return $repos;
			},
			10,
			2
		);
		\add_filter(
			'gu_settings_auth_required',
			function ( $auth_required ) {
				return \array_merge(
					$auth_required,
					[
						'gitlab'            => true,
						'gitlab_private'    => true,
						'gitlab_enterprise' => true,
					]
				);
			},
			10,
			1
		);

		\add_filter(
			'gu_api_repo_type_data',
			function ( $arr, $repo ) {
				if ( 'gitlab' === $repo->git ) {
					$arr['git']           = 'gitlab';
					$arr['base_uri']      = 'https://gitlab.com/api/v4';
					$arr['base_download'] = 'https://gitlab.com';
				}

				return $arr;
			},
			10,
			2
		);

		\add_filter(
			'gu_api_url_type',
			function ( $type, $repo, $download_link, $endpoint ) {
				if ( 'gitlab' === $type['git'] ) {
					if ( $repo->enterprise ) {
						$type['base_download'] = $repo->enterprise;
						$type['base_uri']      = null;
					}
				}

				return $type;
			},
			10,
			4
		);

		\add_filter(
			'gu_git_servers',
			function ( $git_servers ) {
				return array_merge( $git_servers, [ 'gitlab' => 'GitLab' ] );
			},
			10,
			1
		);

		\add_filter(
			'gu_installed_apis',
			function ( $installed_apis ) {
				return array_merge( $installed_apis, [ 'gitlab_api' => true ] );
			},
			10,
			1
		);

		\add_filter(
			'gu_install_remote_install',
			function ( $install, $headers ) {
				if ( 'gitlab' === $install['github_updater_api'] ) {
					$install = ( new GitLab_API() )->remote_install( $headers, $install );
				}

				return $install;
			},
			10,
			2
		);
	}
}
