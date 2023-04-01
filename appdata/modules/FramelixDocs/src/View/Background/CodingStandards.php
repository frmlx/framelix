<?php

namespace Framelix\FramelixDocs\View\Background;

use Framelix\FramelixDocs\View\View;

class CodingStandards extends View
{
    protected string $pageTitle = 'The coding standards we follow';

    public function showContent(): void
    {
        ?>
        <p>
            We are working with strict coding standards and probably you should also when working with Framelix. It
            makes working together in modern times, where you do version control, so much easier.
        </p>
        <p>
            All the settings we use are default shipped with PHP Storm IDE.
        </p>
        <?= $this->getAnchoredTitle('php', 'PHP') ?>

        <p>We try to keep the code confirm with <?= $this->getLinkToExternalPage(
                'https://www.php-fig.org/psr/psr-1/',
                'PSR1'
            ) ?> and <?= $this->getLinkToExternalPage('https://www.php-fig.org/psr/psr-12/', 'PSR12') ?></p>

        <?= $this->getAnchoredTitle('javascript', 'Javascript') ?>
        <p> We try to keep the code confirm with <?= $this->getLinkToExternalPage(
                'https://standardjs.com/',
                'StandardJs'
            ) ?></p>

        <?= $this->getAnchoredTitle('css', 'CSS/SASS') ?>

        <p> We try to keep the code confirm with <?= $this->getLinkToExternalPage(
                'https://www.drupal.org/docs/develop/standards',
                'Drupal CSS'
            ) ?> Standards </p>
        <?php
    }
}