<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class LTPLE_Integrator_Youtube {
	
	var $parent;
	var $apps;
	
	/**
	 * Constructor function
	 */
	public function __construct ( $app_slug, $parent, $apps ) {
		
		$this->parent 		= $parent;
		$this->parent->apps = $apps;

		if( !file_exists($this->parent->vendor . '/autoload.php') ){
			
			$_SESSION['message'] = '<div class="alert alert-danger">';
				
				$_SESSION['message'] .= 'Sorry, Google API is not available on this platform yet, please contact the dev team...';
					
			$_SESSION['message'] .= '</div>';		
		}
		else{		
			
			// get app term

			$this->term = get_term_by('slug',$app_slug,'app-type');

			// get app parameters
			
			$parameters = get_option('parameters_'.$app_slug);
			
			if( isset($parameters['key']) ){
				
				$goo_api_project 		= array_search('goo_api_project', $parameters['key']);
				$goo_consumer_key 		= array_search('goo_consumer_key', $parameters['key']);
				$goo_consumer_secret 	= array_search('goo_consumer_secret', $parameters['key']);
				$goo_oauth_callback 	= $this->parent->urls->apps;

				if( !empty($parameters['value'][$goo_api_project]) && !empty($parameters['value'][$goo_consumer_key]) && !empty($parameters['value'][$goo_consumer_secret]) ){
				
					define('API_PROJECT', 		$parameters['value'][$goo_api_project]);
					define('CONSUMER_KEY', 		$parameters['value'][$goo_consumer_key]);
					define('CONSUMER_SECRET', 	$parameters['value'][$goo_consumer_secret]);
					define('OAUTH_CALLBACK', 	$goo_oauth_callback);
							
					$callback=parse_url(OAUTH_CALLBACK);
					define('JS_ORIGINS', $callback['scheme'].'://'.$callback['host']);
					
					$this->oauthConfig = json_decode('{"web":{"client_id":"'.CONSUMER_KEY.'","project_id":"'.API_PROJECT.'","auth_uri":"https://accounts.google.com/o/oauth2/auth","token_uri":"https://accounts.google.com/o/oauth2/token","auth_provider_x509_cert_url":"https://www.googleapis.com/oauth2/v1/certs","client_secret":"'.CONSUMER_SECRET.'","redirect_uris":["'.OAUTH_CALLBACK.'"],"javascript_origins":["'.JS_ORIGINS.'"]}}', true);
					
					//set client
					$this->client = new Google_Client();
					
					//Set the path to these credentials
					$this->client->setAuthConfig($this->oauthConfig);
					
					//Set the scopes required for the API you are going to call
					$this->client->addScope('https://www.googleapis.com/auth/plus.login');
					$this->client->addScope('https://www.googleapis.com/auth/youtube');
					$this->client->addScope('https://www.googleapis.com/auth/youtube.readonly');
					
					// set Approval Prompt
					$this->client->setApprovalPrompt('auto');
					
					// generates refresh token
					$this->client->setAccessType('offline');       
					
					// get current action
					
					if(!empty($_REQUEST['action'])){
						
						$this->action = $_REQUEST['action'];
					}
					elseif(!empty($_SESSION['action'])){
						
						$this->action = $_SESSION['action'];
					}
					
					$methodName = 'app'.ucfirst($this->action);

					if(method_exists($this,$methodName)){
						
						$this->$methodName();
					}
				}
				else{
					
					$_SESSION['message'] = '<div class="alert alert-danger">';
						
						$_SESSION['message'] .= 'Sorry, youtube is not available on this platform yet, please contact the dev team...';
							
					$_SESSION['message'] .= '</div>';				
				}
			}
		}
	}

	public function appImportImg(){
		
		if(!empty($_REQUEST['id'])){
		
			if( $this->app = $this->parent->apps->getAppData( $_REQUEST['id'], $this->parent->user->ID, true ) ){
				
				$this->client->setAccessToken($this->app);		

				if($this->client->isAccessTokenExpired()){  
					
					// refresh the token
				
					$this->client->refreshToken($this->app);
				}
				
				$service = new Google_Service_YouTube($this->client);
				
				$videos = $service->playlistItems->listPlaylistItems('snippet', array(
					
					'playlistId' => $this->app['playlist_id'],
					'maxResults' => 50
				));
					
				$urls = [];
				
				if(!empty($videos->items)){
					
					foreach($videos->items as $video){
							
						$img_url = $video['snippet']['thumbnails']['default']['url'];
						
						if(isset($video['snippet']['thumbnails']['maxres']['url'])){
							
							$img_url = $video['snippet']['thumbnails']['maxres']['url'];
						}
						elseif(isset($video['snippet']['thumbnails']['standard']['url'])){
							
							$img_url = $video['snippet']['thumbnails']['standard']['url'];
						}
						elseif(isset($video['snippet']['thumbnails']['high']['url'])){
							
							$img_url = $video['snippet']['thumbnails']['high']['url'];
						}
						elseif(isset($video['snippet']['thumbnails']['medium']['url'])){
							
							$img_url = $video['snippet']['thumbnails']['medium']['url'];
						}
						
						$img_title	= str_replace(array('https://','http://','ytimg.com','/vi/','/','.','-'),array('','','','','_','_','_'),$img_url);

						if(!get_page_by_title( $img_title, OBJECT, 'user-image' )){
							
							if($image_id = wp_insert_post(array(
						
								'post_author' 	=> $this->parent->user->ID,
								'post_title' 	=> $img_title,
								'post_content' 	=> $img_url,
								'post_type' 	=> 'user-image',
								'post_status' 	=> 'publish'
							))){
								
								wp_set_object_terms( $image_id, $this->term->term_id, 'app-type' );
							}
						}											
					}
				}
			}
		}
	}
	
	public function appConnect(){
		
		if( isset($_REQUEST['action']) ){
			
			if(!isset($_SESSION['token'])){

				$_SESSION['app'] 				= 'youtube';
				$_SESSION['action'] 			= $_REQUEST['action'];
				$_SESSION['ref'] 				= ( !empty($_REQUEST['ref']) ? $this->parent->request->proto . urldecode($_REQUEST['ref']) : '');

				$this->oauth_url = $this->client->createAuthUrl();
			
				wp_redirect($this->oauth_url);
				echo 'Redirecting youtube oauth...';
				exit;
			}			
		}
		elseif( isset($_SESSION['action']) ){
			
			if(!isset($_SESSION['access_token'])){
				
				// handle connect callback
				
				if(isset($_REQUEST['code'])){
					
					//get access_token
					
					$this->access_token =  $this->client->fetchAccessTokenWithAuthCode($_REQUEST['code']);				
					
					if(!empty($_SESSION)){
							
						//flush session
							
						$_SESSION = array();			
					}					
					
					//store access_token in session					
					$_SESSION['access_token'] = $this->access_token;

					//set access_token	
					$this->client->setAccessToken($this->access_token);	
					
					//start the youtube service
					$service=new Google_Service_YouTube($this->client);

					// get user channels
					$channels = $service->channels->listChannels('snippet,contentDetails', array('mine' => true));

					if( !empty($channels->items)){
						
						foreach($channels->items as $channel){

							$this->access_token['channel_id'] = $channel->id;
							
							$this->access_token['playlist_id'] = $channel->contentDetails['relatedPlaylists']['uploads'];
							
							// store access_token in database		
							
							$app_title = wp_strip_all_tags( 'youtube - ' . $channel->snippet['title'] );
							
							$app_item = get_page_by_title( $app_title, OBJECT, 'user-app' );
							
							if( empty($app_item) ){
								
								// create app item
								
								$app_id = wp_insert_post(array(
								
									'post_title'   	 	=> $app_title,
									'post_status'   	=> 'publish',
									'post_type'  	 	=> 'user-app',
									'post_author'   	=> $this->parent->user->ID
								));
								
								wp_set_object_terms( $app_id, $this->term->term_id, 'app-type' );
								
								// hook connected app
								
								do_action( 'ltple_youtube_account_connected');
								
								$this->parent->apps->newAppConnected();								
							}
							else{

								$app_id = $app_item->ID;
							}
								
							// update app item
								
							update_post_meta( $app_id, 'appData', json_encode($this->access_token,JSON_PRETTY_PRINT));							
						}
					}
					
					if(!empty($_SESSION['ref'])){
						
						$redirect_url = $_SESSION['ref'];
						
						$_SESSION['ref'] = '';
						
						wp_redirect($redirect_url);
						echo 'Redirecting youtube callback...';
						exit;	
					}
					else{
						
						// store success message

						$_SESSION['message'] = '<div class="alert alert-success">';
							
							$_SESSION['message'] .= 'Congratulations, you have successfully connected a Youtube account!';
								
						$_SESSION['message'] .= '</div>';						
					}
				}
				elseif(!empty($_SESSION)){
						
					//flush session
						
					$_SESSION = array();			
				}
			}
		}
	}
} 