import { type ClassValue, clsx } from "clsx"
import { twMerge } from "tailwind-merge"

/**
 * Junta classes Tailwind de forma inteligente
 * - clsx → resolve condicionais
 * - tailwind-merge → remove classes duplicadas/que se anulam
 */
export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}