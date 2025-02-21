<?php

/**
 * Class BootstrapTest
 *
 * @package Git_Updater_GitLab
 */

use Fragen\Git_Updater\GitLab\Bootstrap;

/**
 * Sample test case.
 */
class BootstrapTest extends WP_UnitTestCase {
	/**
	 * A single example test.
	 */
	public function test_sample() {
		// Replace this with some actual testing code.
		$this->assertTrue(true);
	}

	public function test_add_repo_parts() {
		$empty     = ['types' => [], 'uris' => []];
		$expected  = [
			'types' => ['GitLab' => 'gitlab_plugin'],
			'uris'  => ['GitLab' => 'https://gitlab.com/'],
		];
		$acutal = (new Bootstrap())->add_repo_parts($empty, 'plugin');

		$this->assertEqualSetsWithIndex($expected, $acutal);
	}

	public function test_set_auth_required() {
		$expected = [
			'gitlab'            => true,
			'gitlab_private'    => true,
			'gitlab_enterprise' => true,
	];
		$acutal = (new Bootstrap())->set_auth_required([]);
		$this->assertEqualSetsWithIndex($expected, $acutal);
	}

	public function test_set_repo_type_data() {
		$org             = new \stdClass();
		$org->git        = 'gitlab';
		$org->enterprise = null;
		$expected_org    = [
			'git'           => 'gitlab',
			'base_uri'      => 'https://gitlab.com/api/v4',
			'base_download' => 'https://gitlab.com',
		];

		$actual_org   = (new Bootstrap())->set_repo_type_data([], $org);
		$this->assertEqualSetsWithIndex($expected_org, $actual_org);
	}

	public function test_parse_headers() {
		$test = [
			'host'     => null,
			'base_uri' => 'https://api.example.com',
		];

		$expected_rest_api = 'https://api.example.com/api/v4';
		$actual            = (new Bootstrap())->parse_headers($test, 'GitLab');

		$this->assertSame($expected_rest_api, $actual['enterprise_api']);
	}

	public function test_set_credentials() {
		$credentials = [
			'api.wordpress' => false,
			'isset'         => false,
			'token'         => null,
			'type'          => null,
			'enterprise'    => null,
			'slug'          => null,
		];
		$args = [
			'type'          => 'gitlab',
			'headers'       => ['host' => 'gitlab.com'],
			'options'       => ['gitlab_access_token' => 'xxxx'],
			'slug'          => 'my-slug',
			'object'        => new \stdClass,
		];
		$args_enterprise = [
			'type'          => 'gitlab',
			'headers'       => ['host' => 'mygitlab.com'],
			'options'       => ['gitlab_access_token' => 'yyyy'],
			'slug'          => 'my-slug',
			'object'        => new \stdClass,
		];

		$credentials_expected =[
			'api.wordpress' => false,
			'type'          => 'gitlab',
			'isset'         => true,
			'token'         => 'xxxx',
			'enterprise'    => false,
			'slug'          => 'my-slug',
		];
		$credentials_expected_enterprise =[
			'api.wordpress' => false,
			'type'          => 'gitlab',
			'isset'         => true,
			'token'         => 'yyyy',
			'enterprise'    => true,
			'slug'          => 'my-slug',
		];

		$actual            = (new Bootstrap())->set_credentials($credentials, $args);
		$actual_enterprise = (new Bootstrap())->set_credentials($credentials, $args_enterprise);

		$this->assertEqualSetsWithIndex($credentials_expected, $actual);
		$this->assertEqualSetsWithIndex($credentials_expected_enterprise, $actual_enterprise);
	}

	public function test_get_icon_data() {
		$icon_data           = ['headers' => [], 'icons'=>[]];
		$expected['headers'] = ['GitLabPluginURI' => 'GitLab Plugin URI'];
		$expected['icons']   = ['gitlab' => 'git-updater-gitlab/assets/gitlab-logo.svg' ];

		$actual = (new Bootstrap())->set_git_icon_data($icon_data, 'Plugin');

		$this->assertEqualSetsWithIndex($expected, $actual);
	}

}
