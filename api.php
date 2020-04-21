<?php
error_reporting(E_ERROR|E_USER_WARNING|E_USER_NOTICE);
ini_set('display_errors', 'On');

require_once("includes/mysqli.php");
require_once("includes/rest.php");

class API extends REST {

	public $data = '';

	private $mysqli = NULL;
	private $cache_dir = 'cache';
	private $cache_request = '';


//! Functions
	public function __construct() {
		parent::__construct();
		IF(!($this->mysqli = Database())) $this->_request["nocache"] = FALSE;
		//return $this->security();
	}

	private function cache($string) {
    // Cache disabled in DEV
		file_put_contents($this->cache_request, $string);
	}

	private function json($data){
    IF(is_array($data)) {
      IF(!($json = @json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_INVALID_UTF8_SUBSTITUTE))) {
        return json_last_error_msg();
      } ELSE {
        return $json;
      }
    }
  }

	public function requestedAction(){
    //$func = strtolower(trim(str_replace("/","",$_REQUEST['rquest'])));

    $params = explode("/", $_REQUEST['rquest']);
    $func = array_shift($params);
    
    $this->cache_request = $this->cache_dir.'/'.str_replace('--', '-', $func.'-'.strtolower(str_replace(array($func, '--', '/'), '', implode('-', $this->_request)))).'.txt';

    IF(count($params) > 0) $this->_request = array_merge($params, $this->_request);

    FOREACH(array('country', 'county', 'region', 'town') AS $tag) :
      IF(isset($this->_request[$tag])) :
  	    $this->_request["url{$tag}"] = $this->_request[$tag];
  	    $this->_request[$tag] = strtolower(str_replace('-', ' ', $this->_request[$tag]));
      ENDIF;
    ENDFOREACH;
    
    IF($this->_request['random'] == TRUE) $this->_request['nocache'] = TRUE;
    IF(strpos($func, 'add') !== FALSE || strpos($func, 'update') !== FALSE) $this->_request['nocache'] = TRUE;

    IF(method_exists($this,$func)) :
	    IF($this->mysqli != FALSE && (@filemtime($this->cache_request) < time()-1*(3600 * 1) || $this->_request["nochache"] == TRUE) ) : // no cache, or cache expired (1 hour)
		    $this->$func();
		  ELSE :
			  $this->response(file_get_contents($this->cache_request), 201);
		  ENDIF;
    ELSE : 
      $this->response('The requested action does not exist', 501);
    ENDIF;
	}

  private function test(){
    //var_dump($_REQUEST);
    // Cross validation if the request method is GET else it will return "Not Acceptable" status
    //IF($this->get_request_method() != "GET") $this->response('', 406);
    $param = $this->_request;
    // If success everythig is good send header as "OK" return param
    $this->response($this->json($param), 200);
	}

  private function clearcache() {
	  $files = glob("{$this->cache_dir}/*");
		FOREACH($files AS $file) :
			IF(is_file($file) && $file != __FILE__)
				unlink($file);
		ENDFOREACH;
  }

} // END class API

$api = new API();
//IF($api->security())
  $api->requestedAction();
?>
