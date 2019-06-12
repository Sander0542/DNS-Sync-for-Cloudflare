<?php

use GuzzleHttp\Exception\ClientException;

class IndexController extends pm_Controller_Action
{
    /**
     * @var $cloudflare Modules_DnsSyncCloudflare_Cloudflare_Auth
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

    public function indexDataAction()
    {
        $list = $this->_getDomainList();
        // Json data from pm_View_List_Simple
        $this->_helper->json($list->fetchData());
    }

    public function domainDataAction()
    {
        if ($this->cloudflare !== false) {
            $list = $this->_getDomainList();
            // Json data from pm_View_List_Simple
            $this->_helper->json($list->fetchData());
        }
    }

    private function _getDomainList()
    {
        $data = array();

        /**
         * @var $domain pm_Domain
         */
        foreach (pm_Session::getCurrentDomains(true) as $domain)
        {
            $cloudflareID = pm_Locale::lmsg('text.zoneIdNotFound');

            $auth = Modules_DnsSyncCloudflare_Cloudflare_Auth::login($domain);

            if ($auth !== null) {
                /**
                 * @var $zone Modules_DnsSyncCloudflare_Cloudflare_Record
                 */
                $zone = $auth->getZone($domain);
                if ($zone !== null) {
                    $cloudflareID = $zone->id;
                }
            }

            $data[] = array(
                'col-domain' => '<a href="'.pm_Context::getActionUrl('domain', 'records?site_id='.$domain->getId()).'">'.$domain->getName().'</a>',
                'col-zone' => $cloudflareID,
            );
        }

        $list = new pm_View_List_Simple($this->view, $this->_request);
        $list->setData($data);
        $list->setColumns(array(
            'col-domain' => array(
                'title' => pm_Locale::lmsg('table.domainName'),
                'noEscape' => true,
            ),
            'col-zone' => array(
                'title' => pm_Locale::lmsg('table.cloudflareZoneID'),
                'noEscape' => true,
            )
        ));
        $list->setDataUrl(array('action' => 'domain-data'));

        return $list;
    }
}
