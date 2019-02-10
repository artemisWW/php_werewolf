<?php
namespace JINRO_JOSEKI;

require_once('common/data/Role.php');
require_once('Score.php');

use JINRO_JOSEKI\Common\Data\Role;
use JINRO_JOSEKI\Common\Data\Team;
use JINRO_JOSEKI\Common\Data\Species;

class Action
{
    const ROLE = 0;
    const COMINGOUT = 1;
    const DIVINED = 2;
    const IDENTIFIED = 3;
    const GUARDED = 4;
    const VOTE = 5;
    const VOTED = 6;
    const ATTACKED = 7;
    const JUDGED = 8;

    private $role_size_list = null;
    public function __construct(array $role_size_list)
    {
        $this->role_size_list = $role_size_list;
    }
    /**
     * uidの役割を手動でridに設定する
     * @param Score $score
     * @param int $uid user_id
     * @param int $rid role_id
     * @return bool
     */
    public function role(Score &$score, int $uid, int $rid)
    {
        // rid以外を0
        $role_id_list = [];
        foreach ($this->role_size_list as $role_id => $role_size) {
            if ($role_id === $rid) {
                continue;
            }
            $role_id_list[] = $role_id;
        }
        return $score->setZero($uid, $role_id_list);
    }
    /**
     * uidがridとカミングアウトする
     * @param Score $score
     * @param int $uid user_id
     * @param int $rid role_id
     * @return bool
     */
    public function comingout(Score &$score, int $uid, int $rid)
    {
        if ($score->getBool($uid) === 0) {
            // カミングアウトが真のとき
            return $this->role($score, $uid, $rid);
        } else {
            // uidが偽のとき、uidはチーム::村人ではない
            $role_id_list = $this->getRoleIdsByTeam(Team::VILLAGER);
            return $score->setZero($uid, $role_id_list);
        }
    }
    /**
     * uid1がuid2を占った結果がspeciesであることを伝える
     * @param Score $score
     * @param int $uid1  user_id
     * @param int $uid2  user_id
     * @param bool $species  true: 種別::人狼, false: 種別::人間
     * @return bool
     */
    public function divined(Score &$score, int $uid1, int $uid2, bool $species)
    {
        if ($score->getBool($uid1) === 1) {
            // uid1が偽のとき、uid2に対する情報は真偽不明
            return true;
        } else {
            // uid1が真のとき、黒判定であればuid2は種族::人狼、白判定であればuid2は種族::人狼ではない
            if ($species) {
                return $this->role($score, $uid2, Role::WEREWOLF);
            } else {
                return $score->setZero($uid2, [Role::WEREWOLF]);
            }
        }
    }
    /**
     * uid1がuid2を霊能した結果がspeciesであることを伝える
     * @param Score $score
     * @param int $uid1  user_id
     * @param int $uid2  user_id
     * @param bool $species  true: 種別::人狼, false: 種別::人間
     * @return bool
     */
    public function identified(Score &$score, int $uid1, int $uid2, bool $species)
    {
        if ($score->getBool($uid1) === 1) {
            // uid1が偽のとき、uid2に対する情報は真偽不明
            return true;
        } else {
            // uid1が真のとき、黒判定であればuid2は種族::人狼、白判定であればuid2は種族::人狼ではない
            if ($species) {
                return $this->role($score, $uid2, Role::WEREWOLF);
            } else {
                return $score->setZero($uid2, [Role::WEREWOLF]);
            }
        }
    }
    /**
     * uid1がuid2を守ったことを伝える
     * @param Score $score
     * @param int $uid1  user_id
     * @param int $uid2  user_id
     * @return bool
     */
    public function guarded(Score &$score, int $uid1, int $uid2)
    {
        if ($score->getBool($uid1) === 1) {
            // uid1が偽のとき、uid2に対する情報は真偽不明
            return true;
        } else {
            // uid1が真のとき、uid2は種族::人狼ではない
            return $score->setZero($uid2, [Role::WEREWOLF]);
        }
    }
    /**
     * uid1がuid2に投票する
     * @param Score $score
     * @param int $uid1  user_id
     * @param int $uid2  user_id
     * @return bool
     */
    public function vote(Score &$score, int $uid1, int $uid2)
    {
        return true;
    }
    /**
     * uidが追放された(吊られた) (事実の周知)
     * 人狼の人数が1人のときはuidは人狼ではない
     * @param Score $score
     * @param int $uid
     * @return bool
     */
    public function voted(Score &$score, int $uid)
    {
        return $score->setZero($uid, [Role::WEREWOLF]);
    }
    /**
     * uidが攻撃された(噛まれた) (事実の周知)
     * @param Score $score
     * @param int $uid
     * @return bool
     */
    public function attacked(Score &$score, int $uid)
    {
        // 役職::人狼は攻撃されることはない
        return $score->setZero($uid, [Role::WEREWOLF]);
    }
    /**
     * uidがspeciesと判定された (占or霊)
     * @param Score $score
     * @param int $uid
     * @param bool $species  true: 種別::人狼, false: 種別::人間
     * @return bool
     */
    public function judged(Score &$score, int $uid, bool $species)
    {
        if ($species) {
            // uidは種別::人狼
            return $this->role($score, $uid, Role::WEREWOLF);
        } else {
            // uidは種別::人狼ではない
            return $score->setZero($uid, [Role::WEREWOLF]);
        }
    }

    private function getRoleIdsBySpecies(int $sid)
    {
        $role_id_list = [];
        foreach ($this->role_size_list as $role_id => $role_size) {
            if (Role::getSpeciesId($role_id) === $sid) {
                $role_id_list[] = $role_id;
            }
        }
        return $role_id_list;
    }
    private function getRoleIdsByTeam(int $tid)
    {
        $role_id_list = [];
        foreach ($this->role_size_list as $role_id => $role_size) {
            if (Role::getTeamId($role_id) === $tid) {
                $role_id_list[] = $role_id;
            }
        }
        return $role_id_list;
    }
}
