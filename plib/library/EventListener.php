<?php

use Modules_DnsSyncCloudflare_Util_Settings as Settings;
use Modules_DnsSyncCloudflare_Cloudflare_Auth as CloudflareAuth;
use Modules_DnsSyncCloudflare_Records_SyncRecord as SyncRecord;

class Modules_DnsSyncCloudflare_EventListener implements EventListener
{
    /**
     * @return array
     */
    public function filterActions()
    {
        return [
            'domain_dns_update',
        ];
    }

    /**
     * @param $objectType
     * @param $objectId
     * @param $action
     * @param $oldValues
     * @param $newValues
     */
    public function handleEvent($objectType, $objectId, $action, $oldValues, $newValues)
    {
        switch ($action)
        {
            case 'domain_dns_update':
                try
                {
                    $domain = pm_Domain::getByName($oldValues['Domain Name']);

                    if (pm_Settings::get(Settings::getDomainKey(Settings::CLOUDFLARE_AUTO_SYNC, $domain), true))
                    {
                        $cloudflare = CloudflareAuth::login($domain);

                        if ($cloudflare instanceof CloudflareAuth)
                        {
                            $records = SyncRecord::getRecords($domain, $cloudflare);

                            foreach ($records as $record)
                            {
                                $record->syncRecord();
                            }
                        }
                    }
                }
                catch (pm_Exception $e)
                {
                    //TODO: Error reporting
                }
                break;
        }
    }
}

return new Modules_DnsSyncCloudflare_EventListener();
