<?php

class TripAdvisorAPI {
	protected $config = [];

	public function __construct($config = [])
	{
		$this->config = array_merge([
			'key'        		=> null,
			'cachePath'  		=> 'data',
			'cacheExpiration' 	=> (60*24),
			'v'          		=> '2.0',
			'url'        		=> 'http://api.tripadvisor.com/api/partner/',
			'debug'				=> false,
		], $config);

		if (!$this->config['key']) {
			throw new \Exception('Missing API Key');
		}
	}
	
	
	protected function getCache($name)
	{
		$cacheFile = $this->generateCacheTokenPath($name);
		if (file_exists($cacheFile)) {
			$lastModified = filemtime($cacheFile);
			$ageOfFile = time() - $lastModified;
			if ($this->config['cacheExpiration'] > $ageOfFile) return false;

			$content = @file_get_contents($cacheFile);
			if (!$content) return false;

			return json_decode($content);
		}
		
		return false;
	}

	protected function setCache($name, $data)
	{
		$cacheFile = $this->generateCacheTokenPath($name);

		return file_put_contents($cacheFile, json_encode($data));
	}

	protected function generateCacheTokenPath($string)
	{
		$token = md5($string);
		return "{$this->config['cachePath']}/{$token}.json";
	}
		
	protected function getCurl($url)
	{
		$cacheData = $this->getCache($url);
		if ($this->config['debug'] || $cacheData === false) {
			
			$ch = curl_init ();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			$apiResponse = curl_exec($ch);
			$apiResponseDecoded = json_decode($apiResponse);

			curl_close($ch);
			
			if ($apiResponseDecoded !== false && !isset($apiResponseDecoded->error)) {
				$this->setCache($url, $apiResponseDecoded);
			}
		} else {
			$apiResponseDecoded = $cacheData;
		}
		
		return $apiResponseDecoded;
	}
		
  /*
  	query some keywords 
		q			 => some string text about of searc
		distance	 => 0.1
  
  */	
		
	protected function getQuery($template, $value, $query = null, $type = '')
	{
		if (is_array($query)) $query = '&'.http_build_query($query);
		
		$this->config['type']     = ($type=='')?'':$type;
		$this->config['template'] = $template;
		$this->config['value']    = $value;
		
		if ($template=='location_mapper' and $this->config['type']=='') $this->config['type']='hotels';	
		foreach($this->config as $key => $val){
			$arrKeys[] = '{'.$key.'}';
			$arrVals[] = $val;
	    }
		 
		$urlPatterns = [
			'location_mapper' => '{url}{v}/{template}/{value}?key={key}-mapper&category={type}'.$query,
			'location'        => '{url}{v}/{template}/{value}/{type}?key={key}'.$query
		];
		
		$generatedURL = str_replace($arrKeys,$arrVals,((isset($urlPatterns[$template]))?$urlPatterns[$template]:$urlPatterns['location']));
		$response = $this->getCurl($generatedURL);
		
		return $response;
	}


	protected function formatResponse($data)
	{
		// @todo format based on the "type"?
		$formatted = [
			'id' 			=> $data->location_id,
			'name'         	=> $data->name,
			'address'		=> $data->address_obj,
			
			'latitude'	   	=> $data->latitude,
			'longitude'	   	=> $data->longitude,
			'url_web'		=> $data->web_url,
			'url_photos'    => $data->see_all_photos,
			'category'		=> $data->category->name,
		];

		if (isset($data->num_reviews)) {
			$formatted['reviews'] = (object)[
				'total' => $data->num_reviews,
			];
		}
		if (isset($data->rating)) {
			$formatted['rating'] = (object)[
				'total' 	=> $data->rating,
				'by_stars'	=> $data->review_rating_count,
				'image' 	=> $data->rating_image_url,
			];
		}
		if (isset($data->price_level)) {
			$formated['price'] = $data->price_level;
		}
		if (isset($data->rankranking_dataing)) {
			$formatted['ranking'] = (object)[
				'total' 		=> $data->ranking_data->ranking_out_of,
				'rank' 			=> $data->ranking_data->ranking,
				'string' 		=> $data->ranking_data->ranking_string,
			];
		}
		if (isset($data->ranking_data)) {
			$formatted['geo_region'] = (object)[
				'id' 	=> $data->ranking_data->geo_location_id,
				'name' 	=> $data->location_string,
			];
		}
		
		return (object)$formatted;
	}

	// Calls
	public function find($type, $geocode, $query = "", $language = "en") {
		$response = $this->getQuery('location_mapper', $geocode, ['q'=>$query,'lang'=>$language], $type);
		if (count($response->data) <= 0) return false;

		$parsedResults = [];
		foreach ($response->data as $current) {
			$details = $this->getSingle($current->location_id);
			if ($details) {
				$parsedResults[] = $details;
			}
		}
		return $parsedResults;
	}

	public function getSingle($id, $language = "en") {
		$response = $this->getQuery('location', $id, ['lang'=> $language]);
		if (!isset($response->location_id)) return false;

		return $this->formatResponse($response);
	}

	public function getHotels($geocode, $query = "", $language = "en")
	{
		return $this->find('hotels', $geocode, $query = "", $language);
	}

	public function getAttractions($geocode, $query = "", $language = "en")
	{
		return $this->find('attractions', $geocode, $query = "", $language);
	}

	public function getRestaurants($geocode, $query = "", $language = "en")
	{
		return $this->find('restaurants', $geocode, $query = "", $language);
	}
}
