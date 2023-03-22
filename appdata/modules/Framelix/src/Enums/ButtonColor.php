<?php

namespace Framelix\Framelix\Enums;

enum ButtonColor: string
{
    case PRIMARY = 'primary';
    case SUCCESS = 'success';
    case WARNING = 'warning';
    case ERROR = 'error';
    case LIGHT = 'light';
    case DEFAULT = 'default';
    case TRANSPARENT = 'transparent';
    case CUSTOM = 'custom';
}