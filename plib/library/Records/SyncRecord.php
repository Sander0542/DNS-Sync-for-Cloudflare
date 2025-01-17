<?php

use Modules_DnsSyncCloudflare_Cloudflare_Auth as CloudflareAuth;
use Modules_DnsSyncCloudflare_Cloudflare_Record as CloudflareRecord;
use Modules_DnsSyncCloudflare_Records_Match as RecordsMatch;
use Modules_DnsSyncCloudflare_Util_PleskDNS as PleskDNS;
use Modules_DnsSyncCloudflare_Util_Settings as Settings;
use PleskX\Api\Struct\Dns\Info as PleskRecord;

class Modules_DnsSyncCloudflare_Records_SyncRecord
{
    const STATUS_SYNCED = 1;
    const STATUS_RECORD = 2;
    const STATUS_REMOVE = 3;
    const STATUS_NONE = 4;
    const STATUS_DONT_SYNC = 5;

    /**
     * @var CloudflareAuth
     */
    public $cloudflareAuth;
    /**
     * @var pm_Domain
     */
    public $domain = null;
    /**
     * @var PleskRecord|null
     */
    public $pleskRecord = null;
    /**
     * @var CloudflareRecord|null
     */
    public $cloudflareRecord = null;

    /**
     * Modules_DnsSyncCloudflare_Records_SyncRecord constructor.
     * @param CloudflareAuth $cloudflareAuth
     * @param pm_Domain $domain
     * @param PleskRecord $pleskRecord
     * @param null $cloudflareRecord
     */
    private function __construct(CloudflareAuth $cloudflareAuth, pm_Domain $domain, PleskRecord $pleskRecord = null, $cloudflareRecord = null)
    {
        $this->domain = $domain;
        $this->cloudflareAuth = $cloudflareAuth;
        $this->pleskRecord = $pleskRecord;
        $this->cloudflareRecord = $cloudflareRecord;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        if (!Settings::syncRecordType($this->getRecordType(false), $this->domain))
        {
            return self::STATUS_DONT_SYNC;
        }

        if ($this->pleskRecord !== null)
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

        return self::STATUS_REMOVE;
    }

    public function getRecordName()
    {
        if ($this->pleskRecord !== null)
        {
            return PleskDNS::removeDotAfterTLD($this->pleskRecord->host);
        }

        if ($this->cloudflareRecord !== null)
        {
            return $this->cloudflareRecord->name;
        }

        return null;
    }

    public function getRecordType($pretify = true)
    {
        if ($this->pleskRecord !== null)
        {
            if ($this->pleskRecord->type == 'MX' && $pretify)
            {
                return 'MX (' . $this->pleskRecord->opt . ')';
            }
            return $this->pleskRecord->type;
        }

        if ($this->cloudflareRecord !== null)
        {
            if ($this->cloudflareRecord->type == 'MX' && $pretify)
            {
                return 'MX (' . $this->cloudflareRecord->priority . ')';
            }
            return $this->cloudflareRecord->type;
        }

        return null;
    }

    /**
     * @return bool
     */
    public function syncRecord(&$createdCount = 0, &$updatedCount = 0, &$deletedCount = 0, $checkSync = true)
    {
        $dns = $this->cloudflareAuth->getDNS();

        $zoneID = $this->cloudflareAuth->getZone($this->domain)->id;

        if (Settings::syncRecordType($this->getRecordType(false), $this->domain) || !$checkSync)
        {
            switch ($this->getStatus())
            {
                case self::STATUS_RECORD:
                    $updateRecord = CloudflareRecord::fromPleskRecord($this->pleskRecord);
                    // The current record can be updated
                    $response = $dns->updateRecordDetails($zoneID, $this->cloudflareRecord->id, [
                        'type' => $updateRecord->type,
                        'name' => $updateRecord->name,
                        'content' => $updateRecord->content,
                        'proxied' => $updateRecord->proxied,
                        'priority' => $updateRecord->priority
                    ]);

                    $updatedCount += $response->success ? 1 : 0;
                    break;
                case self::STATUS_NONE:
                    $updateRecord = CloudflareRecord::fromPleskRecord($this->pleskRecord);
                    // Create a new record in Cloudflare
                    $createdCount += $dns->addRecord($zoneID, $updateRecord->type, $updateRecord->name, $updateRecord->content, 0, $updateRecord->proxied, $updateRecord->priority) ? 1 : 0;
                    break;
                case self::STATUS_REMOVE:
                    if (pm_Settings::get(Settings::getDomainKey(Settings::CLOUDFLARE_REMOVE_UNUSED, $this->domain), true))
                    {
                        // Remove the record from Cloudflare
                        $deletedCount += $dns->deleteRecord($zoneID, $this->cloudflareRecord->id) ? 1 : 0;
                    }
                    break;
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
    public static function getRecords(pm_Domain $domain, CloudflareAuth $cloudflareAuth)
    {
        $list = [];

        $cloudflareRecords = $cloudflareAuth->getRecords($domain);

        foreach (PleskDNS::getRecords($domain->getId()) as $pleskRecord)
        {
            $cloudflareRecord = RecordsMatch::getCloudflareRecord($cloudflareRecords, $pleskRecord);

            $list[] = new self($cloudflareAuth, $domain, $pleskRecord, $cloudflareRecord);
        }

        foreach ($cloudflareRecords as $cloudflareRecord)
        {
            $list[] = new self($cloudflareAuth, $domain, null, $cloudflareRecord);
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
        try
        {
            $domain = pm_Domain::getByDomainId($pleskRecord->siteId);
            $cloudflareRecords = $cloudflareAuth->getRecords($domain);
            $cloudflareRecord = RecordsMatch::getCloudflareRecord($cloudflareRecords, $pleskRecord);
        }
        catch (pm_Exception $exception)
        {
            $cloudflareRecord = null;
        }

        return new self($cloudflareAuth, $domain, $pleskRecord, $cloudflareRecord);
    }
}