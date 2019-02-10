<?php
namespace JINRO_JOSEKI;

class Hypothesys {
    private $agent_id = -1;
    private $hypo_id = -1;
    private $arg_list = [];
    private $score_list = [];

    public function __construct(int $agent_id, int $hypo_id, array $arg_list, array $score_list)
    {
        $this->agent_id = $agent_id;
        $this->hypo_id = $hypo_id;
        $this->arg_list = $arg_list;
        $this->score_list = $score_list;
    }
    public function getAgentId()
    {
        return $this->agent_id;
    }
    public function getHypoId()
    {
        return $this->hypo_id;
    }
    public function getArgList()
    {
        return $this->arg_list;
    }
    public function getScoreList()
    {
        return $this->score_list;
    }
    public function print()
    {
        $score_str = [];
        foreach ($this->score_list as $score) {
            $score_str[] = sprintf("%.4f", $score);
        }
        print $this->agent_id . ' ' . $this->hypo_id . ' [' . implode(',', $this->arg_list) . '] [' . implode(', ', $score_str) . ']' . "\n";
    }
    public static function select(array $hypo_list)
    {
        $hypo_list0 = self::getMinTeamWerewolf($hypo_list);
        $hypo_list1 = self::getMinEntropy1($hypo_list0);
/*
        $hypo_list2 = self::getMaxEntropy0($hypo_list1);
        shuffle($hypo_list2);
        return $hypo_list2[0];
*/
        return self::getRandomEntropy0($hypo_list1);
    }
    public static function selectForSeer(array $hypo_list)
    {
        $hypo_list0 = self::getMinEntropy1($hypo_list);
        return self::getRandomEntropy0($hypo_list0);
    }
    public static function getMinEntropy1(array $hypo_list)
    {
        $min = 100.0;
        $min_hypo_list = [];
        foreach ($hypo_list as $hypo) {
            $score = $hypo->getScoreList()[1];
            if ($score < $min) {
                $min = $score;
                $min_hypo_list = [];
                $min_hypo_list[] = $hypo;
            } elseif ($score === $min) {
                $min_hypo_list[] = $hypo;
            }
        }
        return $min_hypo_list;
    }
    public static function getMaxEntropy0(array $hypo_list)
    {
        $max = 0.0;
        $max_hypo_list = [];
        foreach ($hypo_list as $hypo) {
            $score = $hypo->getScoreList()[0];
            if ($score > $max) {
                $max = $score;
                $max_hypo_list = [];
                $max_hypo_list[] = $hypo;
            } elseif ($score === $max) {
                $max_hypo_list[] = $hypo;
            }
        }
        return $max_hypo_list;
    }
    private static function getRandomEntropy0(array $hypo_list)
    {
        $sum = 0.0;
        foreach ($hypo_list as $hypo) {
            $sum += $hypo->getScoreList()[0];
        }
        $rand = $sum * \mt_rand() / \mt_getrandmax();
        $sum = 0.0;
        $rand_hypo = null;
        foreach ($hypo_list as $hypo) {
            $sum += $hypo->getScoreList()[0];
            if ($rand <= $sum) {
                $rand_hypo = $hypo;
                break;
            }
        }
        return $rand_hypo;
    }
    public static function getMinTeamWerewolf(array $hypo_list)
    {
        $min = 100.0;
        $min_hypo_list = [];
        foreach ($hypo_list as $hypo) {
            $score = (int)$hypo->getScoreList()[2];
            if ($score < $min) {
                $min = $score;
                $min_hypo_list = [];
                $min_hypo_list[] = $hypo;
            } elseif ($score === $min) {
                $min_hypo_list[] = $hypo;
            }
        }
        return $min_hypo_list;
    }
}
