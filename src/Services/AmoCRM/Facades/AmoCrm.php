<?php

namespace Services\AmoCRM\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * This is the AmoCrm facade class.
 */
class AmoCrm extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'amocrm';
    }
}
