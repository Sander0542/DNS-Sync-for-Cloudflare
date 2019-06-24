<?php

use PleskX\Api\Struct\Dns\Info as PleskRecord;

use Modules_DnsSyncCloudflare_Cloudflare_Auth as CloudflareAuth;
use Modules_DnsSyncCloudflare_Records_Match as RecordsMatch;
use Modules_DnsSyncCloudflare_Util_PleskDNS as PleskDNS;
use Modules_DnsSyncCloudflare_Cloudflare_Record as CloudflareRecord;
use Modules_DnsSyncCloudflare_Util_Settings as Settings;

class Modules_DnsSyncCloudflare_Records_SyncRecord
{
    const STATUS_SYNCED = 1;
    const STATUS_RECORD = 2;
    const STATUS_NONE = 3;

    /**
     * @var PleskRecord
     */
    public $pleskRecord;
    /**
     * @var CloudflareRecord|null
     */
    public $cloudflareRecord = null;
    /**
     * @var CloudflareAuth|null
     */
    public $cloudflareAuth = null;

    /**
     * Modules_DnsSyncCloudflare_Records_SyncRecord constructor.
     * @param CloudflareAuth $cloudflareAuth
     * @param PleskRecord $pleskRecord
     * @param null $cloudflareRecord
     */
    private function __construct(CloudflareAuth $cloudflareAuth, PleskRecord $pleskRecord, $cloudflareRecord = null)
    {
        $this->pleskRecord = $pleskRecord;
        $this->cloudflareAuth = $cloudflareAuth;
        $this->cloudflareRecord = $cloudflareRecord;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        if ($this->cloudflareRecord !== null)
        {
            if (RecordsMatch::doRecordMatch($this->cloudflareRecord, $this->pleskRecord))
            {
                if (RecordsMatch::doValueMatch($this->cloudflareRecord, $this->pleskRecord))
                {
                    return self::STATUS_SYNCED;
                }

                return self::STATUS_RECORD;
            }
        }

        return self::STATUS_NONE;
    }

    /**
     * @return bool
     */
    public function syncRecord($checkSync = true)
    {
        $dns = $this->cloudflareAuth->getDNS();

        $domain = pm_Domain::getByDomainId($this->pleskRecord->siteId);

        $zoneID = $this->cloudflareAuth->getZone($domain)->id;

        $updateRecord = CloudflareRecord::fromPleskRecord($this->pleskRecord);

        if (Settings::syncRecordType($this->pleskRecord->type, $domain) || !$checkSync)
        {
            switch ($this->getStatus())
            {
                case self::STATUS_RECORD:
                    // The current record can be updated
                    $dns->updateRecordDetails($zoneID, $this->cloudflareRecord->id, [
                        'type' => $updateRecord->type,
                        'name' => $updateRecord->name,
                        'content' => $updateRecord->content,
                        'proxied' => $updateRecord->proxied,
                        'priority' => $updateRecord->priority
                    ]);

                    return true;
                case self::STATUS_NONE:
                    // Create a new record in Cloudflare
                    return $dns->addRecord($zoneID, $updateRecord->type, $updateRecord->name, $updateRecord->content, 0, $updateRecord->proxied, $updateRecord->priority);
            }
        }

        return false;
    }

    /**
     * @param pm_Domain $domain
     * @param CloudflareAuth $cloudflareAuth
     * @param bool $removeOld
     * @return self[]
     */
    public static function getRecords(pm_Domain $domain, CloudflareAuth $cloudflareAuth, $removeOld = false)
    {
        $list = [];

        $cloudflareRecords = $cloudflareAuth->getRecords($domain);

        foreach (PleskDNS::getRecords($domain->getId()) as $pleskRecord)
        {
            $cloudflareRecord = RecordsMatch::getCloudflareRecord($cloudflareRecords, $pleskRecord);

            $list[] = new self($cloudflareAuth, $pleskRecord, $cloudflareRecord);
        }

        if ($removeOld === true)
        {
            $dns = $cloudflareAuth->getDNS();

            $zoneID = $cloudflareAuth->getZone($domain)->id;

            foreach ($cloudflareRecords as $cloudflareRecord)
            {
                $dns->deleteRecord($zoneID, $cloudflareRecord->id);
            }
        }

        return $list;
    }

    /**
     * @param PleskRecord $pleskRecord
     * @param CloudflareAuth $cloudflareAuth
     * @return self
     */
    public static function getRecord(PleskRecord $pleskRecord, CloudflareAuth $cloudflareAuth)
    {
        $cloudflareRecords = $cloudflareAuth->getRecords(pm_Domain::getByDomainId($pleskRecord->siteId));
        $cloudflareRecord = RecordsMatch::getCloudflareRecord($cloudflareRecords, $pleskRecord);

        return new self($cloudflareAuth, $pleskRecord, $cloudflareRecord);
    }
}