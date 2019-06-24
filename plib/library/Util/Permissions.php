<?php

class Modules_DnsSyncCloudflare_Util_Permissions
{
    const PERMISSIONS_MANAGE = 'modules_dns_sync_cloudflare_manage';
    const PERMISSIONS_SETTINGS = 'modules_dns_sync_cloudflare_settings';
    const PERMISSIONS_API = 'modules_dns_sync_cloudflare_api';

    /**
     * @param $siteID
     * @return string|pm_Domain
     */
    public static function checkAccess($siteID, $checkSettings = false, $checkAPI = false)
    {
        try
        {
            $client = pm_Session::getClient();
            $domain = pm_Domain::getByDomainId($siteID);

            if ($client->hasPermission(self::PERMISSIONS_MANAGE, $domain))
            {
                if (($client->hasPermission(self::PERMISSIONS_SETTINGS, $domain) || !$checkSettings) && ($client->hasPermission(self::PERMISSIONS_API, $domain) || !$checkAPI))
                {
                    return $domain;
                }
                else
                {
                    return pm_Locale::lmsg('message.noAccessSettings');
                }
            }
            else
            {
                return pm_Locale::lmsg('message.noAccessExtension');
            }
        }
        catch (pm_Exception $e)
        {
            return pm_Locale::lmsg('message.noDomainSelected');
        }
    }
}