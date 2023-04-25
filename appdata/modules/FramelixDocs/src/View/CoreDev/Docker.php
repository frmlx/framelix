<?php

namespace Framelix\FramelixDocs\View\CoreDev;

use Framelix\FramelixDocs\View\View;

class Docker extends View
{
    protected string $pageTitle = 'Docker build development';

    public function showContent(): void
    {
        echo $this->getAnchoredTitle('welcome', 'Docker Image (nullixat/framelix)');
        ?>
        <p>
            Framelix has docker images on the Docker Hub. There are different production tags for major, minor and
            latest versions.
            And there is a special <code>dev</code> image, which is used only for tests on GitHub actions.
            The dev image contains more dependencies and stuff packed right in for unit and playwright tests, which are
            not required for any production builds.
        </p>
        <p>
            The image as based on Ubuntu and contains everything, starting from all services and the Framelix code
            itself. The main services integrated in the docker image are:
        </p>
        <ul>
            <li>Nginx - The webserver hosting the app and serving PHP and all other files</li>
            <li>NodeJS - The javascript backend engine for using Websockets, Compiler, and stuff like that</li>
            <li>PHP - The programming language Framelix is based on</li>
        </ul>
        <p>
            From time to time, the services are validated by hand for upgrades and security updates and if any update
            occurs, the image will be rebuilt and uploaded to docker.
        </p>
        <?php
        echo $this->getAnchoredTitle('build', 'Build and deploy');
        ?>
        <p>
            The images are designed to be built with our Github workflow that build, verify, test and publish the image
            to the docker hub. Only valid images are valid to be pushed to docker. This process is completely automated
            and is triggered manually.
        </p>
        <p>
            The script to build an image locally is <code>bash dev-scripts/build-image.sh</code>.
            It accepts a parameter to define which version you are building.
            If you do development, you go with the <code>dev</code> version.
        </p>
        <p>
            The script to push a container is <code>bash dev-scripts/docker-hub.sh</code>.
            It has various parameters which are shown when you call the script without any of that parameters.
            The docker hub push logic requires and forces a rebuild of the image locally, running all tests on that
            image and only if everything is successfull, pushes the image to docker.
        </p>
        <?php
    }
}