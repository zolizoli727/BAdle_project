import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';
import type { PageProps as InertiaPageProps } from '@inertiajs/core';
import type { Student } from './student';
import type { History } from './history';

// ------------------------------------------------------
// Alap típusok (App szerkezet, auth, menük, stb.)
// ------------------------------------------------------
export interface Auth {
    user: User | null;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
    mode?: string;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    user_level: number;
    created_at: string;
    updated_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;

// ------------------------------------------------------
// Game-specifikus típusok
// ------------------------------------------------------
export type MatchValue = boolean;
export type HeightMatchValue = 'correct' | 'above' | 'below' | null;

export type MatchHandler = (
    matches: Record<string, boolean> | undefined,
    field: string,
    heightStatus?: HeightMatchValue
) => 'bg-cyan-500' | 'bg-pink-500';

export type EquipmentMatcher = (
    matchesObj: Record<string, boolean> | undefined,
    fields: string[]
) => boolean;

// ------------------------------------------------------
// AppPageProps (Inertia oldalak props-ja)
// ------------------------------------------------------
export interface AppPageProps extends InertiaPageProps {
    // --- Általános ---
    [key: string]: any;
    name?: string;
    quote?: { message: string; author: string };
    auth?: Auth;
    ziggy?: Config & { location: string };
    sidebarOpen?: boolean;
    statistics?: Record<string, unknown>;

    // --- Classic mode ---
    dailyStudentClassic?: Student;
    guessHistoryClassic?: History[];
    guessCorrectClassic?: boolean | null;
    classicGameState?: boolean;
    messageBoxShowClassic?: boolean;
    guessedStudentDataClassic?: Student | null;
    matchesClassic?: Record<string, boolean>;
    heightStatusClassic?: HeightMatchValue;

    // --- Hard mode ---
    dailyStudentHard?: Student;
    guessHistoryHard?: History[];
    guessCorrectHard?: boolean | null;
    hardGameState?: boolean;
    messageBoxShowHard?: boolean;
    hardModeClues?: Clue[];
    guessedStudentDataHard?: Student | null;
    matchesHard?: Record<string, boolean>;
    heightStatusHard?: HeightMatchValue;

    // --- Image mode ---
    dailyStudentImage?: Student;
    guessHistoryImage?: History[];
    guessCorrectImage?: boolean | null;
    imageGameState?: boolean;
    messageBoxShowImage?: boolean;
    guessedStudentDataImage?: Student | null;
    matchesImage?: Record<string, boolean>;
    heightStatusImage?: HeightMatchValue;
}

export interface Clue {
    label: string;
    value: string;
    difficulty: 'easy' | 'medium' | 'hard';
    field: string;
    pair: keyof Student;
}

// ------------------------------------------------------
// Globális kiterjesztés (usePage automatikusan tudja)
// ------------------------------------------------------
declare module '@inertiajs/core' {
    // eslint-disable-next-line @typescript-eslint/no-empty-object-type
    export interface PageProps extends AppPageProps {}
}
