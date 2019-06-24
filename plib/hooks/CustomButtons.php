<?php

class Modules_DnsSyncCloudflare_CustomButtons extends pm_Hook_CustomButtons
{
    /**
     * @return array
     */
    public function getButtons()
    {
        $commonParmas = [
            'title' => pm_Locale::lmsg('title'),
            'description' => pm_Locale::lmsg('description'),
            'icon' => pm_Context::getBaseUrl() . '/images/logo.png',
            'link' => pm_Context::getActionUrl('domain', 'records'),
        ];
        return [
            array_merge($commonParmas, [
                'place' => self::PLACE_DOMAIN_PROPERTIES,
                'contextParams' => true,
                'visibility' => [
                    $this,
                    'isClientButtonVisible'
                ]
            ])
        ];
    }

    /**
     * @param $options
     * @return bool
     */
    public function isClientButtonVisible($options)
    {
        if (empty($options['site_id']))
        {
            return false;
        }
        foreach (pm_Session::getCurrentDomains(true) as $domain)
        {
            if ($domain->getId() == $options['site_id'])
            {
                if (pm_Session::getClient()->hasPermission('manage_cloudflare', $domain))
                {
                    return true;
                }
            }
        }
    }
}