<?php

namespace Plugin\SliderPlugin42;

use Eccube\Common\EccubeNav;

class Nav implements EccubeNav
{
    /**
     * @return array
     */
    public static function getNav()
    {
        return [
            'content' => [
                'children' => [
                    'plugin_slider' => [
                        'name' => 'slider',
                        'url' => 'plugin_slider_list',
                    ],
                ],
            ],
        ];
    }
}
