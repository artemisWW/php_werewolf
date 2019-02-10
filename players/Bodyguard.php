<?php
namespace JINRO_JOSEKI\Players;

require_once('Villager.php');
require_once('State.php');
require_once('ScoreAverage.php');
require_once('common/net/GameInfo.php');
require_once('common/net/GameSetting.php');

use JINRO_JOSEKI\Players;
use JINRO_JOSEKI\ScoreAverage;
use JINRO_JOSEKI\Common\Net\GameInfo;
use JINRO_JOSEKI\Common\Net\GameSetting;
use JINRO_JOSEKI\Common\Data\Role;
use JINRO_JOSEKI\Common\Data\Agent;

class Bodyguard extends Villager
{
    public function dayStart()
    {
        parent::dayStart();
        // 死亡した人がいないときは護衛成功とする（狐の処理未実装）
        $dead_id_list = $this->game_info->getLastDeadIdList();
        if (count($dead_id_list) === 0) {
            ;
        }
    }
    public function guard()
    {
        // 占,霊の可能性が高い人を護衛
        $score_set = $this->state->copyScoreSet();
        $average = new ScoreAverage($score_set, $this->co_manager);
        $score_list = $average->getRoleScore([Role::SEER, Role::MEDIUM]);
        // 候補者を更新する
        $candidate_list = $this->getCandidateList();
        return $this->selectMaxScoreAgent($score_list, $candidate_list);
    }
}
