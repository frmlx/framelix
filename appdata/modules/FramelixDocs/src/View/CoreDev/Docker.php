<?php

namespace Framelix\FramelixDocs\View\CoreDev;

use Framelix\FramelixDocs\View\View;

class Docker extends View
{
    protected string $pageTitle = 'Docker build development';

    public function showContent(): void
    {
        echo $this->getAnchoredTitle('welcome', 'Docker Base Image (nullixat/framelix_base)');
        ?>
        <p>
            Framelix has 2 docker images on the Docker Hub. They are split into server only services and Framelix
            specific app stuff and config.
            This way was choosen, as the base image does not change that often (Only when ubuntu, nginx, mariadb, nodejs
            or php updates) but is relatively huge in size (about 1G for the base image).<br/><br/>
            So, to not need to push a new huge image everytime some Framelix codes changes, this split strategy was
            choosen.
        </p>
        <p>
            The base image as based on Ubuntu and is only responsible for the server services like
        </p>
        <ul>
            <li>Nginx - The webserver hosting the app and serving PHP and all other files</li>
            <li>MariaDB (mysql) - The default database engine in Framelix</li>
            <li>NodeJS - The javascript backend engine for using Websockets, Compiler, and stuff like that</li>
            <li>PHP - The programming language Framelix is based on</li>
        </ul>
        <p>
            So, the base image only should be updated when any of this services have gotten updates. This process of
            checking and updating is done manually and from time to time or when security vulnerabilities have been
            discovered. It is not required, to update everytime a minor bugfix release is found in the services or OS,
            especially when the bugfix is in a part of the service that Framelix don't use.
        </p>
        <?php
        echo $this->getAnchoredTitle('welcome', 'Core Image (nullixat/framelix)');
        ?>
        <p>
            The core image, is the image that receives updates much more frequently, as it contains all configuration and source code of Framelix.
            It is relatively small in size
        </p>
        <?php
    }
}