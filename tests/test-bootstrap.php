<?php

/**
 * Class BootstrapTest
 *
 * @package Git_Updater_Bitbucket
 */

use Fragen\Git_Updater\Bitbucket\Bootstrap;

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
		$org->git        = 'bitbucket';
		$org->enterprise = null;
		$expected_org    = [
			'git'           => 'gitlab',
			'base_uri'      => 'https://gitlab.com/api/v4',
			'base_download' => 'https://gitlab.com',
		];

		$actual_org   = (new Bootstrap())->set_repo_type_data([], $org);
		$this->assertEqualSetsWithIndex($expected_org, $actual_org);
	}
}