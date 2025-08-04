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

use Fragen\Git_Updater\API\GitLab_API;
use stdClass;

/*
 * Exit if called directly.
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Bootstrap
 */
class Bootstrap {
	/**
	 * Run the bootstrap.
	 *
	 * @return bool|void
	 */
	public function run() {
		// Exit if Git Updater not running.
		if ( ! class_exists( '\\Fragen\\Git_Updater\\Bootstrap' ) ) {
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
		add_filter( 'gu_parse_enterprise_headers', [ $this, 'parse_headers' ], 10, 2 );
		add_filter( 'gu_settings_auth_required', [ $this, 'set_auth_required' ], 10, 1 );
		add_filter( 'gu_get_repo_api', [ $this, 'set_repo_api' ], 10, 3 );
		add_filter( 'gu_api_repo_type_data', [ $this, 'set_repo_type_data' ], 10, 2 );
		add_filter( 'gu_api_url_type', [ $this, 'set_api_url_data' ], 10, 4 );
		add_filter( 'gu_post_get_credentials', [ $this, 'set_credentials' ], 10, 2 );
		add_filter( 'gu_get_auth_header', [ $this, 'set_auth_header' ], 10, 2 );
		add_filter( 'gu_decode_response', [ $this, 'decode_response' ], 10, 2 );
		add_filter( 'gu_git_servers', [ $this, 'set_git_servers' ], 10, 1 );
		add_filter( 'gu_running_git_servers', [ $this, 'set_running_enterprise_servers' ], 10, 2 );
		add_filter( 'gu_installed_apis', [ $this, 'set_installed_apis' ], 10, 1 );
		add_filter( 'gu_parse_release_asset', [ $this, 'parse_release_asset' ], 10, 4 );
		add_filter( 'gu_install_remote_install', [ $this, 'set_remote_install_data' ], 10, 2 );
		add_filter( 'gu_get_language_pack_json', [ $this, 'set_language_pack_json' ], 10, 4 );
		add_filter( 'gu_post_process_language_pack_package', [ $this, 'process_language_pack_data' ], 10, 4 );
		add_filter( 'gu_get_git_icon_data', [ $this, 'set_git_icon_data' ], 10, 2 );
		add_filter( 'gua_addition_types', [ $this, 'add_addition_types' ], 10, 1 );
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
	 * Modify enterprise API header data.
	 *
	 * @param array  $header Array of repo data.
	 * @param string $git    Name of git host.
	 *
	 * @return string
	 */
	public function parse_headers( $header, $git ) {
		if ( 'GitLab' === $git && false === strpos( $header['host'], 'gitlab.com' ) ) {
			$header['enterprise_uri']  = $header['base_uri'];
			$header['enterprise_api']  = trim( $header['enterprise_uri'], '/' );
			$header['enterprise_api'] .= '/api/v4';
		}

		return $header;
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
	 * Return git host API object.
	 *
	 * @param stdClass $repo_api Git API object.
	 * @param string   $git      Name of git host.
	 * @param stdClass $repo     Repository object.
	 *
	 * @return stdClass
	 */
	public function set_repo_api( $repo_api, $git, $repo ) {
		if ( 'gitlab' === $git ) {
			$repo_api = new GitLab_API( $repo );
		}

		return $repo_api;
	}

	/**
	 * Add API specific repo data.
	 *
	 * @param array    $arr  Array of repo API data.
	 * @param stdClass $repo Repository object.
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
	 * @param array    $type          Array of API type data.
	 * @param stdClass $repo          Repository object.
	 * @param bool     $download_link Boolean indicating a download link.
	 * @param string   $endpoint      API URL endpoint.
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
	 * Add credentials data for API.
	 *
	 * @param array $credentials Array of repository credentials data.
	 * @param array $args        Hook args.
	 *
	 * @return array
	 */
	public function set_credentials( $credentials, $args ) {
		if ( isset( $args['type'], $args['headers'], $args['options'], $args['slug'], $args['object'] ) ) {
			$type    = $args['type'];
			$headers = $args['headers'];
			$options = $args['options'];
			$slug    = $args['slug'];
			$object  = $args['object'];
		} else {
			return $credentials;
		}
		if ( 'gitlab' === $type || $object instanceof GitLab_API ) {
			$token = ! empty( $options['gitlab_access_token'] ) ? $options['gitlab_access_token'] : null;
			$token = ! empty( $options[ $slug ] ) ? $options[ $slug ] : $token;

			$credentials['type']       = 'gitlab';
			$credentials['isset']      = true;
			$credentials['token']      = isset( $token ) ? $token : null;
			$credentials['enterprise'] = ! in_array( $headers['host'], [ 'gitlab.com' ], true );
			$credentials['slug']       = $slug;
		}

		return $credentials;
	}

	/**
	 * Add Basic Authentication header.
	 *
	 * @param array $headers     HTTP GET headers.
	 * @param array $credentials Repository credentials.
	 *
	 * @return array
	 */
	public function set_auth_header( $headers, $credentials ) {
		if ( 'gitlab' === $credentials['type'] ) {
			// https://gitlab.com/gitlab-org/gitlab-foss/issues/63438.
			// Use when GitLab fully supports oAuth 2.0.
			// $headers['headers']['Authorization'] = 'Bearer ' . $credentials['token'];
			$headers['headers']['PRIVATE-TOKEN'] = $credentials['token'];
			$headers['headers']['gitlab']        = $credentials['slug'];
		}

		return $headers;
	}

	/**
	 * Decode API response.
	 *
	 * @param stdClass $response API response object.
	 * @param string   $git      Name  of API, eg 'github'.
	 *
	 * @return stdClass
	 */
	public function decode_response( $response, $git ) {
		if ( 'gitlab' === $git ) {
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
			$response = isset( $response->content ) ? base64_decode( $response->content ) : $response;
		}

		return $response;
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
	 * Set running enterprise git servers.
	 *
	 * @param array $servers Array of repository git types.
	 * @param array $repos   Array of repositories objects.
	 *
	 * @return array
	 */
	public function set_running_enterprise_servers( $servers, $repos ) {
		$ent_servers = array_map(
			function ( $e ) {
				if ( ! empty( $e->enterprise ) ) {
					if ( 'gitlab' === $e->git ) {
						return 'gitlabce';
					}
				}
			},
			$repos
		);
		$ent_servers = array_filter( $ent_servers );

		return array_merge( $servers, $ent_servers );
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
	 * Parse API release asset.
	 *
	 * @param stdClass $response API response object.
	 * @param string   $git      Name of git host.
	 * @param string   $request  Schema of API request.
	 * @param stdClass $obj      Current class object.
	 *
	 * @return string
	 */
	public function parse_release_asset( $response, $git, $request, $obj ) {
		if ( 'gitlab' === $git ) {
			if ( $response ) {
				$response = $obj->get_api_url( $request );
			}
			if ( $obj->type->ci_job && ! empty( $obj->response['release_asset'] ) ) {
				$response = $obj->response['release_asset'];
			}
			$release_asset                       = new stdClass();
			$release_asset->browser_download_url = $response;
			$release_asset->download_count       = 0;
			$obj->set_repo_cache( 'release_asset_response', $release_asset );
		}

		return $response;
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
		if ( 'gitlab' === $install['git_updater_api'] ) {
			$install = ( new GitLab_API() )->remote_install( $headers, $install );
		}

		return $install;
	}

	/**
	 * Filter to return API specific language pack data.
	 *
	 * @param stdClass $response Object of Language Pack API response.
	 * @param string   $git      Name of git host.
	 * @param array    $headers  Array of repo headers.
	 * @param stdClass $obj      Current class object.
	 *
	 * @return stdClass
	 */
	public function set_language_pack_json( $response, $git, $headers, $obj ) {
		if ( 'gitlab' === $git ) {
			$id       = rawurlencode( $headers['owner'] . '/' . $headers['repo'] );
			$response = $obj->api( '/projects/' . $id . '/repository/files/language-pack.json' );
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
	 * @param stdClass    $locale  Object of language pack data.
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

	/**
	 * Set API icon data for display.
	 *
	 * @param array  $icon_data Header data for API.
	 * @param string $type_cap  Plugin|Theme.
	 *
	 * @return array
	 */
	public function set_git_icon_data( $icon_data, $type_cap ) {
		$icon_data['headers'] = array_merge(
			$icon_data['headers'],
			[ "GitLab{$type_cap}URI" => "GitLab {$type_cap} URI" ]
		);
		$icon_data['icons']   = array_merge(
			$icon_data['icons'],
			[ 'gitlab' => basename( dirname( __DIR__ ) ) . '/assets/gitlab-logo.svg' ]
		);

		return $icon_data;
	}

	/**
	 * Add repository types to Git Updater Additions.
	 *
	 * @param array $addition_types Array of Git Updater Additions repository types.
	 *
	 * @return array
	 */
	public function add_addition_types( $addition_types ) {
		return array_merge( $addition_types, [ 'gitlab_plugin', 'gitlab_theme' ] );
	}
}
