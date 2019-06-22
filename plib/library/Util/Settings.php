<?php

use Modules_DnsSyncCloudflare_Util_Records as RecordsUtil;

class Modules_DnsSyncCloudflare_Util_Settings
{
    const CLOUDFLARE_EMAIL = 'cloudflareEmail';
    const CLOUDFLARE_API_KEY = 'cloudflareApiKey';

    const CLOUDFLARE_PROXY = 'cloudflareProxy';
    const CLOUDFLARE_SYNC_TYPES = 'cloudflareSyncTypes';
    const CLOUDFLARE_AUTO_SYNC = 'cloudflareAutomaticSync';

    public static function getUserKey($key, $userID = null)
    {
        return 'u' . (is_numeric($userID) ? $userID : pm_Session::getClient()->getId()) . '_' . $key;
    }

    public static function getDomainKey($key, pm_Domain $domain)
    {
        return 'd' . $domain->getId() . '_' . $key;
    }

    public static function useCloudflareProxy(pm_Domain $domain, $recordType = 'A')
    {
        if (RecordsUtil::canUseProxy($recordType))
        {
            return pm_Settings::get(self::getDomainKey(self::CLOUDFLARE_PROXY, $domain), true);
        }
        return false;
    }

    public static function syncRecordType($recordType, pm_Domain $domain = null)
    {
        //Check if the record can be synced
        if (in_array($recordType, RecordsUtil::getAvailableRecords()))
        {
            //Check for the setting type of this record
            if ($domain === null || pm_Settings::get(self::getDomainKey('record' . $recordType, $domain), true))
            {
                return true;
            }
        }
        return false;
    }
}