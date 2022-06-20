<?php
namespace app\extend\tb;


class FormatEditorDataByPics {

    private function pxToNum ($str) {

        return trim($str, 'px') * 1;
    }
    

    public function handle ($editor_data, $pics_merged) {

        // 生成一个数组, 数组每一项是模块相对于顶部的位置信息
        $result_data = [];
        foreach ($pics_merged as $item) {

            if (count($result_data) == 0) {

                $top = 0;
            } else {

                $last_item = $result_data[count($result_data) - 1];
                $top = $last_item['bottom'];
            }

            array_push($result_data, [
                'top' => $top,
                'bottom' => $top + $item['height'],
                'hotspot' => [],
            ]);
        }

        // 生成一个热区数组, 数组每一项是热区相对于顶部的位置信息
        $hotspot_data = [];
        $top_base = 0;      // 当前模块的top相对定位值
        foreach ($editor_data as $mk_index => $mk) {

            foreach ($mk['groups'] as $component) {

                // 不是热区跳过
                if ($component['type'] !== 'hotspot') continue;

                $boxStyle_now = $component['boxStyle'];
                $hotspot_now = [
                    'width'     => $this->pxToNum($boxStyle_now['width']),
                    'height'    => $this->pxToNum($boxStyle_now['height']),
                    'left'      => $this->pxToNum($boxStyle_now['left']),
                    'top'       => $this->pxToNum($boxStyle_now['top']) + $top_base,
                ];
                $hotspot_now['bottom'] = $hotspot_now['top'] + $hotspot_now['height'];

                array_push($hotspot_data, $hotspot_now);
            }

            $top_base += $this->pxToNum($mk['boxStyle']['height']);
        }


        // 碰撞检测
        /* 
            热区交错的三种情况
            1. Atop <= Btop && (Abottom < Bbottom && Abottom > Btop)
            2. (Atop >= Btop && Atop < Bbottom) && (Abottom <= Bbottom && Abottom > Btop)
            3. (Atop > Btop && Atop < Bbottom) && Abottom >= Bbottom
        */
        foreach ($result_data as $B) {

            $B_top = $B['top'];
            $B_bottom = $B['bottom'];

            foreach ($hotspot_data as $A) {

                $A_top = $A['top'];
                $A_bottom = $A['bottom'];
                
                // 第一种情况
                if ($A_top <= $B_top && ($A_bottom < $B_bottom && $A_bottom > $B_top)) {

                    array_push($aa, $A);
                }

                // 第二种情况
                if (($A_top >= $B_top && $A_top < $B_bottom) && ($A_bottom <= $B_bottom && $A_bottom > $B_top)) {

                    array_push($bb, $A);
                }

                // 第三种情况
                if (($A_top > $B_top && $A_top < $B_bottom) && $A_bottom >= $B_bottom) {

                    array_push($cc, $A);
                }
            }
        }

        dump($aa);
        dump($bb);
        dump($cc);
    }
}