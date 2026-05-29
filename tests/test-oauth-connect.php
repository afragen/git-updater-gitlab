<?php
/**
 * Test OAuth Connect integration for GitLab
 *
 * @package Git_Updater_GitLab
 */

/**
 * Test OAuth Connect field registration
 */
class Test_GitLab_OAuth_Connect extends WP_UnitTestCase {

	/**
	 * Test that OAuth connect field is registered in add_settings
	 */
	public function test_oauth_connect_field_is_registered(): void {
		$api = new Fragen\Git_Updater\API\GitLab_API();
		
		// Get the settings fields registered
		global $wp_settings_fields;
		
		// Call add_settings to register fields
		$api->add_settings( [ 'gitlab_private' => true, 'gitlab_enterprise' => true ] );
		
		// Check that the OAuth connect field was registered
		$this->assertArrayHasKey( 'gitlab_oauth_connect', $wp_settings_fields['git_updater_gitlab_install_settings']['gitlab_settings'] );
	}

	/**
	 * Test OAuth connect field uses correct callback
	 */
	public function test_oauth_connect_field_uses_correct_callback(): void {
		$api = new Fragen\Git_Updater\API\GitLab_API();
		$api->add_settings( [ 'gitlab_private' => true ] );
		
		global $wp_settings_fields;
		$field = $wp_settings_fields['git_updater_gitlab_install_settings']['gitlab_settings']['gitlab_oauth_connect'];
		
		$this->assertEquals( 'GitLab OAuth', $field['title'] );
		$this->assertIs_array( $field['callback'] );
		$this->assertInstanceOf( Fragen\Git_Updater\OAuth\OAuth_Connect::class, $field['callback'][0] );
		$this->assertEquals( 'render_connect_field', $field['callback'][1] );
	}

	/**
	 * Test OAuth connect field passes correct provider argument
	 */
	public function test_oauth_connect_field_passes_correct_provider(): void {
		$api = new Fragen\Git_Updater\API\GitLab_API();
		$api->add_settings( [ 'gitlab_private' => true ] );
		
		global $wp_settings_fields;
		$field = $wp_settings_fields['git_updater_gitlab_install_settings']['gitlab_settings']['gitlab_oauth_connect'];
		
		$this->assertArrayHasKey( 'provider', $field['args'] );
		$this->assertEquals( 'gitlab', $field['args']['provider'] );
	}
}
