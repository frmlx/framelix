<?php

namespace Framelix\FramelixDocs\View;

use Framelix\Framelix\Config;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\HtmlUtils;

class Index extends \Framelix\Framelix\View
{
    protected string $pageTitle = 'Framelix - A rich featured, Docker ready, Full-Stack PHP Framework';
    protected string|bool $accessRole = "*";

    public function onRequest(): void
    {
        ?>
        <!DOCTYPE html>
        <html lang="en" class="landing-page">
        <head>
            <meta charset="UTF-8">
            <title><?= HtmlUtils::escape($this->pageTitle) ?></title>
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <?= HtmlUtils::getIncludeTagForUrl(
                Config::getCompilerFileBundle(
                    "FramelixDocs",
                    "scss",
                    "landing"
                )->getGeneratedBundleUrl()
            ); ?>
            <link rel="icon" href="<?= Url::getUrlToPublicFile(__DIR__ . "/../../../Framelix/public/img/logo.png") ?>">
        </head>
        <body>

        <div class="bg" data-id="logo">
            <img src="<?= Url::getUrlToPublicFile(__DIR__ . "/../../../Framelix/public/img/logo.svg") ?>" alt="Logo">
            <div class="color-dot"></div>
            <div class="color-dot"></div>
            <div class="color-dot"></div>
        </div>
        <div class="page">
            <div class="content-max-width">
                <nav>
                    <a href="/welcome" class="button" target="_blank"><span class="material-icons">terminal</span> Goto
                        Docs</a>
                    <a href="https://github.com/NullixAT/framelix" class="button" target="_blank"><span
                            class="material-icons">code</span> Source at GitHub</a>
                </nav>
            </div>
            <div class="content-max-width welcome">
                <h1>Framelix</h1>
                <h2>A rich featured<span class="variants">
                        <span>Full-Stack</span>
                        <span>Coder friendly</span>
                        <span>Powerful</span>
                        <span>Dockerized</span>
                        <span style="font-size: 0.7em; ">Auto-complete-full</span>
                        <span>Well documented</span>
                    </span><span style="color:var(--accent-light)">PHP</span> Framework</h2>

                <a href="/welcome" style="display: block; font-size:var(--font-size-big)" class="button"><span
                        class="material-icons">rocket</span> Dive in and get started now!</a>
            </div>
            <div class="content-max-width">
                <div class="glass">
                </div>
            </div>
        </div>

        <script>

          (async function () {
            function timer () {
              if (vIndex >= variants.length) vIndex = 0
              const variant = variants[vIndex]
              if (lastVariant && lastVariant !== variant) {
                lastVariant.style.display = 'none'
                lastVariant.classList.remove('float')
              } else {
                variant.style.display = 'inline-flex'
              }
              lastVariant = variant
              let text = variant.innerText.trim()
              if (!variant.originalText) {
                variant.originalText = text
              } else {
                text = variant.originalText
              }
              let chars
              let to = 30
              if (cIndex >= text.length) {
                chars = text
                to = 500
                if (cIndex - 5 >= text.length) {
                  variant.classList.add('float')
                  cIndex = 0
                  vIndex++
                  to = 700
                }
              } else {
                chars = text.substring(0, cIndex)
              }
              cIndex++
              variant.innerHTML = '&gt; ' + chars + ' &lt;'
              setTimeout(timer, to)
            }

            const variants = document.querySelectorAll('.variants span')
            let lastVariant = null
            let vIndex = 0
            let cIndex = 1

            timer()
          })()

          window._paq = window._paq || []
          _paq.push(['setRequestMethod', 'POST'])
          _paq.push(['disableCookies'])
          _paq.push(['trackPageView'])
          _paq.push(['enableLinkTracking']);
          (function () {
            let u = 'https://mtmo.0x.at/'
            _paq.push(['setTrackerUrl', u + 'welcome.php'])
            _paq.push(['setSiteId', '7'])
            let d = document, g = d.createElement('script'), s = d.getElementsByTagName('script')[0]
            g.type = 'text/javascript'
            g.async = true
            g.defer = true
            g.src = u + 'welcome.js'
            s.parentNode.insertBefore(g, s)
          })()
        </script>
        </body>
        </html>
        <?php
    }
}