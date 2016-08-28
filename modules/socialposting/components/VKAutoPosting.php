<?php
class VKAutoPosting {
	private $access_token;
	private $url = "https://api.vk.com/method/";

	public function __construct($access_token) {
		$this->access_token = $access_token;
	}

	public function method($method, $params = null) {	
		$response = $p = "";
		if( $params && is_array($params) ) {
			foreach($params as $key => $param) {
				$p .= ($p == "" ? "" : "&") . $key . "=" . urlencode($param);
			}
		}
		
		if( function_exists('curl_version')  ){
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $this->url . $method . "?" . ($p ? $p . "&" : "") . "access_token=" . $this->access_token);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

			$response = curl_exec($ch);
			curl_close($ch);
		}
		else {
			$ctx = stream_context_create(array('http'=>
				array(
					'timeout' => 10, // 10 Seconds
				)
			));
			$response = file_get_contents($this->url . $method . "?" . ($p ? $p . "&" : "") . "access_token=" . $this->access_token, false, $ctx);
		}

		if( $response )
			return json_decode($response);
		
		return false;
	}
}