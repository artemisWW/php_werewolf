<?php
namespace JINRO_JOSEKI\Common\Data;

class Team
{
    const VILLAGER = 0;
    const WEREWOLF = 1;
    const OTHERS = 2;

    const NAME_LIST = [
        'VILLAGER',
        'WEREWOLF',
        'OTHERS',
    ];
    const JAPANESE_NAME_LIST = [
        '村人',
        '人狼',
        '第三',
    ];
    public static function toTeamName(int $team_id)
    {
        if (!array_key_exists($team_id, self::NAME_LIST)) {
            return '';
        }
        return self::NAME_LIST[$team_id];
    }
    public static function toJapaneseName(int $team_id)
    {
        if (!array_key_exists($team_id, self::JAPANESE_NAME_LIST)) {
            return '';
        }
        return self::JAPANESE_NAME_LIST[$team_id];
    }
    public static function toTeamId(string $team_name)
    {
        $name_map = array_flip(self::NAME_LIST);
        if (!array_key_exists($team_name, $name_map)) {
            return -1;
        }
        return $name_map[$team_name];
    }
}
