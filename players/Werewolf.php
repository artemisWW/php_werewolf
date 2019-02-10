<?php
namespace JINRO_JOSEKI\Players;

require_once('Villager.php');
require_once('ScoreAverage.php');
require_once('State.php');
require_once('common/net/GameInfo.php');
require_once('common/net/GameSetting.php');

use JINRO_JOSEKI\Players;
use JINRO_JOSEKI\ScoreAverage;
use JINRO_JOSEKI\Hypothesize;
use JINRO_JOSEKI\Hypothesys;
use JINRO_JOSEKI\Common\Net\GameInfo;
use JINRO_JOSEKI\Common\Net\GameSetting;
use JINRO_JOSEKI\Common\Data\Role;
use JINRO_JOSEKI\Common\Data\Team;
use JINRO_JOSEKI\Common\Data\Species;
use JINRO_JOSEKI\Common\Data\Agent;
use JINRO_JOSEKI\Common\Data\Talk;
use JINRO_JOSEKI\Common\Data\Status;
use JINRO_JOSEKI\Common\Lib\Content;
use JINRO_JOSEKI\Client\Lib\Topic;

class Werewolf extends Villager
{
    private $comingout_role = -1;
    private $judged_size = 0;

    public function talk()
    {
        // 候補者を取得
        $candidate_list = $this->getCandidateList();
        // カミングアウトしている人がいないときはskip
        if (count($this->co_manager->getCoAgentIdList(Role::SEER)) === 0) {
            ;
        } elseif ($this->talk_id < 5) {
            ;
        } elseif ($this->comingout_role === -1) {
            $hypothesize = new Hypothesize($this->agent_id, $this->role_id, $this->state->getScoreSet(), $this->state_talk->getScoreSet(), $this->co_manager);
            $hypo_list = $hypothesize->calc($candidate_list, [Hypothesize::CO_NONE, Hypothesize::CO_SEER, Hypothesize::CO_MEDIUM]);
            $hypothesys = Hypothesys::select($hypo_list);
            $hypo_id = $hypothesys->getHypoId();
            // カミングアウトしていないときは、hypo_idに応じてカミングアウトする
            $role_id = -1;
            if ($hypo_id === Hypothesize::CO_SEER) {
                $role_id = Role::SEER;
            } elseif ($hypo_id === Hypothesize::CO_MEDIUM) {
                $role_id = Role::MEDIUM;
            }
            if ($role_id !== -1) {
                $this->comingout_role = $role_id;
                $content = new Content(['topic_id' => Topic::COMINGOUT, 'target_id' => $this->agent_id, 'role_id' => $role_id]);
                $this->talk_queue[] = $content->toString();
            }
        } elseif ($this->comingout_role === Role::SEER) {
            if ($this->judged_size < $this->day) {
                $hypothesize = new Hypothesize($this->agent_id, $this->role_id, $this->state->getScoreSet(), $this->state_talk->getScoreSet(), $this->co_manager);
                $hypo_list = $hypothesize->calc($candidate_list, [Hypothesize::DIVINE]);
                $hypothesys = Hypothesys::select($hypo_list);
                // 占い先と判定結果を取得
                list($target_id, $species_id) = $hypothesys->getArgList();
                $content = new Content(['topic_id' => Topic::DIVINED, 'target_id' => $target_id, 'result_id' => $species_id]);
                $this->talk_queue[] = $content->toString();
                $this->judged_size++;
            }
        } elseif ($this->comingout_role === Role::MEDIUM) {
            // 過去の日数分の霊媒結果を報告
            // executed_id_list は1日目からしかデータがない
            $executed_id_list = $this->co_manager->getExecutedIdList();
            if ($this->judged_size < $this->day - 1) {
                $rand = rand(0, 1);
                if ($rand === 0) {
                    $species_id = Species::HUMAN;
                } else {
                    $species_id = Species::WEREWOLF;
                }
                $target_id = $executed_id_list[$this->judged_size + 1];  // 1日目から
                $content = new Content(['topic_id' => Topic::IDENTIFIED, 'target_id' => $target_id, 'result_id' => $species_id]);
                $this->talk_queue[] = $content->toString();
                $this->judged_size++;
            }
        }
        return parent::talk();
    }
    public function attack()
    {
        $score_set = $this->state->copyScoreSet();
        $average = new ScoreAverage($score_set, $this->co_manager);
        // Team::VILLAGER の可能性が一番高い人を attack する
        $score_list = $average->getTeamScore(Team::VILLAGER);
        // 候補者を更新する
        $candidate_list = $this->getCandidateList();
        return $this->selectMaxScoreAgent($score_list, $candidate_list);
    }
}
