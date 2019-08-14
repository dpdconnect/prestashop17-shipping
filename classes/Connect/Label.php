<?php

namespace DpdConnect\classes\Connect;

class Label extends Connection
{
    public function get($parcelNumber)
    {
        return $this->client->getParcel()->getLabel($parcelNumber);
    }
}
