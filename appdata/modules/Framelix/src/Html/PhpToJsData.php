<?php

namespace Framelix\Framelix\Html;

use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\Utils\RandomGenerator;
use JsonSerializable;

use function is_object;

class PhpToJsData implements JsonSerializable
{
    /**
     * Show a render html snippet to render the given js data
     * @param PhpToJsData $jsonData
     * @param HtmlAttributes|null $additionalContainerAttributes
     * @return void
     */
    public static function renderToHtml(
        PhpToJsData $jsonData,
        ?HtmlAttributes $additionalContainerAttributes = null
    ): void {
        $jsonData = JsonUtils::encode($jsonData);
        $randomId = RandomGenerator::getRandomHtmlId();
        ?>
        <div id="<?= $randomId ?>">
            <div class="framelix-loading"></div>
        </div>
        <script>
          (function () {
            const instance = FramelixObjectUtils.phpJsonToJs(<?=$jsonData?>)
            /** @type {FramelixHtmlAttributes|null} containerAttr */
            const containerAttr = FramelixObjectUtils.phpJsonToJs(<?=JsonUtils::encode(
                $additionalContainerAttributes
            )?>)
            instance.container.addClass('framelix-form-field-single')
            FramelixInit.initialized.then(function () {
              instance.render()
              $("#<?=$randomId?>").replaceWith(instance.container)
              if (containerAttr) containerAttr.assignToElement(instance.container)
            })
          })()
        </script>
        <?php
    }

    public function __construct(public array $properties, public object|string $phpClass, public string $jsClass)
    {
    }

    public function jsonSerialize(): array
    {
        return [
            'phpProperties' => $this->properties,
            'phpClass' => is_object($this->phpClass) ? get_class($this->phpClass) : $this->phpClass,
            'jsClass' => $this->jsClass
        ];
    }
}