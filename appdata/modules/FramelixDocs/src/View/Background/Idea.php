<?php

namespace Framelix\FramelixDocs\View\Background;

use Framelix\FramelixDocs\View\View;

class Idea extends View
{
    protected string $pageTitle = 'Idea, motivation and philosophy of Framelix';

    public function showContent(): void
    {
        ?>
        <p>
            It is good to know, what the general idea of a project is, how it has been created and where it should go.

            So, myself, BrainFooLong, initial creator of Framelix, will try to explain you the details of this
            Framework.

            I've created Framelix as a successor of some other PHP Full-Stack frameworks, which never went public and
            are used in closed company apps. I have, as of 2022, more than 15 years experience in web development,
            heavily focused on PHP in the backend and started working when <code>Internet Explorer 6</code> was a hot
            thing and jQuery wasn't even in the making.

            Framelix was designed and created with 3 core principles in mind:

        <ul>
            <li>Focus on productivity apps but let room for flexibility - Framelix was designed to act as a backend
                application to provide productivity tools where data matters but it has already been used for completely
                different stuff like PageMyself
            </li>
            <li>Make it most intuitive for devs - As a dev, you want a framework that can be used without learning hours
                of hours how to use it. This means short but good code documentation in the files, perfect
                auto-completion support that eliminate the need for just knowing things and strings, intuitive naming
                stuff (One char aliases and variable names are a no-no here)
            </li>
            <li>Keep it fast and slim - Not pack it full of things that almost nobody ever need - The core isn't big and
                won't ever be big - This requires thinking about features twice - Better split into a separate module
                that don't clutter the core
            </li>
        </ul>


        I've seen and used many frameworks in my career and i picked ideas of many of them and tried to combine them in a new framework.
        </p>
        <?php
    }
}