<?php

/**
 * Tests for the WP_Customize_Panel class.
 *
 * @group customize
 */
class Tests_WP_Customize_Panel extends WP_UnitTestCase {

	/**
	 * @var WP_Customize_Manager
	 */
	protected $manager;

	public function set_up() {
		parent::set_up();
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$GLOBALS['wp_customize'] = new WP_Customize_Manager();
		$this->manager           = $GLOBALS['wp_customize'];
	}

	public function tear_down() {
		$this->manager = null;
		unset( $GLOBALS['wp_customize'] );
		parent::tear_down();
	}

	/**
	 * @see WP_Customize_Panel::__construct()
	 */
	public function test_construct_default_args() {
		$panel = new WP_Customize_Panel( $this->manager, 'foo' );
		$this->assertIsInt( $panel->instance_number );
		$this->assertSame( $this->manager, $panel->manager );
		$this->assertSame( 'foo', $panel->id );
		$this->assertSame( 160, $panel->priority );
		$this->assertSame( 'edit_theme_options', $panel->capability );
		$this->assertSame( '', $panel->theme_supports );
		$this->assertSame( '', $panel->title );
		$this->assertSame( '', $panel->description );
		$this->assertEmpty( $panel->sections );
		$this->assertSame( 'default', $panel->type );
		$this->assertSame( array( $panel, 'active_callback' ), $panel->active_callback );
	}

	/**
	 * @see WP_Customize_Panel::__construct()
	 */
	public function test_construct_custom_args() {
		$args = array(
			'priority'        => 200,
			'capability'      => 'edit_posts',
			'theme_supports'  => 'html5',
			'title'           => 'Hello World',
			'description'     => 'Lorem Ipsum',
			'type'            => 'horizontal',
			'active_callback' => '__return_true',
		);

		$panel = new WP_Customize_Panel( $this->manager, 'foo', $args );
		foreach ( $args as $key => $value ) {
			$this->assertSame( $value, $panel->$key );
		}
	}

	/**
	 * @see WP_Customize_Panel::__construct()
	 */
	public function test_construct_custom_type() {
		$panel = new Custom_Panel_Test( $this->manager, 'foo' );
		$this->assertSame( 'titleless', $panel->type );
	}

	/**
	 * @see WP_Customize_Panel::active()
	 * @see WP_Customize_Panel::active_callback()
	 */
	public function test_active() {
		$panel = new WP_Customize_Panel( $this->manager, 'foo' );
		$this->assertTrue( $panel->active() );

		$panel = new WP_Customize_Panel(
			$this->manager,
			'foo',
			array(
				'active_callback' => '__return_false',
			)
		);
		$this->assertFalse( $panel->active() );
		add_filter( 'customize_panel_active', array( $this, 'filter_active_test' ), 10, 2 );
		$this->assertTrue( $panel->active() );
	}

	/**
	 * @param bool $active
	 * @param WP_Customize_Panel $panel
	 * @return bool
	 */
	public function filter_active_test( $active, $panel ) {
		$this->assertFalse( $active );
		$this->assertInstanceOf( 'WP_Customize_Panel', $panel );
		$active = true;
		return $active;
	}

	/**
	 * @see WP_Customize_Panel::json()
	 */
	public function test_json() {
		$args  = array(
			'priority'        => 200,
			'capability'      => 'edit_posts',
			'theme_supports'  => 'html5',
			'title'           => 'Hello World',
			'description'     => 'Lorem Ipsum',
			'type'            => 'horizontal',
			'active_callback' => '__return_true',
		);
		$panel = new WP_Customize_Panel( $this->manager, 'foo', $args );
		$data  = $panel->json();
		$this->assertSame( 'foo', $data['id'] );
		foreach ( array( 'title', 'description', 'priority', 'type' ) as $key ) {
			$this->assertSame( $args[ $key ], $data[ $key ] );
		}
		$this->assertEmpty( $data['content'] );
		$this->assertTrue( $data['active'] );
		$this->assertIsInt( $data['instanceNumber'] );
	}

	/**
	 * @see WP_Customize_Panel::check_capabilities()
	 */
	public function test_check_capabilities() {
		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		$panel = new WP_Customize_Panel( $this->manager, 'foo' );
		$this->assertTrue( $panel->check_capabilities() );
		$old_cap           = $panel->capability;
		$panel->capability = 'do_not_allow';
		$this->assertFalse( $panel->check_capabilities() );
		$panel->capability = $old_cap;
		$this->assertTrue( $panel->check_capabilities() );
		$panel->theme_supports = 'impossible_feature';
		$this->assertFalse( $panel->check_capabilities() );
	}

	/**
	 * @see WP_Customize_Panel::get_content()
	 */
	public function test_get_content() {
		$panel = new WP_Customize_Panel( $this->manager, 'foo' );
		$this->assertEmpty( $panel->get_content() );
	}

	/**
	 * @see WP_Customize_Panel::maybe_render()
	 */
	public function test_maybe_render() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$panel                        = new WP_Customize_Panel( $this->manager, 'bar' );
		$customize_render_panel_count = did_action( 'customize_render_panel' );
		add_action( 'customize_render_panel', array( $this, 'action_customize_render_panel_test' ) );
		ob_start();
		$panel->maybe_render();
		$content = ob_get_clean();
		$this->assertTrue( $panel->check_capabilities() );
		$this->assertEmpty( $content );
		$this->assertSame( $customize_render_panel_count + 1, did_action( 'customize_render_panel' ), 'Unexpected did_action count for customize_render_panel' );
		$this->assertSame( 1, did_action( "customize_render_panel_{$panel->id}" ), "Unexpected did_action count for customize_render_panel_{$panel->id}" );
	}

	/**
	 * @see WP_Customize_Panel::maybe_render()
	 * @param WP_Customize_Panel $panel
	 */
	public function action_customize_render_panel_test( $panel ) {
		$this->assertInstanceOf( 'WP_Customize_Panel', $panel );
	}

	/**
	 * @see WP_Customize_Panel::print_template()
	 */
	public function test_print_templates_standard() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$panel = new WP_Customize_Panel( $this->manager, 'baz' );
		ob_start();
		$panel->print_template();
		$content = ob_get_clean();
		$this->assertStringContainsString( '<script type="text/html" id="tmpl-customize-panel-default-content">', $content );
		$this->assertStringContainsString( 'accordion-section-title', $content );
		$this->assertStringContainsString( 'control-panel-content', $content );
		$this->assertStringContainsString( '<script type="text/html" id="tmpl-customize-panel-default">', $content );
		$this->assertStringContainsString( 'customize-panel-description', $content );
		$this->assertStringContainsString( 'preview-notice', $content );
	}

	/**
	 * @see WP_Customize_Panel::print_template()
	 */
	public function test_print_templates_custom() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$panel = new Custom_Panel_Test( $this->manager, 'baz' );
		ob_start();
		$panel->print_template();
		$content = ob_get_clean();
		$this->assertStringContainsString( '<script type="text/html" id="tmpl-customize-panel-titleless-content">', $content );
		$this->assertStringNotContainsString( 'accordion-section-title', $content );

		$this->assertStringContainsString( '<script type="text/html" id="tmpl-customize-panel-titleless">', $content );
		$this->assertStringNotContainsString( 'preview-notice', $content );
	}
}

require_once ABSPATH . WPINC . '/class-wp-customize-panel.php';
class Custom_Panel_Test extends WP_Customize_Panel {
	public $type = 'titleless';

	protected function render_template() {
		?>
		<li id="accordion-panel-{{ data.id }}" class="accordion-section control-section control-panel control-panel-{{ data.type }}">
			<ul class="accordion-sub-container control-panel-content"></ul>
		</li>
		<?php
	}

	protected function content_template() {
		?>
		<li class="panel-meta accordion-section control-section<# if ( ! data.description ) { #> cannot-expand<# } #>">
			<# if ( data.description ) { #>
				<div class="accordion-section-content description">
					{{{ data.description }}}
				</div>
			<# } #>
		</li>
		<?php
	}

}
