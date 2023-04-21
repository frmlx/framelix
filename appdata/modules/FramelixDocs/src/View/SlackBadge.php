<?php

namespace Framelix\FramelixDocs\View;

use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Utils\Browser;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\FramelixDocs\Config;

use function file_exists;
use function filemtime;

use const FRAMELIX_TMP_FOLDER;

class SlackBadge extends \Framelix\Framelix\View
{
    protected string|bool $accessRole = "*";
    protected ?string $customUrl = '/slack-badge.svg';

    public function onRequest(): void
    {
        $cacheFile = FRAMELIX_TMP_FOLDER . "/slack-api-data.json";
        // update filedata each hour
        $nr = "-";
        if (Config::$slackApiToken) {
            if (!file_exists($cacheFile) || filemtime($cacheFile) < time() - 3600) {
                $browser = new Browser();
                $browser->url = 'https://slack.com/api/conversations.list';
                $browser->sendHeaders = ['Authorization: Bearer ' . Config::$slackApiToken];
                $browser->sendRequest();
                $jsonData = $browser->getResponseJson();
                if ($jsonData['ok'] ?? false) {
                    $nr = 0;
                    foreach ($jsonData['channels'] as $row) {
                        $nr += $row['num_members'];
                    }
                    JsonUtils::writeToFile($cacheFile, $nr);
                }
            } else {
                $nr = JsonUtils::readFromFile($cacheFile);
            }
        }
        Response::header('content-type: image/svg+xml');
        echo '<svg xmlns="http://www.w3.org/2000/svg" width="80" height="20"><rect rx="3" width="80" height="20" fill="#555"></rect><rect rx="3" x="47" width="33" height="20" fill="#ff0044"></rect><path d="M47 0h4v20h-4z" fill="#ff0044"></path><g text-anchor="middle" font-family="Verdana" font-size="11"><text fill="#010101" fill-opacity=".3" x="24" y="15">slack</text><text fill="#fff" x="24" y="14">slack</text><text fill="#010101" fill-opacity=".3" x="64" y="15">' . $nr . '</text><text fill="#fff" x="64" y="14">' . $nr . '</text></g></svg>';
    }
}