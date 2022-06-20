<?php
namespace app\extend\tb;



class TbSyncDataBuilder {


    // 生成 detailParam 参数
    private function buildDetailParam () {

    }


    // 生成 templateContent 参数
    private function buildTemplateContent () {

    }


    public function handle ($sync_input) {

        $editor_data = $sync_input['editor_data'];
        $pics = $sync_input['pics'];

        // 提交用的数据
        $commit_data = $this->buildCommitData();
    }
}