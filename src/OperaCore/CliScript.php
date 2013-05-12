<?php
namespace OperaCore;
/**
 * User: nacho
 * Date: 07/02/12
 */

abstract class CliScript extends Controller
{
	/**
	 * color green
	 */
	const GREEN = "\033[32m";

	/**
	 * color red
	 */
	const RED   = "\033[31m";

	/**
	 * color cyan
	 */
	const CYAN  = "\033[36m";

	/**
	 * color white (default!)
	 */
	const WHITE = "\033[37m";

	/**
	 * @var Container
	 */
	protected $container;

	/**
	 * Just execute $this->run after setting the container
	 *
	 * @param array $argv The argv array that PHP receives in CLI
	 * @param Container $container container injected
	 */
	public function __construct( $argv, $container )
	{
		$this->setContainer( $container );
		// init i18n for defines.
		$this->container['I18n'];
		$this->run( $argv );
	}

	/**
	 * This does the job
	 *
	 * @abstract
	 * @param array $argv The argv array that PHP receives in CLI
	 */
	abstract protected function run( $argv );

	/**
	 * If we need more than this, start thinking in extending some CLI class
	 *
	 * @param string $line The line to print
	 * @param string $color The color to use
     *
	 * @return int
	 */
    public function output( $line, $color = self::WHITE )
	{
        if( self::WHITE != $color ) $line = $color . $line . self::WHITE;
        return fwrite( STDOUT, $line ."\n" );
	}

    /**
     * Ask for input interactively
     *
     * @return string the user input
     */
    protected function getInput()
    {
        return trim( fgets( STDIN ) );
    }

    /**
     * Get a password from the shell.
     *
     * This function works on *nix systems only and requires shell_exec and stty.
     *
     * @param  boolean $stars Wether or not to output stars for given characters
     *
     * @return string
     */
    function getPassword( $stars = false )
    {
        // Get current style
        $oldStyle = shell_exec('stty -g');

        if ($stars === false) {
            shell_exec('stty -echo');
            $password = rtrim(fgets(STDIN), "\n");
        } else {
            shell_exec('stty -icanon -echo min 1 time 0');

            $password = '';
            while (true) {
                $char = fgetc(STDIN);

                if ($char === "\n") {
                    break;
                } else if (ord($char) === 127) {
                    if (strlen($password) > 0) {
                        fwrite(STDOUT, "\x08 \x08");
                        $password = substr($password, 0, -1);
                    }
                } else {
                    fwrite(STDOUT, "*");
                    $password .= $char;
                }
            }
        }

        // Reset old style
        shell_exec('stty ' . $oldStyle);

        $this->output("");

        // Return the password
        return $password;
    }
}
