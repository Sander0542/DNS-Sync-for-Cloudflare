<?php

use PleskX\Api\Struct\Dns\Info as PleskRecord;
use Modules_DnsSyncCloudflare_Util_Settings as Settings;

class Modules_DnsSyncCloudflare_Cloudflare_Record
{
    public $id;
    public $type;
    public $name;
    public $content;
    public $proxiable;
    public $proxied;
    public $ttl;
    public $locked;
    public $zone_id;
    public $zone_name;
    public $created_on;
    public $modified_on;
    public $data;
    public $priority;

    /**
     * @param PleskRecord $pleskRecord
     * @return self
     */
    public static function fromPleskRecord(PleskRecord $pleskRecord)
    {
        $record = new self();
        
        $record->type = $pleskRecord->type;
        $record->name = $pleskRecord->host;

//        $record->proxied = (bool)Settings::useCloudflareProxy($pleskRecord->siteId, $pleskRecord->type);
        $record->proxied = false;

        $record->content = $pleskRecord->value;
        $record->priority = '';

        //Special record types
        switch ($pleskRecord->type)
        {
            //Check for MX
            case 'MX':
                $record->priority = $pleskRecord->opt;
                break;
            //Check for SRV
            case 'SRV':
                $content = explode(' ', $pleskRecord->opt . ' ' . $pleskRecord->value);
                $record->content = $content[1] . ' ' . $content[2] . ' ' . $content[3];
                $record->priority = $content[0];
                break;
        }

        return $record;
    }
}