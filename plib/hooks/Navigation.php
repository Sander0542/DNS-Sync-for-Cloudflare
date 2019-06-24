<?php

class Modules_DnsSyncCloudflare_Navigation extends pm_Hook_Navigation
{
    /**
     * @return array
     */
    public function getNavigation()
    {
        return [
            [
                'controller' => 'index',
                'action' => 'index',
                'label' => pm_Locale::lmsg('title'),
                'pages' => [
                    [
                        'controller' => 'domain',
                        'action' => 'records',
                        'label' => pm_Locale::lmsg('tab.records')
                    ],
                    [
                        'controller' => 'domain',
                        'action' => 'settings',
                        'label' => pm_Locale::lmsg('tab.settings')
                    ],
                    [
                        'controller' => 'domain',
                        'action' => 'api',
                        'label' => pm_Locale::lmsg('tab.api')
                    ],
                ]
            ]
        ];
    }
}