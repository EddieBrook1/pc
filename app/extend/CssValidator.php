<?php
namespace app\extend;


// 验证css样式是否可用
class CssValidator {

    // 是否为px尺寸
    public static function isPxSize ($str) {

        preg_match('/^[-+]?[0-9]+([\.][0-9]+)*px$/', $str, $p);
        return !empty($p);
    }

    
    // 颜色是否可用
    public static function isColor ($color) {

        // 尝试匹配rgb格式
        preg_match('/^rgb\(\d{1,3},\s?\d{1,3},\s?\d{1,3}\)$/', $color, $p);
        if (!empty($p)) return true;

        // 尝试匹配rgba格式
        preg_match('/^rgba\(\d{1,3},\s*\d{1,3},\s*\d{1,3},\s*[0-9]+([\.][0-9]+)*\)$/', $color, $p);
        if (!empty($p)) return true;

        // 尝试匹配16进制
        preg_match('/^#[a-zA-Z0-9]{6}$/', $color, $p);
        if (!empty($p)) return true;

        return false;
    }


    // 是否为可用的url
    public static function isUrl ($str) {

        preg_match('/^url\(.*\)$/', $str, $p);
        return !empty($p);
    }

    
    // 宽度是否可用
    public static function isWidth ($str) {

        return self::isPxSize($str);
    }

    
    // 高度是否可用
    public static function isHeight ($str) {

        return self::isPxSize($str);
    }

    
    // top是否可用
    public static function isTop ($str) {

        return self::isPxSize($str);
    }

    
    // left是否可用
    public static function isLeft ($str) {

        return self::isPxSize($str);
    }


    // background-color 是否可用
    public static function isBackgroundColor ($str) {

        return self::isColor($str);
    }


    // background-image 是否可用
    public static function isBackgroundImage ($str) {

        if (self::isUrl($str) || $str === 'none') {
            
            return true;
        } else {

            return false;
        }
    }


    // backgorund-repeat 是否可用
    public static function isBackgroundRepeat ($str) {

        return in_array($str, [
            'repeat',
            'no-repeat',
        ]);
    }


    // text-align 是否可用
    public static function isTextAlign ($textAlign) {
        
        $accept = [
            'center',
            'end',
            'inherit',
            'initial',
            'justify',
            'left',
            'revert',
            'right',
            'start',
            'unset',
        ];

        return in_array($textAlign, $accept);
    }


    // font-weight 是否可用
    public static function isFontWeight ($fontWeight) {

        $accept = [100, 200, 300, 400, 500, 600, 700, 800, 900, 'bold', 'bolder', 'inherit', 'initial', 'lighter', 'normal', 'revert', 'unset'];
        return in_array($fontWeight, $accept);
    }


    // font-style 是否可用
    public static function isFontStyle ($fontStyle) {

        $accept = ['inherit', 'initial', 'italic', 'normal', 'oblique', 'revert', 'unset'];
        return in_array($fontStyle, $accept);
    }


    // writing-mode 是否可用
    public static function isWritingMode ($writingMode) {

        $accept = ['horizontal-tb', 'vertical-rl'];
        return in_array($writingMode, $accept);
    }
}