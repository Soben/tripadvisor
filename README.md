
# TripAdvisor Content API PHP Library 

![tripadvisor](https://static.tacdn.com/img2/branding/rebrand/TA_logo_primary.svg)

# API Description
Approved users of the TripAdvisor Content API can use this API. It can pull a limited set of information about locations. Best with a list of location IDs.

# Config
  
    protected $config = [
		'key'             => null,    // partner key, required
		'cachePath'  	  => 'data',  // query cache folder
		'cacheExpiration' => (60*24), // query cache time
		'v'               => '2.0',   // version
		'url'        	  => 'http://api.tripadvisor.com/api/partner/'
		'debug'			  => false,   // set to true to disable cache 
	];

# Use

	@todo

# More Info

[TripAdvisor Content API Documentation](https://developer-tripadvisor.com/content-api/documentation/)