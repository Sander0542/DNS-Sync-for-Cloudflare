<?php

class Modules_CloudflareDnsSync_Util_Settings
{
  const CLOUDFLARE_EMAIL = 'cloudflareEmail';
  const CLOUDFLARE_API_KEY = 'cloudflareApiKey';

  const CLOUDFLARE_PROXY = 'cloudflareProxy';
  const CLOUDFLARE_SYNC_TYPES = 'cloudflareSyncTypes';

  public static function getUserKey($key, $userID = null) {
    return 'u'.(is_numeric($userID) ? $userID : pm_Session::getClient()->getId()).'_'.$key;
  }

  public static function getDomainKey($key, $site_id) {
    return 'd'.$site_id.'_'.$key;
  }
}