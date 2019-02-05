<?php

namespace Mapmon;

use \Mapmon\Model;

class SampleEmbebedModel extends Model
{
    protected static $collectionName = 'Sample';

    protected static $embeddedObject = array(
        'address' => Address::class,
    );

    protected static $embeddedObjectList = array(
        'addressList' => Address::class,
    );
}
