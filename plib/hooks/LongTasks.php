<?php

use Modules_DnsSyncCloudflare_Task_Sync as SyncTask;

class Modules_DnsSyncCloudflare_LongTasks extends pm_Hook_LongTasks
{
    /**
     * Retrieve the list of long tasks
     *
     * @return pm_LongTask_Task[]
     */
    public function getLongTasks()
    {
        return [
            new SyncTask()
        ];
    }
}