<?php
namespace JINRO_JOSEKI;
require_once('ScoreAverage.php');
require_once('Hypothesys.php');

use JINRO_JOSEKI\ScoreSet;
use JINRO_JOSEKI\ScoreAverage;
use JINRO_JOSEKI\Hypothesys;
use JINRO_JOSEKI\Players\Player;
use JINRO_JOSEKI\Common\Data\Role;
use JINRO_JOSEKI\Common\Data\Team;

class DecideVote {
    private $agent_id = -1;
    private $role_id = -1;
    private $score_set0 = null;
    private $score_set1 = null;
    private $co_manager = null;

    private $role_size_list = [];
    private $user_size = 0;
    private $candidate = [];
    
    public function __construct(int $agent_id, int $role_id, ScoreSet $score_set0, ScoreSet $score_set1, CoManager $co_manager)
    {
        $this->agent_id = $agent_id;
        $this->role_id = $role_id;
        $this->score_set0 = clone $score_set0;
        $this->score_set1 = clone $score_set1;
        $this->co_manager = $co_manager;
        $this->role_size_list = $this->score_set1->getRoleSizeList();
        $this->user_size = 0;
        foreach ($this->role_size_list as $role_id => $role_size) {
            $this->user_size += $role_size;
        }
    }
    public function calc(array $candidate0)
    {
        // 自分から見て、100%味方の人は削る
        $candidate1 = $this->narrowTeam($candidate0);
        // 残り人数が少ないときは、人狼の可能性がない人も残す
        if (count($candidate0) < 4) {
            $candidate2 = $candidate1;
        } else {
            // 全員から見て、人狼の可能性がない人は削る
            $candidate2 = $this->narrowWerewolf($candidate1);
        }
        // 候補者別にスコア計算する
        $score_list = $this->calcProb($candidate2);
        return $score_list;
    }
    /** 自分から見て、味方の可能性が100%の人は候補から捨てる */
    private function narrowTeam(array $candidate0)
    {
        $this_team_id = Role::getTeamId($this->role_id);
        // 自分から見た時に、他の人の役職に対する確率
        $average = new ScoreAverage($this->score_set0, $this->co_manager);
        $score_map = $average->getTeamScore($this_team_id);
        $candidate1 = [];
        foreach ($candidate0 as $user_id) {
            $prob = $score_map[$user_id];
            if ($prob >= 1.0) {
                continue;
            }
            $candidate1[] = $user_id;
        }
        if (count($candidate1) === 0) {
            return $candidate0;
        } else {
            return $candidate1;
        }
    }
    /** 他の人から見て、人狼の可能性がある人のみ候補に残す */
    private function narrowWerewolf(array $candidate0)
    {
        // 自分から見た時に、他の人の役職に対する確率
        $user_role_prob = new ScoreAverage($this->score_set0, $this->co_manager);
        // 候補者別に計算
        $candidate_map1 = [];  // 人狼の可能性がある人(user_id =>1 の連想配列)
        foreach ($candidate0 as $user_id) {
            // 自分から見た時に、他の人の役職の確率で計算する
            $role_prob = $user_role_prob->getUserScore($user_id);
            foreach ($role_prob as $role_id => $prob) {
                if ($prob <= 0.0) {
                    continue;
                }
                // user_idがrole_idとしたときの
                $score_set2 = clone $this->score_set1;
                $score_set2->action_id($user_id, Action::ROLE, [$role_id]);
                $average = new ScoreAverage($score_set2, $this->co_manager);
                $score_list = $average->getRoleScore([Role::WEREWOLF]);
                foreach ($score_list as $user_id2 => $prob2) {
                    if ($prob2 <= 0.0) {
                        continue;
                    }
                    if (!in_array($user_id2, $candidate0, true)) {
                        continue;
                    }
                    $candidate_map1[$user_id2] = 1;
                }
            }
        }
        if (count($candidate_map1) === 0) {
            return $candidate0;
        } else {
            return array_keys($candidate_map1);
        }
    }
    /** 候補者の人狼側/村人側の確率を求める */
    private function calcProb(array $candidate)
    {
        $average = new ScoreAverage($this->score_set0, $this->co_manager);
        $team_id = Role::getTeamId($this->role_id);
        if ($team_id === Team::VILLAGER) {
            // 村人側のときは人狼側の確率を求める
            $prob_list = $average->getTeamScore(Team::WEREWOLF);
        } elseif ($team_id === Team::WEREWOLF) {
            // 人狼側のときは村人側の確率を求める
            $prob_list = $average->getTeamScore(Team::VILLAGER);
        } else {
            ;  // 未対応
        }
        // 候補者の中に入っている人のスコアを結果として出力する
        $score_list = [];
        foreach ($candidate as $user_id) {
            $score_list[$user_id] = $prob_list[$user_id];
        }
        return $score_list;
    }
    public function select(array $score_list)
    {
        // スコアが高い人を1人選ぶ
        $max = 0.0;
        $max_id_list = [];
        foreach ($score_list as $user_id => $score) {
            if ($score > $max) {
                $max = $score;
                $max_id_list = [];
                $max_id_list[] = $user_id;
            } elseif ($score === $max) {
                $max_id_list[] = $user_id;
            }
        }
        shuffle($max_id_list);
        return $max_id_list[0];
    }
}
