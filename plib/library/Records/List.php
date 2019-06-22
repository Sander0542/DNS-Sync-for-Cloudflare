<?php

use Modules_DnsSyncCloudflare_Cloudflare_Auth as CloudflareAuth;
use Modules_DnsSyncCloudflare_Util_PleskDNS as PleskDNS;
use Modules_DnsSyncCloudflare_Records_SyncRecord as SyncRecord;

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
            $cloudflareValue = pm_Locale::lmsg('text.recordNotFound');
            $pleskValue = $record->pleskRecord->value;

            switch ($record->getStatus())
            {
                case SyncRecord::STATUS_SYNCED:
                    $syncStatus = pm_Context::getBaseUrl() . 'images/success.png';
                    break;
                case SyncRecord::STATUS_RECORD:
                    $syncStatus = pm_Context::getBaseUrl() . 'images/warning.png';
                    break;
                default:
                    $syncStatus = pm_Context::getBaseUrl() . 'images/error.png';
                    break;
            }

            if ($record->cloudflareRecord !== null)
            {
                $cloudflareValue = $record->cloudflareRecord->content;

                if ($record->pleskRecord->type == 'SRV')
                {
                    $cloudflareValue = $record->cloudflareRecord->priority . ' ' . str_replace("\t", ' ', $record->cloudflareRecord->content);
                    $pleskValue = $record->pleskRecord->opt . ' ' . $record->pleskRecord->value;
                }
            }

            $data[] = [
                'col-host' => PleskDNS::removeDotAfterTLD($record->pleskRecord->host),
                'col-type' => $record->pleskRecord->type . ($record->pleskRecord->type == 'MX' ? ' (' . $record->pleskRecord->opt . ')' : ''),
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