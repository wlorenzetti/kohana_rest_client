<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Manages communication with REST services through a simple object abstraction
 * API. Instances of this class are referenced by name.
 *
 * @package    Kohana/REST
 * @category   Extension
 * @author     Neuroxy
 * @copyright  (c) 2010 Neuroxy
 * @license    FIXME
 */
class REST_Client {	

	/**
	 * @var  string  default instance name
	 */
	public static $default = 'default';

	/**
	 * @var  array  client instances
	 */
	public static $instances = array();

	/**
	 * Get a singleton object instance of this class. If configuration is not
	 * specified, it will be loaded from the rest configuration file using the
	 * same group as the provided name.
	 *
	 *     // Load the default client instance
	 *     $client = REST_Client::instance();
	 *
	 *     // Create a custom configured instance
	 *     $client = REST_Client::instance('custom', $config);
	 *
	 * @param   string   instance name
	 * @param   array    configuration parameters
	 * @return  REST_Client
	 */
	public static function instance($name = NULL, $config = NULL)
	{
		if ($name === NULL)
		{
			// Use the default instance name
			$name = self::$default;
		}

		if ( ! isset(self::$instances[$name]))
		{
			if ($config === NULL)
			{
				// Load the configuration for this client
				$config = Kohana::config('rest')->$name;
			}

			// Create the client instance
			new REST_Client($name, $config);
		}

		return self::$instances[$name];
	}

	/**
	 * Constants for supported HTTP methods
	 */
	const HTTP_GET    = 'GET';
	const HTTP_PUT    = 'PUT';
	const HTTP_POST   = 'POST';
	const HTTP_DELETE = 'DELETE';

	/**
	 * Constants for known HTTP statuses
	 */
	const HTTP_OK = 200;

	/**
	 * @var  object  the last uri that was requested
	 */
	public $last_uri;

	// Instance name
	protected $_instance;

	// Configuration array
	protected $_config;

	/**
	 * Stores the client configuration locally and names the instance.
	 *
	 * [!!] This method cannot be accessed directly, you must use [REST_Client::instance].
	 *
	 * @return  void
	 */
	protected function __construct($name, array $config)
	{
		// Set the instance name
		$this->_instance = $name;

		// Store the config locally
		$this->_config = $config;

		// Store this client instance
		self::$instances[$name] = $this;
	}

	/**
	 * Does an HTTP GET request and returns the result
	 *
	 * @param   string  the location that we are requesting
	 * @param   array   an array of key value pairs to transform into parameters
	 * @return  object  a REST_Response object
	 */
	public function get($location = NULL, $parameters = NULL)
	{
		// Get the requested document and return it
		return $this->_http_request(self::HTTP_GET, $location, $parameters);
	}

	/**
	 * Does an HTTP PUT request and returns the result
	 *
	 * @param   string  the location that we are requesting
	 * @param   mixed   an array of key value pairs to transform into parameters or a simple string to send as the body
	 * @return  object  a REST_Response object
	 */
	public function put($location = NULL, $parameters = NULL)
	{
		// Get the requested document and return it
		return $this->_http_request(self::HTTP_PUT, $location, $parameters);
	}

	/**
	 * Does an HTTP POST request and returns the result
	 *
	 * @param   string  the location that we are requesting
	 * @param   mixed   an array of key value pairs to transform into parameters or a simple string to send as the body
	 * @return  object  a REST_Response object
	 */
	public function post($location = NULL, $parameters = NULL)
	{
		// Get the requested document and return it
		return $this->_http_request(self::HTTP_POST, $location, $parameters);
	}

	/**
	 * Does an HTTP POST request and returns the result
	 *
	 * @param   string  the location that we are requesting
	 * @param   mixed   an array of key value pairs to transform into parameters or a simple string to send as the body
	 * @return  object  a REST_Response object
	 */
	public function delete($location = NULL, $parameters = NULL)
	{
		// Get the requested document and return it
		return $this->_http_request(self::HTTP_DELETE, $location, $parameters);
	}

	/**
	 * Makes the HTTP request out to the the remote REST server
	 *
	 * @param   string  the method we are using to make the HTTP request
	 * @param   string  the location that we are requesting
	 * @param   array   an array of key value pairs to transform into parameters
	 * @param   array   an array of key value pairs to transform into headers
	 * @return  object  a REST_Response object
	 */
	protected function _http_request($method, $location = NULL, $parameters = NULL, $headers = NULL)
	{
		// Determine what the final URI for this request should be
		$uri = $this->_build_uri($method, $location, $parameters);

		// Initialize the CURL library
		$curl_request = curl_init();

		// No matter what type of request this is we always need the URI
		curl_setopt($curl_request, CURLOPT_URL, $uri);

		// If this is a DELETE or PUT request
		if ($method === self::HTTP_DELETE OR $method === self::HTTP_PUT) {
			// Set the custom request option
			curl_setopt($curl_request, CURLOPT_CUSTOMREQUEST, $method);
		}

		// If this a POST request
		if ($method === self::HTTP_POST) {
			// Set this request up as a POST request
			curl_setopt($curl_request, CURLOPT_POST, TRUE);
		}

		// If this is a PUT or POST request
		if ($method === self::HTTP_PUT OR $method === self::HTTP_POST) {
			// Set the post fields
			curl_setopt($curl_request, CURLOPT_POSTFIELDS, $parameters);
		}

		// Make sure that we get data back when we call exec
		curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, TRUE);

		// If we have headers that we need to send up with the request
		if ($headers !== NULL)
		{
			// Loop over the headers that were passed in
			foreach ($headers as $key => $value)
			{
				// Collapse the key => value pair into one line
				$simple_headers[] = $key.': '.$value;
			}

			// Set the headers we want to send up
			curl_setopt($curl_request, CURLOPT_HTTPHEADERS, $simple_headers);
		}

		// Run the request, get the status, close the request
		$data = curl_exec($curl_request);
		$status = curl_getinfo($curl_request, CURLINFO_HTTP_CODE);

		// Set the last uri variable
		$this->last_uri = $uri;

		// Return an instance of REST_Response with the collected data
		return new REST_Response($data, $status);
	}

	/**
	 * Builds the URI for the request using the configuration data and the passed location
	 *
	 * @param   string  the method we are using to make the HTTP request
	 * @param   string  the location that we are requesting
	 * @param   array   an array of key value pairs to transform into parameters
	 * @return  string   the URI where the requested document can be located
	 */
	protected function _build_uri($method, $location = NULL, $parameters = NULL)
	{
		$uri = $this->_config['uri'];

		// Make sure that there is a slash at the end of the host string
		$uri .= (substr($uri, -1) !== '/') ? '/' : '';

		// Attach the location
		$uri .= $location;

		// If this is a GET or DELETE request and we have parameters to process
		if (($method === self::HTTP_GET OR $method === self::HTTP_DELETE) AND isset($parameters)) {
			// Append the parameters onto the end of the uri string
			$uri .= '?'.http_build_query($parameters);
		}

		// Return the finished URI
		return $uri;
	}

}
