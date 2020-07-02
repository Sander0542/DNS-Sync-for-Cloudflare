<?php

use Cloudflare\API\Adapter\Guzzle;
use Cloudflare\API\Auth\APIKey;
use Cloudflare\API\Endpoints\DNS;
use Cloudflare\API\Endpoints\User;
use Cloudflare\API\Endpoints\Zones;
use Modules_DnsSyncCloudflare_Cloudflare_Record as CloudflareRecord;
use Modules_DnsSyncCloudflare_Util_Settings as Settings;

class Modules_DnsSyncCloudflare_Cloudflare_Auth
{
    /**
     * @var Guzzle $adapter
     */
    private $adapter;

    private $userId;

    /**
     * Module_DnsSyncCloudflare_Cloudflare_Auth constructor.
     * @param Guzzle $adapter
     */
    private function __construct(Guzzle $adapter)
    {
        $this->adapter = $adapter;

        $this->userId = $this->getUser()->getUserID();
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
     * @return object|null
     */
    public function getZone(pm_Domain $domain)
    {
        /**
         * @var $zone CloudflareRecord
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

    public function getRecords(pm_Domain $domain)
    {
        $zoneID = $this->getZone($domain)->id;

        return $this->getDNS()->listRecords($zoneID, '', '', '', 1, 250)->result;
    }

    /**
     * @param pm_Domain $domain
     * @return self|null
     */
    public static function login(pm_Domain $domain)
    {
        $email = pm_Settings::getDecrypted(Settings::getDomainKey(Settings::CLOUDFLARE_EMAIL, $domain));
        $apiKey = pm_Settings::getDecrypted(Settings::getDomainKey(Settings::CLOUDFLARE_API_KEY, $domain));

        try
        {
            if (!empty($email) && !empty($apiKey))
            {
                $key = new APIKey($email, $apiKey);
                $adapter = new Guzzle($key);

                return new self($adapter);
            }
        }
        catch (Exception $exception)
        {
            //TODO: Error reporting
        }
        return null;
    }
}