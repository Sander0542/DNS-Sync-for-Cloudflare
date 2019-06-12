<?php


use GuzzleHttp\Exception\ClientException;

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
                'action' => 'records?site_id='.$siteID,
            ],
            [
                'title' => pm_Locale::lmsg('tab.settings'),
                'action' => 'settings?site_id='.$siteID,
            ],
            [
                'title' => pm_Locale::lmsg('tab.api'),
                'action' => 'api?site_id='.$siteID,
            ],
        ];
    }

    public function recordsAction()
    {
        //Set the active tab
        $this->view->tabs[0]['active'] = true;

        $access = Modules_DnsSyncCloudflare_Util_Permissions::checkAccess($this->getRequest()->getParam("site_id"));

        if ($access instanceof pm_Domain)
        {
            $domain = $access;

            $this->view->pageTitle = pm_Locale::lmsg('title.dnsSyncFor', ['domain' => $domain->getName()]);

            $cloudflare = Modules_DnsSyncCloudflare_Cloudflare_Auth::login($domain);

            if ($cloudflare !== null)
            {
                $zone = $cloudflare->getZone($domain);

                if ($zone !== null)
                {

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

    public function apiAction()
    {
        //Set the active tab
        $this->view->tabs[2]['active'] = true;

        $access = Modules_DnsSyncCloudflare_Util_Permissions::checkAccess($this->getRequest()->getParam("site_id"));

        if ($access instanceof pm_Domain)
        {
            $domain = $access;

            // Create a new Form
            $form = new pm_Form_Simple();
            $form->addElement('Text', Modules_DnsSyncCloudflare_Util_Settings::CLOUDFLARE_EMAIL, array(
                'label' => pm_Locale::lmsg('form.cloudflareEmail'),
                'value' => pm_Settings::getDecrypted(Modules_DnsSyncCloudflare_Util_Settings::getUserKey(Modules_DnsSyncCloudflare_Util_Settings::CLOUDFLARE_EMAIL)),
                'required' => true,
                'validator' => array(
                    array('EmailAddress', true)
                )
            ));
            $form->addElement('Text', Modules_DnsSyncCloudflare_Util_Settings::CLOUDFLARE_API_KEY, array(
                'label' => pm_Locale::lmsg('form.cloudflareApiKey'),
                'value' => pm_Settings::getDecrypted(Modules_DnsSyncCloudflare_Util_Settings::getUserKey(Modules_DnsSyncCloudflare_Util_Settings::CLOUDFLARE_API_KEY)),
                'required' => true,
                'validator' => array(
                    array('NotEmpty', true)
                )
            ));
            $form->addControlButtons(array(
                'cancelLink' => pm_Context::getModulesListUrl(),
            ));

            if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
                pm_Settings::setEncrypted(Modules_DnsSyncCloudflare_Util_Settings::getUserKey(Modules_DnsSyncCloudflare_Util_Settings::CLOUDFLARE_EMAIL), $form->getValue(Modules_DnsSyncCloudflare_Util_Settings::CLOUDFLARE_EMAIL));
                pm_Settings::setEncrypted(Modules_DnsSyncCloudflare_Util_Settings::getUserKey(Modules_DnsSyncCloudflare_Util_Settings::CLOUDFLARE_API_KEY), $form->getValue(Modules_DnsSyncCloudflare_Util_Settings::CLOUDFLARE_API_KEY));
                $this->_status->addMessage('info', pm_Locale::lmsg('message.apiSaved'));
                $this->_helper->json(array('redirect' => pm_Context::getBaseUrl()));
            }

            $this->view->form = $form;
        }
        else
        {
            $this->_status->addMessage('error', $access);
        }
    }

}