<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Defines a structure for data returned by any of the HTTP request methods returned by Rest_Client
 *
 * @package    Kohana/REST
 * @category   Extension
 * @author     Neuroxy
 * @copyright  (c) 2010 Neuroxy
 * @license    FIXME
 */
class REST_Result {	

	/**
	 * @var  string  the raw response returned by the HTTP request
	 */
	public $data = NULL;

	/**
	 * @var  array  the HTTP status returned
	 */
	public $status = NULL;

	/**
	 * Stores the data that gets passed in to the public members
	 *
	 * @param   string  the raw text data returned by an HTTP request
	 * @param   string  the http status that was returned by the HTTP request
	 * @return  void
	 */
	public function __construct($data, $status)
	{
		// Set the member variables to match the data that was passed in
		$this->data = $data;
		$this->status = $status;
	}

}
