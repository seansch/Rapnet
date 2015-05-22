<?php namespace Seansch\Rapnet;

use SoapClient;
use SoapHeader;
use Cache;

class Rapnet {

    private $client;
    private $ticket_hash = null;
    private $shape = null;
    private $size = null;
    private $color = null;
    private $clarity = null;

    public function __construct()
    {
        $this->client = new SoapClient("https://technet.rapaport.com/webservices/prices/rapaportprices.asmx?wsdl", array('trace' => 1));
        $this->login();
    }

    /**
     * Retrieve and set the authentication ticket_hash
     */
    private function login()
    {
        $this->ticket_hash = Cache::get('rap_auth_token');

        if ($this->ticket_hash === null) {
            // Login to the SOAP API
            $this->client->Login(['Username' => config('rapnet.user'), 'Password' => config('rapnet.password')]);

            // Parse Auth Ticket out of response
            $auth_ticket = $this->client->__getLastResponse();
            $ticket_xml = simplexml_load_string($auth_ticket);
            $ticket_xml->registerXPathNamespace('technet', "http://technet.rapaport.com/");
            $ticket = $ticket_xml->xpath('//technet:Ticket');
            $this->ticket_hash = (string)$ticket[0];

            // Store auth token for 45 minutes
            Cache::put('rap_auth_token', $this->ticket_hash, 45);
        }
    }


    /**
     * Modifies and sets the diamond params
     *
     * @param string $shape
     * @param float $size
     * @param string $color
     * @param string $clarity
     */
    public function setDiamondParams($shape, $size, $color, $clarity)
    {
        // If shape isn't Round set to Pear
        $shape = ($shape != 'Round' ? 'Pear' : 'Round');

        // FL doesn't exist, set to IF, or leave the same
        $clarity = ($clarity = 'FL' ? 'IF' : $clarity);

        // Set N or FANCY to M color
        if ($color == "N" || $color == "FANCY") {
            $color = "M";
        }

        // Anything larger than 5.99 is calculated as 10
        $size = ($size > 5.99 ? 10 : $size);

        // If Pear shaped and less than .18 carat, set to .18
        if ($shape == "Pear" && $size < 0.18) {
            $size = 0.18;
        }

        $this->shape    = $shape;
        $this->size     = $size;
        $this->color    = $color;
        $this->clarity  = $clarity;
    }

    /**
     * Retrieves the price for the set diamond params
     *
     * @return int
     */
    public function getPrice()
    {
        if($this->clarity == null || $this->shape == null || $this->size == null || $this->color == null) {
            return false;
        }

        $header = new SOAPHeader(
            "http://technet.rapaport.com/", 'AuthenticationTicketHeader', ['Ticket' => $this->ticket_hash]
        );
        $this->client->__setSoapHeaders($header);

        $this->client->GetPrice([
            $this->shape,
            $this->size,
            $this->color,
            $this->clarity
        ]);

        $price_result = $this->client->__getLastResponse();
        $price_xml = simplexml_load_string($price_result);
        $price_xml->registerXPathNamespace('technet', "http://technet.rapaport.com/");
        $price_array = $price_xml->xpath('//technet:GetPriceResult');
        $price = (int)$price_array[0]->price;

        return $price;
    }
}