<?php
namespace JINRO_JOSEKI\Common\Data;

class Species
{
    const HUMAN = 0;
    const WEREWOLF = 1;
    
    const NAME_LIST = [
        'HUMAN',
        'WEREWOLF',
    ];
    const JAPANESE_NAME_LIST = [
        '人間',
        '人狼',
    ];
    public static function toSpeciesName(int $species_id)
    {
        if (!array_key_exists($species_id, self::NAME_LIST)) {
            return '';
        }
        return self::NAME_LIST[$species_id];
    }
    public static function toJapaneseName(int $species_id)
    {
        if (!array_key_exists($species_id, self::JAPANESE_NAME_LIST)) {
            return '';
        }
        return self::JAPANESE_NAME_LIST[$species_id];
    }
    public static function toSpeciesId(string $species_name)
    {
        $name_map = array_flip(self::NAME_LIST);
        if (!array_key_exists($species_name, $name_map)) {
            return -1;
        }
        return $name_map[$species_name];
    }
}
