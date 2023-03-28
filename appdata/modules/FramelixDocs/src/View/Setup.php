<?php

namespace Framelix\FramelixDocs\View;

use Framelix\Framelix\Framelix;

class Setup extends View
{
    protected string $pageTitle = 'Setup for module development';

    public function showContent(): void
    {
        ?>
        <p>
            Development in Framelix is basically split into <code>modules</code>. One application as just one module, by default.
            So, here we are showing you how you setup for your first application module in Framelix.
            Framelix has a docker image that is ready to kickstart and what contains everything you need to begin
            developing.
        </p>
        <?= $this->getAnchoredTitle('requirements', 'Requirements') ?>
        <p>
            You need <?= $this->getLinkToExternalPage('https://www.docker.com/', 'Docker installed') ?>.<br/>
            On Windows you need to run
            everything <?= $this->getLinkToExternalPage('https://ubuntu.com/tutorials/install-ubuntu-on-wsl2-on-windows-10#1-overview',
                'inside WSL') ?>. It is recommended to use the Ubuntu image for WSL with Docker Desktop installed.
        </p>
        <?= $this->getAnchoredTitle('setup', 'Setup') ?>
        <p>
            Create an empty folder somewhere and open a command line to it.<br/>
            Run the following commands. This will start the Framelix container and extract you the core and a
            starter module from the image, to provide you full autocompletion support and a minimal module to start
            with.
        </p>
        <blockquote>Notice: The extracted module <code>Framelix</code> inside <code>appdata</code> is basically
            "read-only". It is not mapped from host to container by default. If you want quick hack into the core, just
            map the <code>appdata/modules/Framelix</code> folder as well in the <code>docker run</code>. However, if you
            want help develop the core itself, head to this page.
        </blockquote>
        <?php
        $repoName = 'nullixat/framelix:' . Framelix::$version;
        $moduleName = 'FramelixStarter';
        $imageName = 'framelix_starter';
        $volumeName = $imageName . '_db';
        $port = 6456;
        echo $this->getCodeBlock('
        SCRIPTDIR=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" &>/dev/null && pwd)
        docker pull ' . $repoName . '
        mkdir -p $SCRIPTDIR/appdata/modules/Framelix $SCRIPTDIR/appdata/modules/FramelixStarter userdata
        echo -n "FRAMELIX_MODULES=' . $moduleName . ',1,' . $port . '" > $SCRIPTDIR/.env
        docker rm ' . $imageName . '
        docker create --name ' . $imageName . ' ' . $repoName . '
        docker cp ' . $imageName . ':/framelix/appdata/modules/Framelix $SCRIPTDIR/appdata/modules/
        docker cp ' . $imageName . ':/framelix/appdata/modules/FramelixStarter $SCRIPTDIR/appdata/modules/
        docker rm ' . $imageName . '
        docker run --name ' . $imageName . ' -d \
            --env-file $SCRIPTDIR/.env \
            -p "' . $port . ':' . $port . '" \
            -v $SCRIPTDIR/appdata/modules/' . $moduleName . ':/framelix/appdata/modules/' . $moduleName . ' \
            -v $SCRIPTDIR/userdata:/framelix/userdata \
            -v ' . $volumeName . ':/framelix/dbdata \
            ' . $repoName . '         
        echo "Now open https://127.0.0.1:' . $port . ' in your browser and follow setup in the web interface"
        ', "framelix-starter-install.sh");
        ?>

        <?= $this->getAnchoredTitle('recommendations', 'Recommendations') ?>
        <p>
            Our favorite IDE is PhpStorm and Framelix is basically developed only with this. It provides industry
            leading autocompletion and so many other features, which makes development so much faster and easier. We are
            not affiliated with this IDE or company, it's just our recommendation.
        </p>
        <?php
    }
}