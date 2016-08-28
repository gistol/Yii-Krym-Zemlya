<?php
class FBAutoPosting {
	private $page_id;
	private $page_access_token;
	private $post_url = '';

	public function FBAutoPosting($fbToken, $fbUsId) {
		$this->page_access_token = $fbToken;
		$this->page_id = $fbUsId;
		$this->post_url = 'https://graph.facebook.com/'.$this->page_id.'/feed';
	}

	public function message($data) {
		$data['access_token'] = $this->page_access_token;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->post_url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		//curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$return = curl_exec($ch);
		curl_close($ch);

		return $return;
	}
}