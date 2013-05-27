<?php
	
	

	/**
	* главные настройки
	*/

	if (!defined('SITEPATH'))
		define('SITEPATH', $_SERVER['DOCUMENT_ROOT'].'/');

	if (!defined('SITE'))
		define('SITEPATH', $_SERVER['HTTP_HOST']);

	define('PATH_VIEW', SITEPATH.'app/views/');


	/*
	* собираем настройки конфигов
	*/

	function config($key, $value = null) {

  		static $_config = array();

 		if ($value == null)
    		return (isset($_config[$key]) ? $_config[$key] : null);
  		else
    		return ($_config[$key] = $value);
	
	}



	/**
	* возвращаем ошибку
	*/
	function error($code, $message) {

 		 if (php_sapi_name() !== 'cli')
    		@header("HTTP/1.0 {$code} {$message}", true, $code);

  		die("{$code} - {$message}");
	}



	/**
	* функция фамелеон
	*/
	
	function route($method = null, $pattern = null, $callback = null) {

			
		static $maps = array(
    		'GET' => array(),
    		'POST' => array(),
    		'PUT' => array(),
    		'DELETE' => array(),
    		'HEAD' => array()
  		);
		
		 $method = strtoupper($method);
		 
		 if (!in_array($method, array_keys($maps)))
    		error(500, 'Only '.implode(', ', array_keys($maps)).' are supported');

		if ($callback !== null) { // save router
			$maps[$method][$pattern] = array(
      			'pattern' => route_to_regex($pattern),
      			'func' => $callback
    		);

    		return True;
		}

				
		if ($pattern == '/' && isset($maps[$method][$pattern])) { //индексовая страницы
			$func = $maps[$method][$pattern]['func'];
			if (is_callable($func)) 
					call_user_func($func);
		}		

		else {			

			$pattern = trim($pattern, '/');

			foreach ($maps[$method] as $pat => $r) {
			
				if (!preg_match($r['pattern'], $pattern, $vals)) //регулярка
					 continue;
					
				array_shift($vals);
				preg_match_all('@:([\w]+)@', $pat, $keys, PREG_PATTERN_ORDER);
      			
      			$keys = array_shift($keys);
      			$argv = array();	 

      		      		
      			foreach ($keys as $index => $id) {
       				$id = trim(substr($id, 1));
       				if (isset($vals[$id]))
       					$argv[] = $vals[$id];
        		}
			
			
				if (is_callable($r['func'])) 
					call_user_func_array($r['func'], $argv);
			
				break;

			}

		}	

		return;
		
	}


	/**
	* @ - строка, : - цифра
	*/

	function route_to_regex($route) {

 		$route = preg_replace_callback('@:[\w]+@i', function ($matches) {
    	$token = str_replace(':', '', $matches[0]);
    	return '(?P<'.$token.'>[a-z0-9_\0-\.]+)';
  		}, $route);

  		$route = rtrim($route, '/');
  		$route = '@^'.(!strlen($route) ? '/' : $route).'$@i';

  		return $route;

	}	



	function set($name = null, $value = null) {
  		
  		static $set = array();
  		
  		if ($name == null) 
  			return $set;
  		
  		if ($value == null){ 
  			if(array_key_exists($name, $set)) 
  				return $set[$name];
  		}	
  		
  		$set[$name] = $value;

  		return;

	}


	function get($pattern = null, $callback = null) {
		return route('GET', $pattern, $callback);
	}

	
	function post($path = null, $cb = null) {
 		return route('POST', $path, $cb);
	}

	function delete($path = null, $cb = null) {
  		return route('DELETE', $path, $cb);
	}

	function put($path = null, $cb = null) {
  		return route('PUT', $path, $cb);
	}


	function view($temp, $layout = null) {

		$set = set();
		extract($set);

		ob_start();

		if (!include PATH_VIEW.$temp) //формируем content
			error(500, 'template not found');

		content(trim(ob_get_clean()));

		header('Content-type: text/html; charset=utf-8');
		
		ob_start();

		if ($layout == null && $layout = config('layout')){
			if (!include PATH_VIEW.'layouts/'.$layout)
				error(500, 'layout not found');
		}	
		else
   			echo content();

   		echo trim(ob_get_clean());

   		return;
  	
  	} 


	spl_autoload_register(function ($class) {
    	
		$fclass = SITEPATH.'app/models/'.$class.'.php';
		
		if (file_exists($fclass))
    		require $fclass;
    	else
    		error(500, 'not found class '.$class);

	});



	function stash($name, $value = null) {

  		static $_stash = array();

  		if ($value === null)
    		return isset($_stash[$name]) ? $_stash[$name] : null;

  		$_stash[$name] = $value;

  		return $value;
  		
	}



	function content($value = null) {
  		return stash('content', $value);
	}



	function run($method = null, $path = null) {
		
		$method = ($method ? $method : $_SERVER['REQUEST_METHOD']);
		$path = ($path ? $path : $_SERVER['REQUEST_URI']);
		
		route($method, $path);

		return;

	}