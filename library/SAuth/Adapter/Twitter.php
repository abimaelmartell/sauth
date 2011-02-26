<?php

/**  
 * @see SAuth_Adapter_Abstract 
 */
require_once 'SAuth/Adapter/Abstract.php';

/**
 * @see Zend_Auth_Adapter_Interface
 */
require_once 'Zend/Auth/Adapter/Interface.php';

/**  
 * @see Zend_Oauth_Consumer 
 */
require_once 'Zend/Oauth/Consumer.php';


/**
 * Authentication with twitter
 * 
 * http://developer.twitter.com/pages/auth
 */
class SAuth_Adapter_Twitter extends SAuth_Adapter_Abstract implements Zend_Auth_Adapter_Interface {
    
    /**
     * @var array Configuration array
     */
    protected $_config = array(
        'requestScheme' => Zend_Oauth::REQUEST_SCHEME_HEADER,
        'consumerKey' => '',
        'consumerSecret' => '',
        'version' => '1.0',
        'callbackUrl' => '',
        'requestTokenUrl' => 'https://api.twitter.com/oauth/request_token',
        'userAuthorizationUrl' => 'https://api.twitter.com/oauth/authorize',
        'accessTokenUrl' => 'https://api.twitter.com/oauth/access_token',
    );
    
    /**
     * @var string Session key
     */
    protected $_sessionKey = 'SAUTH_TWITTER';
    
    /**
     * Authenticate user by twitter OAuth
     * @return true
     */
    public function authenticate() {
        
        if ($this->isAuthorized()) {
            $this->clearAuth();
        }
        
        $config = $this->getConfig();
        
        if (empty($config['consumerKey']) || empty($config['consumerSecret']) || empty($config['userAuthorizationUrl']) 
            || empty($config['accessTokenUrl']) || empty($config['callbackUrl'])) {
                
            require_once 'SAuth/Exception.php';
            throw new SAuth_Exception('Twitter auth configuration not specifed.');
        }
        
        $consumer = new Zend_Oauth_Consumer($config);
        $tokenRequest = $this->_getTokenRequest();
        
        if (!empty($tokenRequest) && !empty ($_GET)) {
            $tokenAccess = $consumer->getAccessToken($_GET, $tokenRequest);
            $response = $tokenAccess->getResponse();
            
            if ($response->isError()) {
                //TODO:change on custom
                $this->_setError('Twitter Oauth service unavailable');
                return false;
            } elseif ($response->isSuccessful()) {
                $parsedResponse = $this->parseResponseUrl($response->getBody());
                $this->_setTokenAccess($parsedResponse['oauth_token']);
                $this->setUserParameters($parsedResponse);
                $this->_unsetTokenRequest();
                return $this->isAuthorized();
            }
            return false;
            
        } else {
            $tokenRequest = $consumer->getRequestToken();
            $this->_setTokenRequest($tokenRequest);
            $consumer->redirect();
        }
    }

    /**
     * Getting authentication identification
     * @return false|int User ID
     */
    public function getAuthId() {
        
        $id = (int) $this->getUserParameters('user_id');
        return $id > 0 ? $id : false;
    }
    
}