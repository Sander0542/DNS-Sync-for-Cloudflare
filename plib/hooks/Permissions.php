<?php

class Modules_DnsSyncCloudflare_Permissions extends pm_Hook_Permissions
{
    /**
     * @return array
     */
    public function getPermissions()
    {
        return [
            'manage_cloudflare' => [
                'default' => true,
                'place' => self::PLACE_MAIN,
                'name' => pm_Locale::lmsg('permission.cloudflare.title'),
                'description' => pm_Locale::lmsg('permission.cloudflare.description'),
            ],
            'manage_cloudflare_settings' => [
                'default' => true,
                'place' => self::PLACE_MAIN,
                'name' => pm_Locale::lmsg('permission.cloudflareSettings.title'),
                'description' => pm_Locale::lmsg('permission.cloudflareSettings.description'),
                'master' => 'manage_cloudflare',
            ],
            'manage_cloudflare_api' => [
                'default' => false,
                'place' => self::PLACE_MAIN,
                'name' => pm_Locale::lmsg('permission.cloudflareApi.title'),
                'description' => pm_Locale::lmsg('permission.cloudflareApi.description'),
                'master' => 'manage_cloudflare',
            ],
        ];
    }
}