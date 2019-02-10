<?php
namespace JINRO_JOSEKI\Players;

require_once('Villager.php');
require_once('State.php');
require_once('common/net/GameInfo.php');
require_once('common/net/GameSetting.php');
require_once('client/lib/Topic.php');

use JINRO_JOSEKI\Players;
use JINRO_JOSEKI\Action;
use JINRO_JOSEKI\Common\Net\GameInfo;
use JINRO_JOSEKI\Common\Net\GameSetting;
use JINRO_JOSEKI\Common\Data\Role;
use JINRO_JOSEKI\Common\Data\Species;
use JINRO_JOSEKI\Common\Data\Agent;
use JINRO_JOSEKI\Common\Data\Talk;
use JINRO_JOSEKI\Common\Lib\Content;
use JINRO_JOSEKI\Client\Lib\Topic;

class Medium extends Villager
{
    private $judge_queue = [];
    private $isComingout = false;

    public function dayStart()
    {
        parent::dayStart();
        // 霊媒結果を取得
        $judge = $this->game_info->getMediumResult();
        if ($judge !== null) {
            // judge_queue に追加
            $this->judge_queue[] = $judge;
            // 霊媒結果の処理
            $this->state->action_id($judge->getTargetId(), Action::JUDGED, [$judge->getResultId()]);
        }
    }
    public function talk()
    {
        if ($this->isComingout) {
            // comingoutしていているときは、過去の霊媒結果をtalk_queueに追加
            while (count($this->judge_queue) !== 0) {
                $judge = array_shift($this->judge_queue);
                $content = new Content(['topic_id' => Topic::IDENTIFIED, 'target_id' => $judge->getTargetId(), 'result_id' => $judge->getResultId()]);
                $this->talk_queue[] = $content->toString();
            }
        } else {
            // comingoutしていないときは、comingoutするかを判定する
            $co_flag = false;
            // 他にCO霊している人がいるか
            $medium_list = $this->co_manager->getCoAgentIdList(Role::MEDIUM);
            if (count($medium_list)) {
                $co_flag = true;
            } else {
                // 霊媒結果がWEREWOLFのとき
                $judge_size = count($this->judge_queue);
                if ($judge_size > 0) {
                    $judge = $this->judge_queue[$judge_size - 1];
                    if ($judge->getResultId() === Species::WEREWOLF) {
                        $co_flag = true;
                    }
                }
            }
            if (!$co_flag) {
                // ランダムでCO霊する
                $rand = rand(0, 2);
                if ($rand > 0) {
                    $co_flag = true;
                }
            }
            if ($co_flag) {
                // CO霊することにしたとき
                $this->isComingout = true;
                $content = new Content(['topic_id' => Topic::COMINGOUT, 'target_id' => $this->agent_id, 'role_id' => Role::MEDIUM]);
                $this->talk_queue[] = $content->toString();
            } 
        }
        return parent::talk();
    }
}
