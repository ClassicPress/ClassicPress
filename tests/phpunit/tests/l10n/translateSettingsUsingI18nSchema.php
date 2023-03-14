<?php

/**
 * @group l10n
 * @group i18n
 *
 * @covers ::translate_settings_using_i18n_schema
 */
class Tests_L10n_TranslateSettingsUsingI18nSchema extends WP_UnitTestCase {
	/**
	 * Returns Polish locale string.
	 *
	 * @return string
	 */
	public function filter_set_locale_to_polish() {
		return 'pl_PL';
	}

	/**
	 * @ticket 53238
	 */
	public function test_translate_settings_using_i18n_schema() {
		$textdomain = 'notice';

		add_filter( 'locale', array( $this, 'filter_set_locale_to_polish' ) );
		load_textdomain( $textdomain, WP_LANG_DIR . '/plugins/notice-pl_PL.mo' );

		$i18n_schema = (object) array(
			'title'    => 'block title',
			'keywords' => array( 'block keyword' ),
			'styles'   => array(
				(object) array( 'label' => 'block style label' ),
			),
			'context'  => (object) array(
				'*' => (object) array(
					'variations' => array(
						(object) array(
							'title'       => 'block variation title',
							'description' => 'block variation description',
							'keywords'    => array( 'block variation keyword' ),
						),
					),
				),
			),
		);
		$settings    = array(
			'title'    => 'Notice',
			'keywords' => array(
				'alert',
				'message',
			),
			'styles'   => array(
				array( 'label' => 'Default' ),
				array( 'label' => 'Other' ),
			),
			'context'  => array(
				'namespace' => array(
					'variations' => array(
						array(
							'title'       => 'Error',
							'description' => 'Shows error.',
							'keywords'    => array( 'failure' ),
						),
					),
				),
			),
		);
		$result      = translate_settings_using_i18n_schema(
			$i18n_schema,
			$settings,
			$textdomain
		);

		unload_textdomain( $textdomain );
		remove_filter( 'locale', array( $this, 'filter_set_locale_to_polish' ) );

		$this->assertSame( 'Powiadomienie', $result['title'] );
		$this->assertSameSets( array( 'ostrzeżenie', 'wiadomość' ), $result['keywords'] );
		$this->assertSame(
			array(
				array(
					'label' => 'Domyślny',
				),
				array(
					'label' => 'Inny',
				),
			),
			$result['styles']
		);
		$this->assertSame(
			array(
				array(
					'title'       => 'Błąd',
					'description' => 'Wyświetla błąd.',
					'keywords'    => array( 'niepowodzenie' ),
				),
			),
			$result['context']['namespace']['variations']
		);
	}
}
