<?php

use PleskX\Api\Struct\Dns\Info;

abstract class DNSUtilBase
{
  protected $cloudflare;
  protected $pleskDNS;
  protected $cloudflareRecords;
  protected $pleskRecords;

  public $siteID;
  public $domainName;

  public $zoneID;

  /**
   * DNSUtilBase constructor.
   * @param $siteID
   * @param Cloudflare $cloudflare
   * @param PleskDNS $pleskDNS
   * @throws pm_Exception
   */
  public function __construct($siteID, Cloudflare $cloudflare, PleskDNS $pleskDNS)
  {
    //Save the Site ID
    $this->siteID = $siteID;

    //Save Cloudflare and the Plesk NDS
    $this->cloudflare = $cloudflare;
    $this->pleskDNS = $pleskDNS;

    //Fetch the domain from the Site ID
    $this->domainName = pm_Domain::getByDomainId($siteID)->getName();

    $this->zoneID = $this->cloudflare->getZone($siteID)->id;

    $this->cloudflareRecords = $this->cloudflare->getDNS()->listRecords($this->zoneID, '', '', '', 1, 250)->result;
    $this->pleskRecords = $this->pleskDNS->getRecords($this->siteID);
  }

  /**
   * @return CloudflareRecord[]
   */
  public function getCloudflareRecords(): array
  {
    return $this->cloudflareRecords;
  }

  /**
   * @return Info[]
   */
  public function getPleskRecords(): array
  {
    return $this->pleskRecords;
  }

  /**
   * @param Info $pleskRecord
   * @return bool|CloudflareRecord
   */
  protected function getCloudflareRecord(Info $pleskRecord) {
    if ($pleskRecord->type == 'A' || $pleskRecord->type == 'AAAA' || $pleskRecord->type == 'TXT' || $pleskRecord->type == 'CNAME' || $pleskRecord->type == 'MX') {
      foreach ($this->getCloudflareRecords() as $cloudflareRecord) {
        if ($pleskRecord->type == $cloudflareRecord->type && $this->removeDotAfterTLD($pleskRecord->host) == $cloudflareRecord->name) {
          return $cloudflareRecord;
        }
      }
    } else if ($pleskRecord->type == 'NS') {
      foreach ($this->getCloudflareRecords() as $cloudflareRecord) {
        if ($pleskRecord->type == $cloudflareRecord->type && $this->removeDotAfterTLD($pleskRecord->host) == $cloudflareRecord->name && $this->removeDotAfterTLD($pleskRecord->value) == $cloudflareRecord->content) {
          return $cloudflareRecord;
        }
      }
    }
    return false;
  }

  /**
   * @param Info $pleskRecord
   * @param CloudflareRecord $cloudflareRecord
   * @return bool
   */
  protected function doRecordsMatch(Info $pleskRecord, $cloudflareRecord) {
    //Record Type (A, AAAA, CNAME, TEXT, Etc)
    if ($pleskRecord->type == $cloudflareRecord->type) {
      //The Domain name (sub.domain.tld)
      if ($this->removeDotAfterTLD($pleskRecord->host) == $this->removeDotAfterTLD($cloudflareRecord->name)) {
        //The value of the (sub)domain
        if ($this->removeDotAfterTLD($pleskRecord->value) == $cloudflareRecord->content) {
          //If all of this is true, then the domains match
          return true;
        }
      }
    }
    return false;
  }

  /**
   * @param $domain
   * @return string
   */
  protected function removeDotAfterTLD($domain) {
    if (endsWith($domain, '.')) {
      if (strlen($domain) > 1) {
        return substr($domain, 0, strlen($domain) - 1);
      }
    }
    return $domain;
  }
}