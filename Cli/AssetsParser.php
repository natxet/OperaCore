<?php
namespace natxet\OperaCore\Cli;
/**
 * User: nacho
 * Date: 07/02/12
 */
class AssetsParser extends \natxet\OperaCore\CliScript
{
	/**
	 * @var array with key => value
	 */
	static protected $paths;

	/**
	 * @var array defines the possible types
	 */
	protected $types = array( 'css', 'js' );

	protected $app_path;

	/**
	 * @param array $argv the cli argv
	 */
	protected function run( $argv )
	{
		echo "\n=== Init cron\n\n";

		list( , // run.php
			, // name of this class
			$env, // environment dev pro
			$app, // The app name
			) = $argv;

		$this->processApp( $env, $app );

		echo "\n=== Finished cron\n\n";
	}

	protected function processApp( $env, $app )
	{
		echo "== Procesando App $app\n";

		$this->app_path = realpath( __DIR__ . "/../../../app/$app/" ) . '/';
		$config_path    = "{$this->app_path}config/";

		$config      = $this->container['Config'];
		self::$paths = $config->get( 'main', 'paths' );

		foreach ( $this->types as $type )
		{
			$this->deleteGeneratedFiles( "{$this->app_path}public/$type/", $env );
		}

		$framework_assets = $config->get( 'main', 'framework_assets', 'assets' );
		$assets_config    = $this->processFrameworkAssets( "{$this->app_path}public/", $framework_assets );

		$assets = $config->get( 'main', 'assets', 'assets' );
		foreach ( $assets as $asset )
		{
			list( $basename, $extension ) = explode( '.', $asset );
			$checksum = $this->parseAndMinify( $basename, $extension, $env );

			$assets_config[$asset] = array(
				'basename'  => $basename,
				'extension' => $extension,
				'checksum'  => $checksum,
				'url'       => self::$paths[$extension] . $checksum . ".$env.gen.$extension"
			);
		}

		$config_filename = ( $env == 'dev' ) ? 'assets.gen.dev.ini' : 'assets.gen.ini';
		echo "\nWriting config file $config_path$config_filename\n";
		$config->write_ini(
			$assets_config, $config_path . $config_filename,
			'; This is a generated file, please manage your assets in main.ini'
		);
	}

	/**
	 * @param string $app_public_path
	 * @param array  $assets
	 *
	 * @return array
	 */
	function processFrameworkAssets( $app_public_path, $assets )
	{
		echo "\nProcessing framework assets\n";

		$assets_config = array();
		foreach ( $assets as $asset )
		{
			list( $basename, $extension ) = explode( '.', $asset );

			$app_asset_name = preg_replace( '/(.*)(\.[a-z]+)/', '\1.fw\2', $asset );
			$app_asset      = "$app_public_path$extension/$app_asset_name";
			$fw_asset       = "$app_public_path../../../vendor/Framework/public/$extension/$asset";

			if ( !file_exists( $app_asset ) )
			{
				echo " - copying $asset to $app_asset : " . copy( $fw_asset, $app_asset ) . "\n";
			}
			else
			{
				echo " - checked: $app_asset_name exists in $app_public_path$extension/\n";
			}

			$assets_config[$asset] = array(
				'basename'  => $basename,
				'extension' => $extension,
				'url'       => self::$paths[$extension] . "$app_asset_name"
			);
		}
		echo "\n";
		return $assets_config;
	}

	function deleteGeneratedFiles( $path, $env )
	{
		foreach ( new \DirectoryIterator( $path ) as $file )
		{
			$filename = $file->getFilename();

			if ( preg_match( "/.*\\.$env\\.gen\\.(" . implode( '|', $this->types ) . ")/", $filename ) )
			{
				echo "unlinking $path$filename ";
				echo unlink( "$path$filename" ) . "\n";
			}
		}
	}

	function parseAndMinify( $basename, $extension, $env = 'dev' )
	{
		$path     = "{$this->app_path}public/$extension/";
		$filename = "$basename.$extension";
		echo "\nParsing $path$filename \n";

		$minified_filename = str_replace( ".$extension", ".min.$env.gen.$extension", $filename );
		$parsed_filename   = str_replace( ".$extension", ".par.$env.gen.$extension", $filename );
		$contents          = file_get_contents( $path . $filename );

		echo  " - $filename > $parsed_filename\n";
		$contents = $this->replacePaths( $contents );
		$contents = $this->replaceTranslations( $contents );
		file_put_contents( $path . $parsed_filename, $contents );

		echo  " - $filename > $minified_filename\n";
		$contents = self::minifyType( $extension, $contents );
		if ( $contents )
		{
			file_put_contents( $path . $minified_filename, $contents );

			$checksum          = md5_file( $path . $minified_filename );
			$checksum_filename = "$checksum.$env.gen.$extension";

			echo  " - $filename > $checksum_filename\n";
			copy( $path . $minified_filename, $path . $checksum_filename );
			return $checksum;
		}
		else
		{
			echo "!! ERROR: No minified contents in $filename";
			return false;
		}

	}

	/**
	 * @static
	 *
	 * @param string $type   css or js
	 * @param string $string the string to minify
	 *
	 * @return string the minified string
	 */
	static function minifyType( $type, $string )
	{
		switch ( $type )
		{
			case 'js':
				require_once( __DIR__ . '/../../JShrink/JShrink.php' );
				return \JShrink::minify( $string );
			case 'css':
				return \Framework\CssMinifier::minify( $string );
			default:
				return '';
		}
	}

	/**
	 * @param string $string the string to parse searching for {{path.XXX}}
	 *
	 * @return string the resulting string after substitutions
	 */
	function replacePaths( $string )
	{
		// /e modifier makes second parameter evaluated
		// this regex substitutes {{path.css}} with the path in the main.ini
		return preg_replace( '/{{path\.(.+?)}}/e', "self::getPath( '\\1' )", $string );
	}

	/**
	 * @param string $string the string to parse searching for {{path.XXX}}
	 *
	 * @return string the resulting string after substitutions
	 */
	function replaceTranslations( $string )
	{
		// /e modifier makes second parameter evaluated
		// this regex substitutes {{path.css}} with the path in the main.ini
		return $this->container['I18n']->parseTranlations( $string );
	}

	/**
	 * @static
	 *
	 * @param string $key The key in main.ini [paths]
	 *
	 * @return string The path
	 */
	static function getPath( $key )
	{
		// given a path key, returns the path for using in the frontend
		if ( !array_key_exists( $key, self::$paths ) ) return '';
		return self::$paths[$key];
	}
}
