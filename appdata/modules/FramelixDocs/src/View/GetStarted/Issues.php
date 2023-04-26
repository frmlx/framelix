<?php

namespace Framelix\FramelixDocs\View\GetStarted;

use Framelix\Framelix\Network\JsCall;
use Framelix\FramelixDocs\View\View;

class Issues extends View
{
    protected string $pageTitle = 'Questions / Issues?';

    public static function onJsCall(JsCall $jsCall): void
    {
        if ($jsCall->action === 'slack') {
            ?>
            <div>
                To join, please open this link<br/>
                <a href="https://join.slack.com/t/nullixat/shared_invite/zt-12elbg5rk-bZPR118cV1vzSw3pCWIUsw"
                   target="_blank">https://join.slack.com/t/nullixat/shared_invite/zt-12elbg5rk-bZPR118cV1vzSw3pCWIUsw</a>
            </div>
            <?php
        }
    }


    public function showContent(): void
    {
        ?>
        <p>
            We know that being stuck at any point can be quite frustrating. There are some communication channels that
            you can use to get in touch with the community.
        </p>
        <?= $this->getAnchoredTitle('github', 'Forums (Recommended)') ?>
        <p>
            <?= $this->getLinkToExternalPage(
                'https://github.com/NullixAT/framelix/discussions',
                'Github Discussions'
            ) ?>
            - There as a forums/discussion board where you can join.
        </p>
        <?= $this->getAnchoredTitle('slack', 'Slack') ?>
        <p>
            <img src="/slack-badge.svg" height="20" alt="Slack Members"><br/>
            We also have a Slack channel. Click the button bellow to join.
        </p>
        <framelix-button jscall-url="<?= JsCall::getUrl(__CLASS__, 'slack') ?>" icon="730" theme="primary"
                         target="modal">
            Join our Slack channel now
        </framelix-button>
        <?php
    }
}