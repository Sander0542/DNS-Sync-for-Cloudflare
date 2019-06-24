<?php

use Modules_DnsSyncCloudflare_Cloudflare_Auth as CloudflareAuth;
use Modules_DnsSyncCloudflare_Records_List as RecordList;
use Modules_DnsSyncCloudflare_Records_SyncRecord as SyncRecord;
use Modules_DnsSyncCloudflare_Util_Settings as Settings;
use Modules_DnsSyncCloudflare_Util_Permissions as Permissions;
use Modules_DnsSyncCloudflare_Util_Records as RecordsUtil;

class DomainController extends pm_Controller_Action
{
    public function init()
    {
        parent::init();

        // Init title for all actions
        $this->view->pageTitle = pm_Locale::lmsg('title');

        $siteID = $this->getRequest()->getParam("site_id");

        // Init tabs for all actions
        $this->view->tabs = [
            [
                'title' => pm_Locale::lmsg('tab.records'),
                'action' => 'records?site_id=' . $siteID,
            ],
            [
                'title' => pm_Locale::lmsg('tab.settings'),
                'action' => 'settings?site_id=' . $siteID,
            ],
            [
                'title' => pm_Locale::lmsg('tab.api'),
                'action' => 'api?site_id=' . $siteID,
            ],
        ];
    }

    public function recordsAction()
    {
        //Set the active tab
        $this->view->tabs[0]['active'] = true;

        $access = Permissions::checkAccess($this->getRequest()->getParam("site_id"));

        if ($access instanceof pm_Domain)
        {
            $domain = $access;

            $this->view->pageTitle = pm_Locale::lmsg('title.dnsSyncFor', ['domain' => $domain->getName()]);

            $cloudflare = CloudflareAuth::login($domain);

            if ($cloudflare !== null)
            {
                $zone = $cloudflare->getZone($domain);

                if ($zone !== null)
                {
                    $this->view->syncTools = [
                        [
                            'title' => pm_Locale::lmsg('button.syncAll'),
                            'description' => 'Sync all the records.',
                            'class' => 'sb-button1',
                            'action' => 'sync-all?site_id='.$domain->getId(),
                        ],
                    ];

                    $this->view->list = $this->_getRecordsList($domain, $cloudflare);
                }
                else
                {
                    $this->_status->addMessage('error', pm_Locale::lmsg('message.noCloudflareZoneFound'));
                }
            }
            else
            {
                $this->_status->addMessage('error', pm_Locale::lmsg('message.noConnection'));
//                $this->forward('api',null,null,['site_id' => $domain->getId()]);
            }
        }
        else
        {
            $this->_status->addMessage('error', $access);
        }
    }

    public function recordsDataAction()
    {
        $access = Permissions::checkAccess($this->getRequest()->getParam("site_id"));

        if ($access instanceof pm_Domain)
        {
            $domain = $access;

            $cloudflare = CloudflareAuth::login($domain);

            if ($cloudflare !== null)
            {
                $list = $this->_getRecordsList($domain, $cloudflare);
                // Json data from pm_View_List_Simple
                $this->_helper->json($list->fetchData());
            }
        }
    }

    public function settingsAction()
    {
        //Set the active tab
        $this->view->tabs[1]['active'] = true;

        $access = Permissions::checkAccess($this->getRequest()->getParam("site_id"), true);

        if ($access instanceof pm_Domain)
        {
            $domain = $access;

            $this->view->pageTitle = pm_Locale::lmsg('title.dnsSyncFor', ['domain' => $domain->getName()]);

            //List the Type of available records
            $recordOptions = RecordsUtil::getAvailableRecords();

            $selectedRecords = [];
            foreach ($recordOptions as $option)
            {
                if (Settings::syncRecordType($option, $domain))
                {
                    array_push($selectedRecords, $option);
                }
            }

            //Create a new Form
            $form = new pm_Form_Simple();
            $form->addElement('checkbox', Settings::CLOUDFLARE_PROXY, [
                'label' => pm_Locale::lmsg('form.trafficThruCloudflare'),
                'value' => Settings::useCloudflareProxy($domain),
            ]);
            $form->addElement('checkbox', Settings::CLOUDFLARE_AUTO_SYNC, [
                'label' => pm_Locale::lmsg('form.automaticSync'),
                'value' => pm_Settings::get(Settings::getDomainKey(Settings::CLOUDFLARE_AUTO_SYNC, $domain), true),
            ]);
            $form->addElement('checkbox', Settings::CLOUDFLARE_REMOVE_UNUSED, [
                'label' => pm_Locale::lmsg('form.removeUnused'),
                'value' => pm_Settings::get(Settings::getDomainKey(Settings::CLOUDFLARE_REMOVE_UNUSED, $domain), true),
            ]);
            $form->addElement('multiCheckbox', Settings::CLOUDFLARE_SYNC_TYPES, [
                'label' => pm_Locale::lmsg('form.selectRecord'),
                'multiOptions' => $recordOptions,
                'value' => $selectedRecords
            ]);

            $form->addControlButtons([
                'sendTitle' => pm_Locale::lmsg('button.save'),
                'cancelLink' => pm_Context::getActionUrl('domain', 'settings?site_id=' . $domain->getId()),
            ]);

            if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost()))
            {
                pm_Settings::set(Settings::getDomainKey(Settings::CLOUDFLARE_PROXY, $domain), $form->getValue(Settings::CLOUDFLARE_PROXY));
                pm_Settings::set(Settings::getDomainKey(Settings::CLOUDFLARE_AUTO_SYNC, $domain), $form->getValue(Settings::CLOUDFLARE_AUTO_SYNC));
                pm_Settings::set(Settings::getDomainKey(Settings::CLOUDFLARE_REMOVE_UNUSED, $domain), $form->getValue(Settings::CLOUDFLARE_REMOVE_UNUSED));
                foreach ($recordOptions as $option)
                {
                    pm_Settings::set(Settings::getDomainKey('record' . $option, $domain), in_array($option, $form->getValue(Settings::CLOUDFLARE_SYNC_TYPES)));
                }
                $this->_status->addMessage('info', pm_Locale::lmsg('message.settingsSaved'));
                $this->_helper->json(['redirect' => pm_Context::getActionUrl('domain', 'settings?site_id=' . $domain->getId())]);
            }
            $this->view->form = $form;
        }
    }

    public function apiAction()
    {
        //Set the active tab
        $this->view->tabs[2]['active'] = true;

        $access = Permissions::checkAccess($this->getRequest()->getParam("site_id"), false, true);

        if ($access instanceof pm_Domain)
        {
            $domain = $access;

            $this->view->pageTitle = pm_Locale::lmsg('title.dnsSyncFor', ['domain' => $domain->getName()]);

            $cloudflare = CloudflareAuth::login($domain);

            if ($cloudflare !== null)
            {
                $this->_status->addMessage('info', pm_Locale::lmsg('message.signedInAs', (array)$cloudflare->getUser()->getUserDetails()));
            }

            // Create a new Form
            $form = new pm_Form_Simple();
            $form->addElement('Text', Settings::CLOUDFLARE_EMAIL, [
                'label' => pm_Locale::lmsg('form.cloudflareEmail'),
                'value' => pm_Settings::getDecrypted(Settings::getDomainKey(Settings::CLOUDFLARE_EMAIL, $domain)),
                'required' => true,
                'validator' => [
                    [
                        'EmailAddress',
                        true
                    ]
                ],
                'attribs' => [
                    'style' => 'width:320px',
                ]
            ]);
            $form->addElement('Text', Settings::CLOUDFLARE_API_KEY, [
                'label' => pm_Locale::lmsg('form.cloudflareApiKey'),
                'required' => true,
                'validator' => [
                    [
                        'NotEmpty',
                        true
                    ]
                ],
                'attribs' => [
                    'style' => 'width:320px',
                ]
            ]);
            $form->addControlButtons([
                'cancelLink' => pm_Context::getModulesListUrl(),
            ]);

            if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost()))
            {
                pm_Settings::setEncrypted(Settings::getDomainKey(Settings::CLOUDFLARE_EMAIL, $domain), $form->getValue(Settings::CLOUDFLARE_EMAIL));
                pm_Settings::setEncrypted(Settings::getDomainKey(Settings::CLOUDFLARE_API_KEY, $domain), $form->getValue(Settings::CLOUDFLARE_API_KEY));
                $this->_status->addMessage('info', pm_Locale::lmsg('message.apiSaved'));
                $this->_helper->json(['redirect' => pm_Context::getActionUrl('domain', 'records?site_id=' . $domain->getId())]);
            }

            $this->view->form = $form;
        }
        else
        {
            $this->_status->addMessage('error', $access);
        }
    }

    public function syncAllAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $siteID = $this->getRequest()->getParam("site_id");

        $access = Permissions::checkAccess($siteID);

        if ($access instanceof pm_Domain)
        {
            $domain = $access;

            $cloudflare = CloudflareAuth::login($domain);

            if ($cloudflare !== null)
            {
                $removeOld = pm_Settings::get(Settings::getDomainKey(Settings::CLOUDFLARE_REMOVE_UNUSED, $domain), true);

                $records = SyncRecord::getRecords($domain, $cloudflare, $removeOld);

                $successCount = 0;

                foreach ($records as $record)
                {
                    $successCount += $record->syncRecord() ? 1 : 0;
                }

                $this->_status->addMessage($successCount > 0 ? 'info' : 'warning', pm_Locale::lmsg('message.xRecordsUpdated', ['count' => $successCount]));
            }
        }

        $this->redirect('domain/records?site_id='.$siteID);

//        $this->_helper->json(['redirect' => pm_Context::getActionUrl('domain', 'records?site_id=' . $siteID)]);
    }

    private function _getRecordsList(pm_Domain $domain, $cloudflare)
    {
        $data = (new RecordList($domain, $cloudflare))->getList();
        $list = new pm_View_List_Simple($this->view, $this->_request);
        $list->setColumns([
//            pm_View_List_Simple::COLUMN_SELECTION,
            'col-host' => [
                'title' => pm_Locale::lmsg('table.host'),
                'noEscape' => true,
            ],
            'col-type' => [
                'title' => pm_Locale::lmsg('table.recordType'),
                'noEscape' => true,
            ],
            'col-status' => [
                'title' => pm_Locale::lmsg('table.status'),
                'noEscape' => true,
            ],
            'col-plesk' => [
                'title' => pm_Locale::lmsg('table.pleskValue'),
                'noEscape' => true,
            ],
            'col-cloudflare' => [
                'title' => pm_Locale::lmsg('table.cloudflareValue'),
                'noEscape' => true,
            ]
        ]);
        $list->setData($data);
        $list->setDataUrl(['action' => 'records-data?site_id=' . $domain->getId()]);
        return $list;
    }
}
