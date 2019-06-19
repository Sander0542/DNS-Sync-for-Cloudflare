<?php

use PleskX\Api\InternalClient;
use PleskX\Api\Struct\Dns\Info;

class Modules_DnsSyncCloudflare_Util_PleskDNS
{
    /**
     * @param $siteID
     * @return Info[]
     */
    public static function getRecords($siteID)
    {
        $client = new InternalClient();
        return $client->dns()->getAll("site-id", $siteID);
    }
    /**
     * @param $recordID
     * @return Info
     */
    public static function getRecord($recordID)
    {
        $client = new InternalClient();
        return $client->dns()->get('id', $recordID);
    }

    /**
     * @param $domain
     * @return string
     */
    public static function removeDotAfterTLD($domain) {
        if (self::endsWith($domain, '.'))
        {
            if (strlen($domain) > 1)
            {
                return substr($domain, 0, strlen($domain) - 1);
            }
        }
        return $domain;
    }

    /**
     * @param $string
     * @param $endString
     * @return bool
     */
    private static function endsWith($string, $endString)
    {
        $len = strlen($endString);
        if ($len == 0)
        {
            return true;
        }
        return (substr($string, -$len) === $endString);
    }
}