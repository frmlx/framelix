<?php

namespace Framelix\Framelix\Html\TypeDefs;

class ModalShowOptions extends BaseTypeDef
{
    public function __construct(
        /**
         * The body content to render
         * @var string
         * @jstype string|Cash|FramelixRequest
         */
        public string $bodyContent,
        /**
         * The fixed header content which can be optional
         * @var string|null
         * @jstype string|Cash|FramelixRequest|null=
         */
        public ?string $headerContent = null,
        /**
         * The fixed footer content which can be optional
         * @var string|null
         * @jstype string|Cash|FramelixRequest|null=
         */
        public ?string $footerContent = null,
        /**
         * Max width of modal
         * @var string|int|null
         */
        public string|int|null $maxWidth = null,
        /**
         * The modal color, success, warning, error, primary
         * @var string|null
         */
        public string|int|null $color = null,
        /**
         * Reuse the given modal instance instead of creating a new
         * @var string|null
         * @jstype FramelixModal=
         */
        public mixed $instance = null,
        /**
         * Any data to pass to the instance for later reference
         * @var array|null
         * @jstype Object=
         */
        public ?array $data = null,
    ) {
    }
}