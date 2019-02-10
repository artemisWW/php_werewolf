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

class Hypothesize {
    const CO_VILLAGER = 0;
    const CO_WEREWOLF = 1;
    const CO_SEER = 2;
    const CO_MEDIUM = 3;
    const CO_BODYGUARD = 4;
    const CO_POSSESSED = 5;
    const CO_FREEMASON = 6;
    const CO_FOX = 7;
    const CO_NONE = 8;
    const DIVINE = 9;

    private $agent_id = -1;
    private $role_id = -1;
    private $score_set0 = null;
    private $score_set1 = null;
    private $co_manager = null;

    private $role_size_list = [];
    private $user_size = 0;

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
    public function calc(array $candidate, $hypo_id_list = [])
    {
        $hypo_list = [];
        foreach ($hypo_id_list as $hypo_id) {
            if ($hypo_id === self::CO_NONE) {
                // 何もしない
                $entropy_list = $this->getEntropy($candidate, $this->score_set0, $this->score_set1);
                $hypo_list[] = new Hypothesys($this->agent_id, $hypo_id, [], $entropy_list);
            } elseif ($hypo_id === self::CO_MEDIUM) {
                if ($this->getRoleSize(Role::MEDIUM) === 0) {
                    continue;
                }
                $score_set0 = clone $this->score_set0;
                $score_set1 = clone $this->score_set1;
                $score_set0->action_id($this->agent_id, Action::COMINGOUT, [Role::MEDIUM]);
                $score_set1->action_id($this->agent_id, Action::COMINGOUT, [Role::MEDIUM]);
                $entropy_map = $this->getIdentifiedPattern($candidate, $score_set0, $score_set1);
                foreach ($entropy_map as $pattern => $entropy_list) {
                    list($user_id, $bool) = explode("\t", $pattern);
                    $hypo_list[] = new Hypothesys($this->agent_id, $hypo_id, [(int)$user_id, (int)$bool], $entropy_list);
                }
            } elseif ($hypo_id === self::CO_SEER) {
                if ($this->getRoleSize(Role::SEER) === 0) {
                    continue;
                }
                $score_set0 = clone $this->score_set0;
                $score_set1 = clone $this->score_set1;
                $score_set0->action_id($this->agent_id, Action::COMINGOUT, [Role::SEER]);
                $score_set1->action_id($this->agent_id, Action::COMINGOUT, [Role::SEER]);
                $entropy_map = $this->getDivinePattern($candidate, $score_set0, $score_set1);
                foreach ($entropy_map as $pattern => $entropy_list) {
                    list($user_id, $bool) = explode("\t", $pattern);
                    $hypo_list[] = new Hypothesys($this->agent_id, $hypo_id, [(int)$user_id, (int)$bool], $entropy_list);
                }
            } elseif ($hypo_id === self::DIVINE) {
                $entropy_map = $this->getDivinePattern($candidate, $this->score_set0, $this->score_set1);
                foreach ($entropy_map as $pattern => $entropy_list) {
                    list($user_id, $bool) = explode("\t", $pattern);
                    $hypo_list[] = new Hypothesys($this->agent_id, $hypo_id, [(int)$user_id, (int)$bool], $entropy_list);
                }
            }
        }
        return $hypo_list;
    }
    private function getDivinePattern(array $candidate, ScoreSet $score_set0, ScoreSet $score_set1) {
        // 判定先と判定色のパターンで、一番よいパターンを選択する
        // 占い師が占い先を決める時：村人側から見た時に人狼がわかる
        // 狂人が占い先を決める時：人狼から見た時に狂人がわかる→村人側から見た時に人狼がわからない
        // 人狼が占い先を決める時：狂人から見た時に人狼がわかる→村人側から見た時に人狼がわからない
        $entropy_map = [];
        foreach ([false, true] as $bool) {
            foreach ($candidate as $user_id) {
// $bool_str = $bool ? '黒' : '白';
// print $this->agent_id . ' が ' . $user_id . ' を' . $bool_str . '判定したとき' . "\n";
                $score_set = clone $score_set1;
                $score_set->action_id($this->agent_id, Action::DIVINED, [$user_id, $bool]);
                if ($this->checkScoreSet($score_set)) {
                    continue;
                }
                $entropy_list = $this->getEntropy($candidate, $score_set0, $score_set);
                $entropy_map[$user_id . "\t" . $bool] = $entropy_list;
            }
        }
        return $entropy_map;
    }
    private function getIdentifiedPattern(array $candidate, ScoreSet $score_set0, ScoreSet $score_set1) {
        // 判定先と判定色のパターンで、一番よいパターンを選択する
        // 狂人が占い先を決める時：人狼から見た時に狂人がわかる→村人側から見た時に人狼がわからない
        // 人狼が占い先を決める時：狂人から見た時に人狼がわかる→村人側から見た時に人狼がわからない
        $entropy_map = [];
        foreach ([false, true] as $bool) {
            foreach ($candidate as $user_id) {
// $bool_str = $bool ? '黒' : '白';
// print $this->agent_id . ' が ' . $user_id . ' を' . $bool_str . '判定したとき' . "\n";
                $score_set = clone $score_set1;
                $score_set->action_id($this->agent_id, Action::IDENTIFIED, [$user_id, $bool]);
                $entropy_list = $this->getEntropy($candidate, $score_set0, $score_set);
                $entropy_map[$user_id . "\t" . $bool] = $entropy_list;
            }
        }
        return $entropy_map;
    }
    /** 仮説がなくなるパターンかチェック */
    private function checkScoreSet(ScoreSet $score_set)
    {
        $score_size = $score_set->getScoreSize();
        $size = 0;
        for ($score_id = 0; $score_id < $score_size; $score_id++) {
            $score = $score_set->getScore($score_id);
            if ($score === null) {
                continue;
            }
            if ($this->isSkipped($score)) {
                continue;
            }
            $size++;
        }
        if ($size === 0) {
            return true;
        }
        return false;
    }
    private function isSkipped(Score $score)
    {
        if ($this->co_manager === null) {
            return false;
        }
        $co_agent_id_matrix = $this->co_manager->getCoAgentIdMatrix();
        foreach ($co_agent_id_matrix as $co_role_id => $agent_id_list) {
            // COの役職別ですべてのagent_idのboolが1のときはスキップ
            $all_one_flag = true;
            foreach ($agent_id_list as $agent_id) {
                if ($score->getBool($agent_id) !== 1) {
                    $all_one_flag = false;
                    break;
                }
            }
            // all_one_flag = true のとき
            if ($all_one_flag) {
                return true;
            }
        }
        return false;
    }
    /**
     * 他の人から見たときに、この状態(score_set1)の人狼側に対するエントロピーを求める
     * entropy0: 村人側の役職から見た時のエントロピー
     * entropy1: 人狼側の役職から見た時のエントロピー
     * @return array [entropy0, entropy1]
     */
    private function getEntropy(array $candidate, ScoreSet $score_set0, ScoreSet $score_set1)
    {
        // 自分から見た時に、他の人の役職に対する確率
        $user_role_prob = new ScoreAverage($score_set0, $this->co_manager);
        // 各ユーザーの役職者別のエントロピーを計算
        $entropy0 = 0.0;  // 村人側から見た、人狼側のエントロピー
        $entropy1 = 0.0;  // 人狼側から見た、村人側のエントロピー
        $entropy2 = 0.0;  // 村人側から見た、人狼のエントロピー
        foreach ($candidate as $user_id) {
            // 自分から見た時に、user_idはどの役職(role_id)の可能性があるか
            // user_idがrole_idのときにscore_set1のエントロピー(村人側,人狼側)を求める
            $role_prob = $user_role_prob->getUserScore($user_id);
            foreach ($role_prob as $role_id => $prob) {
                if ($prob <= 0.0) {
                    continue;
                }
                $score_set2 = clone $score_set1;
                $score_set2->action_id($user_id, Action::ROLE, [$role_id]);
                $average = new ScoreAverage($score_set2, $this->co_manager);
                $team_entropy = $average->calcTeamEntropy(Team::WEREWOLF);
                $role_entropy = $average->calcRoleEntropy([Role::WEREWOLF]);
                $team_id = Role::getTeamId($role_id);
                if ($team_id === Team::VILLAGER) {
                    $entropy0 += $prob * $team_entropy;
                    $entropy2 += $prob * $role_entropy;
                } elseif ($team_id === Team::WEREWOLF) {
                    $entropy1 += $prob * $team_entropy;
                } else {
                    ;  // 未検討
                }
            }
// print 'ENTROPY0 = ' . sprintf("%.3f", $entropy0) . ', ENTROPY1 = ' . sprintf("%.3f", $entropy1) . "\n";
        }
        // 人狼側の可能性が100%の人がいるかどうか
        $is_team_werewolf = $this->isTeamWerewolf($score_set1);
        return [$entropy0, $entropy1, $is_team_werewolf, $entropy2];
    }
    private function isTeamWerewolf(ScoreSet $score_set)
    {
        $average = new ScoreAverage($score_set, $this->co_manager);
        $score_map = $average->getTeamScore(Team::WEREWOLF);
        foreach ($score_map as $user_id => $prob) {
            if ($prob >= 1.0) {
                return true;
            }
        }
        return false;
    }
    private function getRoleSize(int $role_id)
    {
        if (!array_key_exists($role_id, $this->role_size_list)) {
            return 0;
        }
        return $this->role_size_list[$role_id];
    }
}
