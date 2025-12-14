import { ref, type Ref, watch } from 'vue';

export function useKeyboardNavigation(
    suggestions: Ref<string[]>,
    showSuggestions: Ref<boolean>,
    selectSuggestion: (name: string) => void,
    submit: () => void,
    isValidSelection: Ref<boolean>,
    inputValue: Ref<string>
) {
    const highlightedIndex = ref(-1);

    function handleKeyDown(e: KeyboardEvent) {
        if (e.key === 'Enter') {
            if (isValidSelection.value && (highlightedIndex.value < 0 || !showSuggestions.value)) {
                e.preventDefault();
                showSuggestions.value = false;
                highlightedIndex.value = -1;
                submit();
                return;
            }
        }

        if (!showSuggestions.value || !suggestions.value.length) return;

        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                highlightedIndex.value = Math.min(highlightedIndex.value + 1, suggestions.value.length - 1);
                break;
            case 'ArrowUp':
                e.preventDefault();
                highlightedIndex.value = Math.max(highlightedIndex.value - 1, -1);
                break;
            case 'Enter':
                if (highlightedIndex.value < 0 && suggestions.value.length > 0) {
                    const trimmedInput = inputValue.value.toLowerCase().trim();
                    if (!trimmedInput.length) {
                        return;
                    }

                    const firstSuggestion = suggestions.value[0]?.toLowerCase();
                    if (!firstSuggestion || !firstSuggestion.includes(trimmedInput)) {
                        return;
                    }

                    e.preventDefault();
                    selectSuggestion(suggestions.value[0]);
                    return;
                }
                if (highlightedIndex.value >= 0) {
                    e.preventDefault();
                    selectSuggestion(suggestions.value[highlightedIndex.value]);
                }
                break;
            case 'Escape':
                showSuggestions.value = false;
                break;
        }
    }

    watch([suggestions, showSuggestions], () => {
        if (!showSuggestions.value) {
            highlightedIndex.value = -1;
            return;
        }
        if (highlightedIndex.value >= suggestions.value.length) {
            highlightedIndex.value = -1;
        }
    });

    return {
        highlightedIndex,
        handleKeyDown
    };
}
