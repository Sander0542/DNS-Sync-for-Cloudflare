<?php

/**
 * Created by Sander Jochems
 */

$messages = [
    'title' => 'DNS Sync for Cloudflare®',
    'description' => 'Sync the Plesk DNS to Cloudflare DNS',

    'title.dnsSyncFor' => 'DNS Sync for <b>%%domain%%</b>',

    'tab.records' => 'Records',
    'tab.api' => 'API',
    'tab.settings' => 'Settings',

    'form.cloudflareEmail' => 'Cloudflare Email',
    'form.cloudflareApiKey' => 'Cloudflare API Key',

    'form.trafficThruCloudflare' => 'Traffic Thru Cloudflare',
    'form.automaticSync' => 'Sync DNS automatic',
    'form.selectRecord' => 'Select the type of records you want to sync',
    'form.removeUnused' => 'Remove unused DNS records in Cloudflare',

    'table.domainName' => 'Domain Name',
    'table.cloudflareZoneID' => 'Cloudflare Zone ID',

    'table.host' => 'Record Name',
    'table.recordType' => 'Record Type',
    'table.status' => 'Status',
    'table.cloudflareValue' => 'Cloudflare Value',
    'table.pleskValue' => 'Plesk Value',

    'button.syncAll' => 'Sync All',
    'button.syncSelected' => 'Sync Selected',
    'button.save' => 'Save',

    'text.zoneIdNotFound' => 'Zone ID not found',
    'text.recordNotFound' => 'Record not found',

    'message.apiSaved' => 'API Settings were successfully saved.',
    'message.settingsSaved' => 'The Settings were successfully saved.',
    'message.noConnection' => 'Could not connect to Cloudflare.',
    'message.couldNotSync' => 'Could not sync the Plesk DNS zone to Cloudflare.',
    'message.noCloudflareZoneFound' => 'Could not find a Cloudflare zone for this domain.',
    'message.noAccessToDomain' => 'You do not have access to this domain.',
    'message.noAccessExtension' => 'You do not have access to DNS Sync for Cloudflare.',
    'message.noAccessSettings' => 'You do not have access to the domain settings.',
    'message.noDomainSelected' => 'There was no domain selected.',
    'message.noRecordsEdited' => 'No records created or updated.',
    'message.xRecordsUpdated' => '%%count%% record(s) created or updated.',
    'message.signedInAs' => 'Signed in as %%email%%.',

    'permission.cloudflare.title' => 'DNS Sync for Cloudflare',
    'permission.cloudflare.description' => 'Allow customers to use DNS Sync for Cloudflare',
    'permission.cloudflareSettings.title' => 'DNS Sync for Cloudflare Settings',
    'permission.cloudflareSettings.description' => 'Allow customers to change the settings of DNS Sync for Cloudflare',

    'task.sync.running' => 'Syncing Plesk DNS to Cloudflare DNS',
    'task.sync.done' => 'Synced Plesk DNS to Cloudflare DNSRecords Created: %%createdCount%%Records Updated: %%updatedCount%%Records Deleted: %%deletedCount%%',
];