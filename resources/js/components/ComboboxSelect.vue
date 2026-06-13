<script setup lang="ts">
import { Check, ChevronsUpDown } from '@lucide/vue';
import {
    Combobox,
    ComboboxAnchor,
    ComboboxEmpty,
    ComboboxGroup,
    ComboboxInput,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxList,
    ComboboxTrigger,
    ComboboxViewport,
} from '@/components/ui/combobox';

type Option = { value: string | number; label: string };

const props = withDefaults(
    defineProps<{
        modelValue: string | number | null;
        options: Option[];
        name?: string;
        placeholder?: string;
        searchPlaceholder?: string;
        emptyLabel?: string;
        disabled?: boolean;
    }>(),
    {
        placeholder: 'Select…',
        searchPlaceholder: 'Search…',
        emptyLabel: 'No results found.',
        disabled: false,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string | number | null];
}>();

function labelFor(val: string | number | null): string {
    return props.options.find((o) => o.value === val)?.label ?? '';
}
</script>

<template>
    <Combobox
        :model-value="modelValue"
        :display-value="() => labelFor(modelValue)"
        :disabled="disabled"
        @update:model-value="emit('update:modelValue', $event as string | number | null)"
    >
        <ComboboxAnchor class="w-full">
            <ComboboxTrigger
                class="border-input bg-background ring-offset-background focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 flex h-9 w-full items-center justify-between rounded-md border px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1"
            >
                <span :class="modelValue !== null && modelValue !== '' ? '' : 'text-muted-foreground'">
                    {{ modelValue !== null && modelValue !== '' ? labelFor(modelValue) : placeholder }}
                </span>
                <ChevronsUpDown class="size-4 opacity-50" />
            </ComboboxTrigger>
        </ComboboxAnchor>
        <ComboboxList class="w-(--reka-combobox-trigger-width)">
            <ComboboxInput :placeholder="searchPlaceholder" :display-value="() => ''" />
            <ComboboxEmpty>{{ emptyLabel }}</ComboboxEmpty>
            <ComboboxViewport>
                <ComboboxGroup>
                    <ComboboxItem
                        v-for="option in options"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                        <ComboboxItemIndicator>
                            <Check class="size-4" />
                        </ComboboxItemIndicator>
                    </ComboboxItem>
                </ComboboxGroup>
            </ComboboxViewport>
        </ComboboxList>
    </Combobox>
    <input v-if="name" type="hidden" :name="name" :value="modelValue ?? ''" />
</template>
