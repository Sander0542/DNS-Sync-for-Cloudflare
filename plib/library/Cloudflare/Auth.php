<?php

use Cloudflare\API\Adapter\Guzzle;
use Cloudflare\API\Auth\APIKey;
use Cloudflare\API\Endpoints\DNS;
use Cloudflare\API\Endpoints\Zones;
use Cloudflare\API\Endpoints\User;
use GuzzleHttp\Exception\ClientException;

class Modules_DnsSyncCloudflare_Cloudflare_Auth
{
    /**
     * @var Guzzle $adapter
     */
    private $adapter;

    /**
     * Module_DnsSyncCloudflare_Cloudflare_Auth constructor.
     * @param Guzzle $adapter
     */
    private function __construct(Guzzle $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return new User($this->adapter);
    }

    /**
     * @return DNS
     */
    public function getDNS()
    {
        return new DNS($this->adapter);
    }

    /**
     * @return Zones
     */
    public function getZones()
    {
        return new Zones($this->adapter);
    }

    /**
     * @param pm_Domain $domain
     * @param bool $useAll
     * @return object|null
     */
    public function getZone(pm_Domain $domain)
    {
        /**
         * @var $zone Modules_DnsSyncCloudflare_Cloudflare_Record
         */
        foreach ($this->getZones()->listZones()->result as $zone)
        {
            if ($zone->name == $domain->getName())
            {
                return $zone;
            }
        }

        return null;
    }

    /**
     * @param pm_Domain $domain
     * @return Modules_DnsSyncCloudflare_Cloudflare_Auth|null
     */
    public static function login(pm_Domain $domain)
    {
        $email = pm_Settings::getDecrypted(Modules_DnsSyncCloudflare_Util_Settings::getDomainKey(Modules_DnsSyncCloudflare_Util_Settings::CLOUDFLARE_EMAIL, $domain->getId()));
        $apiKey = pm_Settings::getDecrypted(Modules_DnsSyncCloudflare_Util_Settings::getDomainKey(Modules_DnsSyncCloudflare_Util_Settings::CLOUDFLARE_API_KEY, $domain->getId()));

        try
        {
            if (!empty($email) && !empty($apiKey))
            {
                $key = new APIKey($email, $apiKey);
                $adapter = new Guzzle($key);
                return new Modules_DnsSyncCloudflare_Cloudflare_Auth($adapter);
            }
        }
        catch (ClientException $exception)
        {
            //TODO: Error reporting
        }
        return null;
    }
}