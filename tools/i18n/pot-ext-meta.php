<?php
/**
 * Console application, which adds metadata strings from
 * a ClassicPress extension to a POT file
 *
 * @package wordpress-i18n
 * @subpackage tools
 */

$pomo = dirname( dirname( dirname( __FILE__ ) ) ) . '/src/wp-includes/pomo';
require_once "$pomo/po.php";
require_once dirname( __FILE__ ) . '/makepot.php';

class PotExtMeta {

	var $headers = array(
		'Plugin Name',
		'Theme Name',
		'Plugin URI',
		'Theme URI',
		'Description',
		'Author',
		'Author URI',
		'Tags',
	);


	function usage() {
		fwrite(STDERR, "Usage: php pot-ext-meta.php EXT POT\n");
		fwrite(STDERR, "Adds metadata from a ClassicPress theme or plugin file EXT to POT file\n");
		exit(1);
	}

	function load_from_file($ext_filename) {
		$makepot = new MakePOT;
		$source = $makepot->get_first_lines($ext_filename);
		$pot = '';
		$po = new PO;
		foreach($this->headers as $header) {
			$string = $makepot->get_addon_header($header, $source);
			if (!$string) continue;
			$args = array(
				'singular' => $string,
				'extracted_comments' => $header.' of the plugin/theme',
			);
			$entry = new Translation_Entry($args);
			$pot .= "\n".$po->export_entry($entry)."\n";
		}
		return $pot;
	}

	function append( $ext_filename, $pot_filename, $headers = null ) {
		if ( $headers )
			$this->headers = (array) $headers;
		if ( is_dir( $ext_filename ) ) {
			$pot = implode('', array_map(array($this, 'load_from_file'), glob("$ext_filename/*.php")));
		} else {
			$pot = $this->load_from_file($ext_filename);
		}
		$potf = '-' == $pot_filename? STDOUT : fopen($pot_filename, 'a');
		if (!$potf) return false;
		fwrite($potf, $pot);
		if ('-' != $pot_filename) fclose($potf);
		return true;
	}
}

$included_files = get_included_files();
if ($included_files[0] == __FILE__) {
	ini_set('display_errors', 1);
	$potextmeta = new PotExtMeta;
	if (!isset($argv[1])) {
		$potextmeta->usage();
	}
	$potextmeta->append( $argv[1], isset( $argv[2] ) ? $argv[2] : '-', isset( $argv[3] ) ? $argv[3] : null );
}

?>
