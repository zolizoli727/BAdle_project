import type { HeightStatus } from '@/types/history';
import type { EquipmentName, Student } from '@/types/student';
import { getEquipmentIcon, getSchoolLogo, getWeaponLabel } from '@/types/student';

/**
 * Compacting rules keep long card values from wrapping awkwardly.
 */
const COMPACT_THRESHOLD = 17;
const FORCE_COMPACT_VALUES = new Set(['Hyakkaryouran', 'Plum Blossom Garden', 'Genryumon', 'Submachine Gun']);

export type CardCompactStrategy = 'length' | 'segment';
export const CARD_COMPACT_STRATEGIES: Record<string, CardCompactStrategy> = {
  length: 'length',
  segment: 'segment'
};

export const CARD_COMPACT_STRATEGY: CardCompactStrategy = CARD_COMPACT_STRATEGIES.segment;

/**
 * Matching helpers
 */
export function matchHandler(
  matches: Record<string, boolean> | undefined,
  field: string,
  heightStatus?: HeightStatus
) {
  if (!matches) return 'bg-pink-700';
  if (field === 'height') return heightStatus === 'correct' ? 'bg-cyan-500' : 'bg-pink-700';
  return matches[field] ? 'bg-cyan-500' : 'bg-pink-700';
}

export function equipmentsMatch(matchesObj: Record<string, boolean> | undefined, fields: string[]) {
  if (!matchesObj) return false;
  return fields.every(f => matchesObj[f] === true);
}

/**
 * Equipment helpers
 */
type EquipmentCarrier = {
  equipment_1?: EquipmentName | null;
  equipment_2?: EquipmentName | null;
  equipment_3?: EquipmentName | null;
};

export function equipmentLabel(student: EquipmentCarrier): string {
  return [student.equipment_1, student.equipment_2, student.equipment_3]
    .filter(Boolean)
    .join(', ');
}

export function equipmentIcons(student: EquipmentCarrier) {
  return [student.equipment_1, student.equipment_2, student.equipment_3]
    .map(name => {
      const icon = getEquipmentIcon(name ?? null);
      return icon && name ? { name, icon } : null;
    })
    .filter(
      (entry): entry is { name: EquipmentName; icon: string } => entry !== null
    );
}

/**
 * Image preloading keeps the UI from flashing when we already know the assets.
 */
export async function preloadStudentAssets(student?: Student | null, timeoutMs = 3000) {
  if (!student) return;
  const assets = new Set<string>();
  if (student.image) assets.add(student.image);
  const schoolLogo = getSchoolLogo(student.school);
  if (schoolLogo) assets.add(schoolLogo);
  equipmentIcons(student).forEach(icon => assets.add(icon.icon));
  const promises = Array.from(assets).map(src => preloadImage(src, timeoutMs));
  await Promise.all(promises);
}

function preloadImage(src: string, timeoutMs: number) {
  return new Promise<void>(resolve => {
    const img = new Image();
    img.src = src;
    if (img.complete) {
      resolve();
      return;
    }
    const timeoutId = window.setTimeout(() => resolve(), timeoutMs);
    img.onload = () => {
      clearTimeout(timeoutId);
      resolve();
    };
    img.onerror = () => {
      clearTimeout(timeoutId);
      resolve();
    };
  });
}

/**
 * Display helpers
 */
export function cardTextClass(value: string | number | null | undefined): string {
  if (value === undefined || value === null) {
    return 'card-content';
  }

  const text = String(value).trim();
  if (!text.length) {
    return 'card-content';
  }

  if (FORCE_COMPACT_VALUES.has(text)) {
      return 'card-content card-content--compact card-content--tight';
  }

  if (CARD_COMPACT_STRATEGY === 'segment') {
    return text.length > COMPACT_THRESHOLD ? 'card-content card-content--compact' : 'card-content';
  }

  const segments = text.split(/[\s-]+/);
  const longestSegmentLength = segments.reduce((max, segment) => Math.max(max, segment.length), 0);

  return longestSegmentLength > COMPACT_THRESHOLD ? 'card-content card-content--compact' : 'card-content';
}

export type DisplayableStudentValue = string | number | null | undefined;

export function extractStudentValue(student: Student, key: keyof Student): DisplayableStudentValue {
  const value = student[key];
  return typeof value === 'string' || typeof value === 'number' || value === null || value === undefined
    ? value
    : undefined;
}

export function formatStudentValue(student: Student, key: keyof Student): DisplayableStudentValue {
  if (key === 'weapon_type') {
    return getWeaponLabel(student.weapon_type);
  }
  return extractStudentValue(student, key);
}

/**
 * Map certain attributes to colored dots so the table stays readable.
 */
export function attributeDotClass(field: keyof Student | string, value: DisplayableStudentValue): string | null {
  if (typeof value !== 'string') return null;
  const normalizedValue = value.toLowerCase();

  if (field === 'damage_type') {
    if (normalizedValue === 'piercing') return 'attribute-bullet attribute-gold';
    if (normalizedValue === 'mystic') return 'attribute-bullet attribute-blue';
    if (normalizedValue === 'explosive') return 'attribute-bullet attribute-red';
    if (normalizedValue === 'sonic') return 'attribute-bullet attribute-purple';
  }

  if (field === 'armor_type') {
    if (normalizedValue === 'heavy') return 'attribute-shield attribute-gold';
    if (normalizedValue === 'special') return 'attribute-shield attribute-blue';
    if (normalizedValue === 'light') return 'attribute-shield attribute-red';
    if (normalizedValue === 'elastic') return 'attribute-shield attribute-purple';
  }

  return null;
}
