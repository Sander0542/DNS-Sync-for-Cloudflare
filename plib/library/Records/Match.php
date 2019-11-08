<?php

use PleskX\Api\Struct\Dns\Info as PleskRecord;

use Modules_DnsSyncCloudflare_Cloudflare_Record as CloudflareRecord;
use Modules_DnsSyncCloudflare_Util_PleskDNS as PleskDNS;
use Modules_DnsSyncCloudflare_Util_Settings as Settings;

class Modules_DnsSyncCloudflare_Records_Match
{

    /**
     * @param CloudflareRecord[] $cloudflareRecords
     * @param PleskRecord $pleskRecord
     * @return CloudflareRecord|null
     */
    public static function getCloudflareRecord(&$cloudflareRecords, PleskRecord $pleskRecord)
    {
        /**
         * @var $cloudflareRecord CloudflareRecord
         */
        foreach ($cloudflareRecords as $key => $cloudflareRecord)
        {
            if (self::doRecordMatch($cloudflareRecord, $pleskRecord))
            {
                unset($cloudflareRecords[$key]);
                return $cloudflareRecord;
            }
        }

        return null;
    }

    public static function doRecordMatch($cloudflareRecord, PleskRecord $pleskRecord)
    {
        //Check if the types are the same
        if ($pleskRecord->type == $cloudflareRecord->type)
        {
            //Check if the hostnames are the same
            if (PleskDNS::removeDotAfterTLD($pleskRecord->host) == $cloudflareRecord->name)
            {
                //Check the type of record and use the right checker
                switch ($pleskRecord->type)
                {
                    case 'A';
                    case 'AAAA';
                    case 'CNAME';
                    case 'SRV':
                    case 'TXT':
                        return true;
                    case 'NS':
                        return self::matchValue($cloudflareRecord, $pleskRecord);
                    case 'MX':
                        return $pleskRecord->opt == $cloudflareRecord->priority;
                }
            }
        }

        return false;
    }

    public static function doValueMatch($cloudflareRecord, PleskRecord $pleskRecord)
    {
        //Check if the types are the same
        if ($pleskRecord->type == $cloudflareRecord->type)
        {
            //Check if the hostnames are the same
            if (PleskDNS::removeDotAfterTLD($pleskRecord->host) == $cloudflareRecord->name)
            {
                //Check the type of record and use the right checker
                switch ($pleskRecord->type)
                {
                    case 'A';
                    case 'AAAA';
                    case 'CNAME':
                        if ($cloudflareRecord->proxied == pm_Settings::get(Settings::getDomainKey(Settings::CLOUDFLARE_PROXY, pm_Domain::getByDomainId($pleskRecord->siteId)), true))
                        {
                            return self::matchValue($cloudflareRecord, $pleskRecord);
                        }
                        return false;
                    case 'NS':
                    case 'MX':
                    case 'TXT':
                        return self::matchValue($cloudflareRecord, $pleskRecord);
                }
            }
        }

        return false;
    }

    private static function matchValue($cloudflareRecord, PleskRecord $pleskRecord)
    {
        //Check if the values are the same
        return PleskDNS::removeDotAfterTLD($pleskRecord->value) == $cloudflareRecord->content;
    }
}