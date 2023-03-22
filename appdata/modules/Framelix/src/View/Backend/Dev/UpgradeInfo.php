<?php

namespace Framelix\Framelix\View\Backend\Dev;

use Framelix\Framelix\Framelix;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\HtmlUtils;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\View\Backend\View;

use function file_exists;

class UpgradeInfo extends View
{
    protected string|bool $accessRole = "admin";

    public function onRequest(): void
    {
        $this->showContentBasedOnRequestType();
    }

    public function showContent(): void
    {
        if (!file_exists(Framelix::VERSION_FILE) || !file_exists(Framelix::VERSION_UPGRADE_FILE)) {
            Url::getApplicationUrl()->redirect();
        }
        $currentVersion = JsonUtils::readFromFile(Framelix::VERSION_FILE);
        $upgradeData = JsonUtils::readFromFile(Framelix::VERSION_UPGRADE_FILE);
        ?>
        <framelix-alert theme="success"><?= Lang::get(
                '__framelix_view_backend_dev_upgradeinfo_version__'
            ) ?> <a
                    href="https://hub.docker.com/r/<?= $currentVersion['dockerRepo'] ?>/tags?page=1&name=<?= $currentVersion['tag'] ?>"
                    target="_blank" rel="nofollow"><?= $currentVersion['dockerRepo'] ?>
                :<?= $currentVersion['tag'] ?></a></framelix-alert>
        <framelix-alert theme="primary"><?= HtmlUtils::escape(
                Lang::get(
                    '__framelix_view_backend_dev_upgradeinfo_info__'
                ),
                true
            ) ?> <a
                    href="https://hub.docker.com/r/<?= $upgradeData['dockerRepo'] ?>/tags?page=1&name=<?= $upgradeData['name'] ?>"
                    target="_blank" rel="nofollow"><?= $upgradeData['dockerRepo'] ?>:<?= $upgradeData['name'] ?></a>
        </framelix-alert>
        <framelix-alert style="font-family: monospace;">
            docker stop {CONTAINERNAME}<br/>
            docker rm {CONTAINERNAME}<br/>
            docker pull <?= $upgradeData['dockerRepo'] ?>:<?= $upgradeData['name'] ?><br/>
            # restart app container depending on your settings<br/>
            # here an example<br/>
            docker run -d -p {PORT}:443 \<br/>
            &nbsp;--name {CONTAINERNAME} \<br/>
            &nbsp;--restart=always \<br/>
            &nbsp;-v {DBDATA_VOLUMENAME}:/framelix/dbdata \<br/>
            &nbsp;-v {PATHTO_USERDATA_FOLDER}:/framelix/userdata \<br/>
            &nbsp;<?= $upgradeData['dockerRepo'] ?>:<?= $upgradeData['name'] ?>
        </framelix-alert>
        <?php
    }
}