<?php

// @formatter:off

namespace PHPSTORM_META {

/**
 * PhpStorm Meta file, to provide autocomplete information for PhpStorm
 */

registerArgumentsSet('all_table',
    {{table_list}}
);

expectedArguments(\pdoSelect(), 0, argumentsSet('all_table'));
expectedArguments(\pdoSelectOne(), 0, argumentsSet('all_table'));

}