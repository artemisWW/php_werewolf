<?php
namespace JINRO_JOSEKI\Common\Data;

require_once('common\data\Species.php');
require_once('common\data\Team.php');

use JINRO_JOSEKI\Common\Data;

class Role
{
    const VILLAGER = 0;
    const WEREWOLF = 1;
    const SEER = 2;
    const MEDIUM = 3;
    const BODYGUARD = 4;
    const POSSESSED = 5;
    const FREEMASON = 6;
    const FOX = 7;
    const ROLE_SIZE = 8;
    const UNDEF = -1;

    const NAME_LIST = [
        'VILLAGER',
        'WEREWOLF',
        'SEER',
        'MEDIUM',
        'BODYGUARD',
        'POSSESSED',
        'FREEMASON',
        'FOX',
    ];
    const SHORT_NAME_LIST = [
        'VL',
        'WW',
        'SR',
        'MD',
        'BG',
        'PS',
        'FM',
        'FX',
    ];
    const JAPANESE_NAME_LIST = [
        '村',
        '狼',
        '占',
        '霊',
        '騎',
        '狂',
        '共',
        '狐',
    ];
    const SPECIES_LIST = [
        Species::HUMAN,
        Species::WEREWOLF,
        Species::HUMAN,
        Species::HUMAN,
        Species::HUMAN,
        Species::HUMAN,
        Species::HUMAN,
        Species::HUMAN,
    ];
    const TEAM_LIST = [
        Team::VILLAGER,
        Team::WEREWOLF,
        Team::VILLAGER,
        Team::VILLAGER,
        Team::VILLAGER,
        Team::WEREWOLF,
        Team::VILLAGER,
        Team::OTHERS,
    ];
    
    public static function getSpeciesId(int $role_id)
    {
        return self::SPECIES_LIST[$role_id];
    }
    public static function getTeamId(int $role_id)
    {
        return self::TEAM_LIST[$role_id];
    }
    public static function toRoleId(string $role_name)
    {
        $name_map = array_flip(self::NAME_LIST);
        if (!array_key_exists($role_name, $name_map)) {
            return Self::UNDEF;
        }
        return $name_map[$role_name];
    }
    public static function toRoleIdJapanese(string $role_name)
    {
        $name_map = array_flip(self::JAPANESE_NAME_LIST);
        if (!array_key_exists($role_name, $name_map)) {
            return Self::UNDEF;
        }
        return $name_map[$role_name];
    }
    public static function toRoleName(int $role_id)
    {
        if (!array_key_exists($role_id, self::NAME_LIST)) {
            return '';
        }
        return self::NAME_LIST[$role_id];
    }
    public static function toShortName(int $role_id)
    {
        if (!array_key_exists($role_id, self::SHORT_NAME_LIST)) {
            return '';
        }
        return self::SHORT_NAME_LIST[$role_id];
    }
    public static function toJapaneseName(int $role_id)
    {
        if (!array_key_exists($role_id, self::JAPANESE_NAME_LIST)) {
            return '－';
        }
        return self::JAPANESE_NAME_LIST[$role_id];
    }
}
