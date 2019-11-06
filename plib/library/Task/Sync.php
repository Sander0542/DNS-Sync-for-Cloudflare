<?php

use Modules_DnsSyncCloudflare_Cloudflare_Auth as CloudflareAuth;
use Modules_DnsSyncCloudflare_Records_SyncRecord as SyncRecord;

class Modules_DnsSyncCloudflare_Task_Sync extends pm_LongTask_Task
{
    public $trackProgress = true;

    public $hasDangerousMessage = true;

    public function run()
    {
        try
        {
            $domain = pm_Domain::getByDomainId($this->getParam('site_id'));

            $cloudflare = CloudflareAuth::login($domain);

            if ($cloudflare instanceof CloudflareAuth)
            {
                $records = SyncRecord::getRecords($domain, $cloudflare);

                $createdCount = 0;
                $updatedCount = 0;
                $deletedCount = 0;

                foreach ($records as $record)
                {
                    $record->syncRecord($createdCount, $updatedCount, $deletedCount);

                    $done = $createdCount + $updatedCount + $deletedCount;

                    $progress = 100 / count($records) * $done;

                    $this->updateProgress($progress);
                }

                $this->setParam('createdCount', $createdCount);
                $this->setParam('updatedCount', $updatedCount);
                $this->setParam('deletedCount', $deletedCount);
            }
        }
        catch (pm_Exception $exception)
        {
            
        }
    }

    public function statusMessage()
    {
        switch ($this->getStatus())
        {
            case static::STATUS_RUNNING:
                return pm_Locale::lmsg('task.sync.running');
            case static::STATUS_DONE:
                return pm_Locale::lmsg('task.sync.done', $this->getParams());
        }
        return '';
    }

    public function onStart()
    {
        $this->setParam('onStart', 1);
    }

    public function onDone()
    {
        $this->setParam('onDone', 1);
    }
}