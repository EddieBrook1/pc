<?php
namespace app\controller;

use think\Request;

use app\extend\DoCurl;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request as GuzzRequest;
use think\facade\Queue;
use app\job\Job1;
use think\facade\Cache;
use app\qianhui\Testaaa;
use think\facade\Session;
use app\extend\tb\TbGetFolder;
use app\extend\tb\TbCommitItemTemplate;
use app\extend\Setting;
use app\extend\Util;
use app\BaseController;
use app\model\SyncMainJobModel;
use app\http\worker\MessageValidator;
use app\http\worker\LoginHandle;
use app\extend\tb\FormatEditorDataByPics;
use app\extend\tb\TbMediaUpload;
use app\model\OldUserModel;
use app\http\ErrorWrap;
use think\facade\Db;
use app\http\worker\listeners\SyncMainJobListener;
use app\extend\tb\TbSucaiFiles;
use app\extend\tb\TbWangpuToWenBen;
use app\model\OldDetailTemplateModel;
use app\job\SyncUploadPicJob;
use app\controller\tb\xiangqing\XiangqingAuthValidator;

class Test extends BaseController {

    // 已被使用的模块id
    private $groupid_been_used = [];

    // 已被使用的组件id
    private $componentid_been_used = [];


    public function test () {

        file_get_contents('aabvcd');

        exit;
        $url = 'http://viapi-cn-shanghai-dha-parser.oss-cn-shanghai.aliyuncs.com/upload/result_commoditysegmenter/2021-9-28/invi_commoditysegmenter_016328012243351109543_s8TSTn.png?Expires=1632803024&OSSAccessKeyId=LTAI4FoLmvQ9urWXgSRpDvh1&Signature=n31WOhFMSVQCvDnGr4%2BWPSBmp5U%3D';

        halt(getimagesize($url));
        // $data = file_get_contents($url);
        


        exit;
        $url = 'http://open-api.pinduoduo.com/oauth/token';
        
        $doCurl = new DoCurl;
        $res = $doCurl->post($url, [
            'client_id' => '8e0c16025ae54b36ae79dc683ae8e257',
            'grant_type' => 'refresh_token',
            'refresh_token' => '7ed995e53ae3484fb6461ffc0d76a9883b0c31c1',
            'client_secret' => '716d8abd57c4d037a7beb08db2f66882c5328e0d'
        ], true);
        halt($res);

        exit;
        $sdk = new PddSdk([
            'api_base_url' => config('pdd.api_base_url'),
            'data_type' => 'JSON',
            'client_id' => config('pdd.sj_client_id'),
            'secret_key' => config('pdd.sj_secret_key'),
        ]);

        $img = '/www/wwwroot/test.qianzhiyu.net/runtime/storage/upload/test233.jpg';
        $img_base64 = Util::base64EncodeImage($img);

        $data = [
            'image' => $img_base64
        ];
        $res = $sdk->exec(
            '1a20dceffdd246f4bb054d2cf557e8ce1b8569b9',
            'pdd.goods.image.upload',
            $data
        );

        var_dump($res);
    }


    public function test5 (Request $request) {


        // $url = 'http://test.qianzhiyu.net/test6';
        $url = 'https://xiangqing.wangpu.taobao.com/mygroup/ajax/get_group.do?_input_charset=utf-8&groupId=&isBatch=true';
        // $cookie = 'UM_distinctid=17a1ec168e8a8-04d761c94f647d-66717c1d-1fa400-17a1ec168e9317; thw=cn; t=fc2cccc73d90c619bd9c3c93410f347f; x=e%3D1%26p%3D*%26s%3D0%26c%3D0%26f%3D0%26g%3D0%26t%3D0; enc=LIpcF4LsunooW1jKHYQB0uNYjDlpBgbOlk%2F4n4wc9%2FP52vOkFXZIaR%2FeVxG3S13Z%2F%2F3m9duvRK9vNA%2BNaUNmoQ%3D%3D; _samesite_flag_=true; cookie2=1020307480864a384520905376140a2c; _tb_token_=34eb18e63eee; cancelledSubSites=empty; _page_id=1302436130; xlly_s=1; sgcookie=E100HxTT0X1GTzztheHkvva3bX31vyH%2BInzSByMQ2IBkSIUjmj6T1J8H9fy92VI69UBrh4YMD%2BknCZv6H4p%2FItSyPA%3D%3D; unb=2207945695967; sn=%E6%B7%B1%E5%9C%B3%E5%8D%83%E7%9F%A5%E9%B1%BC%E8%AE%BE%E8%AE%A1%E6%9C%89%E9%99%90%E5%85%AC%E5%8F%B8%3A%E8%AE%BE%E8%AE%A1%E5%B8%88%E8%80%81%E8%92%8B; csg=e649bc3d; skt=f1971ed1bf38419f; _cc_=U%2BGCWk%2F7og%3D%3D; cna=WphLGDWp014CAXjlFklYWY7n; _m_h5_tk=a31a252e32e42dd5ec3e397d95e68b37_1633606598033; _m_h5_tk_enc=184a66430e1669e575cd789e06255ae9; uc1=cookie21=VFC%2FuZ9aj3yE&cookie14=Uoe3dPhHfW%2FuqQ%3D%3D; isg=BPf3mngGWpAuyN4ff8mjy9b9iOtBvMseMf5HDEmkXkYt-Bc6UYzXbpaZ2lqmC6OW; l=eBTkZJA4guyjirA-BOfZlurza77OtBdYYuPzaNbMiOCPObCB5WlfW6eeV_T6CnGAh6jJR3-1ZcbbBeYBq_C-nxvONovPILkmn; tfstk=cxTdB-gmUADnjlf9gHniNcbrP_kcaRMRCW6uyfIV9Dd0fceQpsmXoEhoaD174EGO.';
        // $cookie = 'UM_distinctid=17a1ec168e8a8-04d761c94f647d-66717c1d-1fa400-17a1ec168e9317; thw=cn; t=fc2cccc73d90c619bd9c3c93410f347f; x=e%3D1%26p%3D*%26s%3D0%26c%3D0%26f%3D0%26g%3D0%26t%3D0; enc=LIpcF4LsunooW1jKHYQB0uNYjDlpBgbOlk%2F4n4wc9%2FP52vOkFXZIaR%2FeVxG3S13Z%2F%2F3m9duvRK9vNA%2BNaUNmoQ%3D%3D; _samesite_flag_=true; cookie2=1020307480864a384520905376140a2c; _tb_token_=34eb18e63eee; cancelledSubSites=empty; _page_id=1302436130; xlly_s=1; sgcookie=E100HxTT0X1GTzztheHkvva3bX31vyH%2BInzSByMQ2IBkSIUjmj6T1J8H9fy92VI69UBrh4YMD%2BknCZv6H4p%2FItSyPA%3D%3D; unb=2207945695967; sn=%E6%B7%B1%E5%9C%B3%E5%8D%83%E7%9F%A5%E9%B1%BC%E8%AE%BE%E8%AE%A1%E6%9C%89%E9%99%90%E5%85%AC%E5%8F%B8%3A%E8%AE%BE%E8%AE%A1%E5%B8%88%E8%80%81%E8%92%8B; csg=e649bc3d; skt=f1971ed1bf38419f; _cc_=U%2BGCWk%2F7og%3D%3D; cna=WphLGDWp014CAXjlFklYWY7n; _m_h5_tk=a31a252e32e42dd5ec3e397d95e68b37_1633606598033; _m_h5_tk_enc=184a66430e1669e575cd789e06255ae9; uc1=cookie14=Uoe3dPhHfWjFgw%3D%3D&cookie21=W5iHLLyFfoaZ; isg=BPf3mngGWpAuyN4ff8mjy9b9iOtBvMseMf5HDEmkXkYt-Bc6UYzXbpaZ2lqmC6OW; l=eBTkZJA4guyjirA-BOfZlurza77OtBdYYuPzaNbMiOCPObCB5WlfW6eeV_T6CnGAh6jJR3-1ZcbbBeYBq_C-nxvONovPILkmn; tfstk=cxTdB-gmUADnjlf9gHniNcbrP_kcaRMRCW6uyfIV9Dd0fceQpsmXoEhoaD174EGO.';
        // $cookie = 't=b2996a23d0789ebd901774c10cf4f913; UM_distinctid=17b20453fd875c-0d454a2ea8890b-63755573-1fa400-17b20453fd94c8; thw=cn; _bl_uid=jdk8vs38ueh2R3rkq2v6ee80e262; enc=cDPShT7jFyzAlJ1i7MLjfpIDh5%2BoI60XbOfYZBzNf19o9ypt9nmDU8sqb4Knh%2Fh0DFj9RMeo2ph%2FWtHwV4cSYg%3D%3D; cna=Sl6JGTCrpAMCAXjlFnpLSK+W; cookie2=1a7ccd4d4ebdca18bb552bdea51ca92c; _tb_token_=5b957b4b37e43; xlly_s=1; _samesite_flag_=true; lgc=acado; cancelledSubSites=empty; dnk=acado; publishItemObj=Ng%3D%3D; tracknick=acado; sgcookie=E100TXdX5tUCpxWiPsfFSvx6RW8sdNFPyk18hc%2BryXASPxlJCgcFjrdIsCQ3%2B2CydiHsdfEyqRArxU9q08KSjdP%2FO11uZkDlFKG%2BkXwwUaarIF8%3D; unb=446034741; uc3=vt3=F8dCujXFjUwQOSJ2DSg%3D&lg2=U%2BGCWk%2F75gdr5Q%3D%3D&id2=Vyh60T3e01PC&nk2=AnIuKtE%3D; csg=c9c47b69; cookie17=Vyh60T3e01PC; skt=1833a7cdeeecfda4; existShop=MTYzMzU5Nzc5NQ%3D%3D; uc4=id4=0%40VX9LGI4smVZv%2FI%2BckIDXuBpmX4s%3D&nk4=0%40AJljbwTr%2FIoaiwnSQx7Naw%3D%3D; _cc_=VT5L2FSpdA%3D%3D; _l_g_=Ug%3D%3D; sg=o14; _nk_=acado; cookie1=AiAzH3u3xhNLP%2FB5ofrd8TV4rZZbDt7oISOWMBSI9XA%3D; uc1=cookie16=WqG3DMC9UpAPBHGz5QBErFxlCA%3D%3D&pas=0&cookie15=UIHiLt3xD8xYTw%3D%3D&cookie14=Uoe3dPhHcm%2BLow%3D%3D&existShop=true&cookie21=VFC%2FuZ9ajCbF99I1uFxrPQ%3D%3D; isg=BMfHKwrEagBc1O7hpzHIDCDHVnuRzJuucAJOlZm0mtZ9CObKoZ5x_yuJqshW4HMm; l=eBOMTrMHg_7KTZrpBOfwlurza77tkIRfguPzaNbMiOCPOTfp52vhW6eeq1T9CnGVns_2R38TQoJTB0T_6y4e7xv9-eMvBfTdCdTh.; tfstk=cJ0dBOjzJdvHZNNtgDKMNh4JeuAGZO98C6wlyCE4TBgH_l7RiYumke9eA7w4pCC..';
        // $cookie = 't=b2996a23d0789ebd901774c10cf4f913; UM_distinctid=17b20453fd875c-0d454a2ea8890b-63755573-1fa400-17b20453fd94c8; thw=cn; _bl_uid=jdk8vs38ueh2R3rkq2v6ee80e262; enc=cDPShT7jFyzAlJ1i7MLjfpIDh5%2BoI60XbOfYZBzNf19o9ypt9nmDU8sqb4Knh%2Fh0DFj9RMeo2ph%2FWtHwV4cSYg%3D%3D; cna=Sl6JGTCrpAMCAXjlFnpLSK+W; cookie2=1a7ccd4d4ebdca18bb552bdea51ca92c; _tb_token_=5b957b4b37e43; xlly_s=1; _samesite_flag_=true; lgc=acado; cancelledSubSites=empty; dnk=acado; publishItemObj=Ng%3D%3D; tracknick=acado; sgcookie=E100TXdX5tUCpxWiPsfFSvx6RW8sdNFPyk18hc%2BryXASPxlJCgcFjrdIsCQ3%2B2CydiHsdfEyqRArxU9q08KSjdP%2FO11uZkDlFKG%2BkXwwUaarIF8%3D; unb=446034741; uc3=vt3=F8dCujXFjUwQOSJ2DSg%3D&lg2=U%2BGCWk%2F75gdr5Q%3D%3D&id2=Vyh60T3e01PC&nk2=AnIuKtE%3D; csg=c9c47b69; cookie17=Vyh60T3e01PC; skt=1833a7cdeeecfda4; existShop=MTYzMzU5Nzc5NQ%3D%3D; uc4=id4=0%40VX9LGI4smVZv%2FI%2BckIDXuBpmX4s%3D&nk4=0%40AJljbwTr%2FIoaiwnSQx7Naw%3D%3D; _cc_=VT5L2FSpdA%3D%3D; _l_g_=Ug%3D%3D; sg=o14; _nk_=acado; cookie1=AiAzH3u3xhNLP%2FB5ofrd8TV4rZZbDt7oISOWMBSI9XA%3D; uc1=cookie14=Uoe3dPhHcm5DRg%3D%3D&pas=0&cookie21=URm48syIZJfmYzXkpCGNJg%3D%3D&cookie16=UtASsssmPlP%2Ff1IHDsDaPRu%2BPw%3D%3D&cookie15=UIHiLt3xD8xYTw%3D%3D&existShop=true; isg=BMfHKwrEagBc1O7hpzHIDCDHVnuRzJuucAJOlZm0mtZ9CObKoZ5x_yuJqshW4HMm; l=eBOMTrMHg_7KTZrpBOfwlurza77tkIRfguPzaNbMiOCPOTfp52vhW6eeq1T9CnGVns_2R38TQoJTB0T_6y4e7xv9-eMvBfTdCdTh.; tfstk=cJ0dBOjzJdvHZNNtgDKMNh4JeuAGZO98C6wlyCE4TBgH_l7RiYumke9eA7w4pCC..';
        
        $cookie = 'isg=BA4O1tgx4yagllcT5RrsGDtKXeLQj9KJXerHcjhXMJHMm671oBmRmbFS00f3mMqh; l=eBQ0emtegsKXn9FTBOfalurza77TKIdYYuPzaNbMiOCP_l1e5mRNW6eHjdLwCn1VhswHR3R8R-uYBeYBcBdKnxvTpqSKGqkmn; tfstk=c0ncBicqH71BtDYzvnZjF4fBcWdRZjTUTlzi4DmFU5_mp2gPiqHPWkKdEJ_NiN1..; uc1=cookie21=VT5L2FSpdiBh&cookie14=Uoe3dPup0AzoWw%3D%3D; _utk=VocP@qJyn^AtWdm; cna=LLvkGaNqokICAbcNaqn/W6cH; v=0; _cc_=VFC%2FuZ9ajQ%3D%3D; _tb_token_=4o9GMRVabFSzU; cancelledSubSites=empty; cookie2=19e42f50d2ea2905de223768e2b7ef04; csg=ae612041; sgcookie=E100paOOVH5XyBzg2fiuRwaNweZcB0wpGVB%2F%2BDprfjWLVJF8vw5GTwC4xYMdnhnugcv6JiGMJuW6BetHLPLgyvB%2Bjg%3D%3D; skt=ac6c611d8b79339d; sn=%E6%B7%B1%E5%9C%B3%E5%8D%83%E7%9F%A5%E9%B1%BC%E8%AE%BE%E8%AE%A1%E6%9C%89%E9%99%90%E5%85%AC%E5%8F%B8%3A%E8%AE%BE%E8%AE%A1%E5%B8%88%E8%80%81%E8%92%8B; t=2661dffc35771c60509bc331719ef486; unb=2207945695967; thw=cn; enc=KZ5vK9ZZap%2FDgSmz0v9Qla5ijD6vrBIxCSYDRyNtVFVn4QSHI12NyHUCKpkNkGCQEoRuhmJjNdoSt5SW6dAsEw%3D%3D; _m_h5_tk=61631e81201df66bea24d4f84b41d950_1633633221876; _m_h5_tk_enc=d6c808bc8e587e040eca7309add8e31a; xlly_s=1; UM_distinctid=17c5359e534456-04a4c9ce5429678-3f62694b-2b1100-17c5359e5351905; _uab_collina=163318381289642953023495; _samesite_flag_=true';
        $doCurl = new DoCurl([
            'cookie' => $cookie,
            'print_header' => true
        ]);

        $html = $doCurl->get($url);

        $explode = explode("\r\n\r\n\r\n", $html);

        halt($explode);
        // halt($html);
        exit;
        $url = 'https://item.taobao.com/item.htm?spm=a230r.1.14.142.525e4f393eQOkT&id=627336894125&ns=1&abbucket=6#detail';

        $taobaoGoodsData = new TaobaoGoodsData;
        $taobaoGoodsData->handle($url);

        exit;
        $url = 'https://console.open.taobao.com/handler/sop/startPcProcInst.json';
        $cookie = 'UM_distinctid=17a1ec168e8a8-04d761c94f647d-66717c1d-1fa400-17a1ec168e9317; thw=cn; _uab_collina=162401434581850108500165; _umdata=G306857F4641DB8D49E0308667D590E0495E842; t=fc2cccc73d90c619bd9c3c93410f347f; x=e%3D1%26p%3D*%26s%3D0%26c%3D0%26f%3D0%26g%3D0%26t%3D0; enc=LIpcF4LsunooW1jKHYQB0uNYjDlpBgbOlk%2F4n4wc9%2FP52vOkFXZIaR%2FeVxG3S13Z%2F%2F3m9duvRK9vNA%2BNaUNmoQ%3D%3D; originUrl=/; xlly_s=1; cookie2=1101d9f2b2f4538d5b9eeedc28d2f7e4; _tb_token_=f4e03a5353ee5; _samesite_flag_=true; sgcookie=E100mA0ZhBKHeSjEIEOu3NyLQnLjjSgbNRc2lzlD8A6qdNNYN6GIpjyXWAnRQlmt9oWyXrXf4iRCHYREG%2FInWZ3HtQ%3D%3D; unb=2207945695967; sn=%E6%B7%B1%E5%9C%B3%E5%8D%83%E7%9F%A5%E9%B1%BC%E8%AE%BE%E8%AE%A1%E6%9C%89%E9%99%90%E5%85%AC%E5%8F%B8%3A%E8%AE%BE%E8%AE%A1%E5%B8%88%E8%80%81%E8%92%8B; csg=de97110a; cancelledSubSites=empty; skt=dffa579fba0b6ccd; _cc_=VT5L2FSpdA%3D%3D; cna=WphLGDWp014CAXjlFklYWY7n; _m_h5_tk=9b5be0d731876721d5b8fbf346b988b2_1633510798652; _m_h5_tk_enc=a84e2706104d6d5864245208ffbcd3a1; uc1=cookie21=WqG3DMC9Eman&cookie14=Uoe3dPhOr1oXEQ%3D%3D; _bl_uid=8ykLyuqXfUt5XzdRhvC68d0rRdgp; oldUrl=https%3A//console.open.taobao.com/%3Fspm%3Da219a.7386653.1.15.3b89286c2mOUed%23/index; tfstk=cKsABAZ3_7VDI29RLZUuCLbRkkIOa7V9NxOiX8LATGwjKWGi_sm5xMegQr9s7MwR.; l=eBTkZJA4guyjiKXUBO5Zourza77O1BAnHkPzaNbMiInca6sC1FTfKNCLdYLBRdtjMtfeJexyf_mGlRIsR3UdgwcgsN6Nzzv2pcY6o; isg=BMPDMsY1pnfIjGoDo82v70IxXIdtOFd6BaKTMPWmcyKZtNXWfwixy-FiLkb6D69y';
        // $cookie = 'UM_distinctid=17a1ec168e8a8-04d761c94f647d-66717c1d-1fa400-17a1ec168e9317; thw=cn; t=fc2cccc73d90c619bd9c3c93410f347f; x=e%3D1%26p%3D*%26s%3D0%26c%3D0%26f%3D0%26g%3D0%26t%3D0; cna=WphLGDWp014CAXjlFklYWY7n; mt=ci=116_1; xlly_s=1; cookie2=1c2b25bffe26e02a7b83b9ed50c756c2; _tb_token_=e4b467dee63d0; hng=CN%7Czh-CN%7CCNY%7C156; _samesite_flag_=true; sgcookie=E100ZKOhFaunkta75XJSh%2B7qMg2hk8XtwzWDQyT81zQU%2BKQ4vd4P4pCrky%2FOFaVGzuovzuOH2Y%2F%2BwnbePzxOG4tYmnbvNcTx0m5%2BL17bg6TUqvA%3D; uc3=id2=Vyh60T3e01PC&vt3=F8dCujaIqGOWSS%2Fu%2B8c%3D&lg2=V32FPkk%2Fw0dUvg%3D%3D&nk2=AnIuKtE%3D; csg=51e1dc89; lgc=acado; cancelledSubSites=empty; dnk=acado; skt=7f7e9362e615f276; existShop=MTYzMjgwMjgyNw%3D%3D; uc4=id4=0%40VX9LGI4smVZv%2FI%2BckIF9uoM7SUg%3D&nk4=0%40AJljbwTr%2FIoainWAtGUEIg%3D%3D; publishItemObj=Ng%3D%3D; tracknick=acado; _cc_=UtASsssmfA%3D%3D; v=0; enc=wnIOmqjI17YVHXSjDw1kLpzf%2BTFw63Gi7hcMDAJJz45JU8b5j41iM%2FMSnt%2BSTxQt0zyhBdyTzb%2FekZu%2BbNKDJg%3D%3D; linezing_session=HFXmYpcC9BmFrvNgfLHENctW_1632802922003nxIc_2; uc1=cookie14=Uoe3dYlZWcUCdw%3D%3D&pas=0&existShop=true&cookie16=UIHiLt3xCS3yM2h4eKHS9lpEOw%3D%3D&cookie21=UIHiLt3xSixwG45xoogELw%3D%3D; _m_h5_tk=cc8055aeef0191742c8399f7cc455f9e_1632818035665; _m_h5_tk_enc=68a37e2d05a1d2cc750d9433204c3098; JSESSIONID=4C6D4B12755C40A87035FAA4AA630A61; isg=BF9fZd5TQkQNYEZHivN-PqfV4LPpxLNmWXa_xPGsx45VgH4C_pd0tpdWQhL-A4ve; l=eBLsiRA7gRwuEYivBOfCKurza77TJBdbYuPzaNbMiOCPOg5w5OwGW6e20gTeCnGAH6rB73-1ZcbbB5LGvydq3Tt-CeE8GgDmndC..; tfstk=cIt1BZcy05V_-Su4bdMUgCpGQjjlZ5w5hV1MCENgsFg_5t91i0rPNyHpoSeyR91..';

        $doCurl = new DoCurl([
            'cookie' => $cookie,
            'auto_redirect' => false
        ]);
        $html = $doCurl->post($url, [
            '_tb_token_' => 'f4e03a5353ee5',
            'sopCode' => 'sop_analyse_token',
            'params' => '{"accessToken":"6200d2172f1209a0e4bfb50cfaaf8ZZ089d4b8f7040278e2210433149765"}'
        ]);

        echo $html;

        exit;
        halt(array_slice([1,2,3], 2, 3));

        exit;
        $fn = function ($page, $size) {

            $st = ($page - 1) * $size + 1;
            $ed = $page * $size;

            $taobao_st = ceil($st / 24);
            $taobao_ed = ceil($ed / 24);
            $diff = $taobao_ed - $taobao_st;

            halt(
                "起始数: $st",
                "结尾数: $ed",
                "淘宝起始页码: $taobao_st",
                "淘宝结束页码: $taobao_ed",
                "需向后获取几页: $diff"
            );
        };

        $fn(3, 15);

        exit;
        // $cookie = 'UM_distinctid=17a1ec168e8a8-04d761c94f647d-66717c1d-1fa400-17a1ec168e9317; thw=cn; t=fc2cccc73d90c619bd9c3c93410f347f; x=e%3D1%26p%3D*%26s%3D0%26c%3D0%26f%3D0%26g%3D0%26t%3D0; cna=WphLGDWp014CAXjlFklYWY7n; mt=ci=116_1; xlly_s=1; cookie2=1c2b25bffe26e02a7b83b9ed50c756c2; _tb_token_=e4b467dee63d0; hng=CN%7Czh-CN%7CCNY%7C156; _samesite_flag_=true; sgcookie=E100ZKOhFaunkta75XJSh%2B7qMg2hk8XtwzWDQyT81zQU%2BKQ4vd4P4pCrky%2FOFaVGzuovzuOH2Y%2F%2BwnbePzxOG4tYmnbvNcTx0m5%2BL17bg6TUqvA%3D; uc3=id2=Vyh60T3e01PC&vt3=F8dCujaIqGOWSS%2Fu%2B8c%3D&lg2=V32FPkk%2Fw0dUvg%3D%3D&nk2=AnIuKtE%3D; csg=51e1dc89; lgc=acado; cancelledSubSites=empty; dnk=acado; skt=7f7e9362e615f276; existShop=MTYzMjgwMjgyNw%3D%3D; uc4=id4=0%40VX9LGI4smVZv%2FI%2BckIF9uoM7SUg%3D&nk4=0%40AJljbwTr%2FIoainWAtGUEIg%3D%3D; publishItemObj=Ng%3D%3D; tracknick=acado; _cc_=UtASsssmfA%3D%3D; v=0; enc=wnIOmqjI17YVHXSjDw1kLpzf%2BTFw63Gi7hcMDAJJz45JU8b5j41iM%2FMSnt%2BSTxQt0zyhBdyTzb%2FekZu%2BbNKDJg%3D%3D; linezing_session=HFXmYpcC9BmFrvNgfLHENctW_1632802922003nxIc_2; uc1=cookie14=Uoe3dYlZWcUCdw%3D%3D&pas=0&existShop=true&cookie16=UIHiLt3xCS3yM2h4eKHS9lpEOw%3D%3D&cookie21=UIHiLt3xSixwG45xoogELw%3D%3D; _m_h5_tk=cc8055aeef0191742c8399f7cc455f9e_1632818035665; _m_h5_tk_enc=68a37e2d05a1d2cc750d9433204c3098; JSESSIONID=4C6D4B12755C40A87035FAA4AA630A61; isg=BF9fZd5TQkQNYEZHivN-PqfV4LPpxLNmWXa_xPGsx45VgH4C_pd0tpdWQhL-A4ve; l=eBLsiRA7gRwuEYivBOfCKurza77TJBdbYuPzaNbMiOCPOg5w5OwGW6e20gTeCnGAH6rB73-1ZcbbB5LGvydq3Tt-CeE8GgDmndC..; tfstk=cIt1BZcy05V_-Su4bdMUgCpGQjjlZ5w5hV1MCENgsFg_5t91i0rPNyHpoSeyR91..';
        $cookie = 't=fc2cccc73d90c619bd9c3c93410f347f; enc=wnIOmqjI17YVHXSjDw1kLpzf%2BTFw63Gi7hcMDAJJz45JU8b5j41iM%2FMSnt%2BSTxQt0zyhBdyTzb%2FekZu%2BbNKDJg%3D%3D';

        $url1 = 'https://shopsearch.taobao.com/browse/shop_search.htm?q=%E9%9F%A9%E8%A1%A3%E4%BC%9A';
        $url2 = 'https://scud.m.tmall.com/shop/shop_auction_search.do?suid=446034741';
        $url3 = 'https://scud.m.tmall.com/shop/shop_auction_search.do?spm=a2141.7631565.0.0.95796db2R7ZQWU&suid=446034741';

        $doCurl = new DoCurl([
            'cookie' => $cookie,
            'auto_redirect' => true
        ]);
        $html = $doCurl->get($url1);

        halt($html);

        // exit;
        // echo $html;
        file_put_contents('/www/wwwroot/test.qianzhiyu.net/runtime/storage/upload/test233.txt', $html);

        $html2 = file_get_contents('/www/wwwroot/test.qianzhiyu.net/runtime/storage/upload/test233.txt');

        $doc = new \DOMDocument();
        $doc->loadHTML($html2);
        $doc->normalizeDocument();

        $res = $doc->getElementsByTagName('script');

        $str = $res[5]->nodeValue;

        $str2 = trim($str);
        $res2 = explode("\n", $str2);

        $str3 = trim($res2[0]);
        
        preg_match('/g_page_config\s*=(.+)/', $str3, $p);

        $str4 = $p[1];

        $str4 = trim($str4);
        $str4 = trim($str4, ';');

        $aaa = json_decode($str4, true);

        halt($aaa);
        // echo $str4;exit;
        // halt($str4);
    }
    
    
    public function test6 (Request $request) {

        halt($request->header());
        halt($_SERVER);
    }


    public function test7 (Request $request) {

        phpinfo();

        exit;
        $job_queue_name = "koutu";
        $job_handler_classname = config("jobname.$job_queue_name");
        $job_data = [
            'a' => 'AA'
        ];

        $is_pushed = Queue::push($job_handler_classname, $job_data, $job_queue_name);
    }


    public function index1 (Request $request) {

        Session::set('_qianhui_csrf_', '123456');
        

        halt(1);
        try {
            
            throw new \Exception("Error Processing Request233", 1);
            
        } catch (\Throwable $th) {
            //throw $th;
            halt($th);
        }

        halt(1);
        $cookie = file_get_contents('/www/wwwroot/test.qianzhiyu.net/runtime/storage/upload/cookie.txt');
        $pic = '/www/wwwroot/test.qianzhiyu.net/runtime/storage/upload/0950ab0bd0d8369f76de0d9072dd2008-1637130916.png';
        $folder_id = (new TbGetFolder)->handle($cookie);

        halt($folder_id);
    }


    public function test8 (Request $request) {

        $doCurl = new DoCurl;
        $res = $doCurl->get('http://test.qianzhiyu.net/test/test9');
        halt($res);
    }


    // 生成一个当前环境唯一的模块id
    private function buildGroupId () {

        do {

            $id = 'group' . time() . mt_rand(100, 999);
        } while (in_array($id, $this->groupid_been_used));

        array_push($this->groupid_been_used, $id);
        return $id;
    }


    // 生成一个当前环境唯一的组件id
    private function buildComponentId () {

        do {

            $id = 'component' . time() . mt_rand(100, 999);
        } while (!in_array($id, $this->componentid_been_used));

        array_push($this->componentid_been_used, $id);
        return $id;
    }


    private static function test2333 () {

        return time();
    }


    public function test9 (Request $request) {

        $syncMainJob = SyncMainJobModel::find(64);
        halt($syncMainJob->is_all_uploaded);

        // halt($request->header()['_qianhui_csrf_']);

        halt(Util::getCodeNameByCode(1001));
        $res = (new MessageValidator)->handle([
            'msg_id' => 113,
            'handle' => 'login',
            'data' => [
                'uid' => 113,
            ],
        ]);

        halt($res);
        halt(1);
        halt(method_exists($this, 'test23333'));

        $res = SyncMainJobModel::where('id', 51)->update([
            'progress' => 20
        ]);


        halt($res);
        $cookie = file_get_contents('/www/wwwroot/test.qianzhiyu.net/runtime/storage/upload/cookie.txt');

        $folderId = (new TbGetFolder)->handle($cookie);
        halt($folderId);

        (new TbCommitItemTemplate)->handle([
            'cookie' => $cookie,
            'main_job_id' => 49,
        ]);
    }


    public function test10 (Request $request) {

        $a = [
            'cc' => null
        ];
        $aa = @$a['aa'] ?: null;
        $bb = @$a['bb'] ?: null;

        halt($aa === $a['cc']);

        $doCurl = new DoCurl([
            // 'cookie' => 'x-gpf-submit-trace-id=213134b616387553552714010ed30c; x-gpf-render-trace-id=212b035c16387725032585376e6053; t=db0bbc3d61ae0f6d85b3c23fb876f14e; thw=cn; UM_distinctid=17cf2dd3d8d6ab-01c332fd7ba435-57b193e-1fa400-17cf2dd3d8eb8e; enc=AX1%2FUukQBg6nAAAAAHjl0HEB%2FU%2F9%2FTEV%2Ff39ef39AXMXIlsBR67ohLYUqE0jMYE0e8n9JVm7E%2FpBVQmWBahL%2Bw%3D%3D; hng=CN%7Czh-CN%7CCNY%7C156; xlly_s=1; _samesite_flag_=true; cookie2=1b87ee44a2c506efe15508b518fa4a75; _tb_token_=e7871395e3ffe; _utk=VocP@qJyn^AtWdm; XSRF-TOKEN=1cf80273-6fc0-42fe-8100-2b4aa0a59cc4; _m_h5_tk=038f4b25c5275bd58c779d35b831d67c_1638779899402; _m_h5_tk_enc=c13a7305bcf4c2cde5d99eab5be4d0ff; cna=ytr/GR40kS8CAXjlFgRVsGAF; sgcookie=E100MgoakVu2I32DxFw2%2BiL%2FkaYT3haOlV88W%2BLMUyB7Bc25%2Buu1Jc16XyDCf4vT2ZUmU90dvVn8xplMuFV%2F%2BqKHiweWifGkDH%2BD3cPLVozl7zE%3D; unb=446034741; uc3=vt3=F8dCvUmgynshEg9GOTs%3D&nk2=AnIuKtE%3D&id2=Vyh60T3e01PC&lg2=V32FPkk%2Fw0dUvg%3D%3D; csg=817f3e73; lgc=acado; cancelledSubSites=empty; cookie17=Vyh60T3e01PC; dnk=acado; skt=4c8f53b5b76e72b2; existShop=MTYzODc3MjQ3Ng%3D%3D; uc4=id4=0%40VX9LGI4smVZv%2FI%2BckIsxwALktVo%3D&nk4=0%40AJljbwTr%2FIoagHBwWGH9%2Fw%3D%3D; publishItemObj=Ng%3D%3D; tracknick=acado; _cc_=Vq8l%2BKCLiw%3D%3D; _l_g_=Ug%3D%3D; sg=o14; _nk_=acado; cookie1=AiAzH3u3xhNLP%2FB5ofrd8TV4rZZbDt7oISOWMBSI9XA%3D; mt=ci=83_1; uc1=cookie16=V32FPkk%2FxXMk5UvIbNtImtMfJQ%3D%3D&existShop=true&pas=0&cookie15=WqG3DMC9VAQiUQ%3D%3D&cookie21=URm48syIZJfmYzXkpCGNJg%3D%3D&cookie14=Uoe3f4dkoh%2Fp0Q%3D%3D; x5sec=7b22677066342d74616f62616f3b32223a223165653863393764363762636630323262306239616661633139356466613665434a5465746f3047455079416b6f5446355069522f774561437a51304e6a417a4e4463304d5473784d4c545868674d3d227d; tfstk=crLdByT9ZADHsu59gHnMP3R7id8RZLDRCW6uyfay-Ryzm6aRiyLDkJlmA_6VpfC..; l=eBajLkmrgA5wrlpsBOfwhurza77tOIRAguPzaNbMiOCP_XC65AAOW6IIgTYBCnGVh6o6J3WAVUVzBeYBqnXvyw6mjVl8pSMmn; isg=BICAe5r79e4ZxonQtyLE77CzUQ5SCWTT69hc4voRahssdSCfoxppYldHjd21RRyr',
            'auto_redirect' => true,

            'header' => [
                ':authority: item.upload.taobao.com',
                ':method: POST',
                ':path: /sell/submit.htm',
                "cookie: x-gpf-submit-trace-id=213134b616387553552714010ed30c; x-gpf-render-trace-id=212b035c16387725032585376e6053; t=db0bbc3d61ae0f6d85b3c23fb876f14e; thw=cn; UM_distinctid=17cf2dd3d8d6ab-01c332fd7ba435-57b193e-1fa400-17cf2dd3d8eb8e; enc=AX1%2FUukQBg6nAAAAAHjl0HEB%2FU%2F9%2FTEV%2Ff39ef39AXMXIlsBR67ohLYUqE0jMYE0e8n9JVm7E%2FpBVQmWBahL%2Bw%3D%3D; hng=CN%7Czh-CN%7CCNY%7C156; xlly_s=1; _samesite_flag_=true; cookie2=1b87ee44a2c506efe15508b518fa4a75; _tb_token_=e7871395e3ffe; _utk=VocP@qJyn^AtWdm; XSRF-TOKEN=1cf80273-6fc0-42fe-8100-2b4aa0a59cc4; _m_h5_tk=038f4b25c5275bd58c779d35b831d67c_1638779899402; _m_h5_tk_enc=c13a7305bcf4c2cde5d99eab5be4d0ff; cna=ytr/GR40kS8CAXjlFgRVsGAF; sgcookie=E100MgoakVu2I32DxFw2%2BiL%2FkaYT3haOlV88W%2BLMUyB7Bc25%2Buu1Jc16XyDCf4vT2ZUmU90dvVn8xplMuFV%2F%2BqKHiweWifGkDH%2BD3cPLVozl7zE%3D; unb=446034741; uc3=vt3=F8dCvUmgynshEg9GOTs%3D&nk2=AnIuKtE%3D&id2=Vyh60T3e01PC&lg2=V32FPkk%2Fw0dUvg%3D%3D; csg=817f3e73; lgc=acado; cancelledSubSites=empty; cookie17=Vyh60T3e01PC; dnk=acado; existShop=MTYzODc3MjQ3Ng%3D%3D; uc4=id4=0%40VX9LGI4smVZv%2FI%2BckIsxwALktVo%3D&nk4=0%40AJljbwTr%2FIoagHBwWGH9%2Fw%3D%3D; publishItemObj=Ng%3D%3D; tracknick=acado; _cc_=Vq8l%2BKCLiw%3D%3D; _l_g_=Ug%3D%3D; sg=o14; _nk_=acado; cookie1=AiAzH3u3xhNLP%2FB5ofrd8TV4rZZbDt7oISOWMBSI9XA%3D; mt=ci=83_1; uc1=cookie16=V32FPkk%2FxXMk5UvIbNtImtMfJQ%3D%3D&existShop=true&pas=0&cookie15=WqG3DMC9VAQiUQ%3D%3D&cookie21=URm48syIZJfmYzXkpCGNJg%3D%3D&cookie14=Uoe3f4dkoh%2Fp0Q%3D%3D; x5sec=7b22677066342d74616f62616f3b32223a223165653863393764363762636630323262306239616661633139356466613665434a5465746f3047455079416b6f5446355069522f774561437a51304e6a417a4e4463304d5473784d4c545868674d3d227d; tfstk=crLdByT9ZADHsu59gHnMP3R7id8RZLDRCW6uyfay-Ryzm6aRiyLDkJlmA_6VpfC..; l=eBajLkmrgA5wrlpsBOfwhurza77tOIRAguPzaNbMiOCP_XC65AAOW6IIgTYBCnGVh6o6J3WAVUVzBeYBqnXvyw6mjVl8pSMmn; isg=BICAe5r79e4ZxonQtyLE77CzUQ5SCWTT69hc4voRahssdSCfoxppYldHjd21RRyr",
                'origin: https://item.upload.taobao.com',
                'referer: https://item.upload.taobao.com/sell/publish.htm?itemId=584181130977',
                'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/95.0.4638.54 Safari/537.36',
                // 'x-xsrf-token: 1cf80273-6fc0-42fe-8100-2b4aa0a59cc4',
            ]
        ]);

        
        $jsonBody = file_get_contents('/www/wwwroot/test.qianzhiyu.net/runtime/storage/upload/ccc.txt');

        $url = 'https://item.upload.taobao.com/sell/submit.htm';
        $post_data = [
            'catId' => '201163407',
            'itemId' => '584181130977',
            'jsonBody' => $jsonBody
        ];
        $raw = $doCurl->post($url, $post_data);
        halt($raw);
    }


    public function test11 (Request $request) {

        // $editor_data = file_get_contents('/www/wwwroot/test.qianzhiyu.net/runtime/storage/upload/sync.txt');
        // $editor_data = json_decode($editor_data, true);

        // $result = XiangqingAuthValidator::handle($editor_data, '113', '101203');
        // halt($result);
        // $pc = 'https://item.taobao.com/item.htm';
        // $wireless = '//h5.m.taobao.com/awp/core/detail.htm';
        
        
        // $cookie = OldUserModel::where('user_id', '113')->find()->cookie;
        // $result = (new TbCommitItemTemplate)->handle($cookie, '193');
        // dump($result);
        // $result2 = (new TbCommitItemTemplate)->handle($cookie, '181', 1);
        // halt($result2);
        // $cookie = '123';
        // $data = (new TbGetFolder)->handle($cookie);
        // halt($data);
    }


    public function test12 (Request $request) {

        // halt(urlencode('//cloud.video.taobao.com/play/u/446034741/p/1/e/6/t/1/326617965507.mp4'));
        echo 123;exit;
    }


    public function test13 (Request $request) {

        $doCurl = new DoCurl;
        $result = $doCurl->get('https://test.qianzhiyu.net/tb/xiangqing/custom_module_self?uid=113&template_id=100151');
        halt($result);
        
    }
}
