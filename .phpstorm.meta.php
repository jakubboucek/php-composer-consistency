<?php

namespace PHPSTORM_META {

    expectedArguments(
        \JakubBoucek\ComposerConsistency\ComposerConsistency::__construct(),
        5,
        true,
        false,
        E_USER_ERROR,
        E_USER_WARNING,
        E_USER_NOTICE,
    );

    expectedArguments(
        \JakubBoucek\ComposerConsistency\Builder::errorMode(),
        0,
        E_USER_ERROR,
        E_USER_WARNING,
        E_USER_NOTICE,
    );
}