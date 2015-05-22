<?php namespace Seansch\Rapnet;

use Illuminate\Support\Facades\Facade;

class RapnetFacade extends Facade {

    /**
     * Get the binding in the IoC container
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'rapnet';
    }
}
