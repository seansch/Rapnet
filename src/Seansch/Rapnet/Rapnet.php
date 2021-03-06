<?php

namespace Seansch\Rapnet;

use SoapClient;
use SoapHeader;
use Cache;

class Rapnet
{

    private $client;
    private $ticket_hash = null;
    public $shape = null;
    public $size = null;
    public $color = null;
    public $clarity = null;

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
        // Retrieve auth ticket and cache for 45 minutes (Ticket is valid for only 1 hour)
        $this->ticket_hash = Cache::remember('rap_auth_token', 45, function () {
            // Login to the SOAP API
            $this->client->Login(['Username' => config('rapnet.user'), 'Password' => config('rapnet.password')]);

            // Parse Auth Ticket out of response
            $auth_ticket = $this->client->__getLastResponse();
            $ticket_xml = simplexml_load_string($auth_ticket);
            $ticket_xml->registerXPathNamespace('technet', "http://technet.rapaport.com/");
            $ticket = $ticket_xml->xpath('//technet:Ticket');
            return (string)$ticket[0];
        });
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
        $clarity = ($clarity == 'FL' ? 'IF' : $clarity);

        // Set N or FANCY to M color
        if ($color == "N" || $color == "FANCY") {
            $color = "M";
        }

        // Anything larger than 9.99 is calculated as 10
        $size = ($size > 9.99 ? 10 : $size);

        // Anything between 6 and 9.99 is priced at 5.01
        if (($size > 5.99) && ($size < 10)) {
            $size = 5.01;
        }

        // If Pear shaped and less than .18 carat, set to .18
        if ($shape == "Pear" && $size < 0.18) {
            $size = 0.18;
        }

        $this->shape = $shape;
        $this->size = $size;
        $this->color = $color;
        $this->clarity = $clarity;
    }

    /**
     * Retrieves the price for the set diamond params
     *
     * @return int
     */
    public function getPrice()
    {
        if ($this->clarity == null || $this->shape == null || $this->size == null || $this->color == null) {
            return false;
        }

        $header = new SOAPHeader(
            "http://technet.rapaport.com/", 'AuthenticationTicketHeader', ['Ticket' => $this->ticket_hash]
        );
        $this->client->__setSoapHeaders($header);

        $this->client->GetPrice([
            'shape' => $this->shape,
            'size' => $this->size,
            'color' => $this->color,
            'clarity' => $this->clarity
        ]);

        $price_result = $this->client->__getLastResponse();
        $price_xml = simplexml_load_string($price_result);
        $price_xml->registerXPathNamespace('technet', "http://technet.rapaport.com/");
        $price_array = $price_xml->xpath('//technet:GetPriceResult');
        $price = (int)$price_array[0]->price;

        return $price;
    }
}

