<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Utility;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Abstraction methods which return system environment variables regardless
 * of server OS, CGI/MODULE version etc. Basically they are the _SERVER
 * variables in most cases.
 *
 * This class should be used instead of the $_SERVER/ENV_VARS to get reliable
 * values for all situations.
 *
 * WARNING: Don't try to subclass this class except for use in unit tests. The
 * superglobal replacement will lead to unexpected behavior (on your side).
 *
 * @version $Id: Environment.php 3551 2009-12-21 17:18:32Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class Environment {

	/**
	 * @var string
	 */
	protected $context = '';

	/**
	 * A local copy of the _SERVER super global
	 * @var array
	 */
	protected $SERVER;

	/**
	 * A local copy of the _GET super global
	 * @var array
	 */
	protected $GET;

	/**
	 * A local copy of the _POST super global
	 * @var array
	 */
	protected $POST;

	/**
	 * A local copy of the _FILES super global
	 * @var array
	 */
	protected $FILES;

	/**
	 * A lower case string specifying the currently used Server API. See php_sapi_name()/PHP_SAPI for possible values
	 * @var string
	 */
	protected $SAPIName = PHP_SAPI;

	/**
	 * The base path of $temporaryDirectory. This property can (and should) be set from outside.
	 * @var string
	 */
	protected $temporaryDirectoryBase;

	/**
	 * @var string
	 */
	protected $temporaryDirectory = NULL;

	/**
	 * @var \F3\FLOW3\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * Injects the system logger
	 *
	 * @param \F3\FLOW3\Log\SystemLoggerInterface $systemLogger
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function injectSystemLogger(\F3\FLOW3\Log\SystemLoggerInterface $systemLogger) {
		$this->systemLogger = $systemLogger;
	}

	/**
	 * Sets the FLOW3 context
	 *
	 * @param string $context The FLOW3 context
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setContext($context) {
		$this->context = $context;
	}

	/**
	 * Initializes the environment instance. Copies the superglobals $_SERVER,
	 * $_GET, $_POST, $_FILES to local variables and replaces the superglobals
	 * to block their use.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeObject() {
		if (!($_SERVER instanceof \F3\FLOW3\Utility\SuperGlobalReplacement)) {
			$this->SERVER = $_SERVER;
			$this->GET = $_GET;
			$this->POST = $_POST;
			$this->FILES = $this->untangleFilesArray($_FILES);

			$_SERVER = new \F3\FLOW3\Utility\SuperGlobalReplacement('_SERVER', 'Please use the API of ' . __CLASS__ . ' instead of accessing the superglobal directly.');
			$_GET = new \F3\FLOW3\Utility\SuperGlobalReplacement('_GET', 'Please use the Request object which is built by the Request Handler instead of accessing the _GET superglobal directly.');
			$_POST = new \F3\FLOW3\Utility\SuperGlobalReplacement('_POST', 'Please use the Request object which is built by the Request Handler instead of accessing the _POST superglobal directly.');
			$_FILES = new \F3\FLOW3\Utility\SuperGlobalReplacement('_FILES', 'Please use the Request object which is built by the Request Handler instead of accessing the _FILES superglobal directly.');
		}
	}

	/**
	 * Sets the base path of the temporary directory
	 *
	 * @param string $temporaryDirectoryBase Base path of the temporary directory, with trailing slash
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function setTemporaryDirectoryBase($temporaryDirectoryBase) {
		if (!is_string($temporaryDirectoryBase)) throw new \InvalidArgumentException('String expected.', 1228743683);
		$this->temporaryDirectoryBase = $temporaryDirectoryBase;
		$this->temporaryDirectory = NULL;
	}

	/**
	 * Returns the HTTP Host
	 *
	 * @return string The HTTP Host as found in _SERVER[HTTP_HOST]
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getHTTPHost() {
		return isset($this->SERVER['HTTP_HOST']) ? $this->SERVER['HTTP_HOST'] : NULL;
	}

	/**
	 * Returns the HTTP referer
	 *
	 * @return string The HTTP referer as found in _SERVER[HTTP_REFERER]
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getHTTPReferer() {
		return isset($this->SERVER['HTTP_REFERER']) ? $this->SERVER['HTTP_REFERER'] : NULL;
	}

	/**
	 * Returns the HTTP user agent
	 *
	 * @return string The HTTP user agent as found in _SERVER[HTTP_USER_AGENT]
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getHTTPUserAgent() {
		return isset($this->SERVER['HTTP_USER_AGENT']) ? $this->SERVER['HTTP_USER_AGENT'] : NULL;
	}

	/**
	 * Returns the HTTP accept language
	 *
	 * @return string The HTTP accept language as found in _SERVER[HTTP_ACCEPT_LANGUAGE]
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getHTTPAcceptLanguage() {
		return isset($this->SERVER['HTTP_ACCEPT_LANGUAGE']) ? $this->SERVER['HTTP_ACCEPT_LANGUAGE'] : NULL;
	}

	/**
	 * Returns the remote address
	 *
	 * @return string The remote address as found in _SERVER[REMOTE_ADDR]
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getRemoteAddress() {
		return isset($this->SERVER['REMOTE_ADDR']) ? $this->SERVER['REMOTE_ADDR'] : NULL;
	}

	/**
	 * Returns the remote host
	 *
	 * @return string The remote host as found in _SERVER[REMOTE_HOST]
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getRemoteHost() {
		return isset($this->SERVER['REMOTE_HOST']) ? $this->SERVER['REMOTE_HOST'] : NULL;
	}

	/**
	 * Returns the protocol (http or https) used in the request
	 *
	 * @return string The used protol, either http or https
	 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getRequestProtocol() {
		$protocol = 'http';
		if (isset($this->SERVER['SSL_SESSION_ID'])) $protocol = 'https';
		if (isset($this->SERVER['HTTPS'])) {
			if ($this->SERVER['HTTPS'] === 'on' || strcmp($this->SERVER['HTTPS'], '1') === 0) {
				$protocol = 'https';
			}
		}
		return $protocol;
	}

	/**
	 * Returns the request URI
	 *
	 * @return \F3\FLOW3\Property\DataType\Uri The request URI consisting of protocol, path and query, eg. http://typo3.org/xyz/index.php/arg1/arg2/arg3/?arg1,arg2,arg3&p1=parameter1&p2[key]=value
	 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getRequestUri() {
		if ($this->SAPIName === 'cli') {
			return FALSE;
		}
		if (isset($this->SERVER['PATH_INFO'])) {
			$requestUriString = $this->getRequestProtocol() . '://' . $this->getHTTPHost() . $this->SERVER['PATH_INFO'] . (strlen($this->SERVER['QUERY_STRING']) ? '?' . $this->SERVER['QUERY_STRING'] : '');
		} else {
			$requestUriString = $this->getRequestProtocol() . '://' . $this->getHTTPHost() . '/';
		}

		$requestUri = new \F3\FLOW3\Property\DataType\Uri($requestUriString);
		return $requestUri;
	}

	/**
	 * Returns the full, absolute path and the file name of the executed PHP file
	 *
	 * @return string The full path and file name of the PHP script
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getScriptPathAndFilename() {
		return \F3\FLOW3\Utility\Files::getUnixStylePath($this->SERVER['SCRIPT_FILENAME']);
	}

	/**
	 * Returns the relative path (ie. relative to the web root) and name of the
	 * script as it was accessed through the webserver.
	 *
	 * @return string Relative path and name of the PHP script as accessed through the web
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getScriptRequestPathAndName() {
		if (isset($this->SERVER['SCRIPT_NAME'])) return $this->SERVER['SCRIPT_NAME'];
		if (isset($this->SERVER['ORIG_SCRIPT_NAME'])) return $this->SERVER['ORIG_SCRIPT_NAME'];
	}

	/**
	 * Returns the request method as found in the SERVER environment.
	 *
	 * @return string The request method
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getRequestMethod() {
		return (isset($this->SERVER['REQUEST_METHOD'])) ? $this->SERVER['REQUEST_METHOD'] : NULL;
	}

	/**
	 * Returns the number of command line arguments, including the program name!
	 *
	 * @return integer The number of command line arguments passed to the main script.
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getCommandLineArgumentCount() {
		return isset($this->SERVER['argc']) ? $this->SERVER['argc'] : 0;
	}

	/**
	 * Returns an array of arguments passed through the command line.
	 * Only makes sense in CLI mode of course.
	 *
	 * @return array The command line arguments (including program name), if any
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getCommandLineArguments() {
		return isset($this->SERVER['argv']) ? $this->SERVER['argv'] : array();
	}

	/**
	 * Returns a lowercase string which identifies the currently used
	 * Server API (SAPI).
	 *
	 * Common SAPIS are "apache", "isapi", "cli", "cgi" etc.
	 *
	 * @return string A lower case string identifying the SAPI used
	 * @see php_sapi_name()/PHP_SAPI, getNormalizedSAPIName()
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getSAPIName() {
		return $this->SAPIName;
	}

	/**
	 * Returns the GET arguments array from the _GET superglobal
	 *
	 * @return array Unfiltered, raw, insecure, tainted GET arguments
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function getRawGetArguments() {
		return $this->GET;
	}

	/**
	 * Returns the POST arguments array from the _POST superglobal
	 *
	 * @return array Unfiltered, raw, insecure, tainted POST arguments
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getRawPostArguments() {
		return $this->POST;
	}

	/**
	 * Returns the FILES arguments array from the _FILES superglobal. It applies
	 * some helpful processing to the data before it is returned, so that arrays
	 * of uploaded files are "deinterweaved" to be more as one would expect.
	 *
	 * Nested array uploads are untangled in a smiliar way, so that you always
	 * get a nice and clean array of the form
	 *  argumentName => array(name, tmp_name, error, size, type)
	 * where argumentName can be a nested array if needed:
	 *  form field name "foo[bar]" leads to
	 *  argumentName foo => array(bar => array(...))
	 *
	 * @return array Unfiltered, raw, insecure, tainted files
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getUploadedFiles() {
		return $this->FILES;
	}

	/**
	 * Returns the unfiltered, raw, unchecked SERVER superglobal
	 * If available, please always use an alternative method of this API.
	 *
	 * @return array Unfiltered, raw, insecure, tainted SERVER environment
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getRawServerEnvironment() {
		return $this->SERVER;
	}

	/**
	 * Returns the full path to FLOW3's temporary directory.
	 *
	 * @return string Path to PHP's temporary directory
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getPathToTemporaryDirectory() {
		if ($this->temporaryDirectory !== NULL) return $this->temporaryDirectory;

		try {
			$this->temporaryDirectory = $this->createTemporaryDirectory($this->temporaryDirectoryBase);
		} catch (\F3\FLOW3\Utility\Exception $exception) {
			$fallBackTemporaryDirectoryBase = (DIRECTORY_SEPARATOR === '/') ? '/tmp' : '\\WINDOWS\\TEMP';
			$this->temporaryDirectory = $this->createTemporaryDirectory($fallBackTemporaryDirectoryBase);
		}
		return $this->temporaryDirectory;
	}

	/**
	 * Retrieves the maximum path lenght that is valid in the current environment.
	 *
	 * @return integer The maximum available path length
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getMaximumPathLength() {
		return PHP_MAXPATHLEN;
	}

	/**
	 * Whether or not URL rewriting is enabled.
	 *
	 * @return boolean
	 * @author Karsten Dambekalns <karsten@typo3.org
	 */
	public function isRewriteEnabled() {
		return (boolean)getenv('FLOW3_REWRITEURLS');
	}

	/**
	 * Transforms the convoluted _FILES superglobal into a manageable form.
	 *
	 * @param array $convolutedFiles The _FILES superglobal
	 * @return array Untangled files
	 */
	protected function untangleFilesArray(array $convolutedFiles) {
		$untangledFiles = array();

		$fieldPaths = array();
		foreach ($convolutedFiles as $firstLevelFieldName => $fieldInformation) {
			if (!is_array($fieldInformation['error'])) {
				$fieldPaths[] = array($firstLevelFieldName);
			} else {
				$newFieldPaths = $this->calculateFieldPaths($fieldInformation['error'], $firstLevelFieldName);
				array_walk($newFieldPaths,
					function(&$value, $key) {
						$value = explode('/', $value);
					}
				);
				$fieldPaths = array_merge($fieldPaths, $newFieldPaths);
			}
		}

		foreach ($fieldPaths as $fieldPath) {
			if (count($fieldPath) === 1) {
				$fileInformation = $convolutedFiles[$fieldPath{0}];
			} else {
				$fileInformation = array();
				foreach ($convolutedFiles[$fieldPath{0}] as $key => $subStructure) {
					$fileInformation[$key] = \F3\FLOW3\Utility\Arrays::getValueByPath($subStructure, array_slice($fieldPath, 1));
				}
			}
			$untangledFiles = \F3\FLOW3\Utility\Arrays::setValueByPath($untangledFiles, $fieldPath, $fileInformation);
		}
		return $untangledFiles;
	}

	/**
	 *  Returns and array of all possibles "field paths" for the given array.
	 *
	 *  @param array $structure The array to walk through
	 *  @return array An array of paths (as strings) in the format "key1/key2/key3" ...
	 *  @author Robert Lemke <robert@typo3.org>
	 */
	protected function calculateFieldPaths(array $structure, $firstLevelFieldName = NULL) {
		$fieldPaths = array();
		if (is_array($structure)) {
			foreach ($structure as $key => $subStructure) {
				$fieldPath = ($firstLevelFieldName !== NULL ? $firstLevelFieldName . '/' : '') . $key;
				if (is_array($subStructure)) {
					foreach($this->calculateFieldPaths($subStructure) as $subFieldPath) {
						$fieldPaths[] = $fieldPath . '/' . $subFieldPath;
					}
				} else {
					$fieldPaths[] = $fieldPath;
				}
			}
		}
		return $fieldPaths;
	}

	/**
	 * Creates FLOW3's temporary directory - or at least asserts that it exists and is
	 * writable.
	 *
	 * @param string $temporaryDirectoryBase Full path to the base for the temporary directory
	 * @return string The full path to the temporary directory
	 * @throws \F3\FLOW3\Utility\Exception if the temporary directory could not be created or is not writable
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function createTemporaryDirectory($temporaryDirectoryBase) {
		$temporaryDirectoryBase = \F3\FLOW3\Utility\Files::getUnixStylePath($temporaryDirectoryBase);
		if (substr($temporaryDirectoryBase, -1, 1) !== '/') $temporaryDirectoryBase .= '/';

		$maximumPathLength = $this->getMaximumPathLength();
		if (strlen($temporaryDirectoryBase) > ($maximumPathLength - 230)) {
			$this->systemLogger->log('The path to your temporary directory is ' . strlen($temporaryDirectoryBase) . ' characters long. The maximum path length of your system is only ' . $maximumPathLength . '. Please consider setting the temporaryDirectoryBase option to a shorter path.', LOG_WARNING);
		}

		$processUser = extension_loaded('posix') ? posix_getpwuid(posix_geteuid()) : array('name' => 'default');
		$pathHash = substr(md5(FLOW3_PATH_WEB . $this->getSAPIName() . $processUser['name'] . $this->context), 0, 12);
		$temporaryDirectory = $temporaryDirectoryBase . $pathHash . '/';

		if (!is_dir($temporaryDirectory)) {
			try {
				\F3\FLOW3\Utility\Files::createDirectoryRecursively($temporaryDirectory);
			} catch (\F3\FLOW3\Error\Exception $exception) {
			}
		}

		if (!is_writable($temporaryDirectory)) {
			throw new \F3\FLOW3\Utility\Exception('The temporary directory "' . $temporaryDirectory . '" could not be created or is not writable for the current user "' . $processUser['name'] . '". Please make this directory writable or define another temporary directory by setting the respective system environment variable (eg. TMPDIR) or defining it in the FLOW3 settings.', 1216287176);
		}

		return $temporaryDirectory;
	}
}
?>