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
		add_filter( 'gu_get_repo_parts', [ $this, 'add_repo_parts' ], 10, 2 );
		add_filter( 'gu_settings_auth_required', [ $this, 'set_auth_required' ], 10, 1 );
		add_filter( 'gu_api_repo_type_data', [ $this, 'set_repo_type_data' ], 10, 2 );
		add_filter( 'gu_api_url_type', [ $this, 'set_api_url_data' ], 10, 4 );
		add_filter( 'gu_git_servers', [ $this, 'set_git_servers' ], 10, 1 );
		add_filter( 'gu_installed_apis', [ $this, 'set_installed_apis' ], 10, 1 );
		add_filter( 'gu_install_remote_install', [ $this, 'set_remote_install_data' ], 10, 2 );
		add_filter( 'gu_get_language_pack_json', [ $this, 'set_language_pack_json' ], 10, 4 );
		add_filter( 'gu_post_process_language_pack_package', [ $this, 'process_language_pack_data' ], 10, 4 );
	}

	/**
	 * Add API specific data to `get_repo_parts()`.
	 *
	 * @param array  $repos Array of repo data.
	 * @param string $type  plugin|theme.
	 *
	 * @return array
	 */
	public function add_repo_parts( $repos, $type ) {
		$repos['types'] = array_merge( $repos['types'], [ 'GitLab' => 'gitlab_' . $type ] );
		$repos['uris']  = array_merge( $repos['uris'], [ 'GitLab' => 'https://gitlab.com/' ] );

		return $repos;
	}

	/**
	 * Add API specific auth required data.
	 *
	 * @param array $auth_required Array of authentication required data.
	 *
	 * @return array
	 */
	public function set_auth_required( $auth_required ) {
		return array_merge(
			$auth_required,
			[
				'gitlab'            => true,
				'gitlab_private'    => true,
				'gitlab_enterprise' => true,
			]
		);
	}

	/**
	 * Add API specific repo data.
	 *
	 * @param array     $arr  Array of repo API data.
	 * @param \stdClass $repo Repository object.
	 *
	 * @return array
	 */
	public function set_repo_type_data( $arr, $repo ) {
		if ( 'gitlab' === $repo->git ) {
			$arr['git']           = 'gitlab';
			$arr['base_uri']      = 'https://gitlab.com/api/v4';
			$arr['base_download'] = 'https://gitlab.com';
		}

		return $arr;
	}

	/**
	 * Add API specific URL data.
	 *
	 * @param array     $type          Array of API type data.
	 * @param \stdClass $repo          Repository object.
	 * @param bool      $download_link Boolean indicating a download link.
	 * @param string    $endpoint      API URL endpoint.
	 *
	 * @return array
	 */
	public function set_api_url_data( $type, $repo, $download_link, $endpoint ) {
		if ( 'gitlab' === $type['git'] ) {
			if ( $repo->enterprise ) {
				$type['base_download'] = $repo->enterprise;
				$type['base_uri']      = null;
			}
		}

		return $type;
	}

	/**
	 * Add API as git server.
	 *
	 * @param array $git_servers Array of git servers.
	 *
	 * @return array
	 */
	public function set_git_servers( $git_servers ) {
		return array_merge( $git_servers, [ 'gitlab' => 'GitLab' ] );
	}

	/**
	 * Add API data to $installed_apis.
	 *
	 * @param array $installed_apis Array of installed APIs.
	 *
	 * @return array
	 */
	public function set_installed_apis( $installed_apis ) {
		return array_merge( $installed_apis, [ 'gitlab_api' => true ] );
	}

	/**
	 * Set remote installation data for specific API.
	 *
	 * @param array $install Array of remote installation data.
	 * @param array $headers Array of repository header data.
	 *
	 * @return array
	 */
	public function set_remote_install_data( $install, $headers ) {
		if ( 'gitlab' === $install['github_updater_api'] ) {
			$install = ( new GitLab_API() )->remote_install( $headers, $install );
		}

		return $install;
	}

	/**
	 * Filter to return API specific language pack data.
	 *
	 * @param \stdClass $response Object of Language Pack API response.
	 * @param string    $git      Name of git host.
	 * @param array     $headers  Array of repo headers.
	 * @param \stdClass $obj      Current class object.
	 *
	 * @return \stdClass
	 */
	public function set_language_pack_json( $response, $git, $headers, $obj ) {
		if ( 'gitlab' === $git ) {
			$id       = rawurlencode( $headers['owner'] . '/' . $headers['repo'] );
			$response = $this->api( '/projects/' . $id . '/repository/files/language-pack.json' );
			$response = isset( $response->content )
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
				? json_decode( base64_decode( $response->content ) )
				: null;
		}

		return $response;
	}

	/**
	 * Filter to post process API specific language pack data.
	 *
	 * @param null|string $package URL to language pack.
	 * @param string      $git     Name of git host.
	 * @param \stdClass   $locale  Object of language pack data.
	 * @param array       $headers Array of repository headers.
	 *
	 * @return string
	 */
	public function process_language_pack_data( $package, $git, $locale, $headers ) {
		if ( 'gitlab' === $git ) {
			$package = [ $headers['uri'], 'raw/master' ];
			$package = implode( '/', $package ) . $locale->package;
		}

		return $package;
	}
}
