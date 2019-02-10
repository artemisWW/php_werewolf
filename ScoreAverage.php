<?php
namespace JINRO_JOSEKI;

require_once('common/data/Role.php');
require_once('ScoreSet.php');
require_once('CoManager.php');

use JINRO_JOSEKI;
use JINRO_JOSEKI\CoManager;
use JINRO_JOSEKI\Common\Data\Role;
use JINRO_JOSEKI\Common\Data\Team;
use JINRO_JOSEKI\Common\Data\Species;

class ScoreAverage
{
    /** @var delete_flg */
    private $skip_flag = true;
    /** @var ScoreSet */
    private $score_set = null;
    /** @var CoManager */
    private $co_manager = null;
    /** @var int[] size of each role */
    private $role_size_map = [];
    /** @var int size of user */
    private $user_size = 0;
    /** @var average average of score_list */
    private $average = [];

    public function __construct(ScoreSet $score_set, CoManager $co_manager = null)
    {
        $this->score_set = $score_set;
        $this->co_manager = $co_manager;
        $this->role_size_map = $this->score_set->getRoleSizeList();
        $this->user_size = 0;
        foreach ($this->role_size_map as $role_id => $role_size) {
            $this->user_size += $role_size;
        }
        $this->init();
        $this->calc();
    }
    private function init()
    {
        foreach ($this->role_size_map as $role_id => $role_size) {
            for ($user_id = 0; $user_id < $this->user_size; $user_id++) {
                $this->average[$user_id][$role_id] = 0.0;
            }
        }
    }
    private function calc()
    {
        $score_size = $this->score_set->getScoreSize();
        $size = 0;
        for ($score_id = 0; $score_id < $score_size; $score_id++) {
            $score_class = $this->score_set->getScore($score_id);
            if ($score_class === null) {
                continue;
            }
            if ($this->isSkipped($score_class)) {
                continue;
            }
/*
            if ($this->skip_flag) {
                $size0 = 0;  // bool_list の 0 の個数
                $size1 = 0;  // bool_list の 1 の個数
                for ($user_id = 0; $user_id < $this->user_size; $user_id++) {
                    $bool = $score_class->getBool($user_id);
                    if ($bool === -1) { ; }
                    elseif ($bool === 1) { $size1++; }
                    elseif ($bool === 0) { $size0++; }
                }
                // すべて1(偽)で1(偽)が2個以上ある仮説は除く
//                if ($size0 === 0 && $size1 > 1) {
                if ($size0 === 0) {
                    $skip_sid = $score_id;  // すべてなくなったときはこのscore_idのスコアを平均とする
                    continue;
                }
            }
*/
            $score = $score_class->getScore();
            foreach ($this->role_size_map as $role_id => $role_size) {
                for ($user_id = 0; $user_id < $this->user_size; $user_id++) {
                    $this->average[$user_id][$role_id] += $score[$user_id][$role_id];
                }
            }
            $size++;
        }
/*
        if ($this->skip_flag && $skip_sid >= 0 && $size === 0) {
            // bool_flag = 0 のスコアがなかったときは、bool_flag がすべて1のスコアを採用する（占い師など）
            $score_class = $this->score_set->getScore($skip_sid);
            $score = $score_class->getScore();
            foreach ($this->role_size_map as $role_id => $role_size) {
                for ($user_id = 0; $user_id < $this->user_size; $user_id++) {
                    $this->average[$user_id][$role_id] = $score[$user_id][$role_id];
                }
            }
*/
        if ($size !== 0 && $size !== 1) {
            // size で割って平均にする
            foreach ($this->role_size_map as $role_id => $role_size) {
                for ($user_id = 0; $user_id < $this->user_size; $user_id++) {
                    $this->average[$user_id][$role_id] /= $size;
                }
            }
        }
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
    public function getScoreMatrix()
    {
        return $this->average;
    }
    public function getUserScore(int $user_id)
    {
        return $this->average[$user_id];
    }
    public function getRoleScore(array $role_id_list)
    {
        $score_list = [];
        for ($user_id = 0; $user_id < $this->user_size; $user_id++) {
            $score_list[$user_id] = 0.0;
            foreach ($role_id_list as $role_id) {
                if ($this->getRoleSize($role_id) === 0) {
                    continue;
                }
                $score_list[$user_id] += $this->average[$user_id][$role_id];
            }
        }
        return $score_list;
    }
    public function getTeamScore(int $team_id)
    {
        $score_list = [];
        for ($user_id = 0; $user_id < $this->user_size; $user_id++) {
            $score_list[$user_id] = 0.0;
            foreach ($this->role_size_map as $role_id => $role_size) {
                if (Role::getTeamId($role_id) === $team_id) {
                    $score_list[$user_id] += $this->average[$user_id][$role_id];
                }
            }
        }
        return $score_list;
    }
    public function getSpeciesScore(int $species_id)
    {
        $score_list = [];
        for ($user_id = 0; $user_id < $this->user_size; $user_id++) {
            $score_list[$user_id] = 0.0;
            foreach ($this->role_size_map as $role_id => $role_size) {
                if (Species::getSpeciesId($role_id) === $species_id) {
                    $score_list[$user_id] += $this->average[$user_id][$role_id];
                }
            }
        }
        return $score_list;
    }
    public function getStrList()
    {
        $str_list = array_fill(0, $this->user_size, '？');
        for ($user_id = 0; $user_id < $this->user_size; $user_id++) {
            $species_werewolf_score = 0.0;
            $team_werewolf_score = 0.0;
            $team_villager_score = 0.0;
            foreach ($this->role_size_map as $role_id => $role_sizse) {
                if ($this->average[$user_id][$role_id] >= 1.0) {
                    $str_list[$user_id] = Role::toJapaneseName($role_id);
                    break;
                }
                if (Role::getSpeciesId($role_id) === Species::WEREWOLF) {
                    $species_werewolf_score += $this->average[$user_id][$role_id];
                }
                if (Role::getTeamId($role_id) === Team::VILLAGER) {
                    $team_villager_score += $this->average[$user_id][$role_id];
                }
                if (Role::getTeamId($role_id) === Team::WEREWOLF) {
                    $team_werewolf_score += $this->average[$user_id][$role_id];
                }
            }
            if ($str_list[$user_id] !== '？') {
                continue;
            }
            if ($team_villager_score >= 1.0) {
                $str_list[$user_id] = '◎';
                continue;
            }
            if ($team_werewolf_score >= 1.0) {
                $str_list[$user_id] = '●';
                continue;
            }
            if ($species_werewolf_score === 0.0) {
                $str_list[$user_id] = '○';
                continue;
            }
        }
        return $str_list;
    }
    /**
     * 人狼のスコアを使ってエントロピーを計算
     * obsolete
     */
    public function calcEntropy(array $role_id_list)
    {
        return $this->calcRoleEntropy($role_id_list);
    }
    /** 指定役職のエントロピーを計算 */
    public function calcRoleEntropy(array $role_id_list)
    {
        $score_map = $this->getRoleScore($role_id_list);
        return $this->calcEntropyCore($score_map);
    }
    /** 指定チームのエントロピーを計算 */
    public function calcTeamEntropy(int $team_id)
    {
        $score_map = $this->getTeamScore($team_id);
        return $this->calcEntropyCore($score_map);
    }
    /** エントロピー計算のコア部分 */
    private function calcEntropyCore(array $score_map)
    {
        $entropy = 0;
        foreach ($score_map as $user_id => $prob) {
            if ($prob <= 0.0) {
                continue;
            }
            if ($prob >= 1.0) {
                continue;
            }
            $entropy -= $prob * log($prob);
        }
        return $entropy;
    }
    private function getRoleSize(int $role_id)
    {
        if (!array_key_exists($role_id, $this->role_size_map)) {
            return 0;
        } else {
            $this->role_size_map[$role_id];
        }
    }
    public function print()
    {
        $this->printHeader();
        for ($user_id = 0; $user_id < $this->user_size; $user_id++) {
            printf("%02d ", $user_id);
            foreach ($this->role_size_map as $role_id => $role_size) {
                printf("%.3f ", $this->average[$user_id][$role_id]);
            }
            print "\n";
        }
    }
    private function printHeader()
    {
        print '  ';
        foreach ($this->role_size_map as $role_id => $role_size) {
            print '    ' . Role::toJapaneseName($role_id);
        }
        print "\n";
    }
}
