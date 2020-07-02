<?php

use Modules_DnsSyncCloudflare_Cloudflare_Auth as CloudflareAuth;
use Modules_DnsSyncCloudflare_Records_SyncRecord as SyncRecord;

class Modules_DnsSyncCloudflare_Records_List
{
    /**
     * @param pm_Domain $domain
     * @param CloudflareAuth $cloudflareAuth
     * @return array
     */
    public static function getList(pm_Domain $domain, CloudflareAuth $cloudflareAuth)
    {
        $data = [];

        $records = SyncRecord::getRecords($domain, $cloudflareAuth);

        foreach ($records as $record)
        {
            //Set the default values
            $cloudflareValue = pm_Locale::lmsg('text.recordNotFound');
            $pleskValue = pm_Locale::lmsg('text.recordNotFound');
            $syncStatus = pm_Context::getBaseUrl() . 'images/error.png';

            //Check the status
            switch ($record->getStatus())
            {
                case SyncRecord::STATUS_SYNCED:
                    $syncStatus = pm_Context::getBaseUrl() . 'images/success.png';
                    break;
                case SyncRecord::STATUS_RECORD:
                    $syncStatus = pm_Context::getBaseUrl() . 'images/warning.png';
                    break;
                case SyncRecord::STATUS_REMOVE:
                    $syncStatus = pm_Context::getBaseUrl() . 'images/error2.png';
                    break;
                case SyncRecord::STATUS_DONT_SYNC:
                    $syncStatus = pm_Context::getBaseUrl() . 'images/off.png';
                    break;
            }

            if ($record->cloudflareRecord !== null)
            {
                $cloudflareValue = $record->cloudflareRecord->content;

                if ($record->cloudflareRecord->type == 'SRV') $cloudflareValue = $record->cloudflareRecord->priority . ' ' . str_replace("\t", ' ', $record->cloudflareRecord->content);
            }

            if ($record->pleskRecord !== null)
            {
                $pleskValue = $record->pleskRecord->value;

                if ($record->pleskRecord->type == 'SRV') $pleskValue = $record->pleskRecord->opt . ' ' . $record->pleskRecord->value;
            }

            $data[] = [
                'col-host' => $record->getRecordName(),
                'col-type' => $record->getRecordType(),
                'col-status' => '<img src="' . $syncStatus . '"/>',
                'col-plesk' => self::minifyValue($pleskValue),
                'col-cloudflare' => self::minifyValue($cloudflareValue),
            ];
        }

        return $data;
    }

    private static function minifyValue($value, $length = 60)
    {
        if (strlen($value) > $length)
        {
            return substr($value, 0, $length) . '...';
        }
        return $value;
    }
}