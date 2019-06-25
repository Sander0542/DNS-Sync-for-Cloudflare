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
    const STATUS_REMOVE = 3;
    const STATUS_NONE = 4;

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
                return 'MX ('.$this->pleskRecord->opt.')';
            }
            return $this->pleskRecord->type;
        }

        if ($this->cloudflareRecord !== null)
        {
            if ($this->cloudflareRecord->type == 'MX' && $pretify)
            {
                return 'MX ('.$this->cloudflareRecord->priority.')';
            }
            return $this->cloudflareRecord->type;
        }

        return null;
    }

    /**
     * @return bool
     */
    public function syncRecord($checkSync = true)
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
                    $dns->updateRecordDetails($zoneID, $this->cloudflareRecord->id, [
                        'type' => $updateRecord->type,
                        'name' => $updateRecord->name,
                        'content' => $updateRecord->content,
                        'proxied' => $updateRecord->proxied,
                        'priority' => $updateRecord->priority
                    ]);

                    return true;
                case self::STATUS_NONE:
                    $updateRecord = CloudflareRecord::fromPleskRecord($this->pleskRecord);
                    // Create a new record in Cloudflare
                    return $dns->addRecord($zoneID, $updateRecord->type, $updateRecord->name, $updateRecord->content, 0, $updateRecord->proxied, $updateRecord->priority);
                case self::STATUS_REMOVE:
                    if (pm_Settings::get(Settings::getDomainKey(Settings::CLOUDFLARE_REMOVE_UNUSED, $this->domain), true)) {
                        // Remove the record from Cloudflare
                        return $dns->deleteRecord($zoneID, $this->cloudflareRecord->id);
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
    public static function getRecords(pm_Domain $domain, CloudflareAuth $cloudflareAuth, $removeOld = false)
    {
        $list = [];

        $cloudflareRecords = $cloudflareAuth->getRecords($domain);

        foreach (PleskDNS::getRecords($domain->getId()) as $pleskRecord)
        {
            $cloudflareRecord = RecordsMatch::getCloudflareRecord($cloudflareRecords, $pleskRecord);

            $list[] = new self($cloudflareAuth, $pleskRecord, $cloudflareRecord);
        }

        if ($removeOld == true)
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