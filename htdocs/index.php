<?php

pm_Context::init("dns-sync-cloudflare");

$application = new pm_Application();
$application->run();
