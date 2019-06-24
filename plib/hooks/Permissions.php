<?php

use Modules_DnsSyncCloudflare_Util_Permissions as PermissionsUtil;

class Modules_DnsSyncCloudflare_Permissions extends pm_Hook_Permissions
{
    /**
     * @return array
     */
    public function getPermissions()
    {
        return [
            PermissionsUtil::PERMISSIONS_MANAGE => [
                'default' => true,
                'place' => self::PLACE_MAIN,
                'name' => pm_Locale::lmsg('permission.cloudflare.title'),
                'description' => pm_Locale::lmsg('permission.cloudflare.description'),
            ],
            PermissionsUtil::PERMISSIONS_SETTINGS => [
                'default' => true,
                'place' => self::PLACE_MAIN,
                'name' => pm_Locale::lmsg('permission.cloudflareSettings.title'),
                'description' => pm_Locale::lmsg('permission.cloudflareSettings.description'),
                'master' => PermissionsUtil::PERMISSIONS_MANAGE,
            ],
            PermissionsUtil::PERMISSIONS_API => [
                'default' => true,
                'place' => self::PLACE_MAIN,
                'name' => pm_Locale::lmsg('permission.cloudflareApi.title'),
                'description' => pm_Locale::lmsg('permission.cloudflareApi.description'),
                'master' => PermissionsUtil::PERMISSIONS_MANAGE,
            ],
        ];
    }
}