<?php

use GuzzleHttp\Exception\ClientException;
use Modules_DnsSyncCloudflare_Cloudflare_Record as CloudflareRecord;
use Modules_DnsSyncCloudflare_Cloudflare_Auth as CloudflareAuth;

class IndexController extends pm_Controller_Action
{
    /**
     * @var $cloudflare CloudflareAuth
     */
    private $cloudflare;

    public function init()
    {
        parent::init();

        // Init title for all actions
        $this->view->pageTitle = pm_Locale::lmsg('title');
    }

    public function indexAction()
    {
        try
        {
            $list = $this->_getDomainList();
            $this->view->list = $list;
        }
        catch (ClientException $exception)
        {
            $this->view->error = pm_Locale::lmsg('message.noConnection');
        }
    }

    public function domainDataAction()
    {
        if ($this->cloudflare !== false)
        {
            $list = $this->_getDomainList();
            // Json data from pm_View_List_Simple
            $this->_helper->json($list->fetchData());
        }
    }

    private function _getDomainList()
    {
        $data = [];

        /**
         * @var $domain pm_Domain
         */
        foreach (pm_Session::getCurrentDomains(true) as $domain)
        {
            $cloudflareID = pm_Locale::lmsg('text.zoneIdNotFound');

            $cloudflare = CloudflareAuth::login($domain);

            if ($cloudflare instanceof CloudflareAuth)
            {
                /**
                 * @var $zone CloudflareRecord
                 */
                $zone = $cloudflare->getZone($domain);
                if ($zone !== null)
                {
                    $cloudflareID = $zone->id;
                }
            }

            $data[] = [
                'col-domain' => '<a href="' . pm_Context::getActionUrl('domain', 'records?site_id=' . $domain->getId()) . '">' . $domain->getName() . '</a>',
                'col-zone' => $cloudflareID,
            ];
        }

        $list = new pm_View_List_Simple($this->view, $this->_request);
        $list->setData($data);
        $list->setColumns([
            'col-domain' => [
                'title' => pm_Locale::lmsg('table.domainName'),
                'noEscape' => true,
            ],
            'col-zone' => [
                'title' => pm_Locale::lmsg('table.cloudflareZoneID'),
                'noEscape' => true,
            ]
        ]);
        $list->setDataUrl(['action' => 'domain-data']);

        return $list;
    }
}
