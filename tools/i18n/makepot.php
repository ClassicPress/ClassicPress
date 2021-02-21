<?php
require_once dirname( __FILE__ ) . '/not-gettexted.php';
require_once dirname( __FILE__ ) . '/pot-ext-meta.php';
require_once dirname( __FILE__ ) . '/extract.php';

if ( !defined( 'STDERR' ) ) {
	define( 'STDERR', fopen( 'php://stderr', 'w' ) );
}

/**
 * Class to create POT files for
 *  - ClassicPress core
 *  - ClassicPress plugins (untested)
 *  - ClassicPress themes (untested)
 *
 * Support for older projects can be found in the legacy branch:
 * https://i18n.trac.wordpress.org/browser/tools/branches/legacy
 */
class MakePOT {
	private $max_header_lines = 30;

	public $projects = array(
		'cp-core',
		'cp-plugin',
		'cp-theme',
	);

	public $rules = array(
		'_' => array('string'),
		'__' => array('string'),
		'_e' => array('string'),
		'_c' => array('string'),
		'_n' => array('singular', 'plural'),
		'_n_noop' => array('singular', 'plural'),
		'_nc' => array('singular', 'plural'),
		'__ngettext' => array('singular', 'plural'),
		'__ngettext_noop' => array('singular', 'plural'),
		'_x' => array('string', 'context'),
		'_ex' => array('string', 'context'),
		'_nx' => array('singular', 'plural', null, 'context'),
		'_nx_noop' => array('singular', 'plural', 'context'),
		'_n_js' => array('singular', 'plural'),
		'_nx_js' => array('singular', 'plural', 'context'),
		'esc_attr__' => array('string'),
		'esc_html__' => array('string'),
		'esc_attr_e' => array('string'),
		'esc_html_e' => array('string'),
		'esc_attr_x' => array('string', 'context'),
		'esc_html_x' => array('string', 'context'),
		'comments_number_link' => array('string', 'singular', 'plural'),
	);

	private $temp_files = array();

	public $meta = array(
		'default' => array(
			'from-code' => 'utf-8',
			'msgid-bugs-address' => 'https://forums.classicpress.net/c/team-discussions/internationalisation/42',
			'language' => 'php',
			'add-comments' => 'translators',
			'comments' => "Copyright (C) {year} {package-name}\nThis file is distributed under the same license as the {package-name} package.",
		),
		'cp-core' => array(
			'language' => 'php',
			'package-version' => '{version}',
			'package-name' => 'ClassicPress',
			'comments' => "Copyright (C) {year} {package-name}\nThis file is distributed under the same license as the {package-name} package.",
		),
		'cp-plugin' => array(
			'description' => 'Translation of the ClassicPress plugin {name} {version} by {author}',
			'msgid-bugs-address' => 'https://forums.classicpress.net/c/plugins/plugin-support/67',
			'copyright-holder' => '{author}',
			'package-name' => '{name}',
			'package-version' => '{version}',
		),
		'cp-theme' => array(
			'description' => 'Translation of the ClassicPress theme {name} {version} by {author}',
			'msgid-bugs-address' => 'https://wordpress.org/support/theme/{slug}',
			'copyright-holder' => '{author}',
			'package-name' => '{name}',
			'package-version' => '{version}',
			'comments' => 'Copyright (C) {year} {author}\nThis file is distributed under the same license as the {package-name} package.',
		),
	);

	public function __construct($deprecated = true) {
		$this->extractor = new StringExtractor( $this->rules );
	}

	public function __destruct() {
		foreach ( $this->temp_files as $temp_file )
			unlink( $temp_file );
	}

	private function tempnam( $file ) {
		$tempnam = tempnam( sys_get_temp_dir(), $file );
		$this->temp_files[] = $tempnam;
		return $tempnam;
	}

	private function realpath_missing($path) {
		return realpath(dirname($path)).DIRECTORY_SEPARATOR.basename($path);
	}

	private function xgettext($project, $dir, $output_file, $placeholders = array(), $excludes = array(), $includes = array()) {
		$meta = array_merge( $this->meta['default'], $this->meta[$project] );
		$placeholders = array_merge( $meta, $placeholders );
		$meta['output'] = $this->realpath_missing( $output_file );
		$placeholders['year'] = date( 'Y' );
		$placeholder_keys = array_map( create_function( '$x', 'return "{".$x."}";' ), array_keys( $placeholders ) );
		$placeholder_values = array_values( $placeholders );
		foreach($meta as $key => $value) {
			$meta[$key] = str_replace($placeholder_keys, $placeholder_values, $value);
		}

		$originals = $this->extractor->extract_from_directory( $dir, $excludes, $includes );

		// Crowdin doesn't like spaces in between different kinds of comment blocks
		foreach ( $originals->entries as $str => &$entry ) {
			if ( ! empty( $entry->extracted_comments ) ) {
				$entry->extracted_comments = trim( $entry->extracted_comments );
			}
		}

		$pot = new PO;
		$pot->entries = $originals->entries;

		$pot->set_header( 'Project-Id-Version', $meta['package-name'].' '.$meta['package-version'] );
		$pot->set_header( 'Report-Msgid-Bugs-To', $meta['msgid-bugs-address'] );
		if ( $project !== 'cp-core' ) {
			// Do not put unnecessary information in the core .pot files, these
			// will be managed using git so dates and authors are not needed
			$pot->set_header( 'POT-Creation-Date', gmdate( 'Y-m-d H:i:s+00:00' ) );
		}
		$pot->set_header( 'MIME-Version', '1.0' );
		$pot->set_header( 'Content-Type', 'text/plain; charset=UTF-8' );
		$pot->set_header( 'Content-Transfer-Encoding', '8bit' );
		if ( $project !== 'cp-core' ) {
			$pot->set_header( 'PO-Revision-Date', date( 'Y') . '-MO-DA HO:MI+ZONE' );
			$pot->set_header( 'Last-Translator', 'FULL NAME <EMAIL@ADDRESS>' );
			$pot->set_header( 'Language-Team', 'LANGUAGE <LL@li.org>' );
		}
		$pot->set_comment_before_headers( $meta['comments'] );
		$pot->export_to_file( $output_file );
		return true;
	}

	private function cp_version( $dir ) {
		$version_php = $dir . '/wp-includes/version.php';
		if ( ! is_readable( $version_php ) ) {
			fwrite( STDERR, "File not found: $version_php\n" );
			return false;
		}
		# Get the current git development version
		$git_dir = $dir . '/../.git';
		if ( ! is_dir( $git_dir ) ) {
			fwrite( STDERR, "git directory not found: $git_dir\n" );
			return false;
		}
		$old_dir = getcwd();
		chdir( $git_dir );
		ob_start();
		system( 'git describe', $exit_code );
		$git_version = trim( ob_get_clean() );
		if ( $exit_code ) {
			fwrite( STDERR, "git describe exited with code $exit_code: $git_version\n" );
			return false;
		}
		$pot_version = $git_version;
		// This could be, for example:
		// '1.0.0+dev' indicating the source for a released version
		//   -> strip the '+dev' suffix and return '1.0.0'
		// '1.1.2+dev-6-g077a6862c4' indicating a release plus some changes
		//   -> return '1.1.2+modified'
		$pot_version = preg_replace( '#\+dev$#', '', $pot_version );
		$pot_version = preg_replace( '#\+dev-.*$#', '+modified', $pot_version );
		return [ 'git' => $git_version, 'pot' => $pot_version ];
	}

	public function get_first_lines($filename, $lines = 30) {
		$extf = fopen($filename, 'r');
		if (!$extf) return false;
		$first_lines = '';
		foreach(range(1, $lines) as $x) {
			$line = fgets($extf);
			if (feof($extf)) break;
			if (false === $line) {
				return false;
			}
			$first_lines .= $line;
		}

		// PHP will close file handle, but we are good citizens.
		fclose( $extf );

		// Make sure we catch CR-only line endings.
		$first_lines = str_replace( "\r", "\n", $first_lines );

		return $first_lines;
	}

	public function get_addon_header($header, &$source) {
		/*
		 * A few things this needs to handle:
		 * - 'Header: Value\n'
		 * - '// Header: Value'
		 * - '/* Header: Value * /'
		 * - '<?php // Header: Value ?>'
		 * - '<?php /* Header: Value * / $foo='bar'; ?>'
		 */
		if ( preg_match( '/^(?:[ \t]*<\?php)?[ \t\/*#@]*' . preg_quote( $header, '/' ) . ':(.*)$/mi', $source, $matches ) ) {
			return $this->_cleanup_header_comment( $matches[1] );
		} else {
			return false;
		}
	}

	/**
	 * Removes any trailing closing comment / PHP tags from the header value
	 */
	private function _cleanup_header_comment( $str ) {
		return trim( preg_replace( '/\s*(?:\*\/|\?>).*/', '', $str ) );
	}

	public function cp_core($dir, $output = null, $slug = null, $args = array()) {
		$defaults = array(
			'project' => 'cp-core',
			'default_output' => 'classicpress.pot',
			'includes' => array(),
			'excludes' => array_merge(
				array( 'wp-content/plugins/*', 'wp-content/themes/*', 'wp-includes/class-pop3.php' )
			),
			'extract_not_gettexted' => false,
			'not_gettexted_files_filter' => false,
		);
		$args = array_merge( $defaults, $args );
		extract( $args );
		$placeholders = array();
		if ( $cp_version = $this->cp_version( $dir ) ) {
			$placeholders['version'] = $cp_version['pot'];
			fprintf(
				STDERR,
				"Found ClassicPress version %s -> %s\n",
				$cp_version['git'],
				$cp_version['pot']
			);
		} else {
			return false;
		}
		$output = is_null( $output )? $default_output : $output;
		$res = $this->xgettext( $project, $dir, $output, $placeholders, $excludes, $includes );
		if ( !$res ) return false;
		if ( $extract_not_gettexted ) {
			$old_dir = getcwd();
			$output = realpath( $output );
			chdir( $dir );
			$php_files = NotGettexted::list_php_files('.');
			$php_files = array_filter( $php_files, $not_gettexted_files_filter );
			$not_gettexted = new NotGettexted;
			$res = $not_gettexted->command_extract( $output, $php_files );
			chdir( $old_dir );
			/* Adding non-gettexted strings can repeat some phrases */
			$output_shell = escapeshellarg( $output );
			system( "msguniq --use-first $output_shell -o $output_shell" );
		}
		return $res;
	}

	private function guess_plugin_slug($dir) {
		if ('trunk' == basename($dir)) {
			$slug = basename(dirname($dir));
		} elseif (in_array(basename(dirname($dir)), array('branches', 'tags'))) {
			$slug = basename(dirname(dirname($dir)));
		} else {
			$slug = basename($dir);
		}
		return $slug;
	}

	public function cp_plugin( $dir, $output, $slug = null, $args = array() ) {
		$defaults = array(
			'excludes' => array(),
			'includes' => array(),
		);
		$args = array_merge( $defaults, $args );
		$placeholders = array();
		// guess plugin slug
		if (is_null($slug)) {
			$slug = $this->guess_plugin_slug($dir);
		}

		$plugins_dir = @opendir( $dir );
		$plugin_files = array();
		if ( $plugins_dir ) {
			while ( ( $file = readdir( $plugins_dir ) ) !== false ) {
				if ( '.' === substr( $file, 0, 1 ) ) {
					continue;
				}

				if ( '.php' === substr( $file, -4 ) ) {
					$plugin_files[] = $file;
				}
			}
			closedir( $plugins_dir );
		}

		if ( empty( $plugin_files ) ) {
			return false;
		}

		$main_file = '';
		foreach ( $plugin_files as $plugin_file ) {
			if ( ! is_readable( "$dir/$plugin_file" ) ) {
				continue;
			}

			$source = $this->get_first_lines( "$dir/$plugin_file", $this->max_header_lines );

			// Stop when we find a file with a plugin name header in it.
			if ( $this->get_addon_header( 'Plugin Name', $source ) != false ) {
				$main_file = "$dir/$plugin_file";
				break;
			}
		}

		if ( empty( $main_file ) ) {
			return false;
		}

		$placeholders['version'] = $this->get_addon_header('Version', $source);
		$placeholders['author'] = $this->get_addon_header('Author', $source);
		$placeholders['name'] = $this->get_addon_header('Plugin Name', $source);
		$placeholders['slug'] = $slug;

		$output = is_null($output)? "$slug.pot" : $output;
		$res = $this->xgettext( 'cp-plugin', $dir, $output, $placeholders, $args['excludes'], $args['includes'] );
		if (!$res) return false;
		$potextmeta = new PotExtMeta;
		$res = $potextmeta->append($main_file, $output);
		/* Adding non-gettexted strings can repeat some phrases */
		$output_shell = escapeshellarg($output);
		system("msguniq $output_shell -o $output_shell");
		return $res;
	}

	public function cp_theme($dir, $output, $slug = null) {
		$placeholders = array();
		// guess plugin slug
		if (is_null($slug)) {
			$slug = $this->guess_plugin_slug($dir);
		}
		$main_file = $dir.'/style.css';
		$source = $this->get_first_lines($main_file, $this->max_header_lines);

		$placeholders['version'] = $this->get_addon_header('Version', $source);
		$placeholders['author'] = $this->get_addon_header('Author', $source);
		$placeholders['name'] = $this->get_addon_header('Theme Name', $source);
		$placeholders['slug'] = $slug;

		$license = $this->get_addon_header( 'License', $source );
		if ( $license )
			$this->meta['cp-theme']['comments'] = "Copyright (C) {year} {author}\nThis file is distributed under the {$license}.";
		else
			$this->meta['cp-theme']['comments'] = "Copyright (C) {year} {author}\nThis file is distributed under the same license as the {package-name} package.";

		$output = is_null($output)? "$slug.pot" : $output;
		$res = $this->xgettext('cp-theme', $dir, $output, $placeholders);
		if (! $res )
			return false;
		$potextmeta = new PotExtMeta;
		$res = $potextmeta->append( $main_file, $output, array( 'Theme Name', 'Theme URI', 'Description', 'Author', 'Author URI' ) );
		if ( ! $res )
			return false;
		// If we're dealing with a pre-3.4 default theme, don't extract page templates before 3.4.
		$extract_templates = ! in_array( $slug, array( 'twentyten', 'twentyeleven', 'default', 'classic' ) );
		if ( ! $extract_templates ) {
			$wp_dir = dirname( dirname( dirname( $dir ) ) );
			$extract_templates = file_exists( "$wp_dir/wp-admin/user/about.php" ) || ! file_exists( "$wp_dir/wp-load.php" );
		}
		if ( $extract_templates ) {
			$res = $potextmeta->append( $dir, $output, array( 'Template Name' ) );
			if ( ! $res )
				return false;
			$files = scandir( $dir );
			foreach ( $files as $file ) {
				if ( '.' == $file[0] || 'CVS' == $file )
					continue;
				if ( is_dir( $dir . '/' . $file ) ) {
					$res = $potextmeta->append( $dir . '/' . $file, $output, array( 'Template Name' ) );
					if ( ! $res )
						return false;
				}
			}
		}
		/* Adding non-gettexted strings can repeat some phrases */
		$output_shell = escapeshellarg($output);
		system("msguniq $output_shell -o $output_shell");
		return $res;
	}
}

// run the CLI only if the file
// wasn't included
$included_files = get_included_files();
if ( $included_files[0] == __FILE__ ) {
	$makepot = new MakePOT;
	if (
		count( $argv ) >= 3 &&
		count( $argv ) <= 4 &&
		in_array(
			$method = str_replace( '-', '_', $argv[1] ),
			get_class_methods( $makepot ),
			true
		)
	) {
		$pot_file = isset( $argv[3] ) ? $argv[3] : null;
		$res = call_user_func(
			array( $makepot, $method ),
			realpath( $argv[2] ),
			$pot_file
		);
		if ( false === $res ) {
			fwrite( STDERR, "Couldn't generate POT file!\n" );
			exit( 1 );
		} else {
			fwrite( STDERR, "Generated POT file!\n" );
		}
	} else {
		$usage  = "Usage: php makepot.php PROJECT DIRECTORY [OUTPUT]\n\n";
		$usage .= "Generate POT file from the files in DIRECTORY [OUTPUT]\n";
		$usage .= "Available projects: " . implode( ', ', $makepot->projects ) . "\n";
		fwrite( STDERR, $usage );
		exit( 1 );
	}
}
