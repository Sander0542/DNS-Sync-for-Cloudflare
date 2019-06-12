<?php

class Modules_DnsSyncCloudflare_Util_Permissions
{
    /**
     * @param $siteID
     * @return string|pm_Domain
     */
    public static function checkAccess($siteID) {

        try
        {
            $client = pm_Session::getClient();
            $domain = pm_Domain::getByDomainId($siteID);

            if ($client->hasPermission('manage_cloudflare', $domain))
            {
                if ($client->hasPermission('manage_cloudflare_settings', $domain))
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