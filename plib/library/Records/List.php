<?php

use Modules_DnsSyncCloudflare_Cloudflare_Auth as CloudflareAuth;
use Modules_DnsSyncCloudflare_Util_PleskDNS as PleskDNS;
use Modules_DnsSyncCloudflare_Records_SyncRecord as SyncRecord;
use Modules_DnsSyncCloudflare_Cloudflare_Record as CloudflareRecord;

class Modules_DnsSyncCloudflare_Records_List
{
    private $domain;
    private $cloudflareAuth;

    public function __construct(pm_Domain $domain, CloudflareAuth $cloudflareAuth)
    {
        $this->domain = $domain;
        $this->cloudflareAuth = $cloudflareAuth;
    }

    /**
     * @return array
     */
    public function getList()
    {
        $data = [];

        $records = SyncRecord::getRecords($this->domain, $this->cloudflareAuth);

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
                'col-plesk' => $this->minifyValue($pleskValue),
                'col-cloudflare' => $this->minifyValue($cloudflareValue),
            ];
        }

        return $data;
    }

    private function minifyValue($value, $length = 60)
    {
        if (strlen($value) > $length)
        {
            return substr($value, 0, $length) . '...';
        }
        return $value;
    }
}