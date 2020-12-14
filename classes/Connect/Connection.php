<?php

namespace DpdConnect\classes\Connect;

use Configuration;
use DpdConnect\classes\Version;
use DpdConnect\Sdk\ClientBuilder;
use DpdConnect\Sdk\Objects\MetaData;
use DpdConnect\Sdk\Objects\ObjectFactory;
use DpdConnect\classes\DpdEncryptionManager;

class Connection
{
    protected $client;

    public function __construct()
    {
        $url = Configuration::get('dpdconnect_url');
        $username = Configuration::get('dpdconnect_username');
        $encryptedPassword = Configuration::get('dpdconnect_password');
        if ($encryptedPassword === null || $encryptedPassword === "") {
            throw new Exception('No credentials provided');
        }
        $password = DpdEncryptionManager::decrypt($encryptedPassword);
        $clientBuilder = new ClientBuilder($url, ObjectFactory::create(MetaData::class, [
            'webshopType' => Version::type(),
            'webshopVersion' => Version::webshop(),
            'pluginVersion' => Version::plugin(),
        ]));
        $this->client = $clientBuilder->buildAuthenticatedByPassword($username, $password);

        $this->client->getAuthentication()->setJwtToken(
            Configuration::get('dpdconnect_jwt_token') ?: null
        );

        $this->client->getAuthentication()->setTokenUpdateCallback(function ($jwtToken) {
            Configuration::updateValue('dpdconnect_jwt_token', $jwtToken);
            $this->client->getAuthentication()->setJwtToken($jwtToken);
        });
    }
}
