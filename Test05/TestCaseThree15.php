<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\helpers;

use yii\console\Markdown as ConsoleMarkdown;
use yii\base\Model;

class BaseConsole
{
    // foreground color control codes
    const FG_BLACK = 30;
    const FG_RED = 31;
    const FG_GREEN = 32;
    const FG_YELLOW = 33;
    const FG_BLUE = 34;
    const FG_PURPLE = 35;
    const FG_CYAN = 36;
    const FG_GREY = 37;
    // background color control codes
    const BG_BLACK = 40;
    const BG_RED = 41;
    const BG_GREEN = 42;
    const BG_YELLOW = 43;
    const BG_BLUE = 44;
    const BG_PURPLE = 45;
    const BG_CYAN = 46;
    const BG_GREY = 47;
    // fonts style control codes
    const RESET = 0;
    const NORMAL = 0;
    const BOLD = 1;
    const ITALIC = 3;
    const UNDERLINE = 4;
    const BLINK = 5;
    const NEGATIVE = 7;
    const CONCEALED = 8;
    const CROSSED_OUT = 9;
    const FRAMED = 51;
    const ENCIRCLED = 52;
    const OVERLINED = 53;


    
    public static function ansiToHtml($string, $styleMap = [])
    {
        $styleMap = [
            // http://www.w3.org/TR/CSS2/syndata.html#value-def-color
            self::FG_BLACK => ['color' => 'black'],
            self::FG_BLUE => ['color' => 'blue'],
            self::FG_CYAN => ['color' => 'aqua'],
            self::FG_GREEN => ['color' => 'lime'],
            self::FG_GREY => ['color' => 'silver'],
            // http://meyerweb.com/eric/thoughts/2014/06/19/rebeccapurple/
            // http://dev.w3.org/csswg/css-color/#valuedef-rebeccapurple
            self::FG_PURPLE => ['color' => 'rebeccapurple'],
            self::FG_RED => ['color' => 'red'],
            self::FG_YELLOW => ['color' => 'yellow'],
            self::BG_BLACK => ['background-color' => 'black'],
            self::BG_BLUE => ['background-color' => 'blue'],
            self::BG_CYAN => ['background-color' => 'aqua'],
            self::BG_GREEN => ['background-color' => 'lime'],
            self::BG_GREY => ['background-color' => 'silver'],
            self::BG_PURPLE => ['background-color' => 'rebeccapurple'],
            self::BG_RED => ['background-color' => 'red'],
            self::BG_YELLOW => ['background-color' => 'yellow'],
            self::BOLD => ['font-weight' => 'bold'],
            self::ITALIC => ['font-style' => 'italic'],
            self::UNDERLINE => ['text-decoration' => ['underline']],
            self::OVERLINED => ['text-decoration' => ['overline']],
            self::CROSSED_OUT => ['text-decoration' => ['line-through']],
            self::BLINK => ['text-decoration' => ['blink']],
            self::CONCEALED => ['visibility' => 'hidden'],
        ] + $styleMap;

        $tags = 0;
        $result = preg_replace_callback(
            '/\033\[([\d;]+)m/',
            function ($ansi) use (&$tags, $styleMap) {
                $style = [];
                $reset = false;
                $negative = false;
                foreach (explode(';', $ansi[1]) as $controlCode) {
                    if ($controlCode == 0) {
                        $style = [];
                        $reset = true;
                    } elseif ($controlCode == self::NEGATIVE) {
                        $negative = true;
                    } elseif (isset($styleMap[$controlCode])) {
                        $style[] = $styleMap[$controlCode];
                    }
                }

                $return = '';
                while ($reset && $tags > 0) {
                    $return .= '</span>';
                    $tags--;
                }

                $currentStyle = [];
                foreach ($style as $content) {
                    $currentStyle = ArrayHelper::merge($currentStyle, $content);
                }

                // if negative is set, invert background and foreground
                if ($negative) {
                    if (isset($bgColor)) {
                        $currentStyle['color'] = $bgColor;
                    }
                }

                $styleString = '';
                foreach ($currentStyle as $name => $value) {
                    if (is_array($value)) {
                        $value = implode(' ', $value);
                    }
                    $styleString .= "$name: $value;";
                }
                $tags++;
                return "$return<span style=\"$styleString\">";
            },
            $string
        );
        while ($tags > 0) {
            $result .= '</span>';
            $tags--;
        }

        return $result;
    }

    public static function markdownToAnsi($markdown)
    {
        $parser = new ConsoleMarkdown();
        return $parser->parse($markdown);
    }

    public static function renderColoredString($string, $colored = true)
    {
        // TODO rework/refactor according to https://github.com/yiisoft/yii2/issues/746
        static $conversions = [];

        if ($colored) {
            $string = str_replace('%%', '% ', $string);
            foreach ($conversions as $key => $value) {
                $string = str_replace(
                    $key,
                    static::ansiFormatCode($value),
                    $string
                );
            }
            $string = str_replace('% ', '%', $string);
        } else {
            $string = preg_replace('/%((%)|.)/', '$2', $string);
        }

        return $string;
    }
}
