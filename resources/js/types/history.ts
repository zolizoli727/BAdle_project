import { Student } from "./student";

export type HeightStatus = 'correct' | 'above' | 'below' | null;

export interface History extends Student {
    correct: boolean;
    matches: Record<string, boolean>;
    heightStatus?: HeightStatus;
}
