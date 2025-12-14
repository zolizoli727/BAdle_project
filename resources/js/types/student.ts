export type School =
    | 'Abydos'
    | 'Arius'
    | 'Gehenna'
    | 'Highlander'
    | 'Hyakkiyako'
    | 'Millennium'
    | 'Red Winter'
    | 'Shanhaijing'
    | 'SRT'
    | 'Trinity'
    | 'Valkyrie'
    | 'Wildhunt'
    | 'ETC';

export const SCHOOL_LOGOS: Record<School, string | undefined> = {
    Abydos: '/logos/Abydos W.svg',
    Arius: '/logos/Arius W.svg',
    Gehenna: '/logos/Gehenna W.svg',
    Highlander: '/logos/Highlander W.svg',
    Hyakkiyako: '/logos/Hyakkiyako W.svg',
    Millennium: '/logos/Millennium W.svg',
    'Red Winter': '/logos/Red Winter W.svg',
    Shanhaijing: '/logos/Shanhaijing W.svg',
    SRT: '/logos/SRT W.svg',
    Trinity: '/logos/Trinity W.svg',
    Valkyrie: '/logos/Valkyrie W.svg',
    Wildhunt: undefined,
    ETC: undefined
} as const;

const SCHOOL_KEYS = new Set<School>(Object.keys(SCHOOL_LOGOS) as School[]);

export function getSchoolLogo(school?: string | null): string | undefined {
    if (!school || !SCHOOL_KEYS.has(school as School)) {
        return undefined;
    }
    return SCHOOL_LOGOS[school as School];
}

export type EquipmentName =
    | 'Badge'
    | 'Bag'
    | 'Charm'
    | 'Gloves'
    | 'Hairpin'
    | 'Hat'
    | 'Necklace'
    | 'Shoes'
    | 'Watch';

export const EQUIPMENT_ICONS: Record<EquipmentName, string> = {
    Badge: '/images/equipments/Equipment_Icon_Badge_Tier1.png',
    Bag: '/images/equipments/Equipment_Icon_Bag_Tier1.png',
    Charm: '/images/equipments/Equipment_Icon_Charm_Tier1.png',
    Gloves: '/images/equipments/Equipment_Icon_Gloves_Tier1.png',
    Hairpin: '/images/equipments/Equipment_Icon_Hairpin_Tier1.png',
    Hat: '/images/equipments/Equipment_Icon_Hat_Tier1.png',
    Necklace: '/images/equipments/Equipment_Icon_Necklace_Tier1.png',
    Shoes: '/images/equipments/Equipment_Icon_Shoes_Tier1.png',
    Watch: '/images/equipments/Equipment_Icon_Watch_Tier1.png'
} as const;

export function getEquipmentIcon(equipment?: string | null): string | null {
    if (!equipment) return null;
    return EQUIPMENT_ICONS[equipment as EquipmentName] ?? null;
}

export type WeaponCode =
    | 'AR'
    | 'FT'
    | 'GL'
    | 'HG'
    | 'MG'
    | 'MT'
    | 'RG'
    | 'RL'
    | 'SG'
    | 'SMG'
    | 'SR';

export const WEAPON_LABELS: Record<WeaponCode, string> = {
    AR: 'Assault Rifle',
    FT: 'Flamethrower',
    GL: 'Grenade Launcher',
    HG: 'Handgun',
    MG: 'Machine Gun',
    MT: 'Mortar',
    RG: 'Railgun',
    RL: 'Rocket Launcher',
    SG: 'Shotgun',
    SMG: 'Submachine Gun',
    SR: 'Sniper Rifle'
} as const;

export function getWeaponLabel(code?: string | null): string {
    if (!code) return 'Unknown';
    return WEAPON_LABELS[code as WeaponCode] ?? code;
}

export interface Student {
    id: number;
    first_name: string;
    second_name: string;
    image: string;
    age: number;
    birthday: string;
    height: string;
    release_date_gl: string;
    school: School;
    club: string;
    role: string;
    position: string;
    class: string;
    damage_type: string;
    armor_type: string;
    weapon_type: WeaponCode;
    equipment_1: EquipmentName | null;
    equipment_2: EquipmentName | null;
    equipment_3: EquipmentName | null;
    unique_equipment_name: string;
    unique_equipment_img: string;
    memorial_lobby: string | null;
    matches?: Record<string, boolean>;
}
