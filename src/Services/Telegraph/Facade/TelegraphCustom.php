<?php

namespace Services\Telegraph\Facade;

use DefStudio\Telegraph\Facades\Telegraph;


class TelegraphCustom extends Telegraph
{

    protected static function getFacadeAccessor(): string
    {
        return 'telegraphCustom';
    }
}
