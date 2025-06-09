<?php
/**
 * Mobile Detect Library
 * Lightweight PHP class for detecting mobile devices.
 *
 * @author      Serban Ghita <serbanghita@gmail.com>
 * @license     MIT License https://github.com/serbanghita/Mobile-Detect/blob/master/LICENSE.txt
 *
 * This is a simplified version of the Mobile_Detect class.
 * For the full version, visit: https://github.com/serbanghita/Mobile-Detect
 */

class Mobile_Detect {
    /**
     * Mobile User Agents
     * @var array
     */
    protected static $mobileAgents = [
        'Android', 'iPhone', 'iPod', 'iPad', 'BlackBerry', 'Windows Phone',
        'webOS', 'Mobile', 'Opera Mini', 'IEMobile', 'Kindle', 'Silk',
        'Mobi', 'Tablet', 'Opera Mobi', 'SymbianOS', 'Symbian', 'Palm',
        'Opera Tablet', 'Nexus 7', 'Nexus 10', 'HTC', 'Samsung', 'Motorola',
        'Nokia', 'Lumia', 'Xoom', 'Huawei', 'LG', 'ZTE', 'Lenovo', 'Xiaomi',
        'OPPO', 'Vivo', 'OnePlus', 'Realme', 'Redmi'
    ];

    /**
     * Tablet User Agents
     * @var array
     */
    protected static $tabletAgents = [
        'iPad', 'Tablet', 'Nexus 7', 'Nexus 10', 'Xoom', 'Android.*Tablet',
        'Kindle', 'Silk', 'Opera Tablet', 'Galaxy Tab', 'iPad Pro'
    ];

    /**
     * HTTP Headers
     * @var array
     */
    protected $headers = [];

    /**
     * User Agent
     * @var string
     */
    protected $userAgent = '';

    /**
     * Constructor
     * Sets the User Agent and HTTP Headers
     */
    public function __construct() {
        $this->setUserAgent();
        $this->setHttpHeaders();
    }

    /**
     * Set the User Agent
     */
    public function setUserAgent() {
        $this->userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    }

    /**
     * Set the HTTP Headers
     */
    public function setHttpHeaders() {
        $this->headers = $this->getHttpHeaders();
    }

    /**
     * Get the HTTP Headers
     * @return array
     */
    public function getHttpHeaders() {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) == 'HTTP_') {
                $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                $headers[$header] = $value;
            }
        }
        return $headers;
    }

    /**
     * Check if the device is mobile
     * @return bool
     */
    public function isMobile() {
        if ($this->isTablet()) {
            return false;
        }

        foreach (self::$mobileAgents as $mobileAgent) {
            if (stripos($this->userAgent, $mobileAgent) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the device is a tablet
     * @return bool
     */
    public function isTablet() {
        foreach (self::$tabletAgents as $tabletAgent) {
            if (stripos($this->userAgent, $tabletAgent) !== false) {
                return true;
            }
        }

        return false;
    }
}
