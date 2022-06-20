<?php

use think\facade\Route;

// 测试接口
Route::group('/test', function () {

    Route::get('/index1', 'Test/index1');
    Route::get('/test7', 'Test/test7');
    Route::get('/test8', 'Test/test8');
    Route::get('/test9', 'Test/test9');

    Route::get('/test10', 'Test/test10');
    Route::get('/test11', 'Test/test11');
    Route::get('/test12', 'Test/test12');
    Route::get('/test13', 'Test/test13');

    Route::get('/aaa/test11', 'Test/test11');


    // 测试接口
    Route::group('/api/user', function () {

        // 获取用户列表
        Route::get('/list', 'TestUser/list');

        // 修改用户姓名
        Route::post('/update', 'TestUser/update');

        // 删除用户
        Route::post('/del', 'TestUser/del');

        // 新增用户
        Route::post('/add', 'TestUser/add');
    });

})->middleware(\app\middleware\AllowOrigin::class);

// 公共接口
Route::group('/common', function () {

    // 抠图接口
    Route::group('/koutu', function () {

        // 下载抠图结果
        Route::get('/download', 'common.Koutu/downloadAfterImg');
        
        // 上传一张图片然后抠图
        Route::post('/upload', 'common.Koutu/upload');
    })->middleware(\app\middleware\CheckLogin::class);


    // 刷新cookie专用接口
    Route::group('/refresh_cookie', function () {

        // 刷新淘宝开发者平台的cookie
        Route::get('/tb_isv', 'RefreshCookie/tbIsv');

    });


    // 上传相关
    Route::group('/upload', function () {

        // 文件流上传
        Route::post('/file', 'common.Upload/uploadFile');

    });

})->middleware(\app\middleware\AllowOrigin::class);


// 后台接口
Route::group('/admin', function () {

    Route::get('/', 'Admin/index');

    // 后台设置
    Route::group('/setting', function () {

        // 更新淘宝开发者的cookie
        Route::put('tb_cookie_isv', 'admin.Setting/tbCookieIsv');
        
        // 更新淘宝商家的cookie, 该cookie为内部使用, 用于获取旺旺名、uid、商品列表
        Route::put('tb_cookie_seller', 'admin.Setting/tbCookieSeller');
        
        // 清0 wangwang_by_token_queryed 字段
        Route::get('reset_wangwang_queryed', 'admin.Setting/resetWangwangQueryed');
    });
});


// 千绘-淘宝
Route::group('/tb', function () {

    // 需要登录的接口
    Route::group('/', function () {

        // 详情
        Route::group('/xiangqing', function () {

            // 同步相关
            Route::group('/sync', function () {

                // 同步电脑端
                Route::post('/pc', 'tb.xiangqing.Sync/pc');

                // 同步手机端
                Route::post('/phone', 'tb.xiangqing.Sync/phone');

                // 同步两端
                Route::post('/both', 'tb.xiangqing.Sync/both');

                // 同步暂停
                Route::post('/pause', 'tb.xiangqing.Sync/pause');

                // 同步开始、继续
                Route::post('/start', 'tb.xiangqing.Sync/start');

                // 获取任务状态
                Route::get('/main_job_status', 'tb.xiangqing.Sync/mainJobStatus');

                // 获取一段时间内同步的所有任务
                Route::get('/history', 'tb.xiangqing.Sync/history');

                // 获取同步任务的预处理数据
                Route::get('/get_prepare_data', 'tb.xiangqing.Sync/getPrepareData');

                // 更改主任务状态
                Route::post('/set_main_job_status', 'tb.xiangqing.Sync/setMainJobStatus');
            });


            // 获取某商品的模版数据
            Route::get('/custom_module', 'tb.xiangqing.Index/customModule');


            // 根据 用户id + 模版id + 商品id 保存一份数据
            Route::post('/save_by_goods', 'tb.xiangqing.Index/saveByGoods');

            
            // 根据 用户id + 模版id 保存一份数据
            Route::post('/save_by_template', 'tb.xiangqing.Index/saveByTemplate');


            // 获取用户对该模版的权限
            Route::get('/template_auth', 'tb.xiangqing.Index/templateAuth');


            // 判断模版是否存在
            Route::get('/is_template_exist', 'tb.xiangqing.Index/isTempalteExist');


            // 获取线上数据
            Route::get('/online_imgs', 'tb.xiangqing.Index/onlineImgs');

        });


        // 千绘-淘宝-用户
        Route::group('/user', function () {            

            // 判断用户cookie是否可用
            Route::get('/check_tb_auth', 'tb.User/checkTbAuth');
            
        });


        // 图片空间
        Route::group('/sucai', function () {

            // 文件夹树
            Route::get('/dir_tree', 'tb.Sucai/dirTree');

            // 获取当前文件夹下的图片
            Route::get('/list', 'tb.Sucai/list');

            // 获取当前文件夹下的图片
            Route::get('/list_v2', 'tb.Sucai/listV2');

            // 上传图片
            Route::post('/upload', 'tb.Sucai/upload');

            // 新建文件夹
            Route::post('/add_dir', 'tb.Sucai/addDir');
        });


        // 视频空间
        Route::group('/video', function () {

            // 列表
            Route::get('/list', 'tb.VideoSelector/list');

            // 列表2
            Route::get('/list_v2', 'tb.VideoSelector/listV2');
        });


        // 商品相关
        Route::group('/goods', function () {

            // 获取宝贝链接
            Route::get('/list', 'tb.Goods/list');
        });


        // 链接选择器
        Route::group('/link_selector', function () {

            // 获取常用链接
            Route::get('/common_link', 'tb.LinkSelector/commonLink');

            // 获取宝贝分类链接
            Route::get('/category_link', 'tb.LinkSelector/categoryLink');

            // 获取优惠券链接
            Route::get('/Coupon_link', 'tb.LinkSelector/couponLink');
        });

    })->middleware(\app\middleware\CheckLogin::class);


    // 获取模版的原始数据
    Route::get('/xiangqing/designer_module', 'tb.xiangqing.Index/designerModule');


    // 登录相关
    Route::group('/login', function () {

        // 验证token方式进行登录
        Route::get('/by_token', 'tb.Login/byToken');

    });

    // 更新客户cookie
    Route::post('/user/update_cookie', 'tb.User/updateCookie');

    // 获取某商品的模版数据, 内部使用
    Route::get('/xiangqing/custom_module_self', 'tb.xiangqing.Index/customModuleSelf')
        ->middleware(\app\middleware\OnlyInsideIp::class);

})->middleware(\app\middleware\AllowOrigin::class);


// 千绘-抖音
Route::group('/dy', function () {});


// 千绘-拼多多
Route::group('/pdd', function () {});


// 微信小程序
Route::group('/wxapp', function () {

    Route::get('/open_id', 'wxapp.Index/openId');

});