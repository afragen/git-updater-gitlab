<?php
/**
 * Release-asset fallback tests for GitLab.
 *
 * @package Git_Updater_GitLab
 */

use Fragen\Git_Updater\API\GitLab_API;
use Fragen\Git_Updater\DB\Repo_Cache_Table;

/**
 * Verify that a missing CI job artifact falls back to the repository archive.
 *
 * The cache table is created when absent; if that is not possible in a given
 * bootstrap the download-link assertions still hold because cache reads and
 * writes are best-effort.
 */
class Test_GitLab_Release_Asset_Fallback extends WP_UnitTestCase {

	/**
	 * Unique repo slug for this test.
	 *
	 * @var string
	 */
	private $slug;

	/**
	 * URL requested by the probe.
	 *
	 * @var string
	 */
	private $requested_url = '';

	/**
	 * Set up each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->slug          = 'gu-ra-fallback-' . wp_generate_password( 8, false );
		$this->requested_url = '';

		global $wpdb;
		$table = Repo_Cache_Table::instance();
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table->table_name() ) ) !== $table->table_name() ) {
			$table->install_table();
		}
	}

	/**
	 * Tear down each test.
	 */
	public function tearDown(): void {
		remove_all_filters( 'pre_http_request' );
		parent::tearDown();
	}

	/**
	 * Build a hydrated theme repo object.
	 *
	 * @return stdClass
	 */
	private function get_repo() {
		return (object) [
			'type'           => 'theme',
			'git'            => 'gitlab',
			'owner'          => 'afragen',
			'enterprise'     => null,
			'enterprise_api' => null,
			'slug'           => $this->slug,
			'branch'         => 'master',
			'primary_branch' => 'master',
			'branches'       => [ 'master' => [] ],
			'tags'           => [
				'3.1.0' => '3.1.0',
				'3.0.0' => '3.0.0',
			],
			'newest_tag'     => '3.1.0',
			'release_asset'  => true,
			'ci_job'         => 'build_theme',
		];
	}

	/**
	 * Stub the artifact probe with a fixed HTTP status.
	 *
	 * @param int $code HTTP status code.
	 */
	private function stub_artifact_probe( int $code ): void {
		add_filter(
			'pre_http_request',
			function ( $pre, $args, $url ) use ( $code ) {
				if ( ! str_contains( $url, 'jobs/artifacts' ) ) {
					return $pre;
				}

				$this->requested_url = $url;

				return [
					'headers'  => [],
					'body'     => '{"message":"stub"}',
					'response' => [
						'code'    => $code,
						'message' => 'stub',
					],
					'cookies'  => [],
					'filename' => null,
				];
			},
			10,
			3
		);
	}

	/**
	 * A 404 from the artifact endpoint falls back to the source archive.
	 */
	public function test_missing_artifact_falls_back_to_archive(): void {
		$api = new GitLab_API( $this->get_repo() );
		$api->set_repo_cache( 'release_asset', 'https://gitlab.com/stale.zip' );
		$api->set_repo_cache( 'release_asset_download', 'https://gitlab.com/stale.zip' );

		$this->stub_artifact_probe( 404 );
		$link = $api->construct_download_link();

		$this->assertStringContainsString( 'repository/archive.zip', $link );
		$this->assertStringContainsString( 'sha=3.1.0', $link );
		$this->assertStringNotContainsString( 'jobs/artifacts', $link );

		$this->assertStringContainsString( 'jobs/artifacts/3.1.0/download', $this->requested_url );
		$this->assertStringContainsString( 'job=build_theme', $this->requested_url );

		$this->assertEmpty( $api->get_repo_cache( $this->slug, false, 'release_asset' ) );
		$this->assertEmpty( $api->get_repo_cache( $this->slug, false, 'release_asset_download' ) );
		$this->assertEmpty( $api->get_repo_cache( $this->slug, false, 'error_cache' ) );
	}

	/**
	 * A present artifact (302 redirect) is used as the download link.
	 */
	public function test_existing_artifact_is_used(): void {
		$api = new GitLab_API( $this->get_repo() );

		$this->stub_artifact_probe( 302 );
		$link = $api->construct_download_link();

		$this->assertStringContainsString( 'jobs/artifacts/3.1.0/download', $link );
		$this->assertStringContainsString( 'job=build_theme', $link );
		$this->assertEquals( $link, $api->get_repo_cache( $this->slug, false, 'release_asset_download' ) );
		$this->assertEmpty( $api->get_repo_cache( $this->slug, false, 'error_cache' ) );
	}
}
