<?php
namespace JINRO_JOSEKI\Players;

require_once('Villager.php');
require_once('ScoreAverage.php');
require_once('State.php');
require_once('common/net/GameInfo.php');
require_once('common/net/GameSetting.php');

use JINRO_JOSEKI\Players;
use JINRO_JOSEKI\Action;
use JINRO_JOSEKI\ScoreAverage;
use JINRO_JOSEKI\Hypothesize;
use JINRO_JOSEKI\Hypothesys;
use JINRO_JOSEKI\Common\Net\GameInfo;
use JINRO_JOSEKI\Common\Net\GameSetting;
use JINRO_JOSEKI\Common\Data\Role;
use JINRO_JOSEKI\Common\Data\Species;
use JINRO_JOSEKI\Common\Data\Agent;
use JINRO_JOSEKI\Common\Data\Talk;
use JINRO_JOSEKI\Common\Data\Status;
use JINRO_JOSEKI\Common\Lib\Content;
use JINRO_JOSEKI\Client\Lib\Topic;

class Seer extends Villager
{
    private $judge_queue = [];
    private $divine_list = [];
    private $isComingout = false;
    
    public function dayStart()
    {
        parent::dayStart();
        // 占い結果を取得
        $judge = $this->game_info->getDivineResult();
        if ($judge !== null) {
            // judge_queue に追加
            $this->judge_queue[] = $judge;
            // 占い結果の処理
            $this->state->action_id($judge->getTargetId(), Action::JUDGED, [$judge->getResultId()]);
        }
    }
    public function talk()
    {
        if ($this->isComingout) {
            // comingoutしていているときは、過去の霊媒結果をtalk_queueに追加
            while (count($this->judge_queue) !== 0) {
                $judge = array_shift($this->judge_queue);
                $content = new Content(['topic_id' => Topic::DIVINED, 'target_id' => $judge->getTargetId(), 'result_id' => $judge->getResultId()]);
                $this->talk_queue[] = $content->toString();
            }
        } else {
            // comingoutしていないときは、comingoutするかを判定する
            $co_flag = false;
            // 他にCO占している人がいるか
            $seer_list = $this->co_manager->getCoAgentIdList(Role::SEER);
            if (count($seer_list)) {
                $co_flag = true;
            } else {
                // 占い結果がWEREWOLFのとき
                $judge_size = count($this->judge_queue);
                if ($judge_size > 0) {
                    $judge = $this->judge_queue[$judge_size - 1];
                    if ($judge->getResultId() === Species::WEREWOLF) {
                        $co_flag = true;
                    }
                }
            }
            if (!$co_flag) {
                // ランダムでCO占する
                $rand = rand(0, 2);
                if ($rand > 0) {
                    $co_flag = true;
                }
            }
            if ($co_flag) {
                // CO霊することにしたとき
                $this->isComingout = true;
                $content = new Content(['topic_id' => Topic::COMINGOUT, 'target_id' => $this->agent->getAgentIdx(), 'role_id' => Role::SEER]);
                $this->talk_queue[] = $content->toString();
            } 
        }
        return parent::talk();
    }
    public function divine()
    {
        // 候補者を更新する（過去の占い先も除く）
        $candidate_list = $this->getCandidateList($this->divine_list);
        // 
        $hypothesize = new Hypothesize($this->agent_id, $this->role_id, $this->state->getScoreSet(), $this->state_talk->getScoreSet(), $this->co_manager);
        $hypo_list = $hypothesize->calc($candidate_list, [Hypothesize::DIVINE]);
/*
        foreach ($hypo_list as $hypo) {
            $hypo->print();
        }
*/
        $hypothesys = Hypothesys::selectForSeer($hypo_list);
        // 占い先と判定結果を取得
        list($target_id, $species_id) = $hypothesys->getArgList();
        // 占い先を格納
        $this->divine_list[] = $target_id;
        return $target_id;
    }
}
